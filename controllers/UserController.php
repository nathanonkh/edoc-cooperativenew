<?php
class UserController extends Controller {

    const MAX_PHOTO_SIZE = 2097152; // 2 MB (ตรวจเฉพาะรูปโปรไฟล์ ไม่แก้ MAX_FILE_SIZE ที่ใช้ร่วมกัน)

    private $userModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->userModel = new UserModel($db);
    }

    // ตัวแปรที่ view รายการ/partial ใช้ (ใช้ extract() ที่ผู้เรียก) เดิมเขียนซ้ำใน index() และ ajax_list()
    private function listData() {
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $offset         = ($currentPageNum - 1) * $perPage;
        $keyword        = trim($this->getQueryStr('keyword'));
        $filterRole     = trim($this->getQueryStr('filter_role'));
        $filterEmpType  = trim($this->getQueryStr('filter_emp'));
        $filterStatus   = $this->getQueryStr('filter_status');
        $filterOffice   = trim($this->getQueryStr('filter_office'));
        $filterOffice   = $this->scopedOffice($filterOffice);
        $allUsers       = $this->userModel->getAll($filterOffice ? $filterOffice : null, $keyword, $filterRole, $filterEmpType, $filterStatus);
        $totalItems     = count($allUsers);
        $totalPages     = max(1, ceil($totalItems / $perPage));
        $users          = array_slice($allUsers, $offset, $perPage);
        return array(
            'perPage' => $perPage, 'currentPageNum' => $currentPageNum, 'offset' => $offset,
            'keyword' => $keyword, 'filterRole' => $filterRole, 'filterEmpType' => $filterEmpType,
            'filterStatus' => $filterStatus, 'filterOffice' => $filterOffice,
            'allUsers' => $allUsers, 'totalItems' => $totalItems, 'totalPages' => $totalPages, 'users' => $users,
        );
    }

    public function index() {
        Auth::requireRole('admin');
        extract($this->listData());
        $pageTitle      = 'จัดการสมาชิก';
        $this->render('users/index', get_defined_vars());
    }

    public function create() {
        Auth::requireRole('admin');
        $pageTitle = 'เพิ่มสมาชิก';
        $this->render('users/create', get_defined_vars());
    }

    public function ajax_list() {
        Auth::requireRole('admin');
        requireAjax();
        extract($this->listData());
        $empTypeOptions = getEmployeeTypeOptions();

        include 'views/users/_list_partial.php';
        exit;
    }

    // whitelist ค่า select ที่รับจากฟอร์ม (ซ่อนใน UI อย่างเดียวไม่พอ ต้องตรวจฝั่งเซิร์ฟเวอร์)
    private function requireValidUserFields($backUrl) {
        $office = isset($_POST['office_name'])   ? $_POST['office_name']   : '';
        $emp    = isset($_POST['employee_type']) ? $_POST['employee_type'] : '';
        $emps   = getEmployeeTypeOptions();
        if (!is_string($office) || !in_array($office, manageableOfficeOptions(Auth::currentUser()), true) ||
            !is_string($emp)    || !isset($emps[$emp])) {
            redirectWithFlash($backUrl, 'error', 'ข้อมูลสำนักงานหรือประเภทบุคลากรไม่ถูกต้อง');
        }
    }

    // บังคับบทบาทฝั่งเซิร์ฟเวอร์ตาม assignableRolesFor() (เดิมบังคับเฉพาะฝั่ง JS)
    // admin จังหวัดตั้งบทบาทไม่ได้: สมาชิกใหม่ = submitter, สมาชิกเดิม = คงบทบาทเดิม
    private function enforceRolesByOffice($old = null) {
        $allowed = assignableRolesFor(Auth::currentUser(), $_POST['office_name']);
        if (empty($allowed)) {
            $_POST['roles'] = ($old && $old['roles'] !== '') ? $old['roles'] : 'submitter';
            return;
        }
        $kept = array_values(array_intersect(explode(',', $_POST['roles']), $allowed));
        $_POST['roles'] = empty($kept) ? 'submitter' : implode(',', $kept);
    }

    // อ่านค่าสตริงจาก $_POST (ไม่ใช่สตริง/ไม่มี = '')
    private function postStr($key) {
        return (isset($_POST[$key]) && is_string($_POST[$key])) ? trim($_POST[$key]) : '';
    }

    // ตรวจ+ทำความสะอาดข้อมูลการรับราชการ/อบรม แล้วเขียนค่าที่ผ่านการตรวจกลับลง $_POST (ไม่บังคับกรอกทุกช่อง)
    private function requireValidProfileFields($backUrl) {
        $textFields = array('major', 'training_basic', 'training_intermediate', 'training_advanced');
        foreach ($textFields as $k) {
            $_POST[$k] = safeSubstr($this->postStr($k), 0, 255);
        }

        // วุฒิการศึกษา: เลือกได้เฉพาะค่าใน getEducationOptions() หรือเว้นว่าง
        $edu = $this->postStr('education');
        if ($edu !== '' && !in_array($edu, getEducationOptions(), true)) {
            redirectWithFlash($backUrl, 'error', 'วุฒิการศึกษาไม่ถูกต้อง');
        }
        $_POST['education'] = $edu;

        // วันที่ดำรงตำแหน่ง นว.ตบ. 3 ระดับ (ไม่บังคับ รูปแบบ วว/ดด/ปปปป พ.ศ.)
        $posFields = array(
            'pos_nvtb_pk_date'  => 'นว.ตบ.ปก',
            'pos_nvtb_chk_date' => 'นว.ตบ.ชก',
            'pos_nvtb_chp_date' => 'นว.ตบ.ชพ',
        );
        foreach ($posFields as $k => $label) {
            $v = $this->postStr($k);
            if ($v !== '' && !isValidThaiDate($v)) {
                redirectWithFlash($backUrl, 'error', 'วันที่ดำรงตำแหน่ง ' . $label . ' ไม่ถูกต้อง (รูปแบบ วว/ดด/ปปปป พ.ศ. เช่น 31/03/2560)');
            }
            $_POST[$k] = $v;
        }

        $date = $this->postStr('appointed_date');
        if ($date !== '' && !isValidThaiDate($date)) {
            redirectWithFlash($backUrl, 'error', 'วันที่บรรจุราชการไม่ถูกต้อง (รูปแบบ วว/ดด/ปปปป พ.ศ. เช่น 31/03/2560)');
        }
        $_POST['appointed_date'] = $date;

        // เลขที่ตำแหน่ง 7 หลัก: รับเลข 1-7 หลัก แล้วเติม 0 ข้างหน้าให้ครบ 7 หลัก (เก็บเป็นสตริง เช่น 539 -> 0000539)
        $no = $this->postStr('position_no');
        if ($no !== '' && !preg_match('/^[0-9]{1,7}$/', $no)) {
            redirectWithFlash($backUrl, 'error', 'เลขที่ตำแหน่งต้องเป็นตัวเลขไม่เกิน 7 หลัก');
        }
        if ($no !== '') {
            $no = str_pad($no, 7, '0', STR_PAD_LEFT);
        }
        $_POST['position_no'] = $no;
    }

    // อัปโหลดรูปโปรไฟล์ (ไม่บังคับ) คืน array(path, ชื่อไฟล์เดิม, ข้อความผิดพลาด) ไม่เลือกไฟล์ = ทั้งสามค่าเป็น ''
    private function handlePhotoUpload($fieldName) {
        if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]) ||
            !isset($_FILES[$fieldName]['error']) || is_array($_FILES[$fieldName]['error'])) {
            return array('', '', '');
        }
        $f = $_FILES[$fieldName];
        if ($f['error'] === UPLOAD_ERR_NO_FILE) {
            return array('', '', '');
        }
        if ($f['error'] === UPLOAD_ERR_OK && $f['size'] > self::MAX_PHOTO_SIZE) {
            return array('', '', 'รูปภาพต้องมีขนาดไม่เกิน 2 MB');
        }
        $token = 'usr_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
        $r = FileUpload::uploadImageOnly($f, 'user_photos', $token);
        if (!$r['success']) {
            return array('', '', $r['error']);
        }
        return array($r['filename'], $r['original_name'], '');
    }

    // ไฟล์แนบการอบรม 3 ระดับ (PDF เท่านั้น ไม่บังคับ) ชื่อฟิลด์ฟอร์ม / คอลัมน์ / ชื่อช่องที่ลบ
    private function trainingFileDefs() {
        return array(
            'basic'        => array('input' => 'file_training_basic',        'col' => 'training_basic_file',        'remove' => 'remove_training_basic_file',        'label' => 'ระดับต้น'),
            'intermediate' => array('input' => 'file_training_intermediate', 'col' => 'training_intermediate_file', 'remove' => 'remove_training_intermediate_file', 'label' => 'ระดับกลาง'),
            'advanced'     => array('input' => 'file_training_advanced',     'col' => 'training_advanced_file',     'remove' => 'remove_training_advanced_file',     'label' => 'ระดับสูง'),
        );
    }

    // อัปโหลดไฟล์อบรมทั้ง 3 ช่อง คืน array(array(level => array(path, name)), ข้อความผิดพลาด)
    // ถ้าช่องใดผิดพลาด จะลบไฟล์ที่อัปโหลดสำเร็จไปแล้วทิ้งก่อนคืนค่า
    private function handleTrainingUploads() {
        $done = array();
        foreach ($this->trainingFileDefs() as $level => $def) {
            $done[$level] = array('', '');
            $in = $def['input'];
            if (!isset($_FILES[$in]) || !is_array($_FILES[$in]) ||
                !isset($_FILES[$in]['error']) || is_array($_FILES[$in]['error']) ||
                $_FILES[$in]['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $token = 'trn_' . $level . '_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
            $r = FileUpload::uploadPdfOnly($_FILES[$in], 'user_trainings', $token);
            if (!$r['success']) {
                $this->discardTrainingUploads($done);
                return array($done, 'ไฟล์อบรม' . $def['label'] . ': ' . $r['error']);
            }
            $done[$level] = array($r['filename'], $r['original_name']);
        }
        return array($done, '');
    }

    private function discardTrainingUploads($uploads) {
        foreach ($uploads as $u) {
            if ($u[0] !== '') FileUpload::deleteFile($u[0]);
        }
    }

    // บันทึกไฟล์อบรมที่เปลี่ยน/ลบลงฐานข้อมูล แล้วลบไฟล์เก่าหลัง UPDATE สำเร็จเท่านั้น คืน false เมื่อ UPDATE ล้มเหลว (ลบไฟล์ใหม่ทิ้ง)
    private function applyTrainingFiles($id, $row, $uploads) {
        $changes = array();
        $oldDel  = array();
        foreach ($this->trainingFileDefs() as $level => $def) {
            $col = $def['col'];
            if ($uploads[$level][0] !== '') {
                $changes[$col]            = $uploads[$level][0];
                $changes[$col . '_name']  = $uploads[$level][1];
                if (!empty($row[$col])) $oldDel[] = $row[$col];
            } elseif (!empty($_POST[$def['remove']]) && !empty($row[$col])) {
                $changes[$col]            = '';
                $changes[$col . '_name']  = '';
                $oldDel[] = $row[$col];
            }
        }
        if (count($changes) == 0) {
            return true;
        }
        if (!$this->userModel->updateTrainingFiles($id, $changes)) {
            $this->discardTrainingUploads($uploads);
            return false;
        }
        foreach ($oldDel as $p) {
            FileUpload::deleteFile($p);
        }
        return true;
    }

    // บันทึกรูปใหม่/ลบรูป ลงฐานข้อมูล แล้วลบไฟล์เก่าหลัง UPDATE สำเร็จเท่านั้น คืน false เมื่อ UPDATE ล้มเหลว
    // $path = '' และ $remove = true  -> ลบรูป, $path = '' และ $remove = false -> ไม่ทำอะไร
    private function applyPhotoChange($id, $row, $path, $name, $remove) {
        if ($path === '' && !$remove) {
            return true;
        }
        if (!$this->userModel->updatePhoto($id, $path, $name)) {
            if ($path !== '') FileUpload::deleteFile($path);
            return false;
        }
        if (!empty($row['photo'])) {
            FileUpload::deleteFile($row['photo']);
        }
        return true;
    }

    public function store() {
        Auth::requireRole('admin');
        Auth::checkCsrf();
        $backCreate = APP_URL . '/?page=users&action=create';

        $username = $this->postStr('username');
        $password = (isset($_POST['password']) && is_string($_POST['password'])) ? $_POST['password'] : '';
        if ($username === '' || $password === '' || $this->postStr('firstname') === '' || $this->postStr('lastname') === '') {
            redirectWithFlash($backCreate, 'error', 'กรุณากรอกชื่อผู้ใช้ รหัสผ่าน ชื่อ และนามสกุล');
        }
        if (strlen($password) < 6) {
            redirectWithFlash($backCreate, 'error', 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
        }
        $_POST['username'] = $username; // ให้ตรงกับค่าที่ตรวจซ้ำด้านล่าง

        if ($this->userModel->isUsernameTaken($username)) {
            redirectWithFlash($backCreate, 'error', 'ชื่อผู้ใช้นี้มีอยู่แล้ว');
        }

        $rolesArr = sanitizeRolesInput(isset($_POST['roles']) ? $_POST['roles'] : null);
        $_POST['roles'] = implode(',', $rolesArr);
        $this->requireValidUserFields($backCreate);
        $this->enforceRolesByOffice();
        $this->requireValidProfileFields($backCreate);

        // รูปโปรไฟล์ (ไม่บังคับ) ตรวจ/อัปโหลดก่อนบันทึก ถ้า insert ล้มเหลวจะลบไฟล์ทิ้ง
        list($photoPath, $photoName, $photoErr) = $this->handlePhotoUpload('photo');
        if ($photoErr !== '') {
            redirectWithFlash($backCreate, 'error', 'รูปภาพ: ' . $photoErr);
        }
        // เขียนทับเสมอ กันไคลเอนต์ส่งค่า photo มาเองโดยไม่ผ่านการอัปโหลด
        $_POST['photo']      = $photoPath;
        $_POST['photo_name'] = $photoName;

        // ไฟล์แนบการอบรม 3 ระดับ (PDF ไม่บังคับ) เขียนทับค่าเสมอเช่นเดียวกับรูปภาพ
        list($trainUploads, $trainErr) = $this->handleTrainingUploads();
        if ($trainErr !== '') {
            if ($photoPath !== '') FileUpload::deleteFile($photoPath);
            redirectWithFlash($backCreate, 'error', $trainErr);
        }
        foreach ($this->trainingFileDefs() as $level => $def) {
            $_POST[$def['col']]           = $trainUploads[$level][0];
            $_POST[$def['col'] . '_name'] = $trainUploads[$level][1];
        }

        // เดิมไม่เช็คผลลัพธ์การบันทึกเลย เผื่อกรณี race condition ที่ isUsernameTaken()
        // เช็คผ่านไปแล้วแต่ดันชนกับ UNIQUE KEY ตอน insert จริง (2 request พร้อมกัน)
        $newId = $this->userModel->create($_POST);
        if ($newId === false) {
            if ($photoPath !== '') FileUpload::deleteFile($photoPath);
            $this->discardTrainingUploads($trainUploads);
            redirectWithFlash($backCreate, 'error',
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
        $this->requireOfficeAccess($user['office_name'], APP_URL . '/?page=users');
        $pageTitle = 'แก้ไขสมาชิก';
        $this->render('users/edit', get_defined_vars());
    }

    public function update() {
        Auth::requireRole('admin');
        Auth::checkCsrf();

        $id      = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $editUrl = APP_URL . '/?page=users&action=edit&id=' . $id;

        $old = $this->userModel->getById($id);
        if (!$old) {
            redirectWithFlash(APP_URL . '/?page=users', 'error', 'ไม่พบข้อมูลสมาชิก');
        }
        $this->requireOfficeAccess($old['office_name'], APP_URL . '/?page=users');

        $rolesArr = sanitizeRolesInput(isset($_POST['roles']) ? $_POST['roles'] : null);
        $_POST['roles'] = implode(',', $rolesArr);
        // สถานะบัญชีเป็นได้เฉพาะ 1 (ใช้งาน) หรือ 0 (ระงับ)
        $_POST['is_active'] = (isset($_POST['is_active']) && $_POST['is_active'] === '1') ? '1' : '0';
        $this->requireValidUserFields($editUrl);
        $this->enforceRolesByOffice($old);
        // รหัสผ่านใหม่ (เว้นว่าง = ไม่เปลี่ยน) ต้องเป็นสตริงและยาวอย่างน้อย 6 เท่ากับตอนเพิ่มสมาชิก
        if (isset($_POST['password']) && (!is_string($_POST['password'])
            || ($_POST['password'] !== '' && strlen($_POST['password']) < 6))) {
            redirectWithFlash($editUrl, 'error', 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
        }
        $this->requireValidProfileFields($editUrl);

        list($photoPath, $photoName, $photoErr) = $this->handlePhotoUpload('photo');
        if ($photoErr !== '') {
            redirectWithFlash($editUrl, 'error', 'รูปภาพ: ' . $photoErr);
        }
        list($trainUploads, $trainErr) = $this->handleTrainingUploads();
        if ($trainErr !== '') {
            if ($photoPath !== '') FileUpload::deleteFile($photoPath);
            redirectWithFlash($editUrl, 'error', $trainErr);
        }

        $ok = $this->userModel->update($id, $_POST);
        if (!$ok) {
            if ($photoPath !== '') FileUpload::deleteFile($photoPath);
            $this->discardTrainingUploads($trainUploads);
            redirectWithFlash($editUrl, 'error', 'บันทึกไม่สำเร็จ');
        }

        $remove = !empty($_POST['remove_photo']);
        if (!$this->applyPhotoChange($id, $old, $photoPath, $photoName, $remove)) {
            $this->discardTrainingUploads($trainUploads);
            redirectWithFlash($editUrl, 'error', 'บันทึกข้อมูลสมาชิกแล้ว แต่บันทึกรูปภาพไม่สำเร็จ');
        }
        if (!$this->applyTrainingFiles($id, $old, $trainUploads)) {
            redirectWithFlash($editUrl, 'error', 'บันทึกข้อมูลสมาชิกแล้ว แต่บันทึกไฟล์อบรมไม่สำเร็จ');
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
        $target = $this->userModel->getById($id);
        if (!$target) {
            redirectWithFlash(APP_URL . '/?page=users', 'error', 'ไม่พบข้อมูลสมาชิก');
        }
        $this->requireOfficeAccess($target['office_name'], APP_URL . '/?page=users');
        $this->userModel->delete($id);
        redirectWithFlash(APP_URL . '/?page=users', 'success', 'ระงับการใช้งานสมาชิกแล้ว');
    }

    public function profile() {
        Auth::requireLogin();
        $user      = $this->userModel->getById($_SESSION['user_id']);
        $pageTitle = 'แก้ไขโปรไฟล์';
        $this->render('users/profile', get_defined_vars());
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

    // ดูรายละเอียดสมาชิก (อ่านอย่างเดียว เฉพาะ admin ไม่แสดงรหัสผ่าน)
    public function detail() {
        Auth::requireRole('admin');
        $id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $user = $this->userModel->getById($id);
        if (!$user) {
            redirectWithFlash(APP_URL . '/?page=users', 'error', 'ไม่พบข้อมูลสมาชิก');
        }
        $this->requireOfficeAccess($user['office_name'], APP_URL . '/?page=users');
        $pageTitle = 'รายละเอียดสมาชิก';
        $this->render('users/detail', get_defined_vars());
    }

    // เจ้าของบัญชีแก้ข้อมูลส่วนตัวของตนเอง (POST + CSRF) ใช้ id จาก session เท่านั้น
    // แก้ได้เฉพาะ: คำนำหน้า ชื่อ นามสกุล เบอร์โทร อีเมล และข้อมูลการรับราชการ/อบรม/ดำรงตำแหน่ง
    public function update_info() {
        Auth::requireLogin();
        Auth::checkCsrf();

        $id   = intval($_SESSION['user_id']);
        $back = APP_URL . '/?page=users&action=profile';
        $row  = $this->userModel->getById($id);
        if (!$row) {
            redirectWithFlash($back, 'error', 'ไม่พบข้อมูลสมาชิก');
        }

        $prefix    = $this->postStr('prefix');
        $firstname = $this->postStr('firstname');
        $lastname  = $this->postStr('lastname');
        $phone     = $this->postStr('phone');
        $email     = $this->postStr('email');

        if (!in_array($prefix, array('นาย', 'นาง', 'นางสาว'), true)) {
            redirectWithFlash($back, 'error', 'คำนำหน้าไม่ถูกต้อง');
        }
        if ($firstname === '' || $lastname === '') {
            redirectWithFlash($back, 'error', 'กรุณากรอกชื่อและนามสกุล');
        }
        if (safeStrlen($firstname) > 100 || safeStrlen($lastname) > 100) {
            redirectWithFlash($back, 'error', 'ชื่อหรือนามสกุลยาวเกินไป (ไม่เกิน 100 ตัวอักษร)');
        }
        if (safeStrlen($phone) > 20) {
            redirectWithFlash($back, 'error', 'เบอร์โทรยาวเกินไป (ไม่เกิน 20 ตัวอักษร)');
        }
        if ($email !== '') {
            if (safeStrlen($email) > 100 || (function_exists('filter_var') && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
                redirectWithFlash($back, 'error', 'รูปแบบอีเมลไม่ถูกต้อง');
            }
        }
        $_POST['prefix']    = $prefix;
        $_POST['firstname'] = $firstname;
        $_POST['lastname']  = $lastname;
        $_POST['phone']     = $phone;
        $_POST['email']     = $email;

        $this->requireValidProfileFields($back);

        list($trainUploads, $trainErr) = $this->handleTrainingUploads();
        if ($trainErr !== '') {
            redirectWithFlash($back, 'error', $trainErr);
        }

        if (!$this->userModel->updateInfo($id, $_POST)) {
            $this->discardTrainingUploads($trainUploads);
            redirectWithFlash($back, 'error', 'บันทึกข้อมูลส่วนตัวไม่สำเร็จ');
        }
        if (!$this->applyTrainingFiles($id, $row, $trainUploads)) {
            redirectWithFlash($back, 'error', 'บันทึกข้อมูลส่วนตัวแล้ว แต่บันทึกไฟล์อบรมไม่สำเร็จ');
        }
        // อัปเดตชื่อที่เก็บใน session ให้ตรงกับฐานข้อมูล (ใช้แสดงบนหน้าจอ)
        $_SESSION['prefix']    = $prefix;
        $_SESSION['firstname'] = $firstname;
        $_SESSION['lastname']  = $lastname;

        redirectWithFlash($back, 'success', 'บันทึกข้อมูลส่วนตัวสำเร็จ');
    }

    // เจ้าของบัญชีเปลี่ยน/ลบรูปโปรไฟล์ของตนเองจากหน้าโปรไฟล์ (POST + CSRF)
    public function update_photo() {
        Auth::requireLogin();
        Auth::checkCsrf();

        $id   = intval($_SESSION['user_id']);
        $back = APP_URL . '/?page=users&action=profile';
        $row  = $this->userModel->getById($id);
        if (!$row) {
            redirectWithFlash($back, 'error', 'ไม่พบข้อมูลสมาชิก');
        }

        $remove = !empty($_POST['remove_photo']);
        list($photoPath, $photoName, $photoErr) = $this->handlePhotoUpload('photo');
        if ($photoErr !== '') {
            redirectWithFlash($back, 'error', 'รูปภาพ: ' . $photoErr);
        }
        if ($photoPath === '' && !$remove) {
            redirectWithFlash($back, 'warning', 'กรุณาเลือกไฟล์รูปภาพ');
        }

        if (!$this->applyPhotoChange($id, $row, $photoPath, $photoName, $remove)) {
            redirectWithFlash($back, 'error', 'บันทึกรูปภาพไม่สำเร็จ');
        }
        redirectWithFlash($back, 'success', ($photoPath !== '') ? 'เปลี่ยนรูปโปรไฟล์สำเร็จ' : 'ลบรูปโปรไฟล์แล้ว');
    }

    // แสดงรูปโปรไฟล์: เฉพาะ admin หรือเจ้าของบัญชีเท่านั้น
    public function view_photo() {
        Auth::requireLogin();
        $id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $user = Auth::currentUser();
        $row = $this->userModel->getById($id);
        if ($id != $user['id'] && !($row && adminCanAccessOffice($user, $row['office_name']))) {
            exit('คุณไม่มีสิทธิ์เข้าถึงรูปนี้');
        }
        if (!$row || empty($row['photo'])) {
            exit('ไม่พบรูปภาพ');
        }
        FileUpload::serve($row['photo'], $row['photo_name']);
    }

    // แสดงไฟล์แนบการอบรม (level = basic|intermediate|advanced): เฉพาะ admin หรือเจ้าของบัญชีเท่านั้น
    public function view_training_file() {
        Auth::requireLogin();
        $id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $level = (isset($_GET['level']) && is_string($_GET['level'])) ? $_GET['level'] : '';
        $user  = Auth::currentUser();
        $defs  = $this->trainingFileDefs();
        $row = $this->userModel->getById($id);
        if ($id != $user['id'] && !($row && adminCanAccessOffice($user, $row['office_name']))) {
            exit('คุณไม่มีสิทธิ์เข้าถึงไฟล์นี้');
        }
        if (!isset($defs[$level])) {
            exit('ไม่พบไฟล์');
        }
        $col = $defs[$level]['col'];
        if (!$row || empty($row[$col])) {
            exit('ไม่พบไฟล์');
        }
        $name = !empty($row[$col . '_name']) ? $row[$col . '_name'] : 'training_' . $level . '.pdf';
        FileUpload::serve($row[$col], $name);
    }

    // ส่งออกข้อมูลสมาชิกเป็น Excel ตามตัวกรองปัจจุบัน (เฉพาะ admin ไม่รวมรหัสผ่านและรูปภาพ)
    public function export_excel() {
        Auth::requireRole('admin');

        $keyword       = trim($this->getQueryStr('keyword'));
        $filterRole    = trim($this->getQueryStr('filter_role'));
        $filterEmpType = trim($this->getQueryStr('filter_emp'));
        $filterStatus  = $this->getQueryStr('filter_status');
        $filterOffice  = $this->scopedOffice(trim($this->getQueryStr('filter_office')));
        $allUsers      = $this->userModel->getAll($filterOffice ? $filterOffice : null, $keyword, $filterRole, $filterEmpType, $filterStatus);

        $empTypes = getEmployeeTypeOptions();
        $headers  = array('#', 'ชื่อ-สกุล', 'ชื่อผู้ใช้', 'สำนักงาน', 'ประเภทพนักงาน', 'ตำแหน่ง', 'เลขที่ตำแหน่ง',
                          'วันที่บรรจุราชการ', 'อายุราชการ', 'วุฒิการศึกษา', 'สาขาวิชา',
                          'ดำรงตำแหน่ง นว.ตบ.ปก', 'ดำรงตำแหน่ง นว.ตบ.ชก', 'ดำรงตำแหน่ง นว.ตบ.ชพ',
                          'อบรมระดับต้น', 'อบรมระดับกลาง', 'อบรมระดับสูง',
                          'เบอร์โทร', 'อีเมล', 'บทบาท', 'สถานะ');
        $rows = array();
        $no   = 0;
        foreach ($allUsers as $u) {
            $no++;
            $roleLabels = array();
            foreach (explode(',', $u['roles']) as $r) {
                $r = trim($r);
                if ($r !== '') $roleLabels[] = getRoleLabel($r);
            }
            $appointed = isset($u['appointed_date']) ? $u['appointed_date'] : '';
            $rows[] = array(
                $no,
                getFullname($u),
                $u['username'],
                $u['office_name'],
                isset($empTypes[$u['employee_type']]) ? $empTypes[$u['employee_type']] : $u['employee_type'],
                $u['position'],
                isset($u['position_no']) ? $u['position_no'] : '',
                $appointed,
                ($appointed !== '' && $appointed !== null) ? calcServiceAge($appointed) : '-',
                isset($u['education'])             ? $u['education']             : '',
                isset($u['major'])                 ? $u['major']                 : '',
                isset($u['pos_nvtb_pk_date'])      ? $u['pos_nvtb_pk_date']      : '',
                isset($u['pos_nvtb_chk_date'])     ? $u['pos_nvtb_chk_date']     : '',
                isset($u['pos_nvtb_chp_date'])     ? $u['pos_nvtb_chp_date']     : '',
                isset($u['training_basic'])        ? $u['training_basic']        : '',
                isset($u['training_intermediate']) ? $u['training_intermediate'] : '',
                isset($u['training_advanced'])     ? $u['training_advanced']     : '',
                $u['phone'],
                $u['email'],
                implode(', ', $roleLabels),
                $u['is_active'] ? 'ใช้งาน' : 'ระงับ',
            );
        }

        $subtitleParts = array();
        if ($keyword !== '')       $subtitleParts[] = 'คำค้น ' . $keyword;
        if ($filterRole !== '')    $subtitleParts[] = 'บทบาท ' . getRoleLabel($filterRole);
        if ($filterEmpType !== '') $subtitleParts[] = 'ประเภท ' . (isset($empTypes[$filterEmpType]) ? $empTypes[$filterEmpType] : $filterEmpType);
        if ($filterOffice !== '')  $subtitleParts[] = $filterOffice;
        if ($filterStatus !== '')  $subtitleParts[] = 'สถานะ ' . (intval($filterStatus) ? 'ใช้งาน' : 'ระงับ');
        $subtitleParts[] = 'อายุราชการนับถึง ' . thaiDate(date('Y-m-d'));

        $me          = Auth::currentUser();
        $signerTitle = $this->signerTitle($me['id']);

        ExcelExport::export(
            'รายชื่อสมาชิก',
            $headers,
            $rows,
            $me['fullname'],
            $signerTitle,
            'users_' . date('Ymd'),
            implode(' | ', $subtitleParts)
        );
    }
}
