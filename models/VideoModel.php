<?php
class VideoModel extends ContentModel {

    protected $table       = 'tutorial_videos';
    protected $alias       = 'v';
    protected $orderBy     = 'v.created_at DESC';
    protected $activeLimit = 20;

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
}
