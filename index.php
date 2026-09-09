<?php
header('Content-Type: text/html; charset=UTF-8');

require_once 'config/config.php';
session_start();

// =====================================================
// แก้ปัญหา magic_quotes_gpc (PHP 5.2.3 / AppServ อาจเปิดใช้งานอยู่โดย default)
// ถ้าเปิดอยู่ ค่าจาก $_GET/$_POST/$_COOKIE จะถูกใส่ backslash อัตโนมัติ
// ซึ่งจะไปชนกับ $db->escape() (mysql_real_escape_string) ที่เรียกซ้ำอีกชั้น
// ทำให้ข้อมูลที่มีเครื่องหมาย ' หรือ " ถูกบันทึกผิดเพี้ยน (backslash ซ้อน)
// โค้ดส่วนนี้จะ strip slashes ออกก่อน ถ้าตรวจพบว่า magic_quotes_gpc เปิดอยู่
// =====================================================
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    function stripslashes_deep($value) {
        return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    }
    $_GET     = stripslashes_deep($_GET);
    $_POST    = stripslashes_deep($_POST);
    $_COOKIE  = stripslashes_deep($_COOKIE);
    $_REQUEST = stripslashes_deep($_REQUEST);
}

require_once 'core/Session.php';
require_once 'core/Model.php';
require_once 'core/Controller.php';
require_once 'core/Router.php';

require_once 'helpers/PasswordHash.php';
require_once 'helpers/Auth.php';
require_once 'helpers/FileUpload.php';
require_once 'helpers/Notification.php';
require_once 'helpers/ExcelExport.php';
require_once 'helpers/functions.php';

require_once 'models/UserModel.php';
require_once 'models/DocumentModel.php';
require_once 'models/CooperativeModel.php';
require_once 'models/NotificationModel.php';
require_once 'models/ReportModel.php';
require_once 'models/IssueModel.php';
require_once 'models/IssueReportModel.php';
require_once 'models/AnnouncementModel.php';
require_once 'models/VideoModel.php';
require_once 'models/InternalServiceModel.php';

require_once 'controllers/AuthController.php';
require_once 'controllers/DashboardController.php';
require_once 'controllers/DocumentController.php';
require_once 'controllers/UserController.php';
require_once 'controllers/CooperativeController.php';
require_once 'controllers/NotificationController.php';
require_once 'controllers/ReportController.php';
require_once 'controllers/IssueController.php';
require_once 'controllers/IssueReportController.php';
require_once 'controllers/AnnouncementController.php';
require_once 'controllers/InternalServiceController.php';

$db = new Model();
$router = new Router($db);
$router->dispatch();
