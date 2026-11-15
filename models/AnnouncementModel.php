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

    // แก้ไขประกาศ: ไฟล์แนบ/รูปเปลี่ยนเฉพาะเมื่อส่งไฟล์ใหม่มา ($data['attachment'], $data['image'] ไม่ว่าง)
    public function update($id, $data) {
        $id       = intval($id);
        $title    = $this->db->escape($data['title']);
        $content  = $this->db->escape($data['content']);
        $isPinned = !empty($data['is_pinned']) ? 1 : 0;
        $sets     = "title='$title', content='$content', is_pinned=$isPinned";
        if (!empty($data['attachment'])) {
            $sets .= ", attachment='" . $this->db->escape($data['attachment']) . "', attachment_name='"
                   . $this->db->escape(isset($data['attachment_name']) ? $data['attachment_name'] : '') . "'";
        }
        if (!empty($data['image'])) {
            $sets .= ", image='" . $this->db->escape($data['image']) . "', image_name='"
                   . $this->db->escape(isset($data['image_name']) ? $data['image_name'] : '') . "'";
        }
        return $this->db->query("UPDATE announcements SET $sets WHERE id=$id");
    }
}
