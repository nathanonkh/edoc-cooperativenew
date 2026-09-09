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

    public function read() {
        $id         = isset($_GET['id'])          ? intval($_GET['id'])          : 0;
        $targetId   = isset($_GET['target_id'])   ? intval($_GET['target_id'])   : (isset($_GET['doc_id']) ? intval($_GET['doc_id']) : 0);
        $targetType = isset($_GET['target_type']) ? $_GET['target_type']         : 'document';
        $userId     = intval($_SESSION['user_id']);
        Notification::markRead($this->db, $id, $userId);

        if ($targetType === 'issue') {
            header('Location: ' . APP_URL . '/?page=issues&action=detail&id=' . $targetId);
        } elseif ($targetType === 'service') {
            header('Location: ' . APP_URL . '/?page=internal_services&action=detail&id=' . $targetId);
        } else {
            header('Location: ' . APP_URL . '/?page=documents&action=detail&id=' . $targetId);
        }
        exit;
    }

    public function mark_all_read() {
        $userId = intval($_SESSION['user_id']);
        Notification::markAllRead($this->db, $userId);
        redirectWithFlash(APP_URL . '/?page=notifications', 'success', 'ทำเครื่องหมายอ่านทั้งหมดแล้ว');
    }
}
