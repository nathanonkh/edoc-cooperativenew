<?php
class CooperativeController extends Controller {

    private $coopModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->coopModel = new CooperativeModel($db);
    }

    // ตัวแปรที่ view รายการ/partial ใช้ (ใช้ extract() ที่ผู้เรียก) เดิมเขียนซ้ำใน index() และ ajax_list()
    private function listData() {
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $offset         = ($currentPageNum - 1) * $perPage;
        $keyword        = trim($this->getQueryStr('keyword'));
        $filterType     = trim($this->getQueryStr('filter_type'));
        $filterOffice   = trim($this->getQueryStr('filter_office'));
        $filterStatus   = trim($this->getQueryStr('filter_status'));
        $filterOffice   = $this->scopedOffice($filterOffice);
        $allCoops       = $this->coopModel->getAll($filterOffice ? $filterOffice : null, $filterType ? $filterType : null, $keyword, $filterStatus);
        $totalItems     = count($allCoops);
        $totalPages     = max(1, ceil($totalItems / $perPage));
        $cooperatives   = array_slice($allCoops, $offset, $perPage);
        return array(
            'perPage' => $perPage, 'currentPageNum' => $currentPageNum, 'offset' => $offset,
            'keyword' => $keyword, 'filterType' => $filterType, 'filterOffice' => $filterOffice,
            'filterStatus' => $filterStatus, 'allCoops' => $allCoops, 'totalItems' => $totalItems,
            'totalPages' => $totalPages, 'cooperatives' => $cooperatives,
        );
    }

    public function index() {
        extract($this->listData());
        $pageTitle      = 'จัดการสหกรณ์';
        $this->render('cooperatives/index', get_defined_vars());
    }

    public function create() {
        $pageTitle = 'เพิ่มสหกรณ์';
        $this->render('cooperatives/create', get_defined_vars());
    }

    public function ajax_list() {
        Auth::requireRole(array('admin'));
        requireAjax();
        extract($this->listData());

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
        $this->requireOfficeAccess($_POST['office_name'], APP_URL . '/?page=cooperatives&action=create');

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
        $this->requireOfficeAccess($coop['office_name'], APP_URL . '/?page=cooperatives');
        $pageTitle = 'แก้ไขสหกรณ์';
        $this->render('cooperatives/edit', get_defined_vars());
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

        $existing = $this->coopModel->getById($id);
        if (!$existing) {
            redirectWithFlash(APP_URL . '/?page=cooperatives', 'error', 'ไม่พบข้อมูลสหกรณ์');
        }
        $this->requireOfficeAccess($existing['office_name'], APP_URL . '/?page=cooperatives');
        $this->requireOfficeAccess($_POST['office_name'], APP_URL . '/?page=cooperatives&action=edit&id=' . $id);

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
        $target = $this->coopModel->getById($id);
        if (!$target) {
            redirectWithFlash(APP_URL . '/?page=cooperatives', 'error', 'ไม่พบข้อมูลสหกรณ์');
        }
        $this->requireOfficeAccess($target['office_name'], APP_URL . '/?page=cooperatives');
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

    // ดูรายละเอียดสหกรณ์ (อ่านอย่างเดียว เฉพาะ admin)
    public function detail() {
        Auth::requireRole(array('admin'));
        $id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $coop = $this->coopModel->getById($id);
        if (!$coop) {
            redirectWithFlash(APP_URL . '/?page=cooperatives', 'error', 'ไม่พบข้อมูลสหกรณ์');
        }
        $this->requireOfficeAccess($coop['office_name'], APP_URL . '/?page=cooperatives');
        $pageTitle = 'รายละเอียดสหกรณ์';
        $this->render('cooperatives/detail', get_defined_vars());
    }

    // ส่งออกข้อมูลสหกรณ์เป็น Excel ตามตัวกรองปัจจุบัน (admin เท่านั้น ผ่าน Router)
    public function export_excel() {
        Auth::requireRole(array('admin'));

        $keyword      = trim($this->getQueryStr('keyword'));
        $filterType   = trim($this->getQueryStr('filter_type'));
        $filterOffice = $this->scopedOffice(trim($this->getQueryStr('filter_office')));
        $filterStatus = trim($this->getQueryStr('filter_status'));
        $allCoops     = $this->coopModel->getAll($filterOffice ? $filterOffice : null, $filterType ? $filterType : null, $keyword, $filterStatus);

        $stOpts  = getCooperativeStatusOptions();
        $headers = array('#', 'รหัสสหกรณ์', 'เลขทะเบียน', 'รหัส 13 หลัก', 'ชื่อสหกรณ์', 'ประเภท', 'วันที่จดทะเบียน',
                         'ที่ตั้ง', 'ตำบล', 'อำเภอ', 'จังหวัด', 'สำนักงานตรวจบัญชีสหกรณ์', 'วันสิ้นปีบัญชี', 'สถานะ');
        $rows = array();
        $no   = 0;
        foreach ($allCoops as $c) {
            $no++;
            $rows[] = array(
                $no,
                $c['code'],
                $c['registration_no'],
                $c['regis_13digit'],
                $c['name'],
                $c['type_name'],
                $c['register_date'],
                $c['address'],
                $c['subdistrict'],
                $c['district'],
                $c['province'],
                $c['office_name'],
                $c['fiscal_year'],
                isset($stOpts[$c['status']]) ? $stOpts[$c['status']] : $c['status'],
            );
        }

        $subtitleParts = array();
        if ($keyword !== '')      $subtitleParts[] = 'คำค้น ' . $keyword;
        if ($filterType !== '')   $subtitleParts[] = 'ประเภท ' . $filterType;
        if ($filterOffice !== '') $subtitleParts[] = $filterOffice;
        if ($filterStatus !== '') $subtitleParts[] = 'สถานะ ' . (isset($stOpts[$filterStatus]) ? $stOpts[$filterStatus] : $filterStatus);

        $me          = Auth::currentUser();
        $signerTitle = $this->signerTitle($me['id']);

        ExcelExport::export(
            'รายชื่อสหกรณ์',
            $headers,
            $rows,
            $me['fullname'],
            $signerTitle,
            'cooperatives_' . date('Ymd'),
            implode(' | ', $subtitleParts)
        );
    }
}
