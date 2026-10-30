<?php
class InternalServiceController extends Controller {

    private $serviceModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->serviceModel = new InternalServiceModel($db);
    }

    private function listFilters() {
        return array(
            'status'    => isset($_GET['status'])    ? $_GET['status']    : '',
            'keyword'   => isset($_GET['keyword'])   ? $_GET['keyword']   : '',
            'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
            'date_to'   => isset($_GET['date_to'])   ? $_GET['date_to']   : '',
        );
    }

    private function canViewService($user, $service) {
        return userHasRole($user, 'admin') || isHQ($user) || $service['office_name'] === $user['office_name'];
    }

    public function index() {
        $user    = Auth::currentUser();
        $filters = $this->listFilters();
        $filters['per_page'] = 10;
        $filters['page_num'] = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;

        $services       = $this->serviceModel->getList($user, $filters);
        $totalItems     = $this->serviceModel->countList($user, $filters);
        $totalPages     = ceil($totalItems / $filters['per_page']);
        $currentPageNum = $filters['page_num'];

        $pageTitle = 'การให้บริการภายในสำนักงาน';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/internal_services/index.php';
        include 'views/layout/footer.php';
    }

    public function create() {
        Auth::requireLogin();
        $user           = Auth::currentUser();
        $officeMembers  = getOfficeMembersOptions($this->db, $user['office_name']);
        $pageTitle      = 'ยื่นคำขอใช้บริการภายในสำนักงาน';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/internal_services/create.php';
        include 'views/layout/footer.php';
    }

    public function store() {
        Auth::requireLogin();
        Auth::checkCsrf();

        $back      = APP_URL . '/?page=internal_services&action=create';
        $titleRaw  = isset($_POST['title'])  ? trim($_POST['title'])  : '';
        $detailRaw = isset($_POST['detail']) ? trim($_POST['detail']) : '';
        if ($titleRaw === '' || $detailRaw === '') {
            redirectWithFlash($back, 'error', 'กรุณากรอกชื่อเรื่องและรายละเอียดคำขอ');
        }
        if (strlen($titleRaw) > 255) {
            redirectWithFlash($back, 'error', 'ชื่อเรื่องยาวเกินไป (ไม่เกิน 255 ตัวอักษร)');
        }
        $serviceTypeRaw = isset($_POST['service_type']) ? $_POST['service_type'] : '';
        $typeOptions    = getServiceTypeOptions();
        if (!is_string($serviceTypeRaw) || !isset($typeOptions[$serviceTypeRaw]) || empty($_POST['requester_id'])) {
            redirectWithFlash($back, 'error', 'กรุณาเลือกประเภทบริการและผู้ขอใช้บริการ');
        }

        $user        = Auth::currentUser();
        $requesterId = intval($_POST['requester_id']);
        $officeEsc   = $this->db->escape($user['office_name']);

        // ผู้ขอใช้บริการต้องอยู่สำนักงานเดียวกับผู้บันทึก (กัน POST ปลอมเลือกคนต่างสำนักงาน)
        $requester = $this->db->fetchOne(
            "SELECT id FROM users WHERE id=$requesterId AND office_name='$officeEsc' AND is_active=1"
        );
        if (!$requester) {
            redirectWithFlash($back, 'error', 'ผู้ขอใช้บริการไม่ถูกต้อง');
        }

        $serviceType = $this->db->escape($serviceTypeRaw);
        $title       = $this->db->escape($titleRaw);
        $detail      = $this->db->escape($detailRaw);

        $prefix     = Ticket::prefix('SRV');
        $ticketCode = Ticket::next($this->db, 'internal_services', $prefix);

        $attachmentPath = '';
        $attachmentName = '';
        if (isset($_FILES['attachment'])) {
            $result = FileUpload::uploadAttachment($_FILES['attachment'], 'internal_services', $ticketCode);
            if (!$result['success']) {
                redirectWithFlash($back, 'error', 'ไฟล์แนบ: ' . $result['error']);
            }
            $attachmentPath = $result['filename'];
            $attachmentName = $result['original_name'];
        }

        $attachmentEsc     = $this->db->escape($attachmentPath);
        $attachmentNameEsc = $this->db->escape($attachmentName);
        $submittedBy       = intval($user['id']);

        $ok = false;
        for ($try = 0; $try < 5 && !$ok; $try++) {
            if ($try > 0) {
                $ticketCode = Ticket::next($this->db, 'internal_services', $prefix);
            }
            $ticketEsc = $this->db->escape($ticketCode);
            $ok = $this->db->query(
                "INSERT INTO internal_services
                 (ticket_code, service_type, title, detail, attachment, attachment_name,
                  requester_id, office_name, submitted_by, status)
                 VALUES
                 ('$ticketEsc', '$serviceType', '$title', '$detail', '$attachmentEsc', '$attachmentNameEsc',
                  $requesterId, '$officeEsc', $submittedBy, 'pending')"
            );
        }
        if (!$ok) {
            if ($attachmentPath !== '') FileUpload::deleteFile($attachmentPath);
            redirectWithFlash($back, 'error', 'บันทึกคำขอไม่สำเร็จ กรุณาลองใหม่อีกครั้ง (ตรวจสอบว่ารัน migration.sql แล้ว)');
        }

        $serviceId = $this->db->insertId();
        $this->serviceModel->addLog($serviceId, 'ยื่นคำขอใช้บริการ', $submittedBy);

        // แจ้งเตือนผู้ตรวจสอบในสำนักงานเดียวกัน (ไม่รวมตัวเอง)
        $staffIds = Notification::getUsersByRole($this->db, 'inspector', $user['office_name'], false);
        $staffIds = array_values(array_diff($staffIds, array($submittedBy)));
        Notification::createForMultiple($this->db, $staffIds, $serviceId, 'service', 'service_status',
            'มีคำขอใช้บริการภายในสำนักงานใหม่',
            'เรื่อง ' . $ticketCode . ' รอตรวจสอบ');

        redirectWithFlash(APP_URL . '/?page=internal_services', 'success', 'ส่งคำขอใช้บริการสำเร็จ รหัส: ' . $ticketCode);
    }

    public function detail() {
        $id      = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $service = $this->serviceModel->getById($id);
        if (!$service) {
            redirectWithFlash(APP_URL . '/?page=internal_services', 'error', 'ไม่พบคำขอใช้บริการนี้');
        }
        if (!$this->canViewService(Auth::currentUser(), $service)) {
            redirectWithFlash(APP_URL . '/?page=internal_services', 'error', 'คุณไม่มีสิทธิ์ดูรายการนี้');
        }

        $logs = $this->serviceModel->getLogs($service['id']);

        $pageTitle = 'รายละเอียดคำขอ ' . $service['ticket_code'];
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/internal_services/detail.php';
        include 'views/layout/footer.php';
    }

    // รอตรวจสอบ -> กำลังดำเนินการ
    public function start() {
        $this->changeStatus('pending', 'in_progress', 'รับเรื่องดำเนินการ');
    }

    // กำลังดำเนินการ -> เสร็จสิ้น
    public function complete() {
        $this->changeStatus('in_progress', 'completed', 'ดำเนินการเสร็จสิ้น');
    }

    private function changeStatus($fromStatus, $toStatus, $actionLabel) {
        Auth::requireLogin();
        Auth::checkCsrf(); // ตรวจก่อนอ่าน DB/ตรวจสิทธิ์
        $id      = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $service = $this->db->fetchOne("SELECT * FROM internal_services WHERE id=$id AND status='$fromStatus'");
        if (!$service) {
            redirectWithFlash(APP_URL . '/?page=internal_services', 'error', 'ไม่พบรายการหรือสถานะไม่ถูกต้อง');
        }

        $user = Auth::currentUser();
        if (!canHandleService($user, $service)) {
            redirectWithFlash(APP_URL . '/?page=internal_services', 'error', 'คุณไม่มีสิทธิ์ดำเนินการ');
        }

        $userId = intval($user['id']);
        $now    = date('Y-m-d H:i:s');
        $note   = isset($_POST['note']) ? trim($_POST['note']) : '';

        $this->db->query("UPDATE internal_services SET status='$toStatus', updated_at='$now' WHERE id=$id");
        $this->serviceModel->addLog($id, $actionLabel, $userId, $note);

        $submitter = intval($service['submitted_by']);
        if ($submitter != $userId) {
            $msg = ($toStatus === 'in_progress')
                ? 'เรื่อง ' . $service['ticket_code'] . ' กำลังดำเนินการ'
                : 'เรื่อง ' . $service['ticket_code'] . ' ดำเนินการเสร็จสิ้นแล้ว';
            Notification::createFor($this->db, $submitter, $id, 'service', 'service_status',
                'อัปเดตสถานะคำขอใช้บริการ', $msg);
        }

        redirectWithFlash(APP_URL . '/?page=internal_services&action=detail&id=' . $id, 'success', 'อัปเดตสถานะสำเร็จ');
    }

    public function view_attachment() {
        Auth::requireLogin();
        $id      = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $service = $this->serviceModel->getById($id);
        if (!$service || empty($service['attachment'])) {
            exit('ไม่พบไฟล์แนบ');
        }
        if (!$this->canViewService(Auth::currentUser(), $service)) {
            exit('คุณไม่มีสิทธิ์เข้าถึงไฟล์นี้');
        }
        FileUpload::serve($service['attachment'], $service['attachment_name']);
    }

    public function ajax_list() {
        Auth::requireLogin();
        requireAjax();

        $user    = Auth::currentUser();
        $filters = $this->listFilters();
        $filters['per_page'] = 10;
        $filters['page_num'] = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;

        $services       = $this->serviceModel->getList($user, $filters);
        $totalItems     = $this->serviceModel->countList($user, $filters);
        $totalPages     = ceil($totalItems / $filters['per_page']);
        $currentPageNum = $filters['page_num'];

        include 'views/internal_services/_list_partial.php';
        exit;
    }

    // ส่งออกรายการคำขอใช้บริการเป็น Excel (.xls) ตามตัวกรองปัจจุบัน พร้อมลายเซ็นท้ายเอกสาร
    public function export_excel() {
        Auth::requireLogin();

        $user     = Auth::currentUser();
        $filters  = $this->listFilters();
        $services = $this->serviceModel->getAllForExport($user, $filters);

        $headers = array('#','วันที่','เลขที่คำขอ','ประเภทบริการ','ชื่อเรื่อง','ผู้ขอใช้บริการ','ผู้บันทึก','สำนักงาน','สถานะ');
        $rows    = array();
        foreach ($services as $idx => $sv) {
            $rows[] = array(
                $idx + 1,
                thaiDate($sv['created_at'], true),
                $sv['ticket_code'],
                serviceTypeLabel($sv['service_type']),
                $sv['title'],
                $sv['requester_name'],
                $sv['submitter_name'],
                $sv['office_name'],
                serviceStatusLabel($sv['status']),
            );
        }

        $subtitleParts = array();
        if (!empty($filters['status']))    $subtitleParts[] = 'สถานะ ' . serviceStatusLabel($filters['status']);
        if (!empty($filters['date_from'])) $subtitleParts[] = 'ตั้งแต่ ' . $filters['date_from'];
        if (!empty($filters['date_to']))   $subtitleParts[] = 'ถึง ' . $filters['date_to'];

        $signerRow   = getUserPosition($this->db, $user['id']);
        $signerTitle = $signerRow ? $signerRow['position'] : '';

        ExcelExport::export(
            'รายงานการให้บริการภายในสำนักงาน',
            $headers,
            $rows,
            $user['fullname'],
            $signerTitle,
            'internal_services_' . date('Ymd'),
            implode(' | ', $subtitleParts)
        );
    }
}
