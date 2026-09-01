<?php
class AnnouncementModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getActive($limit = 10) {
        $limit = intval($limit);
        return $this->db->fetchAll(
            "SELECT a.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS author_name
             FROM announcements a
             JOIN users u ON u.id = a.created_by
             WHERE a.is_active = 1
             ORDER BY a.is_pinned DESC, a.created_at DESC
             LIMIT $limit"
        );
    }

    public function getAll() {
        return $this->db->fetchAll(
            "SELECT a.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS author_name
             FROM announcements a
             JOIN users u ON u.id = a.created_by
             ORDER BY a.is_pinned DESC, a.created_at DESC"
        );
    }

    public function getById($id) {
        $id = intval($id);
        return $this->db->fetchOne(
            "SELECT a.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS author_name
             FROM announcements a
             JOIN users u ON u.id = a.created_by
             WHERE a.id = $id"
        );
    }

    // $data['attachment']/['attachment_name'] = ไฟล์ PDF แนบ (ไม่บังคับ)
    // $data['image']/['image_name']           = ไฟล์รูปภาพแนบ (ไม่บังคับ)
    public function create($data, $userId) {
        $title       = $this->db->escape($data['title']);
        $content     = $this->db->escape($data['content']);
        $isPinned    = !empty($data['is_pinned']) ? 1 : 0;
        $attachment  = $this->db->escape(isset($data['attachment'])      ? $data['attachment']      : '');
        $attachName  = $this->db->escape(isset($data['attachment_name']) ? $data['attachment_name'] : '');
        $image       = $this->db->escape(isset($data['image'])           ? $data['image']           : '');
        $imageName   = $this->db->escape(isset($data['image_name'])      ? $data['image_name']      : '');
        $userId      = intval($userId);
        $this->db->query(
            "INSERT INTO announcements
             (title, content, is_pinned, attachment, attachment_name, image, image_name, created_by)
             VALUES
             ('$title', '$content', $isPinned, '$attachment', '$attachName', '$image', '$imageName', $userId)"
        );
        return $this->db->insertId();
    }

    public function delete($id) {
        $this->db->query("UPDATE announcements SET is_active = 0 WHERE id=" . intval($id));
    }
}
