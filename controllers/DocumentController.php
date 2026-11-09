<?php
class DocumentController extends Controller {

    private $docModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->docModel = new DocumentModel($db);
    }

    // สิทธิ์ดูเอกสาร (ใช้ร่วมกันใน detail / view_file / ajax_status)
    private function canViewDoc($user, $doc) {
        if (isHQ($user) || adminCanAccessOffice($user, $doc['office_name'])) return true;
        if (userHasRole($user, 'submitter') && $doc['submitted_by'] == $user['id']) return true;
        if (userHasAnyRole($user, array('inspector','approver','operator'))) {
            return ($doc['office_name'] === $user['office_name']);
        }
        return false;
    }

    // ตัวแปรที่ view รายการ/partial ใช้ (ใช้ extract() ที่ผู้เรียก) เดิมเขียนซ้ำใน index() และ ajax_list()
    private function listData($user, $filters) {
        $documents = $this->docModel->getList($user, $filters);
        $totalItems = $this->docModel->countList($user, $filters);
        return array(
            'documents'        => $documents,
            'totalItems'     => $totalItems,
            'totalPages'     => ceil($totalItems / $filters['per_page']),
            'currentPageNum' => $filters['page_num'],
        );
    }

    public function index() {
        $user    = Auth::currentUser();
        $filters = $this->standardListFilters();

        extract($this->listData($user, $filters));

        $pageTitle = 'รายการเอกสาร';
        $this->render('documents/index', get_defined_vars());
    }

    public function create() {
        Auth::requireRole(array('submitter', 'admin'));
        $pageTitle = 'นำส่งเอกสารใหม่';
        $this->render('documents/create', get_defined_vars());
    }

    public function store() {
        Auth::requireRole(array('submitter','admin'));
        Auth::checkCsrf();

        $back = APP_URL . '/?page=documents&action=create';
        $user = Auth::currentUser();

        $typeRaw   = isset($_POST['cooperative_type_name']) ? $_POST['cooperative_type_name'] : '';
        $coopId    = isset($_POST['cooperative_id']) ? intval($_POST['cooperative_id']) : 0;
        $yearRaw   = isset($_POST['fiscal_year']) ? trim($_POST['fiscal_year']) : '';
        $dateRaw   = isset($_POST['submitted_date']) ? trim($_POST['submitted_date']) : '';
        $docNumRaw = isset($_POST['document_number']) ? trim($_POST['document_number']) : '';

        // ปีบัญชีถูกใช้เป็นส่วนหนึ่งของ path อัปโหลด จึงต้องเป็นตัวเลข 4 หลักเท่านั้น (กัน Path Traversal)
        if (!preg_match('/^[0-9]{4}$/', $yearRaw)) {
            redirectWithFlash($back, 'error', 'ปีบัญชีไม่ถูกต้อง');
        }
        if (!preg_match('/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4}$/', $dateRaw)) {
            redirectWithFlash($back, 'error', 'วันที่นำส่งไม่ถูกต้อง (รูปแบบ วว/ดด/ปปปป)');
        }
        if ($docNumRaw === '' || strlen($docNumRaw) > 100) {
            redirectWithFlash($back, 'error', 'กรุณากรอกเลขที่หนังสือ (ไม่เกิน 100 ตัวอักษร)');
        }

        $typeName       = $this->db->escape($typeRaw);
        $year           = $yearRaw;
        $date           = $this->db->escape($dateRaw);
        $officeName     = $this->db->escape($user['office_name']);
        $documentNumber = $this->db->escape($docNumRaw);

        $coop = $this->db->fetchOne(
            "SELECT * FROM cooperatives WHERE id=$coopId AND type_name='$typeName' AND status='active'"
        );
        if (!$coop) {
            redirectWithFlash($back, 'error', 'ข้อมูลสหกรณ์ไม่ถูกต้อง');
        }
        // ต้องเป็นสหกรณ์ในสำนักงานของผู้นำส่ง (ส่วนกลางเลือกได้ทุกแห่ง)
        if (!(isHQ($user) || $coop['office_name'] === $user['office_name'])) {
            redirectWithFlash($back, 'error', 'คุณไม่มีสิทธิ์นำส่งเอกสารของสหกรณ์นี้');
        }

        $prefix     = Ticket::prefix('TKT');
        $ticketCode = Ticket::next($this->db, 'documents', $prefix);
        $files      = array();
        $fileNames  = array();

        for ($i = 1; $i <= 4; $i++) {
            if (!isset($_FILES['file_doc' . $i])) {
                foreach ($files as $f) { FileUpload::deleteFile($f); }
                redirectWithFlash($back, 'error', 'กรุณาแนบไฟล์ที่ ' . $i);
            }
            $result = FileUpload::upload($_FILES['file_doc' . $i], $coop['code'], $ticketCode, $i, $year);
            if (!$result['success']) {
                foreach ($files as $f) { FileUpload::deleteFile($f); }
                redirectWithFlash($back, 'error', 'ไฟล์ที่ ' . $i . ': ' . $result['error']);
            }
            $files[$i]     = $result['filename'];
            $fileNames[$i] = $result['original_name'];
        }

        $coopCode    = $this->db->escape($coop['code']);
        $coopName    = $this->db->escape($coop['name']);
        $submittedBy = intval($user['id']);
        $f1 = $this->db->escape($files[1]); $n1 = $this->db->escape($fileNames[1]);
        $f2 = $this->db->escape($files[2]); $n2 = $this->db->escape($fileNames[2]);
        $f3 = $this->db->escape($files[3]); $n3 = $this->db->escape($fileNames[3]);
        $f4 = $this->db->escape($files[4]); $n4 = $this->db->escape($fileNames[4]);

        // เลขที่เอกสารชน UNIQUE KEY (ส่งพร้อมกัน) -> ขอเลขใหม่แล้วลองอีก สูงสุด 5 ครั้ง
        $insertResult = false;
        for ($try = 0; $try < 5 && !$insertResult; $try++) {
            if ($try > 0) {
                $ticketCode = Ticket::next($this->db, 'documents', $prefix);
            }
            $ticketEsc = $this->db->escape($ticketCode);
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
        }
        if (!$insertResult) {
            foreach ($files as $f) { FileUpload::deleteFile($f); }
            redirectWithFlash($back, 'error', 'บันทึกเอกสารไม่สำเร็จ กรุณาลองนำส่งใหม่อีกครั้ง');
        }

        $docId = $this->db->insertId();
        $this->docModel->addLog($docId, 'นำส่งเอกสาร', $submittedBy);

        // 'inspecting' ในที่นี้คือคีย์ของข้อความ "มีเอกสารใหม่รอตรวจสอบ" ใน Notification (ไม่ใช่สถานะของเอกสาร)
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
        if (!$this->canViewDoc($user, $doc)) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'คุณไม่มีสิทธิ์ดูเอกสารนี้');
        }

        $logs = $this->docModel->getLogs($doc['id']);

        $pageTitle = 'รายละเอียดเอกสาร ' . $doc['ticket_code'];
        $this->render('documents/detail', get_defined_vars());
    }

    public function edit() {
        Auth::requireRole(array('submitter', 'admin'));
        $id  = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $doc = $this->docModel->getById($id);
        if (!$doc || $doc['status'] !== 'revision') {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่สามารถแก้ไขเอกสารนี้ได้');
        }
        $user = Auth::currentUser();
        if (!adminCanAccessOffice($user, $doc['office_name']) && $doc['submitted_by'] != $user['id']) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'คุณไม่มีสิทธิ์แก้ไขเอกสารนี้');
        }

        $pageTitle = 'แก้ไขเอกสาร';
        $this->render('documents/edit', get_defined_vars());
    }

    public function inspect() {
        Auth::requireRole(array('inspector','admin'));
        Auth::checkCsrf();

        $docId         = isset($_POST['doc_id']) ? intval($_POST['doc_id']) : 0;
        $receiveRaw    = isset($_POST['receive_number']) ? trim($_POST['receive_number']) : '';
        $noteRaw       = isset($_POST['inspector_note']) ? $_POST['inspector_note'] : '';
        $userId        = intval($_SESSION['user_id']);
        $now           = date('Y-m-d H:i:s');

        if ($receiveRaw === '') {
            redirectWithFlash(APP_URL . '/?page=documents&action=detail&id=' . $docId, 'error', 'กรุณากรอกเลขรับหนังสือ');
        }
        $receiveNumber = $this->db->escape($receiveRaw);
        $note          = $this->db->escape($noteRaw);

        $doc = $this->db->fetchOne("SELECT * FROM documents WHERE id=$docId AND status IN ('pending','inspecting')");
        if (!$doc) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่พบเอกสาร');
        }
        if (!canActionDocument(Auth::currentUser(), $doc)) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'คุณไม่มีสิทธิ์ดำเนินการกับเอกสารนี้');
        }

        $this->db->query(
            "UPDATE documents
             SET status='approving', receive_number='$receiveNumber', inspector_note='$note',
                 inspected_by=$userId, inspected_at='$now', updated_at='$now'
             WHERE id=$docId"
        );
        $this->docModel->addLog($docId, 'ตรวจสอบและส่งต่ออนุมัติ', $userId, $noteRaw);

        $updatedDoc = $this->docModel->getById($docId);
        Notification::notifyStatusChange($this->db, $updatedDoc, 'approving', $userId);

        redirectWithFlash(APP_URL . '/?page=documents', 'success', 'ยืนยันการตรวจสอบสำเร็จ');
    }

    public function approve() {
        Auth::requireRole(array('approver','admin'));
        Auth::checkCsrf();

        $docId   = isset($_POST['doc_id']) ? intval($_POST['doc_id']) : 0;
        $noteRaw = isset($_POST['approver_note']) ? $_POST['approver_note'] : '';
        $note    = $this->db->escape($noteRaw);
        $userId  = intval($_SESSION['user_id']);
        $now     = date('Y-m-d H:i:s');

        $doc = $this->db->fetchOne("SELECT * FROM documents WHERE id=$docId AND status='approving'");
        if (!$doc) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่พบเอกสาร');
        }
        if (!canActionDocument(Auth::currentUser(), $doc)) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'คุณไม่มีสิทธิ์ดำเนินการกับเอกสารนี้');
        }

        $this->db->query(
            "UPDATE documents
             SET status='operating', approver_note='$note',
                 approved_by=$userId, approved_at='$now', updated_at='$now'
             WHERE id=$docId"
        );
        $this->docModel->addLog($docId, 'อนุมัติเอกสาร', $userId, $noteRaw);

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

        $userId      = intval($_SESSION['user_id']);
        $currentUser = Auth::currentUser();
        $count       = 0;
        $now         = date('Y-m-d H:i:s');

        foreach ($_POST['doc_ids'] as $docId) {
            $docId = intval($docId);
            $doc   = $this->db->fetchOne("SELECT * FROM documents WHERE id=$docId AND status='approving'");
            if (!$doc) continue;
            if (!canActionDocument($currentUser, $doc)) continue;

            $this->db->query(
                "UPDATE documents SET status='operating', approved_by=$userId,
                 approved_at='$now', updated_at='$now' WHERE id=$docId"
            );
            $this->docModel->addLog($docId, 'อนุมัติเอกสาร', $userId, 'อนุมัติพร้อมกันหลายรายการ');

            $updatedDoc = $this->docModel->getById($docId);
            Notification::notifyStatusChange($this->db, $updatedDoc, 'operating', $userId);
            $count++;
        }

        redirectWithFlash(APP_URL . '/?page=documents', 'success', 'อนุมัติเอกสารสำเร็จ ' . $count . ' รายการ');
    }

    public function operate() {
        Auth::requireRole(array('operator','admin'));
        Auth::checkCsrf();

        $docId   = isset($_POST['doc_id']) ? intval($_POST['doc_id']) : 0;
        $noteRaw = isset($_POST['operator_note']) ? $_POST['operator_note'] : '';
        $note    = $this->db->escape($noteRaw);
        $userId  = intval($_SESSION['user_id']);
        $now     = date('Y-m-d H:i:s');

        $doc = $this->db->fetchOne("SELECT * FROM documents WHERE id=$docId AND status='operating'");
        if (!$doc) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่พบเอกสาร');
        }
        if (!canActionDocument(Auth::currentUser(), $doc)) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'คุณไม่มีสิทธิ์ดำเนินการกับเอกสารนี้');
        }

        $this->db->query(
            "UPDATE documents
             SET status='completed', operator_note='$note',
                 operated_by=$userId, operated_at='$now', updated_at='$now'
             WHERE id=$docId"
        );
        $this->docModel->addLog($docId, 'ดำเนินการเสร็จสิ้น', $userId, $noteRaw);

        $updatedDoc = $this->docModel->getById($docId);
        Notification::notifyStatusChange($this->db, $updatedDoc, 'completed', $userId);

        redirectWithFlash(APP_URL . '/?page=documents', 'success', 'ยืนยันการดำเนินการเสร็จสิ้น');
    }

    // ส่งกลับให้แก้ไข: ผู้นำส่งดึงกลับเอง (รอผู้ตรวจสอบ) หรือผู้รับผิดชอบขั้นนั้นส่งกลับ
    // กฎอยู่ที่ returnForEditRules()['documents'] ไม่มีสิทธิ์พิเศษของ admin
    public function revision() {
        $ctx  = $this->authorizeReturnForEdit('documents', 'documents', 'doc_id');
        $role = ($ctx['actor'] === 'submitter') ? 'submitter' : substr($ctx['actor'], 5);
        $this->commitReturnForEdit('documents', 'documents', $ctx, ", revision_role='" . $this->db->escape($role) . "'");

        $docId  = intval($ctx['row']['id']);
        $userId = intval($ctx['user']['id']);
        $this->docModel->addLog($docId, 'ส่งกลับแก้ไข', $userId, $ctx['note']);

        // ผู้นำส่งดึงกลับเองไม่ต้องแจ้งเตือนตัวเอง
        if ($ctx['actor'] !== 'submitter') {
            Notification::notifyStatusChange($this->db, $this->docModel->getById($docId), 'revision', $userId);
        }
        $this->returnDone('documents');
    }

    public function resubmit() {
        Auth::requireRole(array('submitter','admin'));
        Auth::checkCsrf();

        $docId  = isset($_POST['doc_id']) ? intval($_POST['doc_id']) : 0;
        $userId = intval($_SESSION['user_id']);
        $now    = date('Y-m-d H:i:s');

        $doc = $this->db->fetchOne("SELECT * FROM documents WHERE id=$docId AND status='revision'");
        // เจ้าของเอกสารหรือ admin (ให้ตรงกับเงื่อนไขในหน้า edit)
        if (!$doc || (!adminCanAccessOffice(Auth::currentUser(), $doc['office_name']) && $doc['submitted_by'] != $userId)) {
            redirectWithFlash(APP_URL . '/?page=documents', 'error', 'ไม่พบเอกสาร');
        }

        $year    = $doc['fiscal_year'];
        $updates = array();
        $oldFiles = array();

        for ($i = 1; $i <= 4; $i++) {
            if (isset($_FILES['file_doc'.$i]) && $_FILES['file_doc'.$i]['error'] === UPLOAD_ERR_OK) {
                $result = FileUpload::upload($_FILES['file_doc'.$i], $doc['cooperative_code'], $doc['ticket_code'], $i, $year);
                if (!$result['success']) {
                    redirectWithFlash(APP_URL . '/?page=documents&action=edit&id=' . $docId,
                        'error', 'ไฟล์ที่ ' . $i . ': ' . $result['error']);
                }
                if (!empty($doc['file_doc' . $i])) {
                    $oldFiles[] = $doc['file_doc' . $i];
                }
                $fn = $this->db->escape($result['filename']);
                $nn = $this->db->escape($result['original_name']);
                $updates[] = "file_doc$i='$fn', file_doc{$i}_name='$nn'";
            }
        }

        $updateStr = !empty($updates) ? implode(', ', $updates) . ', ' : '';
        $ok = $this->db->query(
            "UPDATE documents SET {$updateStr}status='pending',
                    revision_note=NULL, revision_by=NULL, revision_role=NULL,
                    receive_number=NULL, inspector_note=NULL, approver_note=NULL, operator_note=NULL,
                    inspected_by=NULL, inspected_at=NULL, approved_by=NULL, approved_at=NULL,
                    operated_by=NULL, operated_at=NULL, updated_at='$now'
             WHERE id=$docId AND status='revision'"
        );
        if (!$ok) {
            redirectWithFlash(APP_URL . '/?page=documents&action=edit&id=' . $docId, 'error', 'บันทึกไม่สำเร็จ กรุณาลองใหม่');
        }
        // ลบไฟล์เก่าหลังบันทึกฐานข้อมูลสำเร็จเท่านั้น
        foreach ($oldFiles as $f) { FileUpload::deleteFile($f); }

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

        if (!$this->canViewDoc(Auth::currentUser(), $doc)) {
            exit('คุณไม่มีสิทธิ์เข้าถึงไฟล์นี้');
        }

        FileUpload::serve($doc['file_doc' . $fileNum], $doc['ticket_code'] . '_' . docFileLabel($fileNum) . '.pdf');
    }

    // =====================================================
    // AJAX Actions
    // =====================================================

    public function ajax_list() {
        Auth::requireLogin();
        requireAjax();

        $user    = Auth::currentUser();
        $filters = $this->standardListFilters();

        extract($this->listData($user, $filters));

        include 'views/documents/_list_partial.php';
        exit;
    }

    public function ajax_cooperatives() {
        Auth::requireLogin();
        requireAjax();
        $typeName   = $this->db->escape($this->getQueryStr('type_name'));
        $officeName = $this->db->escape($_SESSION['office_name']);

        if (!$typeName) {
            jsonResponse(array());
        }

        $currentUser = Auth::currentUser();
        if (isHQ($currentUser)) {
            $rows = $this->db->fetchAll(
                "SELECT id, name, code FROM cooperatives WHERE type_name='$typeName' AND status='active' ORDER BY name"
            );
        } else {
            $rows = $this->db->fetchAll(
                "SELECT id, name, code FROM cooperatives
                 WHERE type_name='$typeName' AND office_name='$officeName' AND status='active' ORDER BY name"
            );
        }
        // ฝั่งหน้าเว็บนำค่านี้ไปต่อสตริง HTML (<option>) จึง escape ที่นี่เพื่อกัน XSS
        foreach ($rows as $i => $r) {
            $rows[$i]['name'] = e($r['name']);
            $rows[$i]['code'] = e($r['code']);
        }
        jsonResponse($rows);
    }

    public function ajax_status() {
        Auth::requireLogin();
        requireAjax();
        $id  = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $doc = $this->docModel->getById($id);
        if (!$doc || !$this->canViewDoc(Auth::currentUser(), $doc)) {
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
        requireAjax();
        jsonResponse(array('count' => Notification::countUnread($this->db, $_SESSION['user_id'])));
    }

    public function ajax_mark_read() {
        Auth::requireLogin();
        requireAjax();
        Auth::checkCsrf();
        $notifId = isset($_POST['id']) ? intval($_POST['id']) : 0;
        Notification::markRead($this->db, $notifId, $_SESSION['user_id']);
        jsonResponse(array('success' => true));
    }
}
