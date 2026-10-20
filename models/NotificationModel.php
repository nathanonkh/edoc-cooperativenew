<?php
class NotificationModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ใช้ query เดียวกับ Notification::getRecent() (เดิมเขียน SQL ซ้ำกัน)
    public function getByUser($userId, $limit = 50) {
        return Notification::getRecent($this->db, $userId, $limit);
    }
}
