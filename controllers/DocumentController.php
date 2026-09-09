<?php
class DocumentController extends Controller {

    private $docModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->docModel = new DocumentModel($db);
    }

    public function index() {
        $user    = Auth::currentUser();
        $filters = array(
            'status'    => isset($_GET['status'])    ? $_GET['status']    : '',
            'keyword'   => isset($_GET['keyword'])   ? $_GET['keyword']   : '',
            'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
            'date_to'   => isset($_GET['date_to'])   ? $_GET['date_to']   : '',
            'per_page'  => 10,
            'page_num'  => isset($_GET['p'])         ? intval($_GET['p']) : 1,
        );

        $documents      = $this->docModel->getList($user, $filters);
        $totalItems     = $this->docModel->countList($user, $filters);
        $totalPages     = ceil($totalItems / $filters['per_page']);
        $currentPageNum = $filters['page_num'];

        $pageTitle = 'รายการเอกสาร';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/documents/index.php';
        include 'views/layout/footer.php';
    }

    public function create() {
        Auth::requireRole(array('submitter', 'admin'));
        $pageTitle = 'นำส่งเอกสารใหม่';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/documents/create.php';
        include 'views/layout/footer.php';
    }

    public function store() {
        Auth::requireRole(array('submitter','admin'));
        Auth::checkCsrf();

        $user           = Auth::currentUser();
        $typeName       = $this->db->escape($_POST['cooperative_type_name']);
        $coopId         = intval($_POST['cooperative_id']);
        $year           = $this->db->escape($_POST['fiscal_year']);
        $date           = $this->db->escape($_POST['submitted_date']);
        $officeName     = $this->db->escape($user['office_name']);
        $documentNumber = $this->db->escape(isset($_POST['document_number']) ? trim($_POST['document_number']) : '');

        // ตรวจสอบรูปแบบปีบัญชีให้เป็นตัวเลขล้วนเท่านั้น เพราะค่านี้ถูกนำไปใช้เป็น
        // ส่วนหนึ่งของ path โฟลเดอร์ปลายทางตอนอัปโหลดไฟล์ (กัน Path Traversal)
        if (!preg_match('/^[0-9]{4}$/', $year)) {
            redirectWithFlash(APP_URL . '/?page=documents&action=create', 'error', 'ปีบัญชีไม่ถูกต้อง');
        }

        $coop = $this->db->fetchOne(
            "SELECT * FROM cooperatives WHERE id=$coopId AND type_name='$typeName'"
        );
        if (!$coop) {
            redirectWithFlash(APP_URL . '/?page=documents&action=create', 'error', 'ข้อมูลสหกรณ์ไม่ถูกต้อง');
        }

        $ticketCode = $this->docModel->generateTicketCode();
        $files      = array();
        $fileNames  = array();

        for ($i = 1; $i <= 4; $i++) {
            $result = FileUpload::upload(
                $_FILES['file_doc' . $i],
                $coop['code'], $ticketCode, $i, $year
            );
            if (!$result['success']) {
                // ลบไฟล์ที่อัปโหลดสำเร็จไปแล้วก่อนหน้า (ถ้ามี) เพื่อไม่ให้ไฟล์กำพร้าค้างระบบ
                foreach ($files as $f) { FileUpload::deleteFile($f); }
                redirectWithFlash(APP_URL . '/?page=documents&action=create', 'error',
                    'ไฟล์ที่ ' . $i . ': ' . $result['error']);
            }
            $files[$i]     = $result['filename'];
            $fileNames[$i] = $result['original_name'];
        }

        $ticketEsc   = $this->db->escape($ticketCode);
        $coopCode    = $this->db->escape($coop['code']);
        $coopName    = $this->db->escape($coop['name']);
        $submittedBy = intval($user['id']);

        $f1 = $this->db->escape($files[1]); $n1 = $this->db->escape($fileNames[1]);
        $f2 = $this->db->escape($files[2]); $n2 = $this->db->escape($fileNames[2]);
        $f3 = $this->db->escape($files[3]); $n3 = $this->db->escape($fileNames[3]);
        $f4 = $this->db->escape($files[4]); $n4 = $this->db->escape($fileNames[4]);

        $insertResult = $this->db->query(
            "INSERT INTO documents
             (ticket_code, cooperative_type_name, cooperative_id, cooperative_code,
              cooperative_name, fiscal_year, submitted_date, document_number,
              file_doc1, file_doc2, file_doc3, file_doc4,
              file_doc1_name, file_doc2_name, file_doc3_name, file_doc4_name,
              submitted_by, office_name, status)
             VALUES
             ('$ticketEsc', '$typeName', $coopId, '$coopCode',
              '$coopName', '$year', '$date', '$documentNumber',
              '$f1', '$f2', '$f3', '$f4',
              '$n1', '$n2', '$n3', '$n4',
              $submittedBy, '$officeName', 'pending')"
        );

        // เดิมไม่เช็คผลลัพธ์การ insert เลย ถ้าเกิด ticket_code ชนกับ UNIQUE KEY
        // (เช่น 2 คนกดนำส่งพร้อมกันจนได้เลขที่เอกสารซ้ำ) จะเห็นข้อความสำเร็จ
        // ทั้งที่ไฟล์ถูกอัปโหลดไปแล้วแต่ไม่มี record ในฐานข้อมูลเลย
        if (!$insertResult) {
            foreach ($files as $f) { FileUpload::deleteFile($f); }
            redirectWithFlash(APP_URL . '/?page=documents&action=create', 'error',
                'บันทึกเอกสารไม่สำเร็จ กรุณาลองนำส่งใหม่อีกครั้ง');
        }

        $docId = $this->db->insertId();

        $this->docModel->addLog($docId, 'นำส่งเอกสาร', $submittedBy);

        $newDoc = $this->docModel->getById($docId);
        Notification::notifyStatusChange($this->db, $newDoc, 'inspecting', $submittedBy);

        redirectWithFlash(APP_URL . '/?page=documents', 'success', 'นำส่งเอกสารสำเร็จ รหัส: ' . $ticketCode);
    }

    public function detail() {
        $id  = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $doc = $this->docModel->getById($id);
        if (!$doc) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่พบเอกสาร');
        }

        $user = Auth::currentUser();

        $canView = false;
        if (userHasRole($user, 'admin') || isHQ($user)) {
            $canView = true;
        } elseif (userHasRole($user, 'submitter') && $doc['submitted_by'] == $user['id']) {
            $canView = true;
        } elseif (userHasAnyRole($user, array('inspector','approver','operator'))) {
            $canView = ($doc['office_name'] === $user['office_name']);
        }

        if (!$canView) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'คุณไม่มีสิทธิ์ดูเอกสารนี้');
        }

        $logs = $this->docModel->getLogs($doc['id']);

        $pageTitle = 'รายละเอียดเอกสาร ' . $doc['ticket_code'];
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/documents/detail.php';
        include 'views/layout/footer.php';
    }

    public function edit() {
        Auth::requireRole(array('submitter', 'admin'));
        $id  = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $doc = $this->docModel->getById($id);
        if (!$doc || $doc['status'] !== 'revision') {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่สามารถแก้ไขเอกสารนี้ได้');
        }
        $user = Auth::currentUser();
        // เดิมเช็คแค่ $user['role'] ซึ่งเป็น role แรกสุดในรายการเดียวเท่านั้น
        // ทำให้ user ที่มีหลายบทบาท เช่น "submitter,admin" แต่ role แรกในระบบ
        // เก็บเป็น submitter จะไม่ถูกมองว่าเป็น admin ทั้งที่จุดอื่นทั้งระบบใช้
        // userHasRole() สม่ำเสมอ จึงแก้ให้ตรงกัน
        if (!userHasRole($user, 'admin') && $doc['submitted_by'] != $user['id']) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'คุณไม่มีสิทธิ์แก้ไขเอกสารนี้');
        }

        $pageTitle = 'แก้ไขเอกสาร';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/documents/edit.php';
        include 'views/layout/footer.php';
    }

    public function inspect() {
        Auth::requireRole(array('inspector','admin'));
        Auth::checkCsrf();

        $docId         = intval($_POST['doc_id']);
        $receiveNumber = $this->db->escape($_POST['receive_number']);
        $note          = $this->db->escape(isset($_POST['inspector_note']) ? $_POST['inspector_note'] : '');
        $userId        = intval($_SESSION['user_id']);
        $now           = date('Y-m-d H:i:s');

        $receiveNumberTrimmed = trim($_POST['receive_number']);
        if (empty($receiveNumberTrimmed)) {
            redirectWithFlash(APP_URL . '/?page=documents&action=detail&id=' . $docId,
                'error', 'กรุณากรอกเลขรับหนังสือ');
        }

        $doc = $this->db->fetchOne(
            "SELECT * FROM documents WHERE id=$docId AND status IN ('pending','inspecting')"
        );
        if (!$doc) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่พบเอกสาร');
        }
        $currentUser = Auth::currentUser();
        if (!canActionDocument($currentUser, $doc)) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'คุณไม่มีสิทธิ์ดำเนินการกับเอกสารนี้');
        }

        $this->db->query(
            "UPDATE documents
             SET status='approving', receive_number='$receiveNumber', inspector_note='$note',
                 inspected_by=$userId, inspected_at='$now', updated_at='$now'
             WHERE id=$docId"
        );
        $this->docModel->addLog($docId, 'ตรวจสอบและส่งต่ออนุมัติ', $userId, isset($_POST['inspector_note']) ? $_POST['inspector_note'] : '');

        $updatedDoc = $this->docModel->getById($docId);
        Notification::notifyStatusChange($this->db, $updatedDoc, 'approving', $userId);

        redirectWithFlash(APP_URL . '/?page=documents', 'success', 'ยืนยันการตรวจสอบสำเร็จ');
    }

    public function approve() {
        Auth::requireRole(array('approver','admin'));
        Auth::checkCsrf();

        $docId  = intval($_POST['doc_id']);
        $note   = $this->db->escape(isset($_POST['approver_note']) ? $_POST['approver_note'] : '');
        $userId = intval($_SESSION['user_id']);
        $now    = date('Y-m-d H:i:s');

        $doc = $this->db->fetchOne(
            "SELECT * FROM documents WHERE id=$docId AND status='approving'"
        );
        if (!$doc) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่พบเอกสาร');
        }
        $currentUser = Auth::currentUser();
        if (!canActionDocument($currentUser, $doc)) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'คุณไม่มีสิทธิ์ดำเนินการกับเอกสารนี้');
        }

        $this->db->query(
            "UPDATE documents
             SET status='operating', approver_note='$note',
                 approved_by=$userId, approved_at='$now', updated_at='$now'
             WHERE id=$docId"
        );
        $this->docModel->addLog($docId, 'อนุมัติเอกสาร', $userId, isset($_POST['approver_note']) ? $_POST['approver_note'] : '');

        $updatedDoc = $this->docModel->getById($docId);
        Notification::notifyStatusChange($this->db, $updatedDoc, 'operating', $userId);

        redirectWithFlash(APP_URL . '/?page=documents', 'success', 'อนุมัติเอกสารสำเร็จ');
    }

    public function bulk_approve() {
        Auth::requireRole(array('approver','admin'));
        Auth::checkCsrf();

        if (!isset($_POST['doc_ids']) || !is_array($_POST['doc_ids'])) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่พบรายการที่เลือก');
        }

        $userId = intval($_SESSION['user_id']);
        $count  = 0;
        $now    = date('Y-m-d H:i:s');

        foreach ($_POST['doc_ids'] as $docId) {
            $docId = intval($docId);
            $doc   = $this->db->fetchOne(
                "SELECT * FROM documents WHERE id=$docId AND status='approving'"
            );
            if (!$doc) continue;
            $currentUser = Auth::currentUser();
            if (!canActionDocument($currentUser, $doc)) continue;

            $this->db->query(
                "UPDATE documents SET status='operating', approved_by=$userId,
                 approved_at='$now', updated_at='$now' WHERE id=$docId"
            );
            $this->docModel->addLog($docId, 'bulk_approved', $userId, 'อนุมัติพร้อมกันหลายรายการ');

            $updatedDoc = $this->docModel->getById($docId);
            Notification::notifyStatusChange($this->db, $updatedDoc, 'operating', $userId);
            $count++;
        }

        redirectWithFlash(APP_URL . '/?page=documents', 'success', 'อนุมัติเอกสารสำเร็จ ' . $count . ' รายการ');
    }

    public function operate() {
        Auth::requireRole(array('operator','admin'));
        Auth::checkCsrf();

        $docId  = intval($_POST['doc_id']);
        $note   = $this->db->escape(isset($_POST['operator_note']) ? $_POST['operator_note'] : '');
        $userId = intval($_SESSION['user_id']);
        $now    = date('Y-m-d H:i:s');

        $doc = $this->db->fetchOne(
            "SELECT * FROM documents WHERE id=$docId AND status='operating'"
        );
        if (!$doc) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่พบเอกสาร');
        }
        $currentUser = Auth::currentUser();
        if (!canActionDocument($currentUser, $doc)) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'คุณไม่มีสิทธิ์ดำเนินการกับเอกสารนี้');
        }

        $this->db->query(
            "UPDATE documents
             SET status='completed', operator_note='$note',
                 operated_by=$userId, operated_at='$now', updated_at='$now'
             WHERE id=$docId"
        );
        $this->docModel->addLog($docId, 'ดำเนินการเสร็จสิ้น', $userId, isset($_POST['operator_note']) ? $_POST['operator_note'] : '');

        $updatedDoc = $this->docModel->getById($docId);
        Notification::notifyStatusChange($this->db, $updatedDoc, 'completed', $userId);

        redirectWithFlash(APP_URL . '/?page=documents', 'success', 'ยืนยันการดำเนินการเสร็จสิ้น');
    }

    public function revision() {
        Auth::requireLogin();
        Auth::checkCsrf();

        $docId        = intval($_POST['doc_id']);
        $revisionNote = $this->db->escape($_POST['revision_note']);
        $userId       = intval($_SESSION['user_id']);
        $now          = date('Y-m-d H:i:s');

        if (!Auth::hasAnyRole(array('inspector','approver','operator','admin'))) {
            if (isAjax()) {
                jsonResponse(array('success' => false, 'message' => 'ไม่มีสิทธิ์'));
            }
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่มีสิทธิ์');
        }

        $doc = $this->db->fetchOne("SELECT * FROM documents WHERE id=$docId");
        if (!$doc) {
            if (isAjax()) {
                jsonResponse(array('success' => false, 'message' => 'ไม่พบเอกสาร'));
            }
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่พบเอกสาร');
        }
        $currentUser = Auth::currentUser();
        if (!canActionDocument($currentUser, $doc)) {
            if (isAjax()) {
                jsonResponse(array('success' => false, 'message' => 'คุณไม่มีสิทธิ์ดำเนินการกับเอกสารนี้'));
            }
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'คุณไม่มีสิทธิ์ดำเนินการกับเอกสารนี้');
        }

        $roleForLog = $doc['status'];
        $role       = (strpos($roleForLog, 'approving') !== false)
            ? 'approver'
            : ((strpos($roleForLog, 'operating') !== false) ? 'operator' : 'inspector');
        $roleEsc = $this->db->escape($role);

        $this->db->query(
            "UPDATE documents
             SET status='revision', revision_note='$revisionNote',
                 revision_by=$userId, revision_role='$roleEsc', updated_at='$now'
             WHERE id=$docId"
        );
        $this->docModel->addLog($docId, 'ส่งกลับแก้ไข', $userId, $_POST['revision_note']);

        $updatedDoc = $this->docModel->getById($docId);
        Notification::notifyStatusChange($this->db, $updatedDoc, 'revision', $userId);

        if (isAjax()) {
            jsonResponse(array('success' => true, 'message' => 'ส่งกลับแก้ไขสำเร็จ'));
        }

        redirectWithFlash(APP_URL . '/?page=documents', 'success', 'ส่งกลับแก้ไขสำเร็จ');
    }

    public function resubmit() {
        Auth::requireRole(array('submitter','admin'));
        Auth::checkCsrf();

        $docId  = intval($_POST['doc_id']);
        $userId = intval($_SESSION['user_id']);
        $now    = date('Y-m-d H:i:s');

        $doc = $this->db->fetchOne(
            "SELECT * FROM documents WHERE id=$docId AND submitted_by=$userId AND status='revision'"
        );
        if (!$doc) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่พบเอกสาร');
        }

        $year    = $doc['fiscal_year'];
        $updates = array();

        for ($i = 1; $i <= 4; $i++) {
            if (isset($_FILES['file_doc'.$i]) && $_FILES['file_doc'.$i]['error'] === UPLOAD_ERR_OK) {
                $result = FileUpload::upload(
                    $_FILES['file_doc'.$i], $doc['cooperative_code'],
                    $doc['ticket_code'], $i, $year
                );
                if (!$result['success']) {
                    redirectWithFlash(APP_URL . '/?page=documents&action=edit&id=' . $docId,
                        'error', 'ไฟล์ที่ ' . $i . ': ' . $result['error']);
                }

                // ลบไฟล์เดิมออกจากระบบก่อน เพื่อไม่ให้ไฟล์เก่าค้างอยู่โดยไม่มีการอ้างอิง
                $oldFileField = 'file_doc' . $i;
                if (!empty($doc[$oldFileField])) {
                    FileUpload::deleteFile($doc[$oldFileField]);
                }

                $fn = $this->db->escape($result['filename']);
                $nn = $this->db->escape($result['original_name']);
                $updates[] = "file_doc$i='$fn', file_doc{$i}_name='$nn'";
            }
        }

        $updateStr = !empty($updates) ? implode(', ', $updates) . ', ' : '';
        $this->db->query(
            "UPDATE documents SET {$updateStr}status='pending', updated_at='$now' WHERE id=$docId"
        );
        $this->docModel->addLog($docId, 'แก้ไขและส่งใหม่', $userId);

        $updatedDoc = $this->docModel->getById($docId);
        Notification::notifyStatusChange($this->db, $updatedDoc, 'pending', $userId);

        redirectWithFlash(APP_URL . '/?page=documents', 'success', 'ส่งเอกสารใหม่สำเร็จ');
    }

    public function view_file() {
        Auth::requireLogin();
        $docId   = isset($_GET['id'])   ? intval($_GET['id'])   : 0;
        $fileNum = isset($_GET['file']) ? intval($_GET['file']) : 0;

        if ($fileNum < 1 || $fileNum > 4) exit('ไม่พบไฟล์');

        $doc = $this->docModel->getById($docId);
        if (!$doc) exit('ไม่พบเอกสาร');

        // แก้ IDOR: เดิมฟังก์ชันนี้เช็คแค่ Auth::requireLogin() (แค่ล็อกอินแล้วก็เปิดได้)
        // ทำให้ผู้ใช้คนไหนก็ตามเดา id เอกสารแล้วเปิดไฟล์ PDF ของหน่วยงาน/ผู้อื่นได้
        // ตอนนี้ใช้ logic การตรวจสิทธิ์แบบเดียวกับ detail() เพื่อจำกัดว่าเห็นได้เฉพาะ
        // เอกสารที่ตนเองมีสิทธิ์เข้าถึงเท่านั้น
        $user = Auth::currentUser();
        $canView = false;
        if (userHasRole($user, 'admin') || isHQ($user)) {
            $canView = true;
        } elseif (userHasRole($user, 'submitter') && $doc['submitted_by'] == $user['id']) {
            $canView = true;
        } elseif (userHasAnyRole($user, array('inspector','approver','operator'))) {
            $canView = ($doc['office_name'] === $user['office_name']);
        }
        if (!$canView) {
            exit('คุณไม่มีสิทธิ์เข้าถึงไฟล์นี้');
        }

        $field     = 'file_doc' . $fileNum;
        $nameField = 'file_doc' . $fileNum . '_name';
        $filePath  = UPLOAD_DIR . $doc[$field];

        if (!file_exists($filePath)) exit('ไม่พบไฟล์ในระบบ');

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($doc[$nameField]) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($filePath);
        exit;
    }

    // =====================================================
    // AJAX Actions
    // =====================================================

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
            'page_num'  => isset($_GET['p'])         ? intval($_GET['p']) : 1,
        );

        $documents      = $this->docModel->getList($user, $filters);
        $totalItems     = $this->docModel->countList($user, $filters);
        $totalPages     = ceil($totalItems / $filters['per_page']);
        $currentPageNum = $filters['page_num'];

        include 'views/documents/_list_partial.php';
        exit;
    }

    public function ajax_cooperatives() {
        Auth::requireLogin();
        if (!isAjax()) {
            http_response_code(403); exit('Forbidden');
        }
        $typeName   = isset($_GET['type_name']) ? $this->db->escape($_GET['type_name']) : '';
        $officeName = $this->db->escape($_SESSION['office_name']);

        if (!$typeName) {
            jsonResponse(array());
        }

        $currentUser = Auth::currentUser();
        if (userHasRole($currentUser, 'admin') || isHQ($currentUser)) {
            $rows = $this->db->fetchAll(
                "SELECT id, name, code FROM cooperatives WHERE type_name='$typeName' AND status='active' ORDER BY name"
            );
        } else {
            $rows = $this->db->fetchAll(
                "SELECT id, name, code FROM cooperatives
                 WHERE type_name='$typeName' AND office_name='$officeName' AND status='active' ORDER BY name"
            );
        }
        jsonResponse($rows);
    }

    public function ajax_status() {
        Auth::requireLogin();
        if (!isAjax()) {
            http_response_code(403); exit('Forbidden');
        }
        $id  = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $doc = $this->docModel->getById($id);
        if (!$doc) {
            jsonResponse(array('success' => false));
        }
        jsonResponse(array(
            'success'  => true,
            'status'   => $doc['status'],
            'label'    => docStatusLabel($doc['status']),
            'badge'    => docStatusBadgeClass($doc['status']),
            'updated'  => $doc['updated_at'],
        ));
    }

    public function ajax_unread_count() {
        Auth::requireLogin();
        if (!isAjax()) {
            http_response_code(403); exit('Forbidden');
        }
        $userId = intval($_SESSION['user_id']);
        $row    = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$userId AND is_read=0"
        );
        jsonResponse(array('count' => intval($row['cnt'])));
    }

    public function ajax_mark_read() {
        Auth::requireLogin();
        if (!isAjax()) {
            http_response_code(403); exit('Forbidden');
        }
        $notifId = intval($_POST['id']);
        $userId  = intval($_SESSION['user_id']);
        $this->db->query(
            "UPDATE notifications SET is_read=1 WHERE id=$notifId AND user_id=$userId"
        );
        jsonResponse(array('success' => true));
    }
}