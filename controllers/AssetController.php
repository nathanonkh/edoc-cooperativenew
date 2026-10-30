<?php
class AssetController extends Controller {

    private $assetModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->assetModel = new AssetModel($db);
    }

    // =====================================================
    // Helpers ภายใน
    // =====================================================

    private function getStr($key) {
        return (isset($_GET[$key]) && is_string($_GET[$key])) ? trim($_GET[$key]) : '';
    }

    private function listFilters() {
        return array(
            'asset_type' => $this->getStr('asset_type'),
            'status'     => $this->getStr('status'),
            'keyword'    => $this->getStr('keyword'),
            'per_page'   => 10,
            'page_num'   => isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1,
        );
    }

    private function requireManage() {
        if (!canManageAssets(Auth::currentUser())) {
            redirectWithFlash(APP_URL . '/?page=assets', 'error', 'คุณไม่มีสิทธิ์จัดการครุภัณฑ์');
        }
    }

    // โหลดครุภัณฑ์เพื่อจัดการ: ต้องมีอยู่จริงและอยู่ในสำนักงานที่ผู้ใช้มีสิทธิ์
    private function loadManageable($id) {
        $this->requireManage();
        $asset = $this->assetModel->getById($id);
        if (!$asset) {
            redirectWithFlash(APP_URL . '/?page=assets', 'error', 'ไม่พบครุภัณฑ์');
        }
        if (!canViewAsset(Auth::currentUser(), $asset)) {
            redirectWithFlash(APP_URL . '/?page=assets', 'error', 'คุณไม่มีสิทธิ์จัดการครุภัณฑ์ของสำนักงานอื่น');
        }
        return $asset;
    }

    // อ่าน+ตรวจข้อมูลฟอร์มครุภัณฑ์ (ฟิลด์ต่างกันตามประเภท) คืน array(ข้อมูล, ข้อความผิดพลาด)
    private function readInput($user, $isEdit, $existing) {
        $keys = array('asset_type','category','name','gf_code','official_no','quantity','unit','price','acquired_date',
                      'acquire_method','acq_cost','depreciation','office_name','status','note');
        $in = array();
        foreach ($keys as $k) {
            $in[$k] = (isset($_POST[$k]) && is_string($_POST[$k])) ? trim($_POST[$k]) : '';
        }
        $caps = array('category'=>100,'name'=>255,'gf_code'=>30,'official_no'=>100,'unit'=>30,
                      'acquire_method'=>50,'note'=>2000);
        foreach ($caps as $k => $max) {
            $in[$k] = safeSubstr($in[$k], 0, $max);
        }
        foreach (array('price','acq_cost','depreciation','quantity') as $k) {
            $in[$k] = str_replace(',', '', $in[$k]);
        }

        $types = getAssetTypeOptions();
        $type  = $in['asset_type'];
        if (!isset($types[$type])) {
            return array($in, 'กรุณาเลือกประเภทครุภัณฑ์');
        }

        // ฟิลด์ที่ไม่ใช้ในประเภทนี้ ให้เป็นค่าเริ่มต้น
        if (!assetTypeHas($type, 'gf_code'))  $in['gf_code'] = '';
        if (!assetTypeHas($type, 'quantity')) { $in['quantity'] = '1'; $in['unit'] = ''; }
        if (!assetTypeHas($type, 'price'))    $in['price'] = '0';
        if (!assetTypeHas($type, 'acquired')) $in['acquired_date'] = '';
        if (!assetTypeHas($type, 'method'))   $in['acquire_method'] = '';
        if (!assetTypeHas($type, 'costs'))    { $in['acq_cost'] = ''; $in['depreciation'] = ''; }

        if ($in['name'] === '') {
            return array($in, 'กรุณากรอกรายการ/คำอธิบายของสินทรัพย์');
        }

        // หมวดทรัพย์สิน ต้องอยู่ในรายการของประเภทนั้น (ยอมให้คงค่าเดิมของรายการที่แก้ไข)
        $cats = getAssetCategoryOptions($type);
        $keepOld = ($isEdit && $existing && $existing['category'] === $in['category']);
        if (!in_array($in['category'], $cats) && !$keepOld) {
            return array($in, 'กรุณาเลือกหมวดทรัพย์สิน');
        }

        if ($in['quantity'] === '') $in['quantity'] = '1';
        if (!preg_match('/^[0-9]{1,6}$/', $in['quantity']) || intval($in['quantity']) < 1) {
            return array($in, 'จำนวนต้องเป็นเลขจำนวนเต็มตั้งแต่ 1 ขึ้นไป');
        }
        if ($in['price'] === '') $in['price'] = '0';
        if (!is_numeric($in['price']) || floatval($in['price']) < 0 || floatval($in['price']) > 999999999999) {
            return array($in, 'ราคา/มูลค่าไม่ถูกต้อง');
        }
        if ($type === 'under10k' && floatval($in['price']) > assetUnderLimit()) {
            return array($in, 'ครุภัณฑ์ไม่เกิน 10,000 ต้องมีราคาต่อหน่วยไม่เกิน 10,000 บาท');
        }
        foreach (array('acq_cost' => 'รก.ค่าการได้มา', 'depreciation' => 'ค่าบ/สิ้นงวด') as $k => $lbl) {
            if ($in[$k] !== '' && (!is_numeric($in[$k]) || floatval($in[$k]) < 0)) {
                return array($in, $lbl . ' ไม่ถูกต้อง');
            }
        }
        if ($in['acquired_date'] !== '' && !isValidThaiDate($in['acquired_date'])) {
            return array($in, 'วันที่ได้มาไม่ถูกต้อง (รูปแบบ วว/ดด/ปปปป พ.ศ. เช่น 31/03/2568)');
        }

        // สำนักงาน: admin/ส่วนกลางเลือกได้ทุกแห่ง ที่เหลือบังคับเป็นสำนักงานตนเอง
        if (userHasRole($user, 'admin') || isHQ($user)) {
            if (!in_array($in['office_name'], getOfficeOptions())) {
                return array($in, 'กรุณาเลือกสำนักงาน');
            }
        } else {
            $in['office_name'] = $user['office_name'];
        }

        if ($isEdit) {
            $statuses = getAssetStatusOptions();
            if (!isset($statuses[$in['status']])) $in['status'] = $existing['status'];
        } else {
            $statuses = getAssetStatusOptions();
            if (!isset($statuses[$in['status']])) $in['status'] = 'normal';
        }
        return array($in, '');
    }

    private function renderForm($asset, $isEdit, $error) {
        $pageTitle = $isEdit ? 'แก้ไขครุภัณฑ์' : 'เพิ่มครุภัณฑ์';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/assets/form.php';
        include 'views/layout/footer.php';
        exit;
    }

    // =====================================================
    // รายการ / เพิ่ม / แก้ไข / ลบ / ดูรายละเอียด
    // =====================================================

    public function index() {
        $user    = Auth::currentUser();
        $filters = $this->listFilters();

        $assets         = $this->assetModel->getList($user, $filters);
        $totalItems     = $this->assetModel->countList($user, $filters);
        $totalPages     = max(1, ceil($totalItems / $filters['per_page']));
        $currentPageNum = $filters['page_num'];

        $pageTitle = 'จัดการครุภัณฑ์';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/assets/index.php';
        include 'views/layout/footer.php';
    }

    public function ajax_list() {
        Auth::requireLogin();
        requireAjax();
        $user    = Auth::currentUser();
        $filters = $this->listFilters();

        $assets         = $this->assetModel->getList($user, $filters);
        $totalItems     = $this->assetModel->countList($user, $filters);
        $totalPages     = max(1, ceil($totalItems / $filters['per_page']));
        $currentPageNum = $filters['page_num'];

        include 'views/assets/_list_partial.php';
        exit;
    }

    public function create() {
        $this->requireManage();
        $user  = Auth::currentUser();
        $asset = array(
            'asset_type' => 'registered', 'category' => '', 'name' => '', 'gf_code' => '', 'official_no' => '',
            'quantity' => '1', 'unit' => '', 'price' => '', 'acquired_date' => '', 'acquire_method' => 'งบประมาณ',
            'acq_cost' => '', 'depreciation' => '', 'office_name' => $user['office_name'], 'status' => 'normal', 'note' => '',
        );
        $this->renderForm($asset, false, '');
    }

    public function store() {
        $this->requireManage();
        Auth::checkCsrf();
        $user = Auth::currentUser();

        list($in, $err) = $this->readInput($user, false, null);
        if ($err !== '') {
            $this->renderForm($in, false, $err);
        }

        $res = $this->assetModel->create($in, $user['id']);
        if ($res === false) {
            $this->renderForm($in, false, 'บันทึกไม่สำเร็จ กรุณาลองใหม่อีกครั้ง (ตรวจสอบว่ารัน migration_assets.sql แล้ว)');
        }

        $this->assetModel->addLog($res['id'], 'เพิ่มครุภัณฑ์', $user['id']);
        redirectWithFlash(APP_URL . '/?page=assets&action=detail&id=' . $res['id'], 'success',
            'เพิ่มครุภัณฑ์สำเร็จ รหัส: ' . $res['code']);
    }

    public function detail() {
        $id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $asset = $this->assetModel->getById($id);
        $user  = Auth::currentUser();
        if (!$asset) {
            redirectWithFlash(APP_URL . '/?page=assets', 'error', 'ไม่พบครุภัณฑ์');
        }
        if (!canViewAsset($user, $asset)) {
            redirectWithFlash(APP_URL . '/?page=assets', 'error', 'คุณไม่มีสิทธิ์ดูครุภัณฑ์นี้');
        }

        $checks = $this->assetModel->getChecksForAsset($asset['id']);
        $logs   = $this->assetModel->getLogs($asset['id']);

        $pageTitle = 'ครุภัณฑ์ ' . $asset['asset_code'];
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/assets/detail.php';
        include 'views/layout/footer.php';
    }

    public function edit() {
        $id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $asset = $this->loadManageable($id);
        $this->renderForm($asset, true, '');
    }

    public function update() {
        $this->requireManage();
        Auth::checkCsrf();
        $user  = Auth::currentUser();
        $id    = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $asset = $this->loadManageable($id);

        list($in, $err) = $this->readInput($user, true, $asset);
        if ($err !== '') {
            $in['id']         = $asset['id'];
            $in['asset_code'] = $asset['asset_code'];
            $this->renderForm($in, true, $err);
        }

        if (!$this->assetModel->update($id, $in)) {
            $in['id']         = $asset['id'];
            $in['asset_code'] = $asset['asset_code'];
            $this->renderForm($in, true, 'บันทึกไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        $this->assetModel->addLog($id, 'แก้ไขข้อมูลครุภัณฑ์', $user['id']);
        redirectWithFlash(APP_URL . '/?page=assets&action=detail&id=' . $id, 'success', 'อัปเดตข้อมูลครุภัณฑ์สำเร็จ');
    }

    // ลบ = ซ่อน (ส่งผ่าน POST + CSRF ด้วย confirmDelete ใน app.js)
    public function delete() {
        $this->requireManage();
        Auth::checkCsrf();
        $id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $asset = $this->loadManageable($id);
        $this->assetModel->softDelete($id);
        $this->assetModel->addLog($id, 'ลบครุภัณฑ์', intval($_SESSION['user_id']));
        redirectWithFlash(APP_URL . '/?page=assets', 'success', 'ลบครุภัณฑ์ ' . $asset['asset_code'] . ' แล้ว');
    }

    // =====================================================
    // พิมพ์สติ๊กเกอร์ QR Code
    // POST ids[] (+csrf) จากหน้ารายการ หรือ GET id=ครุภัณฑ์ชิ้นเดียว
    // =====================================================

    public function print_labels() {
        $this->requireManage();
        $user = Auth::currentUser();

        $ids = array();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::checkCsrf();
            if (isset($_POST['ids']) && is_array($_POST['ids'])) $ids = $_POST['ids'];
        } elseif (isset($_GET['id'])) {
            $ids = array(intval($_GET['id']));
        }
        $ids = array_slice($ids, 0, 500);

        $labels = $this->assetModel->getByIds($user, $ids);
        if (empty($labels)) {
            redirectWithFlash(APP_URL . '/?page=assets', 'error', 'ไม่พบครุภัณฑ์ที่เลือกพิมพ์');
        }

        include 'views/assets/print_labels.php';
        exit;
    }

    // =====================================================
    // สแกนตรวจนับ (มือถือ)
    // =====================================================

    // หน้าสแกนด้วยกล้องในระบบ (ใช้กล้องมือถือสแกนตรงก็ได้ เพราะ QR เป็น URL เปิดหน้า scan เอง)
    public function scanner() {
        $user  = Auth::currentUser();
        $rounds = $this->assetModel->getOpenRoundsForUser($user);

        $pageTitle = 'สแกนตรวจนับครุภัณฑ์';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/assets/scanner.php';
        include 'views/layout/footer.php';
    }

    public function scan() {
        $user = Auth::currentUser();
        $code = $this->getStr('code');
        $back = APP_URL . '/?page=assets&action=scanner';

        if (!preg_match('/^[A-Za-z0-9_\-]{1,50}$/', $code)) {
            redirectWithFlash($back, 'error', 'รหัส QR Code ไม่ถูกต้อง');
        }
        $asset = $this->assetModel->getByCode($code);
        if (!$asset) {
            redirectWithFlash($back, 'error', 'ไม่พบครุภัณฑ์รหัส ' . $code);
        }
        if (!canViewAsset($user, $asset)) {
            redirectWithFlash($back, 'error', 'ครุภัณฑ์นี้เป็นของสำนักงานอื่น คุณไม่มีสิทธิ์ตรวจนับ');
        }

        $round    = null;
        $existing = null;
        if ($asset['status'] !== 'disposed') {
            $round = $this->assetModel->getOpenRoundForAsset($asset);
            if ($round) {
                $existing = $this->assetModel->getCheck($round['id'], $asset['id']);
            }
        }

        $pageTitle = 'ตรวจนับ ' . $asset['asset_code'];
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/assets/scan.php';
        include 'views/layout/footer.php';
    }

    public function check() {
        Auth::checkCsrf();
        $user    = Auth::currentUser();
        $assetId = isset($_POST['asset_id']) ? intval($_POST['asset_id']) : 0;
        $roundId = isset($_POST['round_id']) ? intval($_POST['round_id']) : 0;
        $result  = (isset($_POST['result']) && is_string($_POST['result'])) ? $_POST['result'] : '';
        $loc     = (isset($_POST['location_found']) && is_string($_POST['location_found'])) ? trim($_POST['location_found']) : '';
        $note    = (isset($_POST['note']) && is_string($_POST['note'])) ? trim($_POST['note']) : '';
        $loc     = safeSubstr($loc, 0, 255);
        $note    = safeSubstr($note, 0, 500);
        $back    = APP_URL . '/?page=assets&action=scanner';

        $asset = $this->assetModel->getById($assetId);
        if (!$asset || !canViewAsset($user, $asset)) {
            redirectWithFlash($back, 'error', 'ไม่พบครุภัณฑ์หรือไม่มีสิทธิ์ตรวจนับ');
        }
        $scanUrl = APP_URL . '/?page=assets&action=scan&code=' . rawurlencode($asset['asset_code']);

        $options = getCheckResultOptions();
        if (!isset($options[$result])) {
            redirectWithFlash($scanUrl, 'error', 'กรุณาเลือกผลการตรวจนับ');
        }
        if ($result === 'found_moved' && $loc === '') {
            redirectWithFlash($scanUrl, 'error', 'กรุณาระบุสถานที่ที่พบจริง');
        }
        if ($result !== 'found_moved') $loc = '';

        // รอบต้องเปิดอยู่ และครอบคลุมครุภัณฑ์ชิ้นนี้ (สำนักงานเดียวกัน + ประเภทตรงกัน)
        $round = $this->assetModel->getRound($roundId);
        if (!$round || $round['status'] !== 'open'
            || $round['office_name'] !== $asset['office_name']
            || ($round['asset_type'] !== '' && $round['asset_type'] !== $asset['asset_type'])
            || $asset['status'] === 'disposed') {
            redirectWithFlash($scanUrl, 'error', 'รอบตรวจนับนี้ปิดแล้วหรือไม่ครอบคลุมครุภัณฑ์ชิ้นนี้');
        }

        if (!$this->assetModel->saveCheck($round['id'], $asset['id'], $result, $loc, $note, $user['id'])) {
            redirectWithFlash($scanUrl, 'error', 'บันทึกผลตรวจนับไม่สำเร็จ กรุณาลองใหม่');
        }

        $logNote = 'รอบ: ' . $round['title'] . ($loc !== '' ? ' | พบที่: ' . $loc : '') . ($note !== '' ? ' | ' . $note : '');
        $this->assetModel->addLog($asset['id'], 'ตรวจนับ: ' . checkResultLabel($result), $user['id'], $logNote);

        redirectWithFlash($back, 'success', 'บันทึกผลตรวจนับ ' . $asset['asset_code'] . ' เรียบร้อย');
    }

    // =====================================================
    // รอบตรวจนับ
    // =====================================================

    public function rounds() {
        $user   = Auth::currentUser();
        $rounds = $this->assetModel->getRounds($user);

        $pageTitle = 'รอบตรวจนับครุภัณฑ์';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/assets/rounds.php';
        include 'views/layout/footer.php';
    }

    public function round_store() {
        $this->requireManage();
        Auth::checkCsrf();
        $user = Auth::currentUser();
        $back = APP_URL . '/?page=assets&action=rounds';

        $title = (isset($_POST['title']) && is_string($_POST['title'])) ? trim($_POST['title']) : '';
        $fy    = (isset($_POST['fiscal_year']) && is_string($_POST['fiscal_year'])) ? trim($_POST['fiscal_year']) : '';
        $type  = (isset($_POST['asset_type']) && is_string($_POST['asset_type'])) ? trim($_POST['asset_type']) : '';
        $off   = (isset($_POST['office_name']) && is_string($_POST['office_name'])) ? trim($_POST['office_name']) : '';

        $title = safeSubstr($title, 0, 255);
        if ($title === '') {
            redirectWithFlash($back, 'error', 'กรุณากรอกชื่อรอบตรวจนับ');
        }
        if (!preg_match('/^[0-9]{4}$/', $fy)) {
            redirectWithFlash($back, 'error', 'ปีบัญชีไม่ถูกต้อง');
        }
        $types = getAssetTypeOptions();
        if ($type !== '' && !isset($types[$type])) {
            redirectWithFlash($back, 'error', 'ประเภทครุภัณฑ์ไม่ถูกต้อง');
        }
        if (userHasRole($user, 'admin') || isHQ($user)) {
            if (!in_array($off, getOfficeOptions())) {
                redirectWithFlash($back, 'error', 'กรุณาเลือกสำนักงาน');
            }
        } else {
            $off = $user['office_name'];
        }

        $id = $this->assetModel->createRound($title, $fy, $off, $type, $user['id']);
        if (!$id) {
            redirectWithFlash($back, 'error', 'สร้างรอบตรวจนับไม่สำเร็จ (ตรวจสอบว่ารัน migration_assets.sql แล้ว)');
        }
        redirectWithFlash(APP_URL . '/?page=assets&action=round_detail&id=' . $id, 'success', 'เปิดรอบตรวจนับแล้ว');
    }

    // โหลดรอบที่ผู้ใช้มีสิทธิ์ดู
    private function loadRound($id) {
        $user  = Auth::currentUser();
        $round = $this->assetModel->getRound($id);
        if (!$round) {
            redirectWithFlash(APP_URL . '/?page=assets&action=rounds', 'error', 'ไม่พบรอบตรวจนับ');
        }
        if (!(userHasRole($user, 'admin') || isHQ($user) || $round['office_name'] === $user['office_name'])) {
            redirectWithFlash(APP_URL . '/?page=assets&action=rounds', 'error', 'คุณไม่มีสิทธิ์ดูรอบตรวจนับนี้');
        }
        return $round;
    }

    public function round_detail() {
        $id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $round = $this->loadRound($id);

        $view = $this->getStr('view');
        if (!in_array($view, array('all', 'checked', 'unchecked'))) $view = 'all';

        $items  = $this->assetModel->getRoundAssets($round, $view);
        $counts = $this->assetModel->getRoundResultCounts($round['id']);

        $pageTitle = 'รอบตรวจนับ: ' . $round['title'];
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/assets/round_detail.php';
        include 'views/layout/footer.php';
    }

    public function round_close() {
        $this->requireManage();
        Auth::checkCsrf();
        $id    = isset($_POST['round_id']) ? intval($_POST['round_id']) : 0;
        $round = $this->loadRound($id);
        $this->assetModel->closeRound($round['id'], $_SESSION['user_id']);
        redirectWithFlash(APP_URL . '/?page=assets&action=round_detail&id=' . $round['id'], 'success', 'ปิดรอบตรวจนับแล้ว');
    }

    // =====================================================
    // ส่งออก Excel
    // =====================================================

    private function signerTitle($user) {
        $row = getUserPosition($this->db, $user['id']);
        return $row ? $row['position'] : '';
    }

    // ส่งออกตามแบบไฟล์ Excel ของแต่ละประเภท (GET: asset_type, fiscal_year, office_name, as_of, chair, member1, member2, with_disposed)
    public function export_excel() {
        $user = Auth::currentUser();
        $type = $this->getStr('asset_type');
        $types = getAssetTypeOptions();
        if (!isset($types[$type])) {
            redirectWithFlash(APP_URL . '/?page=assets', 'error', 'กรุณาเลือกประเภทครุภัณฑ์ที่จะส่งออก');
        }

        $office = $this->getStr('office_name');
        if (userHasRole($user, 'admin') || isHQ($user)) {
            if (!in_array($office, getOfficeOptions())) $office = $user['office_name'];
        } else {
            $office = $user['office_name'];
        }

        $fy = $this->getStr('fiscal_year');
        if (!preg_match('/^[0-9]{4}$/', $fy)) $fy = (string) currentFiscalYear();

        $asOf = safeSubstr($this->getStr('as_of'), 0, 60);
        if ($asOf !== '' && !preg_match('/^ณ /u', $asOf)) $asOf = 'ณ ' . $asOf;

        $assets = $this->assetModel->getForExport($office, $type, $this->getStr('with_disposed') === '1');

        AssetExcel::export($type, $assets, array(
            'fiscal_year' => $fy,
            'office_name' => $office,
            'as_of'       => $asOf,
            'chair'       => safeSubstr($this->getStr('chair'), 0, 100),
            'member1'     => safeSubstr($this->getStr('member1'), 0, 100),
            'member2'     => safeSubstr($this->getStr('member2'), 0, 100),
            'filename'    => 'assets_' . $type . '_' . $fy . '_' . date('Ymd'),
        ));
    }

    // รายงานผลตรวจนับของรอบ (Excel)
    public function round_export() {
        $id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $round = $this->loadRound($id);
        $user  = Auth::currentUser();
        $items = $this->assetModel->getRoundAssets($round, 'all');

        $headers = array('#','รหัสในระบบ','รหัสสินทรัพย์ (สนง.)','รายการ','ประเภท','หมวดทรัพย์สิน','หมายเหตุตามทะเบียน',
                         'ผลการตรวจนับ','สถานที่ที่พบจริง','หมายเหตุการตรวจ','ผู้ตรวจ','วันที่ตรวจ');
        $rows = array();
        foreach ($items as $idx => $a) {
            $rows[] = array(
                $idx + 1, $a['asset_code'], $a['official_no'], $a['name'], assetTypeLabel($a['asset_type']),
                $a['category'], $a['note'],
                empty($a['result']) ? 'ยังไม่ตรวจ' : checkResultLabel($a['result']),
                $a['location_found'], $a['check_note'], $a['checker_name'],
                empty($a['checked_at']) ? '' : thaiDate($a['checked_at'], true),
            );
        }

        $sub = array($round['title'], $round['office_name'], 'ปีบัญชี ' . $round['fiscal_year']);
        if ($round['asset_type'] !== '') $sub[] = assetTypeLabel($round['asset_type']);

        $row = getUserPosition($this->db, $user['id']);
        ExcelExport::export('รายงานผลการตรวจนับครุภัณฑ์', $headers, $rows, $user['fullname'], $row ? $row['position'] : '',
            'asset_check_' . $round['id'] . '_' . date('Ymd'), implode(' | ', $sub));
    }
}
