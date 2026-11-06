<?php
class Controller {

    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // layout -> view -> layout เดิมเขียนซ้ำทุก action ส่ง get_defined_vars() ของ method ผู้เรียก
    // เพื่อให้ view เห็นตัวแปรชุดเดียวกับเดิม (ใช้ชื่อ $__ ภายใน กัน extract ทับ)
    protected function render($__view, $__vars = array()) {
        // get_defined_vars() ของ PHP 5.x อาจมีคีย์ 'this' ปนมา ตัดทิ้งกันไม่ให้ extract() ยุ่งกับ $this
        unset($__vars['this'], $__vars['__view'], $__vars['__vars']);
        extract($__vars, EXTR_SKIP);
        include 'views/layout/header.php';
        include 'views/layout/navbar.php';
        include 'views/' . $__view . '.php';
        include 'views/layout/footer.php';
    }

    // อ่านค่าสตริงจาก $_GET (ไม่มี/ไม่ใช่สตริง เช่น ?keyword[]=x = '')
    protected function getQueryStr($key) {
        return (isset($_GET[$key]) && is_string($_GET[$key])) ? $_GET[$key] : '';
    }

    // ตัวกรองรายการแบบมาตรฐานของ Document/Issue (InternalService ต่างจึงมีของตนเอง)
    protected function standardListFilters() {
        return array(
            'status'    => $this->getQueryStr('status'),
            'keyword'   => $this->getQueryStr('keyword'),
            'date_from' => $this->getQueryStr('date_from'),
            'date_to'   => $this->getQueryStr('date_to'),
            'per_page'  => 10,
            'page_num'  => isset($_GET['p'])         ? max(1, intval($_GET['p'])) : 1,
        );
    }

    // อ่านตัวกรองหน้ารายงานจาก $_GET ตามรายการ key (ไม่ใช่ string = '')
    protected function readReportFilters($keys) {
        $f = array();
        foreach ($keys as $k) {
            $f[$k] = (isset($_GET[$k]) && is_string($_GET[$k])) ? $_GET[$k] : '';
        }
        return $f;
    }

    // ตัวแปรที่ view/partial รายงานใช้ (ผู้เรียกใช้ extract()) $model ต้องมี getDetailReport/getSummaryByStatus
    protected function loadReportData($model, $user, $filters) {
        $allDetails     = $model->getDetailReport($user, $filters);
        $perPage        = 10;
        $currentPageNum = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $totalItems     = count($allDetails);
        return array(
            'summary'        => $model->getSummaryByStatus($user, $filters),
            'details'        => array_slice($allDetails, ($currentPageNum - 1) * $perPage, $perPage),
            'totalItems'     => $totalItems,
            'totalPages'     => max(1, ceil($totalItems / $perPage)),
            'currentPageNum' => $currentPageNum,
        );
    }

    // ตำแหน่งผู้ลงนามในรายงาน Excel
    protected function signerTitle($userId) {
        $row = getUserPosition($this->db, $userId);
        return $row ? $row['position'] : '';
    }
}
