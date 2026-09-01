<?php
class VideoModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getActive($limit = 20) {
        $limit = intval($limit);
        return $this->db->fetchAll(
            "SELECT v.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS author_name
             FROM tutorial_videos v
             JOIN users u ON u.id = v.created_by
             WHERE v.is_active = 1
             ORDER BY v.created_at DESC
             LIMIT $limit"
        );
    }

    public function getAll() {
        return $this->db->fetchAll(
            "SELECT v.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS author_name
             FROM tutorial_videos v
             JOIN users u ON u.id = v.created_by
             ORDER BY v.created_at DESC"
        );
    }

    public function getById($id) {
        $id = intval($id);
        return $this->db->fetchOne(
            "SELECT v.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS author_name
             FROM tutorial_videos v
             JOIN users u ON u.id = v.created_by
             WHERE v.id = $id"
        );
    }

    // $data['attachment']/['attachment_name'] = ไฟล์ PDF แนบประกอบวีดีโอ (ไม่บังคับ)
    public function create($data, $userId) {
        $title       = $this->db->escape($data['title']);
        $videoUrl    = $this->db->escape($data['video_url']);
        $description = $this->db->escape(isset($data['description'])     ? $data['description']     : '');
        $attachment  = $this->db->escape(isset($data['attachment'])      ? $data['attachment']      : '');
        $attachName  = $this->db->escape(isset($data['attachment_name']) ? $data['attachment_name'] : '');
        $userId      = intval($userId);
        $this->db->query(
            "INSERT INTO tutorial_videos (title, video_url, description, attachment, attachment_name, created_by)
             VALUES ('$title', '$videoUrl', '$description', '$attachment', '$attachName', $userId)"
        );
        return $this->db->insertId();
    }

    public function delete($id) {
        $this->db->query("UPDATE tutorial_videos SET is_active = 0 WHERE id=" . intval($id));
    }
}
