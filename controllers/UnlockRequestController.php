<?php
// ปลดล็อคข้อมูล: ยื่นคำขอ / รายการคำขอ / รายงาน
// สถานะ: pending (รอตรวจสอบ) -> completed (เสร็จสิ้น) โดย admin หรือผู้ดำเนินการ (operator) ตาม canCompleteUnlock()
class UnlockRequestController extends Controller {

    private $unlockModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->unlockModel = new UnlockRequestModel($db);
    }

    // ตัวแปรที่ view รายการ/partial ใช้ (ผู้เรียกใช้ extract())
    private function listData($user, $filters) {
        return $this->buildListData($this->unlockModel, 'requests', $user, $filters);
    }

    public function index() {
        $user    = Auth::currentUser();
        $filters = $this->standardListFilters();
        extract($this->listData($user, $filters));

        $pageTitle = 'รายการคำขอปลดล็อคข้อมูล';
        $this->render('unlock_requests/index', get_defined_vars());
    }

    public function ajax_list() {
        Auth::requireLogin();
        requireAjax();

        $user    = Auth::currentUser();
        $filters = $this->standardListFilters();
        extract($this->listData($user, $filters));

        include 'views/unlock_requests/_list_partial.php';
        exit;
    }

    public function create() {
        Auth::requireLogin();
        $user          = Auth::currentUser();
        // ผู้สอบบัญชี: เฉพาะสมาชิกที่ใช้งานอยู่ในสำนักงานของผู้ล็อกอิน
        $officeMembers = getOfficeMembersOptions($this->db, $user['office_name']);
        $pageTitle     = 'ยื่นคำขอปลดล็อคข้อมูล';
        $this->render('unlock_requests/create', get_defined_vars());
    }

    // อ่านค่า POST เป็นสตริง (ไม่มี/ไม่ใช่สตริง เช่น title[]=x = '')
    private function postStr($key) {
        return (isset($_POST[$key]) && is_string($_POST[$key])) ? trim($_POST[$key]) : '';
    }

    public function store() {
        Auth::requireLogin();
        Auth::checkCsrf();

        $back     = APP_URL . '/?page=unlock_requests&action=create';
        $titleRaw = $this->postStr('title');
        if ($titleRaw === '') {
            redirectWithFlash($back, 'error', 'กรุณากรอกชื่อเรื่อง');
        }
        if (strlen($titleRaw) > 255) {
            redirectWithFlash($back, 'error', 'ชื่อเรื่องยาวเกินไป (ไม่เกิน 255 ตัวอักษร)');
        }
        $detailRaw = $this->postStr('detail');
        if ($detailRaw === '') {
            redirectWithFlash($back, 'error', 'กรุณากรอกรายละเอียดคำขอ');
        }
        if (strlen($detailRaw) > 60000) { // คอลัมน์ detail เป็น TEXT (สูงสุด 65,535 ไบต์) กันถูกตัดเงียบ
            redirectWithFlash($back, 'error', 'รายละเอียดยาวเกินไป');
        }

        $typeRaw      = $this->postStr('cooperative_type_name');
        $coopId       = intval($this->postStr('cooperative_id'));
        $auditorId    = intval($this->postStr('auditor_id'));
        if ($typeRaw === '' || $coopId < 1 || $auditorId < 1) {
            redirectWithFlash($back, 'error', 'กรุณาเลือกประเภทสหกรณ์ สหกรณ์ และผู้สอบบัญชี');
        }

        $user    = Auth::currentUser();
        $typeEsc = $this->db->escape($typeRaw);

        $coop = $this->db->fetchOne(
            "SELECT * FROM cooperatives WHERE id=$coopId AND type_name='$typeEsc'"
        );
        if (!$coop) {
            redirectWithFlash($back, 'error', 'ข้อมูลสหกรณ์ไม่ถูกต้อง');
        }
        // ต้องเป็นสหกรณ์ในสำนักงานของผู้ยื่น (ส่วนกลางยื่นแทนได้ทุกแห่ง)
        if (!(isHQ($user) || $coop['office_name'] === $user['office_name'])) {
            redirectWithFlash($back, 'error', 'คุณไม่มีสิทธิ์ยื่นคำขอของสหกรณ์นี้');
        }

        // ผู้สอบบัญชีต้องอยู่สำนักงานเดียวกับผู้ยื่น และยังใช้งานอยู่ (กัน POST ปลอมเลือกคนต่างสำนักงาน)
        $officeEsc = $this->db->escape($user['office_name']);
        $auditor   = $this->db->fetchOne(
            "SELECT id FROM users WHERE id=$auditorId AND office_name='$officeEsc' AND is_active=1"
        );
        if (!$auditor) {
            redirectWithFlash($back, 'error', 'ผู้สอบบัญชีไม่ถูกต้อง');
        }

        $title       = $this->db->escape($titleRaw);
        $detail      = $this->db->escape($detailRaw);
        $coopCode    = $this->db->escape($coop['code']);
        $coopName    = $this->db->escape($coop['name']);
        $submittedBy = intval($user['id']);

        $prefix     = Ticket::prefix('UNL');
        $ticketCode = Ticket::next($this->db, 'unlock_requests', $prefix);

        $attachmentPath = '';
        $attachmentName = '';
        if (isset($_FILES['attachment'])) {
            $result = FileUpload::uploadAttachment($_FILES['attachment'], 'unlock_requests', $ticketCode);
            if (!$result['success']) {
                redirectWithFlash($back, 'error', 'ไฟล์แนบ: ' . $result['error']);
            }
            $attachmentPath = $result['filename'];
            $attachmentName = $result['original_name'];
        }
        $attachmentEsc     = $this->db->escape($attachmentPath);
        $attachmentNameEsc = $this->db->escape($attachmentName);

        // เลขที่ชน UNIQUE KEY -> ขอเลขใหม่แล้วลองอีก สูงสุด 5 ครั้ง
        $ok = false;
        for ($try = 0; $try < 5 && !$ok; $try++) {
            if ($try > 0) {
                $ticketCode = Ticket::next($this->db, 'unlock_requests', $prefix);
            }
            $ticketEsc = $this->db->escape($ticketCode);
            $ok = $this->db->query(
                "INSERT INTO unlock_requests
                 (ticket_code, cooperative_type_name, cooperative_id, cooperative_code, cooperative_name,
                  auditor_id, title, detail, attachment, attachment_name, office_name, submitted_by, status)
                 VALUES
                 ('$ticketEsc', '$typeEsc', $coopId, '$coopCode', '$coopName',
                  $auditorId, '$title', '$detail', '$attachmentEsc', '$attachmentNameEsc', '$officeEsc', $submittedBy, 'pending')"
            );
        }
        if (!$ok) {
            if ($attachmentPath !== '') FileUpload::deleteFile($attachmentPath);
            redirectWithFlash($back, 'error', 'บันทึกคำขอไม่สำเร็จ กรุณาลองใหม่อีกครั้ง (ตรวจสอบว่ารัน migration_unlock_requests.sql และ migration_unlock_detail_attachment.sql แล้ว)');
        }

        $unlockId = $this->db->insertId();
        $this->unlockModel->addLog($unlockId, 'ยื่นคำขอปลดล็อคข้อมูล', $submittedBy);

        // แจ้งผู้ที่กดเสร็จสิ้นได้: ผู้ดำเนินการ (operator) และ admin ของสำนักงานนั้น + ส่วนกลาง
        $staffIds = array_unique(array_merge(
            Notification::getUsersByRole($this->db, 'operator', $user['office_name']),
            Notification::getUsersByRole($this->db, 'admin', $user['office_name'])
        ));
        $staffIds = array_values(array_diff($staffIds, array($submittedBy)));
        Notification::createForMultiple($this->db, $staffIds, $unlockId, 'unlock', 'status_changed',
            'มีคำขอปลดล็อคข้อมูลใหม่',
            'คำขอ ' . $ticketCode . ' ของ ' . $coop['name'] . ' รอตรวจสอบ');

        redirectWithFlash(APP_URL . '/?page=unlock_requests', 'success', 'ยื่นคำขอปลดล็อคข้อมูลสำเร็จ รหัส: ' . $ticketCode);
    }

    public function detail() {
        Auth::requireLogin();
        $id      = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $request = $this->unlockModel->getById($id);
        if (!$request) {
            redirectWithFlash(APP_URL . '/?page=unlock_requests', 'error', 'ไม่พบคำขอปลดล็อคข้อมูลนี้');
        }
        if (!canViewUnlock(Auth::currentUser(), $request)) {
            redirectWithFlash(APP_URL . '/?page=unlock_requests', 'error', 'คุณไม่มีสิทธิ์ดูรายการนี้');
        }

        $logs      = $this->unlockModel->getLogs($request['id']);
        $pageTitle = 'รายละเอียดคำขอ ' . $request['ticket_code'];
        $this->render('unlock_requests/detail', get_defined_vars());
    }

    public function view_attachment() {
        Auth::requireLogin();
        $id      = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $request = $this->unlockModel->getById($id);
        if (!$request || empty($request['attachment'])) {
            exit('ไม่พบไฟล์แนบ');
        }
        if (!canViewUnlock(Auth::currentUser(), $request)) {
            exit('คุณไม่มีสิทธิ์เข้าถึงไฟล์นี้');
        }
        FileUpload::serve($request['attachment'], $request['attachment_name']);
    }

    // รอตรวจสอบ -> เสร็จสิ้น (admin / ผู้ดำเนินการ ตาม canCompleteUnlock)
    public function complete() {
        Auth::requireLogin();
        Auth::checkCsrf(); // ตรวจก่อนอ่าน DB/ตรวจสิทธิ์

        $id  = intval($this->postStr('unlock_id'));
        $row = $this->db->fetchOne("SELECT * FROM unlock_requests WHERE id=$id AND status='pending'");
        if (!$row) {
            redirectWithFlash(APP_URL . '/?page=unlock_requests', 'error', 'ไม่พบรายการหรือสถานะไม่ถูกต้อง');
        }

        $user = Auth::currentUser();
        if (!canCompleteUnlock($user, $row)) {
            redirectWithFlash(APP_URL . '/?page=unlock_requests', 'error', 'คุณไม่มีสิทธิ์ดำเนินการ');
        }

        $userId = intval($user['id']);
        $now    = date('Y-m-d H:i:s');
        $note   = $this->postStr('note');
        $back   = APP_URL . '/?page=unlock_requests&action=detail&id=' . $id;

        // เปลี่ยนเฉพาะเมื่อสถานะยังเป็น pending (กันกดซ้ำ/แข่งกัน) แล้วเช็คผลก่อนแจ้งสำเร็จ
        $ok = $this->db->query(
            "UPDATE unlock_requests SET status='completed', updated_at='$now'
             WHERE id=$id AND status='pending'"
        );
        if (!$ok || $this->db->affectedRows() < 1) {
            redirectWithFlash($back, 'error', 'อัปเดตสถานะไม่สำเร็จ หรือมีผู้อื่นดำเนินการไปแล้ว');
        }
        $this->unlockModel->addLog($id, 'ดำเนินการเสร็จสิ้น', $userId, $note);

        // แจ้งผู้ยื่นและผู้สอบบัญชี (ไม่แจ้งตัวผู้กดเอง)
        $notifyIds = array_unique(array(intval($row['submitted_by']), intval($row['auditor_id'])));
        $notifyIds = array_values(array_diff($notifyIds, array($userId)));
        Notification::createForMultiple($this->db, $notifyIds, $id, 'unlock', 'completed',
            'คำขอปลดล็อคข้อมูลเสร็จสิ้น',
            'คำขอ ' . $row['ticket_code'] . ' ของ ' . $row['cooperative_name'] . ' ดำเนินการเสร็จสิ้นแล้ว');

        redirectWithFlash($back, 'success', 'อัปเดตสถานะสำเร็จ');
    }

    // ตัวกรองหน้ารายงาน/ส่งออก (ค่าเป็นสตริงเท่านั้น)
    private function reportFilters() {
        return $this->readReportFilters(array('status', 'keyword', 'office_name', 'date_from', 'date_to'));
    }

    // ตัวแปรที่ view/partial ของรายงานใช้ (ผู้เรียกใช้ extract()) แบ่งหน้าที่ SQL
    private function reportData($user, $filters) {
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $filters['per_page'] = $perPage;
        $filters['page_num'] = $currentPageNum;
        $totalItems = $this->unlockModel->countList($user, $filters);
        return array(
            'summary'        => $this->unlockModel->getSummaryByStatus($user, $filters),
            'details'        => $this->unlockModel->getList($user, $filters),
            'totalItems'     => $totalItems,
            'totalPages'     => max(1, ceil($totalItems / $perPage)),
            'currentPageNum' => $currentPageNum,
        );
    }

    public function report() {
        Auth::requireLogin();
        $user    = Auth::currentUser();
        $filters = $this->reportFilters();
        extract($this->reportData($user, $filters));

        $pageTitle = 'รายงานปลดล็อคข้อมูล';
        $this->render('unlock_requests/report', get_defined_vars());
    }

    public function ajax_report() {
        Auth::requireLogin();
        requireAjax();

        $user    = Auth::currentUser();
        $filters = $this->reportFilters();
        extract($this->reportData($user, $filters));

        include 'views/unlock_requests/_report_list_partial.php';
        exit;
    }

    // ส่งออกรายงานเป็น Excel (.xls) ตามตัวกรองปัจจุบัน พร้อมลายเซ็นท้ายเอกสาร
    public function export_excel() {
        Auth::requireLogin();

        $user     = Auth::currentUser();
        $filters  = $this->reportFilters();
        $requests = $this->unlockModel->getAllForExport($user, $filters);

        $headers = array('#','วันที่','เลขที่คำขอ','ประเภทสหกรณ์','สหกรณ์','ผู้สอบบัญชี','ชื่อเรื่อง','ผู้บันทึก','สำนักงาน','สถานะ','วันที่เสร็จสิ้น','ระยะเวลาดำเนินการ','ผลการดำเนินงาน');
        $rows    = array();
        foreach ($requests as $idx => $rq) {
            $done = ($rq['status'] === 'completed');
            $rows[] = array(
                $idx + 1,
                thaiDate($rq['created_at'], true),
                $rq['ticket_code'],
                $rq['cooperative_type_name'],
                $rq['cooperative_name'],
                $rq['auditor_name'],
                $rq['title'],
                $rq['submitter_name'],
                $rq['office_name'],
                unlockStatusLabel($rq['status']),
                $done ? thaiDateOrBlank($rq['completed_at']) : '',
                $done ? workDaysLabel($rq['created_at'], $rq['completed_at']) : '',
                ($done && isset($rq['result_note'])) ? $rq['result_note'] : '',
            );
        }

        $subtitleParts = array();
        if ($filters['status'] !== '')      $subtitleParts[] = 'สถานะ ' . unlockStatusLabel($filters['status']);
        if ($filters['date_from'] !== '')   $subtitleParts[] = 'ตั้งแต่ ' . $filters['date_from'];
        if ($filters['date_to'] !== '')     $subtitleParts[] = 'ถึง ' . $filters['date_to'];
        if ($filters['office_name'] !== '') $subtitleParts[] = $filters['office_name'];

        ExcelExport::export(
            'รายงานปลดล็อคข้อมูล',
            $headers,
            $rows,
            $user['fullname'],
            $this->signerTitle($user['id']),
            'unlock_requests_' . date('Ymd'),
            implode(' | ', $subtitleParts)
        );
    }
}
