<?php
class CooperativeController extends Controller {

    private $coopModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->coopModel = new CooperativeModel($db);
    }

    public function index() {
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $offset         = ($currentPageNum - 1) * $perPage;
        $keyword        = isset($_GET['keyword'])       ? trim($_GET['keyword'])       : '';
        $filterType     = isset($_GET['filter_type'])   ? trim($_GET['filter_type'])   : '';
        $filterOffice   = isset($_GET['filter_office']) ? trim($_GET['filter_office']) : '';
        $filterStatus   = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
        $allCoops       = $this->coopModel->getAll($filterOffice ? $filterOffice : null, $filterType ? $filterType : null, $keyword, $filterStatus);
        $totalItems     = count($allCoops);
        $totalPages     = max(1, ceil($totalItems / $perPage));
        $cooperatives   = array_slice($allCoops, $offset, $perPage);
        $pageTitle      = 'จัดการสหกรณ์';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/cooperatives/index.php';
        include 'views/layout/footer.php';
    }

    public function create() {
        $pageTitle = 'เพิ่มสหกรณ์';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/cooperatives/create.php';
        include 'views/layout/footer.php';
    }

    public function ajax_list() {
        Auth::requireRole(array('admin'));
        requireAjax();
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $offset         = ($currentPageNum - 1) * $perPage;
        $keyword        = isset($_GET['keyword'])       ? trim($_GET['keyword'])       : '';
        $filterType     = isset($_GET['filter_type'])   ? trim($_GET['filter_type'])   : '';
        $filterOffice   = isset($_GET['filter_office']) ? trim($_GET['filter_office']) : '';
        $filterStatus   = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
        $allCoops       = $this->coopModel->getAll($filterOffice ? $filterOffice : null, $filterType ? $filterType : null, $keyword, $filterStatus);
        $totalItems     = count($allCoops);
        $totalPages     = max(1, ceil($totalItems / $perPage));
        $cooperatives   = array_slice($allCoops, $offset, $perPage);

        include 'views/cooperatives/_list_partial.php';
        exit;
    }

    public function store() {
        Auth::checkCsrf();

        // เดิมส่ง $_POST เข้า Model ตรง ๆ โดยไม่เช็คก่อนเลย ถ้ามีคนยิง request ข้าม form
        // มาจะเจอ Undefined index และอาจสร้าง record ข้อมูลว่างเปล่าได้
        if (empty($_POST['code']) || empty($_POST['name']) ||
            empty($_POST['type_name']) || empty($_POST['office_name'])) {
            redirectWithFlash(APP_URL . '/?page=cooperatives&action=create', 'error',
                'กรุณากรอกข้อมูลให้ครบ (รหัสสหกรณ์, ชื่อ, ประเภท, สำนักงาน)');
        }

        $stOpts = getCooperativeStatusOptions();
        if (isset($_POST['status']) && (!is_string($_POST['status']) || !isset($stOpts[$_POST['status']]))) {
            redirectWithFlash(APP_URL . '/?page=cooperatives&action=create', 'error', 'สถานะสหกรณ์ไม่ถูกต้อง');
        }

        // เดิมไม่เช็คผลลัพธ์การบันทึกเลย ถ้า insert ล้มเหลว (เช่น รหัสสหกรณ์ซ้ำกับ
        // UNIQUE KEY `code`) ผู้ใช้จะยังเห็นข้อความ "เพิ่มข้อมูลสำเร็จ" ทั้งที่ข้อมูล
        // ไม่ถูกบันทึกจริง จึงเพิ่มการเช็คค่าที่ create() คืนกลับมา
        $newId = $this->coopModel->create($_POST);
        if ($newId === false) {
            redirectWithFlash(APP_URL . '/?page=cooperatives&action=create', 'error',
                'บันทึกไม่สำเร็จ อาจเป็นเพราะรหัสสหกรณ์นี้มีอยู่แล้วในระบบ');
        }

        redirectWithFlash(APP_URL . '/?page=cooperatives', 'success', 'เพิ่มข้อมูลสหกรณ์สำเร็จ');
    }

    public function edit() {
        $id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $coop = $this->coopModel->getById($id);
        if (!$coop) {
            redirectWithFlash(APP_URL . '/?page=cooperatives', 'error', 'ไม่พบข้อมูลสหกรณ์');
        }
        $pageTitle = 'แก้ไขสหกรณ์';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/cooperatives/edit.php';
        include 'views/layout/footer.php';
    }

    public function update() {
        Auth::checkCsrf();
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (empty($_POST['code']) || empty($_POST['name']) ||
            empty($_POST['type_name']) || empty($_POST['office_name'])) {
            redirectWithFlash(APP_URL . '/?page=cooperatives&action=edit&id=' . $id, 'error',
                'กรุณากรอกข้อมูลให้ครบ (รหัสสหกรณ์, ชื่อ, ประเภท, สำนักงาน)');
        }

        $stOpts = getCooperativeStatusOptions();
        if (isset($_POST['status']) && (!is_string($_POST['status']) || !isset($stOpts[$_POST['status']]))) {
            redirectWithFlash(APP_URL . '/?page=cooperatives&action=edit&id=' . $id, 'error', 'สถานะสหกรณ์ไม่ถูกต้อง');
        }

        $ok = $this->coopModel->update($id, $_POST);
        if (!$ok) {
            redirectWithFlash(APP_URL . '/?page=cooperatives&action=edit&id=' . $id, 'error',
                'บันทึกไม่สำเร็จ อาจเป็นเพราะรหัสสหกรณ์นี้ซ้ำกับรายการอื่น');
        }

        redirectWithFlash(APP_URL . '/?page=cooperatives', 'success', 'อัปเดตข้อมูลสหกรณ์สำเร็จ');
    }

    public function delete() {
        // เดิม action นี้ถูกเรียกผ่านลิงก์ GET ตรง ๆ โดยไม่มีการตรวจ CSRF token เลย
        // ทำให้ถูกโจมตีแบบ CSRF ได้ง่าย (เช่น ฝัง <img src="...&action=delete&id=1">)
        // ตอนนี้ฝั่ง frontend (app.js: confirmDelete) เปลี่ยนมาส่งเป็น POST พร้อม csrf_token แล้ว
        Auth::checkCsrf();
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        // MyISAM ไม่มี FK: ลบทั้งที่ยังมีเอกสาร/เรื่องแจ้งปัญหาอ้างอิงจะทำให้ข้อมูลกำพร้า จึงต้องตรวจก่อน
        $refDocs   = $this->db->fetchOne("SELECT COUNT(*) AS n FROM documents WHERE cooperative_id=$id");
        $refIssues = $this->db->fetchOne("SELECT COUNT(*) AS n FROM issues WHERE cooperative_id=$id");
        if (!$refDocs || !$refIssues) {
            redirectWithFlash(APP_URL . '/?page=cooperatives', 'error', 'ตรวจสอบการอ้างอิงไม่สำเร็จ ยังไม่ได้ลบ');
        }
        if (intval($refDocs['n']) > 0 || intval($refIssues['n']) > 0) {
            redirectWithFlash(APP_URL . '/?page=cooperatives', 'error',
                'ไม่สามารถลบได้ เนื่องจากมีเอกสารหรือเรื่องแจ้งปัญหาอ้างอิงสหกรณ์นี้อยู่');
        }
        $this->coopModel->delete($id);
        redirectWithFlash(APP_URL . '/?page=cooperatives', 'success', 'ลบข้อมูลสหกรณ์แล้ว');
    }
}