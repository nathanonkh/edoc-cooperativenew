<?php
class AnnouncementController extends Controller {

    private $announcementModel;
    private $videoModel;
    private $bannerModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->announcementModel = new AnnouncementModel($db);
        $this->videoModel        = new VideoModel($db);
        $this->bannerModel       = new BannerModel($db);
    }

    public function index() {
        Auth::requireRole('admin');
        $announcements = $this->announcementModel->getAll();
        $videos        = $this->videoModel->getAll();
        $banners       = $this->bannerModel->getAll();
        $pageTitle     = 'จัดการหน้าหลัก';
        $this->render('announcements/index', get_defined_vars());
    }

    public function store() {
        Auth::requireRole('admin');
        Auth::checkCsrf();
        $back = APP_URL . '/?page=announcements';
        if (empty($_POST['title']) || empty($_POST['content'])) {
            redirectWithFlash($back, 'error', 'กรุณากรอกหัวข้อและเนื้อหาประกาศ');
        }

        $token = 'ann_' . date('YmdHis') . '_' . mt_rand(1000, 9999);

        $attachmentPath = '';
        $attachmentName = '';
        if (isset($_FILES['attachment'])) {
            $result = FileUpload::uploadPdfOnly($_FILES['attachment'], 'announcements', $token);
            if (!$result['success']) {
                redirectWithFlash($back, 'error', 'ไฟล์ PDF: ' . $result['error']);
            }
            $attachmentPath = $result['filename'];
            $attachmentName = $result['original_name'];
        }

        $imagePath = '';
        $imageName = '';
        if (isset($_FILES['image'])) {
            $result = FileUpload::uploadImageOnly($_FILES['image'], 'announcements', $token);
            if (!$result['success']) {
                if ($attachmentPath !== '') FileUpload::deleteFile($attachmentPath);
                redirectWithFlash($back, 'error', 'รูปภาพ: ' . $result['error']);
            }
            $imagePath = $result['filename'];
            $imageName = $result['original_name'];
        }

        $data                    = $_POST;
        $data['attachment']      = $attachmentPath;
        $data['attachment_name'] = $attachmentName;
        $data['image']           = $imagePath;
        $data['image_name']      = $imageName;

        $this->announcementModel->create($data, $_SESSION['user_id']);
        redirectWithFlash($back, 'success', 'เพิ่มประกาศสำเร็จ');
    }

    public function delete() {
        Auth::requireRole('admin');
        Auth::checkCsrf();
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $this->announcementModel->delete($id);
        redirectWithFlash(APP_URL . '/?page=announcements', 'success', 'ลบประกาศแล้ว');
    }

    public function store_video() {
        Auth::requireRole('admin');
        Auth::checkCsrf();
        $back = APP_URL . '/?page=announcements';
        if (empty($_POST['title']) || empty($_POST['video_url'])) {
            redirectWithFlash($back, 'error', 'กรุณากรอกหัวข้อและลิงก์วีดีโอ');
        }

        $token = 'vid_' . date('YmdHis') . '_' . mt_rand(1000, 9999);

        $attachmentPath = '';
        $attachmentName = '';
        if (isset($_FILES['attachment'])) {
            $result = FileUpload::uploadPdfOnly($_FILES['attachment'], 'videos', $token);
            if (!$result['success']) {
                redirectWithFlash($back, 'error', 'ไฟล์ PDF: ' . $result['error']);
            }
            $attachmentPath = $result['filename'];
            $attachmentName = $result['original_name'];
        }

        $data                    = $_POST;
        $data['attachment']      = $attachmentPath;
        $data['attachment_name'] = $attachmentName;

        $this->videoModel->create($data, $_SESSION['user_id']);
        redirectWithFlash($back, 'success', 'เพิ่มวีดีโอสำเร็จ');
    }

    public function delete_video() {
        Auth::requireRole('admin');
        Auth::checkCsrf();
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $this->videoModel->delete($id);
        redirectWithFlash(APP_URL . '/?page=announcements', 'success', 'ลบวีดีโอแล้ว');
    }

    // หน้ารายละเอียด/ไฟล์แนบ — ผู้ใช้ที่ล็อกอินแล้วทุกคนดูได้ แต่รายการที่ปิดใช้งานแล้วเห็นได้เฉพาะ admin
    public function detail() {
        Auth::requireLogin();
        $id           = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $announcement = $this->announcementModel->getById($id);
        $user         = Auth::currentUser();

        if (!$announcement || (!$announcement['is_active'] && !userHasRole($user, 'admin'))) {
            redirectWithFlash(APP_URL . '/?page=dashboard', 'error', 'ไม่พบประกาศนี้');
        }

        $pageTitle = $announcement['title'];
        $this->render('announcements/detail', get_defined_vars());
    }

    public function video_detail() {
        Auth::requireLogin();
        $id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $video = $this->videoModel->getById($id);
        $user  = Auth::currentUser();

        if (!$video || (!$video['is_active'] && !userHasRole($user, 'admin'))) {
            redirectWithFlash(APP_URL . '/?page=dashboard', 'error', 'ไม่พบวีดีโอนี้');
        }

        $pageTitle = $video['title'];
        $this->render('announcements/video_detail', get_defined_vars());
    }

    // ตรวจว่ารายการมีอยู่ เปิดใช้งาน (หรือผู้ดู = admin) และมีไฟล์ในฟิลด์ที่ระบุ
    private function serveField($row, $pathField, $nameField, $notFoundMsg) {
        if (!$row || empty($row[$pathField])) {
            exit($notFoundMsg);
        }
        if (!$row['is_active'] && !userHasRole(Auth::currentUser(), 'admin')) {
            exit($notFoundMsg);
        }
        FileUpload::serve($row[$pathField], $row[$nameField]);
    }

    public function view_attachment() {
        Auth::requireLogin();
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $this->serveField($this->announcementModel->getById($id), 'attachment', 'attachment_name', 'ไม่พบไฟล์แนบ');
    }

    public function view_image() {
        Auth::requireLogin();
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $this->serveField($this->announcementModel->getById($id), 'image', 'image_name', 'ไม่พบรูปภาพ');
    }

    // ลิงก์แบนเนอร์ต้องเป็นลิงก์ภายในระบบ (?page=...) หรือ https:// เท่านั้น (กัน javascript:/data: ฯลฯ)
    private function isSafeBannerLink($url) {
        if ($url === '') return true;
        if (strlen($url) > 500) return false;
        if (preg_match('/^\?page=[a-z_]+(&[a-z_]+=[A-Za-z0-9_%.\-]*)*$/', $url)) return true;
        return (bool) preg_match('#^https://[A-Za-z0-9._~:/?\#\[\]@!$&()*+,;=%\-]+$#', $url);
    }

    public function store_banner() {
        Auth::requireRole('admin');
        Auth::checkCsrf();
        $back  = APP_URL . '/?page=announcements';
        $title = (isset($_POST['title']) && is_string($_POST['title'])) ? trim($_POST['title']) : '';
        if ($title === '') {
            redirectWithFlash($back, 'error', 'กรุณากรอกหัวข้อแบนเนอร์');
        }
        if (strlen($title) > 255) {
            redirectWithFlash($back, 'error', 'หัวข้อแบนเนอร์ยาวเกินไป (ไม่เกิน 255 ตัวอักษร)');
        }
        $subtitle = (isset($_POST['subtitle']) && is_string($_POST['subtitle'])) ? trim($_POST['subtitle']) : '';
        if (strlen($subtitle) > 255) {
            redirectWithFlash($back, 'error', 'ข้อความรองยาวเกินไป (ไม่เกิน 255 ตัวอักษร)');
        }
        $linkUrl = (isset($_POST['link_url']) && is_string($_POST['link_url'])) ? trim($_POST['link_url']) : '';
        if (!$this->isSafeBannerLink($linkUrl)) {
            redirectWithFlash($back, 'error', 'ลิงก์ต้องขึ้นต้นด้วย ?page= (ในระบบ) หรือ https:// เท่านั้น');
        }
        if (!isset($_FILES['image']) || !isset($_FILES['image']['error']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            redirectWithFlash($back, 'error', 'กรุณาเลือกรูปภาพแบนเนอร์');
        }

        $token  = 'ban_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
        $result = FileUpload::uploadImageOnly($_FILES['image'], 'banners', $token);
        if (!$result['success']) {
            redirectWithFlash($back, 'error', 'รูปภาพ: ' . $result['error']);
        }
        if ($result['filename'] === '') {
            redirectWithFlash($back, 'error', 'กรุณาเลือกรูปภาพแบนเนอร์');
        }

        $data = array(
            'title'      => $title,
            'subtitle'   => $subtitle,
            'link_url'   => $linkUrl,
            'sort_order' => (isset($_POST['sort_order']) && is_string($_POST['sort_order'])) ? intval($_POST['sort_order']) : 0,
            'show_caption' => !empty($_POST['show_caption']) ? 1 : 0,
            'image'      => $result['filename'],
            'image_name' => $result['original_name'],
        );
        $newId = $this->bannerModel->create($data, $_SESSION['user_id']);
        if (!$newId) {
            FileUpload::deleteFile($result['filename']);
            redirectWithFlash($back, 'error', 'บันทึกแบนเนอร์ไม่สำเร็จ กรุณาลองใหม่');
        }
        redirectWithFlash($back, 'success', 'เพิ่มแบนเนอร์สำเร็จ');
    }

    public function delete_banner() {
        Auth::requireRole('admin');
        Auth::checkCsrf();
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $this->bannerModel->delete($id);
        redirectWithFlash(APP_URL . '/?page=announcements', 'success', 'ลบแบนเนอร์แล้ว');
    }

    // =====================================================
    // แก้ไข / นำกลับมาใช้ (ประกาศ วีดีโอ แบนเนอร์) — admin ส่วนกลางเท่านั้น (Router ล็อกไว้) ทุก action เป็น POST + CSRF
    // =====================================================

    private function postId() {
        return (isset($_POST['id']) && is_string($_POST['id'])) ? intval($_POST['id']) : 0;
    }

    private function restoreItem($model, $label) {
        Auth::requireRole('admin');
        Auth::checkCsrf();
        $back = APP_URL . '/?page=announcements';
        $id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$model->getById($id)) {
            redirectWithFlash($back, 'error', 'ไม่พบ' . $label);
        }
        if (!$model->restore($id)) {
            redirectWithFlash($back, 'error', 'นำ' . $label . 'กลับมาใช้ไม่สำเร็จ กรุณาลองใหม่');
        }
        redirectWithFlash($back, 'success', 'นำ' . $label . 'กลับมาใช้แล้ว');
    }

    public function restore()        { $this->restoreItem($this->announcementModel, 'ประกาศ'); }
    public function restore_video()  { $this->restoreItem($this->videoModel, 'วีดีโอ'); }
    public function restore_banner() { $this->restoreItem($this->bannerModel, 'แบนเนอร์'); }

    public function update() {
        Auth::requireRole('admin');
        Auth::checkCsrf();
        $back = APP_URL . '/?page=announcements';
        $id   = $this->postId();
        $row  = $this->announcementModel->getById($id);
        if (!$row) {
            redirectWithFlash($back, 'error', 'ไม่พบประกาศ');
        }
        $title   = (isset($_POST['title'])   && is_string($_POST['title']))   ? trim($_POST['title'])   : '';
        $content = (isset($_POST['content']) && is_string($_POST['content'])) ? trim($_POST['content']) : '';
        if ($title === '' || $content === '') {
            redirectWithFlash($back, 'error', 'กรุณากรอกหัวข้อและเนื้อหาประกาศ');
        }

        $token = 'ann_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
        $data  = array('title' => $title, 'content' => $content, 'is_pinned' => !empty($_POST['is_pinned']) ? 1 : 0);

        if (isset($_FILES['attachment'])) {
            $result = FileUpload::uploadPdfOnly($_FILES['attachment'], 'announcements', $token);
            if (!$result['success']) {
                redirectWithFlash($back, 'error', 'ไฟล์ PDF: ' . $result['error']);
            }
            $data['attachment']      = $result['filename'];
            $data['attachment_name'] = $result['original_name'];
        }
        if (isset($_FILES['image'])) {
            $result = FileUpload::uploadImageOnly($_FILES['image'], 'announcements', $token);
            if (!$result['success']) {
                if (!empty($data['attachment'])) FileUpload::deleteFile($data['attachment']);
                redirectWithFlash($back, 'error', 'รูปภาพ: ' . $result['error']);
            }
            $data['image']      = $result['filename'];
            $data['image_name'] = $result['original_name'];
        }

        if (!$this->announcementModel->update($id, $data)) {
            if (!empty($data['attachment'])) FileUpload::deleteFile($data['attachment']);
            if (!empty($data['image']))      FileUpload::deleteFile($data['image']);
            redirectWithFlash($back, 'error', 'บันทึกการแก้ไขไม่สำเร็จ กรุณาลองใหม่');
        }
        // ลบไฟล์เก่าหลังบันทึกสำเร็จเท่านั้น
        if (!empty($data['attachment']) && !empty($row['attachment'])) FileUpload::deleteFile($row['attachment']);
        if (!empty($data['image'])      && !empty($row['image']))      FileUpload::deleteFile($row['image']);
        redirectWithFlash($back, 'success', 'แก้ไขประกาศสำเร็จ');
    }

    public function update_video() {
        Auth::requireRole('admin');
        Auth::checkCsrf();
        $back = APP_URL . '/?page=announcements';
        $id   = $this->postId();
        $row  = $this->videoModel->getById($id);
        if (!$row) {
            redirectWithFlash($back, 'error', 'ไม่พบวีดีโอ');
        }
        $title    = (isset($_POST['title'])     && is_string($_POST['title']))     ? trim($_POST['title'])     : '';
        $videoUrl = (isset($_POST['video_url']) && is_string($_POST['video_url'])) ? trim($_POST['video_url']) : '';
        if ($title === '' || $videoUrl === '') {
            redirectWithFlash($back, 'error', 'กรุณากรอกหัวข้อและลิงก์วีดีโอ');
        }
        $data = array(
            'title'       => $title,
            'video_url'   => $videoUrl,
            'description' => (isset($_POST['description']) && is_string($_POST['description'])) ? $_POST['description'] : '',
        );
        $token = 'vid_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
        if (isset($_FILES['attachment'])) {
            $result = FileUpload::uploadPdfOnly($_FILES['attachment'], 'videos', $token);
            if (!$result['success']) {
                redirectWithFlash($back, 'error', 'ไฟล์ PDF: ' . $result['error']);
            }
            $data['attachment']      = $result['filename'];
            $data['attachment_name'] = $result['original_name'];
        }
        if (!$this->videoModel->update($id, $data)) {
            if (!empty($data['attachment'])) FileUpload::deleteFile($data['attachment']);
            redirectWithFlash($back, 'error', 'บันทึกการแก้ไขไม่สำเร็จ กรุณาลองใหม่');
        }
        if (!empty($data['attachment']) && !empty($row['attachment'])) FileUpload::deleteFile($row['attachment']);
        redirectWithFlash($back, 'success', 'แก้ไขวีดีโอสำเร็จ');
    }

    public function update_banner() {
        Auth::requireRole('admin');
        Auth::checkCsrf();
        $back = APP_URL . '/?page=announcements';
        $id   = $this->postId();
        $row  = $this->bannerModel->getById($id);
        if (!$row) {
            redirectWithFlash($back, 'error', 'ไม่พบแบนเนอร์');
        }
        $title = (isset($_POST['title']) && is_string($_POST['title'])) ? trim($_POST['title']) : '';
        if ($title === '') {
            redirectWithFlash($back, 'error', 'กรุณากรอกหัวข้อแบนเนอร์');
        }
        if (strlen($title) > 255) {
            redirectWithFlash($back, 'error', 'หัวข้อแบนเนอร์ยาวเกินไป (ไม่เกิน 255 ตัวอักษร)');
        }
        $subtitle = (isset($_POST['subtitle']) && is_string($_POST['subtitle'])) ? trim($_POST['subtitle']) : '';
        if (strlen($subtitle) > 255) {
            redirectWithFlash($back, 'error', 'ข้อความรองยาวเกินไป (ไม่เกิน 255 ตัวอักษร)');
        }
        $linkUrl = (isset($_POST['link_url']) && is_string($_POST['link_url'])) ? trim($_POST['link_url']) : '';
        if (!$this->isSafeBannerLink($linkUrl)) {
            redirectWithFlash($back, 'error', 'ลิงก์ต้องขึ้นต้นด้วย ?page= (ในระบบ) หรือ https:// เท่านั้น');
        }
        $data = array(
            'title'        => $title,
            'subtitle'     => $subtitle,
            'link_url'     => $linkUrl,
            'sort_order'   => (isset($_POST['sort_order']) && is_string($_POST['sort_order'])) ? intval($_POST['sort_order']) : 0,
            'show_caption' => !empty($_POST['show_caption']) ? 1 : 0,
        );
        if (isset($_FILES['image'])) {
            $token  = 'ban_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
            $result = FileUpload::uploadImageOnly($_FILES['image'], 'banners', $token);
            if (!$result['success']) {
                redirectWithFlash($back, 'error', 'รูปภาพ: ' . $result['error']);
            }
            if ($result['filename'] !== '') {
                $data['image']      = $result['filename'];
                $data['image_name'] = $result['original_name'];
            }
        }
        if (!$this->bannerModel->update($id, $data)) {
            if (!empty($data['image'])) FileUpload::deleteFile($data['image']);
            redirectWithFlash($back, 'error', 'บันทึกการแก้ไขไม่สำเร็จ กรุณาลองใหม่');
        }
        if (!empty($data['image']) && !empty($row['image'])) FileUpload::deleteFile($row['image']);
        redirectWithFlash($back, 'success', 'แก้ไขแบนเนอร์สำเร็จ');
    }

    // ทิ้งข้อมูลที่ค้างใน output buffer (เช่น BOM/ช่องว่างจากไฟล์ include) และกัน zlib บีบอัด ก่อนส่งไฟล์ไบนารี
    private function cleanOutputBuffers() {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        @ini_set('zlib.output_compression', '0');
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
    }

    // หน้าวินิจฉัยแบบอ่านอย่างเดียว (เฉพาะ admin): ?page=announcements&action=view_banner&id=N&diag=1
    private function bannerDiag($row, $id) {
        $stray = '';
        while (ob_get_level() > 0) {
            $stray = ob_get_clean() . $stray;
        }
        $hf = '';
        $hl = 0;
        $sent = headers_sent($hf, $hl);
        $lines = array();
        $lines[] = 'view_banner diagnostic (read-only) id=' . $id;
        $lines[] = 'PHP ' . PHP_VERSION . ' / ' . PHP_OS;
        $lines[] = 'bytes buffered before output: ' . strlen($stray) . ($stray !== '' ? ' (hex ' . bin2hex(substr($stray, 0, 16)) . ')' : '');
        $lines[] = 'headers_sent: ' . ($sent ? 'YES at ' . $hf . ':' . $hl : 'no');
        $ini = array('output_buffering', 'zlib.output_compression', 'display_errors', 'error_reporting', 'file_uploads', 'upload_max_filesize');
        foreach ($ini as $k) {
            $lines[] = 'ini ' . $k . ' = ' . var_export(ini_get($k), true);
        }
        $lines[] = 'UPLOAD_DIR = ' . UPLOAD_DIR;
        if (!$row) {
            $lines[] = 'banner row: NOT FOUND';
        } else {
            $rel  = isset($row['image']) ? $row['image'] : '';
            $full = UPLOAD_DIR . $rel;
            $lines[] = 'banner row: found, is_active=' . $row['is_active'];
            $lines[] = 'image (db) = ' . $rel;
            $lines[] = 'full path = ' . $full;
            $lines[] = 'is_file = ' . (is_file($full) ? 'yes' : 'NO') . ', readable = ' . (is_readable($full) ? 'yes' : 'NO');
            if (is_file($full)) {
                $lines[] = 'size = ' . filesize($full) . ' bytes';
                $fp = @fopen($full, 'rb');
                $head = $fp ? fread($fp, 8) : '';
                if ($fp) fclose($fp);
                $sig = 'unknown';
                if (substr($head, 0, 3) === "\xFF\xD8\xFF") $sig = 'JPEG';
                if (substr($head, 0, 8) === "\x89PNG\r\n\x1a\n") $sig = 'PNG';
                $lines[] = 'signature = ' . $sig . ' (hex ' . bin2hex($head) . ')';
            }
        }
        if (!$sent) {
            header('Content-Type: text/plain; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
        }
        echo implode("\n", $lines) . "\n";
        exit;
    }

    public function view_banner() {
        Auth::requireLogin();
        $id  = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $row = $this->bannerModel->getById($id);
        if (isset($_GET['diag']) && Auth::hasRole('admin')) {
            $this->bannerDiag($row, $id);
        }
        $this->cleanOutputBuffers();
        $this->serveField($row, 'image', 'image_name', 'ไม่พบรูปภาพ');
    }

    public function video_attachment() {
        Auth::requireLogin();
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $this->serveField($this->videoModel->getById($id), 'attachment', 'attachment_name', 'ไม่พบไฟล์แนบ');
    }
}
