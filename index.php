<?php
header('Content-Type: text/html; charset=UTF-8');

require_once 'config/config.php';

// ความปลอดภัยของ session cookie
@ini_set('session.cookie_httponly', '1');
@ini_set('session.use_only_cookies', '1');
session_start();

// ป้องกัน magic_quotes_gpc (PHP 5.2 อาจเปิดอยู่) ไม่ให้ escape ซ้ำซ้อนกับ $db->escape()
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
require_once 'helpers/Ticket.php';
require_once 'helpers/ActivityLog.php';
require_once 'helpers/Auth.php';
require_once 'helpers/FileUpload.php';
require_once 'helpers/Notification.php';
require_once 'helpers/ExcelExport.php';
require_once 'helpers/functions.php';
require_once 'helpers/asset_helpers.php';
require_once 'helpers/AssetExcel.php';

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
require_once 'models/SatisfactionModel.php';
require_once 'models/AssetModel.php';

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
require_once 'controllers/SatisfactionController.php';
require_once 'controllers/AssetController.php';

$db = new Model();
$router = new Router($db);
$router->dispatch();
