<?php
class IssueReportController extends Controller {

    public function __construct($db) {
        parent::__construct($db);
    }

    private function reportFilters() {
        $keys = array('status', 'office_name', 'issue_type', 'program_name', 'date_from', 'date_to');
        $f = array();
        foreach ($keys as $k) {
            $f[$k] = (isset($_GET[$k]) && is_string($_GET[$k])) ? $_GET[$k] : '';
        }
        return $f;
    }

    // คืนตัวแปรที่ view/partial ใช้ (ใช้ extract() ที่ผู้เรียก)
    private function reportData($user, $filters) {
        $model          = new IssueReportModel($this->db);
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

        include 'views/issue_reports/_list_partial.php';
        exit;
    }

    public function index() {
        $user    = Auth::currentUser();
        $filters = $this->reportFilters();
        extract($this->reportData($user, $filters));
        $pageTitle = 'รายงานแจ้งปัญหา';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/issue_reports/index.php';
        include 'views/layout/footer.php';
    }

    // ส่งออกรายงานแจ้งปัญหาเป็น Excel (.xls) ตามตัวกรองปัจจุบัน พร้อมลายเซ็นท้ายเอกสาร
    public function export_excel() {
        Auth::requireLogin();

        $user        = Auth::currentUser();
        $reportModel = new IssueReportModel($this->db);
        $filters     = $this->reportFilters();
        $details     = $reportModel->getDetailReport($user, $filters);

        $headers = array('#','วันที่','เลขที่แจ้ง','สหกรณ์','ประเภทปัญหา','โปรแกรม','สำนักงาน','ผู้แจ้ง','สถานะ');
        $rows    = array();
        foreach ($details as $idx => $iss) {
            $rows[] = array(
                $idx + 1,
                thaiDate($iss['created_at'], true),
                $iss['ticket_code'],
                $iss['cooperative_name'],
                issueTypeLabel($iss['issue_type']),
                programLabel($iss['program_name']),
                $iss['office_name'],
                $iss['submitter_name'],
                issueStatusLabel($iss['status'], !empty($iss['handled_by_central'])),
            );
        }

        $subtitleParts = array();
        if (!empty($filters['status']))       $subtitleParts[] = 'สถานะ ' . issueStatusLabel($filters['status']);
        if (!empty($filters['issue_type']))   $subtitleParts[] = issueTypeLabel($filters['issue_type']);
        if (!empty($filters['program_name'])) $subtitleParts[] = programLabel($filters['program_name']);
        if (!empty($filters['office_name']))  $subtitleParts[] = $filters['office_name'];
        if (!empty($filters['date_from']))    $subtitleParts[] = 'ตั้งแต่ ' . $filters['date_from'];
        if (!empty($filters['date_to']))      $subtitleParts[] = 'ถึง ' . $filters['date_to'];

        $signerRow   = getUserPosition($this->db, $user['id']);
        $signerTitle = $signerRow ? $signerRow['position'] : '';

        ExcelExport::export(
            'รายงานแจ้งปัญหา',
            $headers,
            $rows,
            $user['fullname'],
            $signerTitle,
            'report_issue_' . date('Ymd'),
            implode(' | ', $subtitleParts)
        );
    }
}
