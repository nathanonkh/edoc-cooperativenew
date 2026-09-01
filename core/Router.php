<?php
class Router {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function dispatch() {
        $page   = isset($_GET['page'])   ? $_GET['page']   : 'dashboard';
        $action = isset($_GET['action']) ? $_GET['action'] : 'index';

        if ($action === 'logout') {
            Auth::logout();
        }

        switch ($page) {
            case 'login':
                $ctrl = new AuthController($this->db);
                $ctrl->$action();
                break;

            case 'dashboard':
                Auth::requireLogin();
                $ctrl = new DashboardController($this->db);
                $ctrl->index();
                break;

            case 'documents':
                Auth::requireLogin();
                $ctrl = new DocumentController($this->db);
                $validActions = array(
                    'index','create','store','detail','edit',
                    'inspect','approve','bulk_approve','operate',
                    'revision','resubmit','view_file',
                    'ajax_cooperatives','ajax_status','ajax_unread_count','ajax_list'
                );
                $action = in_array($action, $validActions) ? $action : 'index';
                $ctrl->$action();
                break;

            case 'issues':
                Auth::requireLogin();
                $ctrl = new IssueController($this->db);
                $validActions = array(
                    'index','create','store','detail',
                    'start','escalate','accept_central','complete',
                    'view_attachment','ajax_list'
                );
                $action = in_array($action, $validActions) ? $action : 'index';
                $ctrl->$action();
                break;

            case 'reports':
                Auth::requireLogin();
                $ctrl = new ReportController($this->db);
                $validActions = array('index','ajax_list');
                $action = in_array($action, $validActions) ? $action : 'index';
                $ctrl->$action();
                break;

            case 'issue_reports':
                Auth::requireLogin();
                $ctrl = new IssueReportController($this->db);
                $validActions = array('index','ajax_list');
                $action = in_array($action, $validActions) ? $action : 'index';
                $ctrl->$action();
                break;

            case 'users':
                Auth::requireLogin();
                $ctrl = new UserController($this->db);
                $validActions = array('index','create','store','edit','update','delete','profile','update_profile','ajax_list');
                $action = in_array($action, $validActions) ? $action : 'index';
                $ctrl->$action();
                break;

            case 'cooperatives':
                Auth::requireRole(array('admin'));
                $ctrl = new CooperativeController($this->db);
                $validActions = array('index','create','store','edit','update','delete','ajax_list');
                $action = in_array($action, $validActions) ? $action : 'index';
                $ctrl->$action();
                break;

            case 'notifications':
                Auth::requireLogin();
                $ctrl = new NotificationController($this->db);
                $validActions = array('index','read','mark_all_read');
                $action = in_array($action, $validActions) ? $action : 'index';
                $ctrl->$action();
                break;

            case 'announcements':
                // เดิม: ทั้งหน้า 'announcements' ต้องเป็น admin เท่านั้น ทำให้ผู้ใช้ทั่วไป
                // คลิกเข้าไปดูรายละเอียดประกาศ/วีดีโอ หรือเปิดไฟล์แนบไม่ได้เลย
                // ตอนนี้แยกสิทธิ์: การจัดการ (เพิ่ม/ลบ) ยังคง admin เท่านั้น
                // ส่วนการดูรายละเอียดและเปิดไฟล์แนบ ผู้ใช้ที่ล็อกอินแล้วทุกคนเข้าถึงได้
                Auth::requireLogin();
                $ctrl = new AnnouncementController($this->db);
                $validActions = array(
                    'index','store','delete','store_video','delete_video',
                    'detail','video_detail','view_attachment','view_image','video_attachment'
                );
                $action = in_array($action, $validActions) ? $action : 'index';

                $adminOnlyActions = array('index','store','delete','store_video','delete_video');
                if (in_array($action, $adminOnlyActions)) {
                    Auth::requireRole(array('admin'));
                }
                $ctrl->$action();
                break;

            default:
                Auth::requireLogin();
                $ctrl = new DashboardController($this->db);
                $ctrl->index();
                break;
        }
    }
}
