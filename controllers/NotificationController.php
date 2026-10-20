<?php
class NotificationController extends Controller {

    public function __construct($db) {
        parent::__construct($db);
    }

    public function index() {
        $userId         = intval($_SESSION['user_id']);
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $offset         = ($currentPageNum - 1) * $perPage;
        $notifModel     = new NotificationModel($this->db);
        $allNotifs      = $notifModel->getByUser($userId, 1000);
        $totalItems     = count($allNotifs);
        $totalPages     = max(1, ceil($totalItems / $perPage));
        $notifications  = array_slice($allNotifs, $offset, $perPage);
        $pageTitle      = 'การแจ้งเตือน';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/notifications/index.php';
        include 'views/layout/footer.php';
    }

    // ปลายทางอ่านจากฐานข้อมูลของการแจ้งเตือนที่เป็นของผู้ใช้เอง ไม่เชื่อค่า target จาก URL
    public function read() {
        $id     = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $userId = intval($_SESSION['user_id']);

        $row = $this->db->fetchOne(
            "SELECT document_id, issue_id, service_id FROM notifications WHERE id=$id AND user_id=$userId"
        );
        if (!$row) {
            redirectWithFlash(APP_URL . '/?page=notifications', 'error', 'ไม่พบการแจ้งเตือน');
        }
        Notification::markRead($this->db, $id, $userId);

        if (!empty($row['issue_id'])) {
            $url = '?page=issues&action=detail&id=' . intval($row['issue_id']);
        } elseif (!empty($row['service_id'])) {
            $url = '?page=internal_services&action=detail&id=' . intval($row['service_id']);
        } elseif (!empty($row['document_id'])) {
            $url = '?page=documents&action=detail&id=' . intval($row['document_id']);
        } else {
            $url = '?page=notifications';
        }
        header('Location: ' . APP_URL . '/' . $url);
        exit;
    }

    public function mark_all_read() {
        $userId = intval($_SESSION['user_id']);
        Notification::markAllRead($this->db, $userId);
        redirectWithFlash(APP_URL . '/?page=notifications', 'success', 'ทำเครื่องหมายอ่านทั้งหมดแล้ว');
    }
}
