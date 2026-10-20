<?php
class AnnouncementController extends Controller {

    private $announcementModel;
    private $videoModel;

    public function __construct($db) {
        parent::__construct($db);
        $this->announcementModel = new AnnouncementModel($db);
        $this->videoModel        = new VideoModel($db);
    }

    public function index() {
        Auth::requireRole('admin');
        $announcements = $this->announcementModel->getAll();
        $videos        = $this->videoModel->getAll();
        $pageTitle     = 'จัดการหน้าหลัก';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/announcements/index.php';
        include 'views/layout/footer.php';
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
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/announcements/detail.php';
        include 'views/layout/footer.php';
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
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/announcements/video_detail.php';
        include 'views/layout/footer.php';
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

    public function video_attachment() {
        Auth::requireLogin();
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $this->serveField($this->videoModel->getById($id), 'attachment', 'attachment_name', 'ไม่พบไฟล์แนบ');
    }
}
