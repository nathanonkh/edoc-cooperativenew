<?php
class Router {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // เรียก action เฉพาะที่อยู่ใน whitelist เท่านั้น (ค่าแรกของ array คือ default)
    private function call($ctrl, $action, $validActions) {
        if (!in_array($action, $validActions)) {
            $action = $validActions[0];
        }
        $ctrl->$action();
    }

    public function dispatch() {
        $page   = isset($_GET['page'])   ? $_GET['page']   : 'dashboard';
        $action = isset($_GET['action']) ? $_GET['action'] : 'index';
        if (!is_string($page))   $page   = 'dashboard';
        if (!is_string($action)) $action = 'index';

        if ($action === 'logout') {
            Auth::logout();
        }

        switch ($page) {
            case 'login':
                $this->call(new AuthController($this->db), $action, array('index', 'login'));
                break;

            case 'dashboard':
                Auth::requireLogin();
                $ctrl = new DashboardController($this->db);
                $ctrl->index();
                break;

            case 'documents':
                Auth::requireLogin();
                $this->call(new DocumentController($this->db), $action, array(
                    'index','create','store','detail','edit',
                    'inspect','approve','bulk_approve','operate',
                    'revision','resubmit','view_file',
                    'ajax_cooperatives','ajax_status','ajax_unread_count','ajax_mark_read','ajax_list'
                ));
                break;

            case 'issues':
                Auth::requireLogin();
                $this->call(new IssueController($this->db), $action, array(
                    'index','create','store','detail',
                    'start','escalate','accept_central','complete',
                    'view_attachment','ajax_list'
                ));
                break;

            case 'internal_services':
                Auth::requireLogin();
                $this->call(new InternalServiceController($this->db), $action, array(
                    'index','create','store','detail',
                    'start','complete',
                    'view_attachment','ajax_list','export_excel'
                ));
                break;

            case 'reports':
                Auth::requireLogin();
                $this->call(new ReportController($this->db), $action, array('index','ajax_list','export_excel'));
                break;

            case 'issue_reports':
                Auth::requireLogin();
                $this->call(new IssueReportController($this->db), $action, array('index','ajax_list','export_excel'));
                break;

            case 'users':
                Auth::requireLogin();
                $this->call(new UserController($this->db), $action, array(
                    'index','create','store','edit','update','delete','profile','update_profile','ajax_list'
                ));
                break;

            case 'cooperatives':
                Auth::requireRole(array('admin'));
                $this->call(new CooperativeController($this->db), $action, array(
                    'index','create','store','edit','update','delete','ajax_list'
                ));
                break;

            case 'notifications':
                Auth::requireLogin();
                $this->call(new NotificationController($this->db), $action, array('index','read','mark_all_read'));
                break;

            case 'announcements':
                // จัดการ (เพิ่ม/ลบ) เฉพาะ admin — ดูรายละเอียดและไฟล์แนบ ผู้ใช้ที่ล็อกอินแล้วทุกคนเข้าได้
                Auth::requireLogin();
                $validActions = array(
                    'index','store','delete','store_video','delete_video',
                    'detail','video_detail','view_attachment','view_image','video_attachment'
                );
                if (!in_array($action, $validActions)) $action = 'index';
                $adminOnlyActions = array('index','store','delete','store_video','delete_video');
                if (in_array($action, $adminOnlyActions)) {
                    Auth::requireRole(array('admin'));
                }
                $ctrl = new AnnouncementController($this->db);
                $ctrl->$action();
                break;

            case 'satisfaction':
                Auth::requireLogin();
                $validActions = array('index','store','results','export_excel');
                if (!in_array($action, $validActions)) $action = 'index';
                if ($action === 'results' || $action === 'export_excel') {
                    Auth::requireRole(array('admin'));
                }
                $ctrl = new SatisfactionController($this->db);
                $ctrl->$action();
                break;

            case 'assets':
                // ครุภัณฑ์: ผู้ใช้ที่ล็อกอินดู/สแกนตรวจนับได้ — สิทธิ์จัดการตรวจใน AssetController
                Auth::requireLogin();
                $this->call(new AssetController($this->db), $action, array(
                    'index','ajax_list','create','store','detail','edit','update','delete',
                    'print_labels','scanner','scan','check',
                    'rounds','round_store','round_detail','round_close','round_export','export_excel'
                ));
                break;

            default:
                Auth::requireLogin();
                $ctrl = new DashboardController($this->db);
                $ctrl->index();
                break;
        }
    }
}
