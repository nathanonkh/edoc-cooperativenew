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
        if (empty($_POST['title']) || empty($_POST['content'])) {
            redirectWithFlash(APP_URL . '/?page=announcements', 'error', 'กรุณากรอกหัวข้อและเนื้อหาประกาศ');
        }

        // token เฉพาะกิจสำหรับตั้งชื่อไฟล์ให้ไม่ซ้ำกัน (ประกาศไม่มีเลขที่อ้างอิงเหมือนเอกสาร/แจ้งปัญหา)
        $token = 'ann_' . date('YmdHis') . '_' . mt_rand(1000, 9999);

        $attachmentPath = '';
        $attachmentName = '';
        if (isset($_FILES['attachment'])) {
            $result = FileUpload::uploadPdfOnly($_FILES['attachment'], 'announcements', $token);
            if (!$result['success']) {
                redirectWithFlash(APP_URL . '/?page=announcements', 'error', 'ไฟล์ PDF: ' . $result['error']);
            }
            $attachmentPath = $result['filename'];
            $attachmentName = $result['original_name'];
        }

        $imagePath = '';
        $imageName = '';
        if (isset($_FILES['image'])) {
            $result = FileUpload::uploadImageOnly($_FILES['image'], 'announcements', $token);
            if (!$result['success']) {
                redirectWithFlash(APP_URL . '/?page=announcements', 'error', 'รูปภาพ: ' . $result['error']);
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
        redirectWithFlash(APP_URL . '/?page=announcements', 'success', 'เพิ่มประกาศสำเร็จ');
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
        if (empty($_POST['title']) || empty($_POST['video_url'])) {
            redirectWithFlash(APP_URL . '/?page=announcements', 'error', 'กรุณากรอกหัวข้อและลิงก์วีดีโอ');
        }

        $token = 'vid_' . date('YmdHis') . '_' . mt_rand(1000, 9999);

        $attachmentPath = '';
        $attachmentName = '';
        if (isset($_FILES['attachment'])) {
            $result = FileUpload::uploadPdfOnly($_FILES['attachment'], 'videos', $token);
            if (!$result['success']) {
                redirectWithFlash(APP_URL . '/?page=announcements', 'error', 'ไฟล์ PDF: ' . $result['error']);
            }
            $attachmentPath = $result['filename'];
            $attachmentName = $result['original_name'];
        }

        $data                    = $_POST;
        $data['attachment']      = $attachmentPath;
        $data['attachment_name'] = $attachmentName;

        $this->videoModel->create($data, $_SESSION['user_id']);
        redirectWithFlash(APP_URL . '/?page=announcements', 'success', 'เพิ่มวีดีโอสำเร็จ');
    }

    public function delete_video() {
        Auth::requireRole('admin');
        Auth::checkCsrf();
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $this->videoModel->delete($id);
        redirectWithFlash(APP_URL . '/?page=announcements', 'success', 'ลบวีดีโอแล้ว');
    }

    // ---------------------------------------------------------------
    // หน้ารายละเอียด — เปิดให้ผู้ใช้ที่ล็อกอินแล้วทุกคนเข้าดูได้ (ไม่จำกัดเฉพาะ admin)
    // ---------------------------------------------------------------

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

    // ---------------------------------------------------------------
    // เสิร์ฟไฟล์แนบ (เหมือนแนวทางเดียวกับ IssueController::view_attachment)
    // ---------------------------------------------------------------

    public function view_attachment() {
        Auth::requireLogin();
        $id           = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $announcement = $this->announcementModel->getById($id);
        if (!$announcement || empty($announcement['attachment'])) {
            exit('ไม่พบไฟล์แนบ');
        }

        $filePath = UPLOAD_DIR . $announcement['attachment'];
        if (!file_exists($filePath)) {
            exit('ไม่พบไฟล์ในระบบ');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($announcement['attachment_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($filePath);
        exit;
    }

    public function view_image() {
        Auth::requireLogin();
        $id           = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $announcement = $this->announcementModel->getById($id);
        if (!$announcement || empty($announcement['image'])) {
            exit('ไม่พบรูปภาพ');
        }

        $filePath = UPLOAD_DIR . $announcement['image'];
        if (!file_exists($filePath)) {
            exit('ไม่พบไฟล์ในระบบ');
        }

        $ext     = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeMap = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png');
        $mime    = isset($mimeMap[$ext]) ? $mimeMap[$ext] : 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($announcement['image_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($filePath);
        exit;
    }

    public function video_attachment() {
        Auth::requireLogin();
        $id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $video = $this->videoModel->getById($id);
        if (!$video || empty($video['attachment'])) {
            exit('ไม่พบไฟล์แนบ');
        }

        $filePath = UPLOAD_DIR . $video['attachment'];
        if (!file_exists($filePath)) {
            exit('ไม่พบไฟล์ในระบบ');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($video['attachment_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($filePath);
        exit;
    }
}
