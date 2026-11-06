<?php
class AnnouncementModel extends ContentModel {

    protected $table       = 'announcements';
    protected $alias       = 'a';
    protected $orderBy     = 'a.is_pinned DESC, a.created_at DESC';
    protected $activeLimit = 10;

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
}
