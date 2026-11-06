<?php
class DashboardController extends Controller {

    public function __construct($db) {
        parent::__construct($db);
    }

    public function index() {
        $announcementModel = new AnnouncementModel($this->db);
        $videoModel        = new VideoModel($this->db);

        $announcements = $announcementModel->getActive(10);
        $videos        = $videoModel->getActive(20);

        $pageTitle = 'หน้าหลัก';
        $this->render('dashboard/index', get_defined_vars());
    }
}
