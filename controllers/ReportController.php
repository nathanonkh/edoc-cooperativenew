<?php
class ReportController extends Controller {

    public function __construct($db) {
        parent::__construct($db);
    }

    private function reportFilters() {
        $keys = array('status', 'fiscal_year', 'office_name', 'date_from', 'date_to');
        $f = array();
        foreach ($keys as $k) {
            $f[$k] = (isset($_GET[$k]) && is_string($_GET[$k])) ? $_GET[$k] : '';
        }
        return $f;
    }

    // คืนตัวแปรที่ view/partial ใช้ (ใช้ extract() ที่ผู้เรียก)
    private function reportData($user, $filters) {
        $model          = new ReportModel($this->db);
        $allDetails     = $model->getDetailReport($user, $filters);
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $totalItems     = count($allDetails);
        return array(
            'summary'        => $model->getSummaryByStatus($user, $filters),
            'details'        => array_slice($allDetails, ($currentPageNum - 1) * $perPage, $perPage),
            'totalItems'     => $totalItems,
            'totalPages'     => max(1, ceil($totalItems / $perPage)),
            'currentPageNum' => $currentPageNum,
        );
    }

    public function ajax_list() {
        Auth::requireLogin();
        requireAjax();

        $user    = Auth::currentUser();
        $filters = $this->reportFilters();
        extract($this->reportData($user, $filters));

        include 'views/reports/_list_partial.php';
        exit;
    }

    public function index() {
        $user    = Auth::currentUser();
        $filters = $this->reportFilters();
        extract($this->reportData($user, $filters));
        $pageTitle = 'รายงานเอกสาร';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/reports/index.php';
        include 'views/layout/footer.php';
    }

    // ส่งออกรายงานเอกสารเป็น Excel (.xls) ตามตัวกรองปัจจุบัน พร้อมลายเซ็นท้ายเอกสาร
    public function export_excel() {
        Auth::requireLogin();

        $user        = Auth::currentUser();
        $reportModel = new ReportModel($this->db);
        $filters     = $this->reportFilters();
        $details     = $reportModel->getDetailReport($user, $filters);

        $headers = array('#','วันที่','เลขที่เอกสาร','สหกรณ์','ประเภท','ปีบัญชี','สำนักงาน','ผู้นำส่ง','เลขรับ','สถานะ');
        $rows    = array();
        foreach ($details as $idx => $doc) {
            $rows[] = array(
                $idx + 1,
                thaiDate($doc['created_at'], true),
                $doc['ticket_code'],
                $doc['cooperative_name'],
                $doc['cooperative_type_name'],
                $doc['fiscal_year'],
                $doc['office_name'],
                $doc['submitter_name'],
                $doc['receive_number'],
                docStatusLabel($doc['status']),
            );
        }

        $subtitleParts = array();
        if (!empty($filters['fiscal_year'])) $subtitleParts[] = 'ปีบัญชี ' . $filters['fiscal_year'];
        if (!empty($filters['status']))      $subtitleParts[] = 'สถานะ ' . docStatusLabel($filters['status']);
        if (!empty($filters['office_name'])) $subtitleParts[] = $filters['office_name'];
        if (!empty($filters['date_from']))   $subtitleParts[] = 'ตั้งแต่ ' . $filters['date_from'];
        if (!empty($filters['date_to']))     $subtitleParts[] = 'ถึง ' . $filters['date_to'];

        $signerRow   = getUserPosition($this->db, $user['id']);
        $signerTitle = $signerRow ? $signerRow['position'] : '';

        ExcelExport::export(
            'รายงานเอกสาร',
            $headers,
            $rows,
            $user['fullname'],
            $signerTitle,
            'report_edoc_' . date('Ymd'),
            implode(' | ', $subtitleParts)
        );
    }
}
