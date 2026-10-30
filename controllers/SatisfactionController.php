<?php
class SatisfactionController extends Controller {

    private $model;

    public function __construct($db) {
        parent::__construct($db);
        $this->model = new SatisfactionModel($db);
    }

    // แบบประเมิน (ผู้ใช้ที่ล็อกอินทุกคน)
    public function index() {
        Auth::requireLogin();
        $user      = Auth::currentUser();
        $history   = $this->model->getByUser($user['id'], 5);
        $pageTitle = 'ประเมินความพึงพอใจ';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/satisfaction/index.php';
        include 'views/layout/footer.php';
    }

    public function store() {
        Auth::requireLogin();
        Auth::checkCsrf();
        $back = APP_URL . '/?page=satisfaction';

        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        if ($rating < 1 || $rating > 5) {
            redirectWithFlash($back, 'error', 'กรุณาเลือกคะแนนความพึงพอใจ 1-5 ดาว');
        }

        $comment = (isset($_POST['comment']) && is_string($_POST['comment'])) ? trim($_POST['comment']) : '';
        if (safeStrlen($comment) > 1000) {
            $comment = safeSubstr($comment, 0, 1000);
        }

        $user = Auth::currentUser();
        // กันกดส่งซ้ำรัว ๆ (ส่งได้อีกครั้งหลังผ่านไป 1 นาที)
        if ($this->model->secondsSinceLast($user['id']) < 60) {
            redirectWithFlash($back, 'warning', 'คุณเพิ่งส่งแบบประเมิน กรุณารอสักครู่แล้วลองใหม่');
        }

        if (!$this->model->create($user['id'], $user['office_name'], $rating, $comment)) {
            redirectWithFlash($back, 'error', 'บันทึกไม่สำเร็จ (ตรวจสอบว่ารัน migration_satisfaction.sql แล้ว)');
        }
        redirectWithFlash($back, 'success', 'ขอบคุณสำหรับการประเมินความพึงพอใจ');
    }

    private function resultFilters() {
        $rating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
        return array(
            'rating'      => ($rating >= 1 && $rating <= 5) ? $rating : '',
            'office_name' => isset($_GET['office_name']) && is_string($_GET['office_name']) ? $_GET['office_name'] : '',
            'date_from'   => isset($_GET['date_from']) && is_string($_GET['date_from']) ? $_GET['date_from'] : '',
            'date_to'     => isset($_GET['date_to']) && is_string($_GET['date_to']) ? $_GET['date_to'] : '',
            'has_comment' => !empty($_GET['has_comment']) ? '1' : '',
        );
    }

    // ผลการประเมิน (เฉพาะ admin)
    public function results() {
        Auth::requireRole(array('admin'));
        $filters        = $this->resultFilters();
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;

        $summary    = $this->model->getSummary($filters);
        $totalItems = $this->model->countList($filters);
        $totalPages = max(1, ceil($totalItems / $perPage));
        $items      = $this->model->getList($filters, $perPage, $currentPageNum);

        $pageTitle = 'ผลการประเมินความพึงพอใจ';
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/satisfaction/results.php';
        include 'views/layout/footer.php';
    }

    public function export_excel() {
        Auth::requireRole(array('admin'));
        $user    = Auth::currentUser();
        $filters = $this->resultFilters();
        $items   = $this->model->getAllForExport($filters);

        $headers = array('#', 'วันที่', 'ผู้ประเมิน', 'สำนักงาน', 'คะแนน', 'ข้อเสนอแนะ');
        $rows    = array();
        foreach ($items as $idx => $it) {
            $rows[] = array(
                $idx + 1,
                thaiDate($it['created_at'], true),
                $it['user_name'],
                $it['office_name'],
                $it['rating'],
                $it['comment'],
            );
        }

        $sum = $this->model->getSummary($filters);
        $subtitleParts = array('ทั้งหมด ' . $sum['count'] . ' รายการ', 'คะแนนเฉลี่ย ' . number_format($sum['avg'], 2));
        if (!empty($filters['rating']))      $subtitleParts[] = 'เฉพาะ ' . $filters['rating'] . ' ดาว';
        if (!empty($filters['office_name'])) $subtitleParts[] = $filters['office_name'];
        if (!empty($filters['date_from']))   $subtitleParts[] = 'ตั้งแต่ ' . $filters['date_from'];
        if (!empty($filters['date_to']))     $subtitleParts[] = 'ถึง ' . $filters['date_to'];

        $signerRow   = getUserPosition($this->db, $user['id']);
        $signerTitle = $signerRow ? $signerRow['position'] : '';

        ExcelExport::export(
            'รายงานผลการประเมินความพึงพอใจ',
            $headers, $rows,
            $user['fullname'], $signerTitle,
            'satisfaction_' . date('Ymd'),
            implode(' | ', $subtitleParts)
        );
    }
}
