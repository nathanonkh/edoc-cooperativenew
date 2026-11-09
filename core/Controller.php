<?php
class Controller {

    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // layout -> view -> layout เดิมเขียนซ้ำทุก action ส่ง get_defined_vars() ของ method ผู้เรียก
    // เพื่อให้ view เห็นตัวแปรชุดเดียวกับเดิม (ใช้ชื่อ $__ ภายใน กัน extract ทับ)
    protected function render($__view, $__vars = array()) {
        // get_defined_vars() ของ PHP 5.x อาจมีคีย์ 'this' ปนมา ตัดทิ้งกันไม่ให้ extract() ยุ่งกับ $this
        unset($__vars['this'], $__vars['__view'], $__vars['__vars']);
        extract($__vars, EXTR_SKIP);
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/' . $__view . '.php';
        include 'views/layout/footer.php';
    }

    // อ่านค่าสตริงจาก $_GET (ไม่มี/ไม่ใช่สตริง เช่น ?keyword[]=x = '')
    protected function getQueryStr($key) {
        return (isset($_GET[$key]) && is_string($_GET[$key])) ? $_GET[$key] : '';
    }

    // ตัวกรองรายการแบบมาตรฐานของ Document/Issue (InternalService ต่างจึงมีของตนเอง)
    protected function standardListFilters() {
        return array(
            'status'    => $this->getQueryStr('status'),
            'keyword'   => $this->getQueryStr('keyword'),
            'date_from' => $this->getQueryStr('date_from'),
            'date_to'   => $this->getQueryStr('date_to'),
            'per_page'  => 10,
            'page_num'  => isset($_GET['p'])         ? max(1, intval($_GET['p'])) : 1,
        );
    }

    // อ่านตัวกรองหน้ารายงานจาก $_GET ตามรายการ key (ไม่ใช่ string = '')
    protected function readReportFilters($keys) {
        $f = array();
        foreach ($keys as $k) {
            $f[$k] = (isset($_GET[$k]) && is_string($_GET[$k])) ? $_GET[$k] : '';
        }
        return $f;
    }

    // ตัวแปรที่ view/partial รายงานใช้ (ผู้เรียกใช้ extract()) $model ต้องมี getDetailReport/getSummaryByStatus
    protected function loadReportData($model, $user, $filters) {
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

    // ตัวกรองสำนักงานของหน้าจัดการ (สมาชิก/สหกรณ์): ส่วนกลางเลือกได้ตามที่ขอ, admin จังหวัดถูกบังคับเป็นสำนักงานตนเอง
    protected function scopedOffice($requested) {
        $u = Auth::currentUser();
        return isHQ($u) ? $requested : $u['office_name'];
    }

    // ปฏิเสธเมื่อรายการอยู่นอกสำนักงานที่ admin คนนี้จัดการได้ (ใช้กฎเดิม adminCanAccessOffice)
    protected function requireOfficeAccess($officeName, $backUrl) {
        if (!adminCanAccessOffice(Auth::currentUser(), $officeName)) {
            redirectWithFlash($backUrl, 'error', 'คุณไม่มีสิทธิ์จัดการข้อมูลของสำนักงานนี้');
        }
    }

    // ตำแหน่งผู้ลงนามในรายงาน Excel
    protected function signerTitle($userId) {
        $row = getUserPosition($this->db, $userId);
        return $row ? $row['position'] : '';
    }

    // =====================================================
    // ส่งกลับไปแก้ไข / แก้ไขและส่งใหม่ (ใช้ร่วม documents, issues, internal_services)
    // กฎสิทธิ์อยู่ที่ returnForEditRules() ใน helpers/functions.php ($table และ $module มาจากโค้ด ไม่ใช่ input)
    // =====================================================

    protected function returnFail($module, $message) {
        if (isAjax()) {
            jsonResponse(array('success' => false, 'message' => $message));
        }
        redirectWithFlash(APP_URL . '/?page=' . $module, 'error', $message);
    }

    // ตรวจ CSRF/เหตุผล/สิทธิ์ แล้วคืน array(row, actor, note, user) ถ้าไม่ผ่านจะตอบ error และจบการทำงาน
    protected function authorizeReturnForEdit($module, $table, $idKey) {
        Auth::requireLogin();
        Auth::checkCsrf();

        $id   = (isset($_POST[$idKey]) && is_string($_POST[$idKey])) ? intval($_POST[$idKey]) : 0;
        $note = (isset($_POST['revision_note']) && is_string($_POST['revision_note'])) ? trim($_POST['revision_note']) : '';
        if ($note === '') {
            $this->returnFail($module, 'กรุณากรอกเหตุผลที่ส่งกลับ');
        }
        $row = $this->db->fetchOne("SELECT * FROM $table WHERE id=$id");
        if (!$row) {
            $this->returnFail($module, 'ไม่พบรายการ');
        }
        $user  = Auth::currentUser();
        $actor = matchReturnRule($module, $user, $row);
        if ($actor === '') {
            $this->returnFail($module, 'ไม่สามารถส่งกลับแก้ไขรายการนี้ได้ (สถานะหรือสิทธิ์ไม่ตรงตามเงื่อนไข)');
        }
        return array('row' => $row, 'actor' => $actor, 'note' => $note, 'user' => $user);
    }

    // เปลี่ยนเป็นสถานะ revision เฉพาะเมื่อสถานะยังเท่าเดิม (กันกดซ้ำ/แข่งกัน) $extraSet ขึ้นต้นด้วย ", " ถ้ามี
    protected function commitReturnForEdit($module, $table, $ctx, $extraSet = '') {
        $id   = intval($ctx['row']['id']);
        $uid  = intval($ctx['user']['id']);
        $from = $this->db->escape($ctx['row']['status']);
        $note = $this->db->escape($ctx['note']);
        $now  = date('Y-m-d H:i:s');
        $ok = $this->db->query(
            "UPDATE $table
             SET status='revision', revision_note='$note', revision_by=$uid, updated_at='$now'$extraSet
             WHERE id=$id AND status='$from'"
        );
        if (!$ok || $this->db->affectedRows() < 1) {
            $this->returnFail($module, 'ส่งกลับแก้ไขไม่สำเร็จ กรุณาลองใหม่');
        }
    }

    protected function returnDone($module) {
        if (isAjax()) {
            jsonResponse(array('success' => true, 'message' => 'ส่งกลับแก้ไขสำเร็จ'));
        }
        redirectWithFlash(APP_URL . '/?page=' . $module, 'success', 'ส่งกลับแก้ไขสำเร็จ');
    }

    // แก้ไข title/detail/ไฟล์แนบ ของ issues / internal_services ที่ถูกส่งกลับ แล้วเริ่มขั้นตอนใหม่ที่ pending
    // คืน row เดิม (ก่อนแก้) เมื่อสำเร็จ ถ้าไม่ผ่านจะ redirect และจบการทำงาน
    protected function resubmitSimple($module, $table, $idKey, $uploadDir, $logModel) {
        Auth::requireLogin();
        Auth::checkCsrf();

        $id   = (isset($_POST[$idKey]) && is_string($_POST[$idKey])) ? intval($_POST[$idKey]) : 0;
        $row  = $this->db->fetchOne("SELECT * FROM $table WHERE id=$id");
        $user = Auth::currentUser();
        if (!$row || !canResubmitAfterReturn($module, $user, $row)) {
            redirectWithFlash(APP_URL . '/?page=' . $module, 'error', 'ไม่สามารถแก้ไขรายการนี้ได้');
        }
        $back      = APP_URL . '/?page=' . $module . '&action=edit&id=' . $id;
        $titleRaw  = (isset($_POST['title'])  && is_string($_POST['title']))  ? trim($_POST['title'])  : '';
        $detailRaw = (isset($_POST['detail']) && is_string($_POST['detail'])) ? trim($_POST['detail']) : '';
        if ($titleRaw === '' || $detailRaw === '') {
            redirectWithFlash($back, 'error', 'กรุณากรอกชื่อเรื่องและรายละเอียด');
        }
        if (strlen($titleRaw) > 255) {
            redirectWithFlash($back, 'error', 'ชื่อเรื่องยาวเกินไป (ไม่เกิน 255 ตัวอักษร)');
        }

        $attachSet = '';
        $newFile   = '';
        if (isset($_FILES['attachment'])) {
            $result = FileUpload::uploadAttachment($_FILES['attachment'], $uploadDir, $row['ticket_code']);
            if (!$result['success']) {
                redirectWithFlash($back, 'error', 'ไฟล์แนบ: ' . $result['error']);
            }
            if ($result['filename'] !== '') {
                $newFile   = $result['filename'];
                $attachSet = ", attachment='" . $this->db->escape($result['filename'])
                           . "', attachment_name='" . $this->db->escape($result['original_name']) . "'";
            }
        }

        $now = date('Y-m-d H:i:s');
        // เริ่มสเต็ปใหม่: กลับ pending ล้างเหตุผลส่งกลับ และ (เฉพาะแจ้งปัญหา) ล้างสถานะส่งส่วนกลาง
        $centralSet = ($table === 'issues') ? ', handled_by_central=0' : '';
        $ok = $this->db->query(
            "UPDATE $table SET title='" . $this->db->escape($titleRaw) . "', detail='" . $this->db->escape($detailRaw) . "'$attachSet,
                    status='pending', revision_note=NULL, revision_by=NULL$centralSet, updated_at='$now'
             WHERE id=$id AND status='revision'"
        );
        if (!$ok || $this->db->affectedRows() < 1) {
            if ($newFile !== '') FileUpload::deleteFile($newFile);
            redirectWithFlash($back, 'error', 'บันทึกไม่สำเร็จ กรุณาลองใหม่');
        }
        // ลบไฟล์เก่าหลังบันทึกสำเร็จเท่านั้น
        if ($newFile !== '' && !empty($row['attachment'])) {
            FileUpload::deleteFile($row['attachment']);
        }
        $logModel->addLog($id, 'แก้ไขและส่งใหม่', intval($user['id']));
        return $row;
    }

    // หน้าฟอร์มแก้ไข (partials/resubmit_form) ของรายการที่ถูกส่งกลับ ผู้เรียกส่งชื่อโมดูลและตาราง
    protected function renderResubmitForm($module, $table, $idKey, $pageLabel) {
        Auth::requireLogin();
        $id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $row  = $this->db->fetchOne("SELECT * FROM $table WHERE id=$id");
        $user = Auth::currentUser();
        if (!$row || !canResubmitAfterReturn($module, $user, $row)) {
            redirectWithFlash(APP_URL . '/?page=' . $module, 'error', 'ไม่สามารถแก้ไขรายการนี้ได้');
        }
        $pageTitle = 'แก้ไข ' . $row['ticket_code'];
        $this->render('partials/resubmit_form', get_defined_vars());
    }
}
