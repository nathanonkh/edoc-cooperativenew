<?php
class BannerModel extends ContentModel {

    protected $table       = 'banners';
    protected $alias       = 'b';
    protected $orderBy     = 'b.sort_order ASC, b.created_at DESC';
    protected $activeLimit = 10;

    // $data['image']/['image_name'] = ไฟล์รูปแบนเนอร์ (จำเป็น)
    public function create($data, $userId) {
        $title     = $this->db->escape($data['title']);
        $subtitle  = $this->db->escape(isset($data['subtitle'])   ? $data['subtitle']   : '');
        $linkUrl   = $this->db->escape(isset($data['link_url'])   ? $data['link_url']   : '');
        $image     = $this->db->escape($data['image']);
        $imageName = $this->db->escape(isset($data['image_name']) ? $data['image_name'] : '');
        $sortOrder = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
        $showCap   = !empty($data['show_caption']) ? 1 : 0;
        $userId    = intval($userId);
        $this->db->query(
            "INSERT INTO banners (title, subtitle, link_url, image, image_name, sort_order, show_caption, created_by)
             VALUES ('$title', '$subtitle', '$linkUrl', '$image', '$imageName', $sortOrder, $showCap, $userId)"
        );
        return $this->db->insertId();
    }

    // แก้ไขแบนเนอร์: รูปเปลี่ยนเฉพาะเมื่อส่งไฟล์ใหม่มา ($data['image'] ไม่ว่าง)
    public function update($id, $data) {
        $id        = intval($id);
        $title     = $this->db->escape($data['title']);
        $subtitle  = $this->db->escape(isset($data['subtitle']) ? $data['subtitle'] : '');
        $linkUrl   = $this->db->escape(isset($data['link_url']) ? $data['link_url'] : '');
        $sortOrder = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
        $showCap   = !empty($data['show_caption']) ? 1 : 0;
        $sets      = "title='$title', subtitle='$subtitle', link_url='$linkUrl', sort_order=$sortOrder, show_caption=$showCap";
        if (!empty($data['image'])) {
            $sets .= ", image='" . $this->db->escape($data['image']) . "', image_name='"
                   . $this->db->escape(isset($data['image_name']) ? $data['image_name'] : '') . "'";
        }
        return $this->db->query("UPDATE banners SET $sets WHERE id=$id");
    }
}
