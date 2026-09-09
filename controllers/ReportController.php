<?php
class ReportController extends Controller {

    public function __construct($db) {
        parent::__construct($db);
    }

    public function ajax_list() {
        Auth::requireLogin();
        if (!isAjax()) {
            http_response_code(403); exit('Forbidden');
        }

        $user        = Auth::currentUser();
        $reportModel = new ReportModel($this->db);

        $filters = array(
            'status'      => isset($_GET['status'])      ? $_GET['status']      : '',
            'fiscal_year' => isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : '',
            'office_name' => isset($_GET['office_name']) ? $_GET['office_name'] : '',
            'date_from'   => isset($_GET['date_from'])   ? $_GET['date_from']   : '',
            'date_to'     => isset($_GET['date_to'])     ? $_GET['date_to']     : '',
        );

        $summary        = $reportModel->getSummaryByStatus($user, $filters);
        $allDetails     = $reportModel->getDetailReport($user, $filters);
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $totalItems     = count($allDetails);
        $totalPages     = max(1, ceil($totalItems / $perPage));
        $offset         = ($currentPageNum - 1) * $perPage;
        $details        = array_slice($allDetails, $offset, $perPage);

        include 'views/reports/_list_partial.php';
        exit;
    }

    public function index() {
        $user        = Auth::currentUser();
        $reportModel = new ReportModel($this->db);

        $filters = array(
            'status'      => isset($_GET['status'])      ? $_GET['status']      : '',
            'fiscal_year' => isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : '',
            'office_name' => isset($_GET['office_name']) ? $_GET['office_name'] : '',
            'date_from'   => isset($_GET['date_from'])   ? $_GET['date_from']   : '',
            'date_to'     => isset($_GET['date_to'])     ? $_GET['date_to']     : '',
        );

        $summary        = $reportModel->getSummaryByStatus($user, $filters);
        $allDetails     = $reportModel->getDetailReport($user, $filters);
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $totalItems     = count($allDetails);
        $totalPages     = max(1, ceil($totalItems / $perPage));
        $offset         = ($currentPageNum - 1) * $perPage;
        $details        = array_slice($allDetails, $offset, $perPage);
        $pageTitle      = 'รายงานเอกสาร';
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

        $filters = array(
            'status'      => isset($_GET['status'])      ? $_GET['status']      : '',
            'fiscal_year' => isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : '',
            'office_name' => isset($_GET['office_name']) ? $_GET['office_name'] : '',
            'date_from'   => isset($_GET['date_from'])   ? $_GET['date_from']   : '',
            'date_to'     => isset($_GET['date_to'])     ? $_GET['date_to']     : '',
        );

        $details = $reportModel->getDetailReport($user, $filters);

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

        $signerRow   = $this->db->fetchOne("SELECT position FROM users WHERE id=" . intval($user['id']));
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
