<?php
class UserController extends Controller {

    private $userModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->userModel = new UserModel($db);
    }

    public function index() {
        Auth::requireRole('admin');
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $offset         = ($currentPageNum - 1) * $perPage;
        $keyword        = isset($_GET['keyword'])       ? trim($_GET['keyword'])      : '';
        $filterRole     = isset($_GET['filter_role'])   ? trim($_GET['filter_role'])  : '';
        $filterEmpType  = isset($_GET['filter_emp'])    ? trim($_GET['filter_emp'])   : '';
        $filterStatus   = isset($_GET['filter_status']) ? $_GET['filter_status']      : '';
        $filterOffice   = isset($_GET['filter_office']) ? trim($_GET['filter_office']): '';
        $allUsers       = $this->userModel->getAll($filterOffice ? $filterOffice : null, $keyword, $filterRole, $filterEmpType, $filterStatus);
        $totalItems     = count($allUsers);
        $totalPages     = max(1, ceil($totalItems / $perPage));
        $users          = array_slice($allUsers, $offset, $perPage);
        $pageTitle      = 'จัดการสมาชิก';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/users/index.php';
        include 'views/layout/footer.php';
    }

    public function create() {
        Auth::requireRole('admin');
        $pageTitle = 'เพิ่มสมาชิก';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/users/create.php';
        include 'views/layout/footer.php';
    }

    public function ajax_list() {
        Auth::requireRole('admin');
        requireAjax();
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $offset         = ($currentPageNum - 1) * $perPage;
        $keyword        = isset($_GET['keyword'])       ? trim($_GET['keyword'])      : '';
        $filterRole     = isset($_GET['filter_role'])   ? trim($_GET['filter_role'])  : '';
        $filterEmpType  = isset($_GET['filter_emp'])    ? trim($_GET['filter_emp'])   : '';
        $filterStatus   = isset($_GET['filter_status']) ? $_GET['filter_status']      : '';
        $filterOffice   = isset($_GET['filter_office']) ? trim($_GET['filter_office']): '';
        $empTypeOptions = getEmployeeTypeOptions();
        $allUsers       = $this->userModel->getAll($filterOffice ? $filterOffice : null, $keyword, $filterRole, $filterEmpType, $filterStatus);
        $totalItems     = count($allUsers);
        $totalPages     = max(1, ceil($totalItems / $perPage));
        $users          = array_slice($allUsers, $offset, $perPage);

        include 'views/users/_list_partial.php';
        exit;
    }

    // whitelist ค่า select ที่รับจากฟอร์ม (ซ่อนใน UI อย่างเดียวไม่พอ ต้องตรวจฝั่งเซิร์ฟเวอร์)
    private function requireValidUserFields($backUrl) {
        $office = isset($_POST['office_name'])   ? $_POST['office_name']   : '';
        $emp    = isset($_POST['employee_type']) ? $_POST['employee_type'] : '';
        $emps   = getEmployeeTypeOptions();
        if (!is_string($office) || !in_array($office, getOfficeOptions(), true) ||
            !is_string($emp)    || !isset($emps[$emp])) {
            redirectWithFlash($backUrl, 'error', 'ข้อมูลสำนักงานหรือประเภทบุคลากรไม่ถูกต้อง');
        }
    }

    public function store() {
        Auth::requireRole('admin');
        Auth::checkCsrf();

        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        if ($this->userModel->isUsernameTaken($username)) {
            redirectWithFlash(APP_URL . '/?page=users&action=create', 'error', 'ชื่อผู้ใช้นี้มีอยู่แล้ว');
        }

        $rolesArr = sanitizeRolesInput(isset($_POST['roles']) ? $_POST['roles'] : null);
        $_POST['roles'] = implode(',', $rolesArr);
        $this->requireValidUserFields(APP_URL . '/?page=users&action=create');

        // เดิมไม่เช็คผลลัพธ์การบันทึกเลย เผื่อกรณี race condition ที่ isUsernameTaken()
        // เช็คผ่านไปแล้วแต่ดันชนกับ UNIQUE KEY ตอน insert จริง (2 request พร้อมกัน)
        $newId = $this->userModel->create($_POST);
        if ($newId === false) {
            redirectWithFlash(APP_URL . '/?page=users&action=create', 'error',
                'บันทึกไม่สำเร็จ ชื่อผู้ใช้นี้อาจถูกใช้ไปแล้ว');
        }

        redirectWithFlash(APP_URL . '/?page=users', 'success', 'เพิ่มสมาชิกสำเร็จ');
    }

    public function edit() {
        Auth::requireRole('admin');
        $id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $user = $this->userModel->getById($id);
        if (!$user) {
            redirectWithFlash(APP_URL . '/?page=users', 'error', 'ไม่พบข้อมูลสมาชิก');
        }
        $pageTitle = 'แก้ไขสมาชิก';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/users/edit.php';
        include 'views/layout/footer.php';
    }

    public function update() {
        Auth::requireRole('admin');
        Auth::checkCsrf();

        $id       = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $rolesArr = sanitizeRolesInput(isset($_POST['roles']) ? $_POST['roles'] : null);
        $_POST['roles'] = implode(',', $rolesArr);
        $this->requireValidUserFields(APP_URL . '/?page=users&action=edit&id=' . $id);

        $ok = $this->userModel->update($id, $_POST);
        if (!$ok) {
            redirectWithFlash(APP_URL . '/?page=users&action=edit&id=' . $id, 'error', 'บันทึกไม่สำเร็จ');
        }

        redirectWithFlash(APP_URL . '/?page=users', 'success', 'อัปเดตข้อมูลสมาชิกสำเร็จ');
    }

    public function delete() {
        Auth::requireRole('admin');
        // เดิมไม่มีการตรวจ CSRF ใน action ที่ระงับผู้ใช้ซึ่งเรียกผ่าน GET เพิ่มไว้เพื่อกัน CSRF
        // (ฝั่ง frontend เปลี่ยนมาส่งเป็น POST พร้อม csrf_token แล้วผ่าน app.js: confirmDelete)
        Auth::checkCsrf();
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id == $_SESSION['user_id']) {
            redirectWithFlash(APP_URL . '/?page=users', 'error', 'ไม่สามารถลบบัญชีตัวเองได้');
        }
        $this->userModel->delete($id);
        redirectWithFlash(APP_URL . '/?page=users', 'success', 'ระงับการใช้งานสมาชิกแล้ว');
    }

    public function profile() {
        Auth::requireLogin();
        $user      = $this->userModel->getById($_SESSION['user_id']);
        $pageTitle = 'แก้ไขโปรไฟล์';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/users/profile.php';
        include 'views/layout/footer.php';
    }

    public function update_profile() {
        Auth::requireLogin();
        Auth::checkCsrf();

        $id   = intval($_SESSION['user_id']);
        $user = $this->userModel->getById($id);

        $currentPw = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        // ใช้ PasswordHash::verify() แทนการเทียบ sha1() ตรง ๆ
        // เพื่อรองรับทั้ง hash รูปแบบใหม่ (มี salt) และรูปแบบเก่าที่ยังไม่ได้อัปเกรด
        if (!PasswordHash::verify($currentPw, $user['password'])) {
            redirectWithFlash(APP_URL . '/?page=users&action=profile', 'error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
        }

        $newPw  = isset($_POST['new_password'])     ? $_POST['new_password']     : '';
        $confPw = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        if (empty($newPw)) {
            redirectWithFlash(APP_URL . '/?page=users&action=profile', 'error', 'กรุณากรอกรหัสผ่านใหม่');
        }
        if (strlen($newPw) < 6) {
            redirectWithFlash(APP_URL . '/?page=users&action=profile', 'error', 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร');
        }
        if ($newPw !== $confPw) {
            redirectWithFlash(APP_URL . '/?page=users&action=profile', 'error', 'รหัสผ่านใหม่ไม่ตรงกัน');
        }

        $this->userModel->updateProfile($id, $_POST);
        redirectWithFlash(APP_URL . '/?page=users&action=profile', 'success', 'เปลี่ยนรหัสผ่านสำเร็จ');
    }
}