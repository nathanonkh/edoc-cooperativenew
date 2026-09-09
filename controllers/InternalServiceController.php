<?php
class InternalServiceController extends Controller {

    private $serviceModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->serviceModel = new InternalServiceModel($db);
    }

    public function index() {
        $user    = Auth::currentUser();
        $filters = array(
            'status'    => isset($_GET['status'])    ? $_GET['status']    : '',
            'keyword'   => isset($_GET['keyword'])   ? $_GET['keyword']   : '',
            'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
            'date_to'   => isset($_GET['date_to'])   ? $_GET['date_to']   : '',
            'per_page'  => 10,
            'page_num'  => isset($_GET['p']) ? intval($_GET['p']) : 1,
        );

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

        if (empty($_POST['title']) || empty($_POST['detail'])) {
            redirectWithFlash(APP_URL . '/?page=internal_services&action=create', 'error', 'กรุณากรอกชื่อเรื่องและรายละเอียดคำขอ');
        }
        if (empty($_POST['service_type']) || empty($_POST['requester_id'])) {
            redirectWithFlash(APP_URL . '/?page=internal_services&action=create', 'error', 'กรุณาเลือกประเภทบริการและผู้ขอใช้บริการ');
        }

        $user        = Auth::currentUser();
        $requesterId = intval($_POST['requester_id']);
        $officeEsc   = $this->db->escape($user['office_name']);

        // ตรวจสอบซ้ำฝั่งเซิร์ฟเวอร์ว่าผู้ขอใช้บริการที่เลือกอยู่สำนักงานเดียวกับผู้บันทึกจริง
        // (ฟอร์มจำกัด dropdown ไว้แล้ว แต่กันกรณี POST ถูกปลอมแปลงเลือกรหัสคนต่างสำนักงาน)
        $requester = $this->db->fetchOne(
            "SELECT id FROM users WHERE id=$requesterId AND office_name='$officeEsc' AND is_active=1"
        );
        if (!$requester) {
            redirectWithFlash(APP_URL . '/?page=internal_services&action=create', 'error', 'ผู้ขอใช้บริการไม่ถูกต้อง');
        }

        $serviceType = $this->db->escape($_POST['service_type']);
        $title       = $this->db->escape(trim($_POST['title']));
        $detail      = $this->db->escape(trim($_POST['detail']));

        $ticketCode = $this->serviceModel->generateTicketCode();

        $attachmentPath = '';
        $attachmentName = '';
        if (isset($_FILES['attachment'])) {
            $result = FileUpload::uploadAttachment($_FILES['attachment'], 'internal_services', $ticketCode);
            if (!$result['success']) {
                redirectWithFlash(APP_URL . '/?page=internal_services&action=create', 'error', 'ไฟล์แนบ: ' . $result['error']);
            }
            $attachmentPath = $result['filename'];
            $attachmentName = $result['original_name'];
        }

        $ticketEsc         = $this->db->escape($ticketCode);
        $attachmentEsc     = $this->db->escape($attachmentPath);
        $attachmentNameEsc = $this->db->escape($attachmentName);
        $submittedBy       = intval($user['id']);

        $this->db->query(
            "INSERT INTO internal_services
             (ticket_code, service_type, title, detail, attachment, attachment_name,
              requester_id, office_name, submitted_by, status)
             VALUES
             ('$ticketEsc', '$serviceType', '$title', '$detail', '$attachmentEsc', '$attachmentNameEsc',
              $requesterId, '$officeEsc', $submittedBy, 'pending')"
        );
        $serviceId = $this->db->insertId();
        $this->serviceModel->addLog($serviceId, 'ยื่นคำขอใช้บริการ', $submittedBy);

        // แจ้งเตือนเจ้าหน้าที่ผู้ตรวจสอบในสำนักงานเดียวกัน (ไม่รวมตัวเอง)
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

        $user    = Auth::currentUser();
        $canView = userHasRole($user, 'admin') || isHQ($user)
                 || $service['office_name'] === $user['office_name'];
        if (!$canView) {
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
        $id      = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $service = $this->db->fetchOne("SELECT * FROM internal_services WHERE id=$id AND status='$fromStatus'");
        if (!$service) {
            redirectWithFlash(APP_URL . '/?page=internal_services', 'error', 'ไม่พบรายการหรือสถานะไม่ถูกต้อง');
        }

        $user = Auth::currentUser();
        if (!canHandleService($user, $service)) {
            redirectWithFlash(APP_URL . '/?page=internal_services', 'error', 'คุณไม่มีสิทธิ์ดำเนินการ');
        }
        Auth::checkCsrf();

        $userId = intval($user['id']);
        $now    = date('Y-m-d H:i:s');
        $note   = isset($_POST['note']) ? trim($_POST['note']) : '';

        $this->db->query(
            "UPDATE internal_services SET status='$toStatus', updated_at='$now' WHERE id=$id"
        );
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

        $user    = Auth::currentUser();
        $canView = userHasRole($user, 'admin') || isHQ($user)
                 || $service['office_name'] === $user['office_name'];
        if (!$canView) {
            exit('คุณไม่มีสิทธิ์เข้าถึงไฟล์นี้');
        }

        $filePath = UPLOAD_DIR . $service['attachment'];
        if (!file_exists($filePath)) {
            exit('ไม่พบไฟล์ในระบบ');
        }

        $ext     = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeMap = array('pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png');
        $mime    = isset($mimeMap[$ext]) ? $mimeMap[$ext] : 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($service['attachment_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($filePath);
        exit;
    }

    public function ajax_list() {
        Auth::requireLogin();
        if (!isAjax()) {
            http_response_code(403); exit('Forbidden');
        }

        $user    = Auth::currentUser();
        $filters = array(
            'status'    => isset($_GET['status'])    ? $_GET['status']    : '',
            'keyword'   => isset($_GET['keyword'])   ? $_GET['keyword']   : '',
            'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
            'date_to'   => isset($_GET['date_to'])   ? $_GET['date_to']   : '',
            'per_page'  => 10,
            'page_num'  => isset($_GET['p']) ? intval($_GET['p']) : 1,
        );

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

        $user    = Auth::currentUser();
        $filters = array(
            'status'    => isset($_GET['status'])    ? $_GET['status']    : '',
            'keyword'   => isset($_GET['keyword'])   ? $_GET['keyword']   : '',
            'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
            'date_to'   => isset($_GET['date_to'])   ? $_GET['date_to']   : '',
        );

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

        $signerRow   = $this->db->fetchOne("SELECT position FROM users WHERE id=" . intval($user['id']));
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
