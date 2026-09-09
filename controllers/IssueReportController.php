<?php
class IssueReportController extends Controller {

    public function __construct($db) {
        parent::__construct($db);
    }

    public function ajax_list() {
        Auth::requireLogin();
        if (!isAjax()) {
            http_response_code(403); exit('Forbidden');
        }

        $user        = Auth::currentUser();
        $reportModel = new IssueReportModel($this->db);

        $filters = array(
            'status'       => isset($_GET['status'])       ? $_GET['status']       : '',
            'office_name'  => isset($_GET['office_name'])  ? $_GET['office_name']  : '',
            'issue_type'   => isset($_GET['issue_type'])   ? $_GET['issue_type']   : '',
            'program_name' => isset($_GET['program_name']) ? $_GET['program_name'] : '',
            'date_from'    => isset($_GET['date_from'])    ? $_GET['date_from']    : '',
            'date_to'      => isset($_GET['date_to'])      ? $_GET['date_to']      : '',
        );

        $summary        = $reportModel->getSummaryByStatus($user, $filters);
        $allDetails     = $reportModel->getDetailReport($user, $filters);
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $totalItems     = count($allDetails);
        $totalPages     = max(1, ceil($totalItems / $perPage));
        $offset         = ($currentPageNum - 1) * $perPage;
        $details        = array_slice($allDetails, $offset, $perPage);

        include 'views/issue_reports/_list_partial.php';
        exit;
    }

    public function index() {
        $user        = Auth::currentUser();
        $reportModel = new IssueReportModel($this->db);

        $filters = array(
            'status'       => isset($_GET['status'])       ? $_GET['status']       : '',
            'office_name'  => isset($_GET['office_name'])  ? $_GET['office_name']  : '',
            'issue_type'   => isset($_GET['issue_type'])   ? $_GET['issue_type']   : '',
            'program_name' => isset($_GET['program_name']) ? $_GET['program_name'] : '',
            'date_from'    => isset($_GET['date_from'])    ? $_GET['date_from']    : '',
            'date_to'      => isset($_GET['date_to'])      ? $_GET['date_to']      : '',
        );

        $summary        = $reportModel->getSummaryByStatus($user, $filters);
        $allDetails     = $reportModel->getDetailReport($user, $filters);
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $totalItems     = count($allDetails);
        $totalPages     = max(1, ceil($totalItems / $perPage));
        $offset         = ($currentPageNum - 1) * $perPage;
        $details        = array_slice($allDetails, $offset, $perPage);
        $pageTitle      = 'รายงานแจ้งปัญหา';
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

        $filters = array(
            'status'       => isset($_GET['status'])       ? $_GET['status']       : '',
            'office_name'  => isset($_GET['office_name'])  ? $_GET['office_name']  : '',
            'issue_type'   => isset($_GET['issue_type'])   ? $_GET['issue_type']   : '',
            'program_name' => isset($_GET['program_name']) ? $_GET['program_name'] : '',
            'date_from'    => isset($_GET['date_from'])    ? $_GET['date_from']    : '',
            'date_to'      => isset($_GET['date_to'])      ? $_GET['date_to']      : '',
        );

        $details = $reportModel->getDetailReport($user, $filters);

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

        $signerRow   = $this->db->fetchOne("SELECT position FROM users WHERE id=" . intval($user['id']));
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
