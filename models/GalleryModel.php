<?php
// ตาราง gallery_images / image_faces (ดู sql/face_search.sql)
class GalleryModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function insertImage($filename, $thumb, $originalName, $faceCount, $userId, $folderId = 0) {
        $now = date('Y-m-d H:i:s');
        $f   = $this->db->escape($filename);
        $t   = ($thumb !== '') ? "'" . $this->db->escape($thumb) . "'" : 'NULL';
        $o   = $this->db->escape($originalName);
        $ok  = $this->db->query(
            "INSERT INTO gallery_images (filename, thumb, original_name, face_count, uploaded_by, created_at)
             VALUES ('$f', $t, '$o', " . intval($faceCount) . ", " . intval($userId) . ", '$now')"
        );
        // folder_id ตั้งแยกหลัง INSERT เพื่อให้ INSERT เดิมทำงานได้แม้ยังไม่ได้รัน face_search_folders.sql
        if ($ok && intval($folderId) > 0) {
            $newId = intval($this->db->insertId());
            if (!$this->db->query("UPDATE gallery_images SET folder_id=" . intval($folderId) . " WHERE id=$newId")) {
                $this->db->query("DELETE FROM gallery_images WHERE id=$newId");
                return 0;
            }
            return $newId;
        }
        if (!$ok) return 0;
        return intval($this->db->insertId());
    }

    public function insertFace($imageId, $descriptorStr, $box) {
        $d  = $this->db->escape($descriptorStr);
        $b  = ($box !== '') ? "'" . $this->db->escape($box) . "'" : 'NULL';
        return (bool) $this->db->query(
            "INSERT INTO image_faces (image_id, descriptor, box) VALUES (" . intval($imageId) . ", '$d', $b)"
        );
    }

    // ลบรูปและใบหน้าของรูปนั้น คืนแถวรูปเดิม (ผู้เรียกลบไฟล์) หรือ null ถ้าไม่พบ
    public function deleteImage($id) {
        $id  = intval($id);
        $row = $this->findImage($id);
        if (!$row) return null;
        $this->db->query("DELETE FROM image_faces WHERE image_id=$id");
        $ok = $this->db->query("DELETE FROM gallery_images WHERE id=$id");
        if (!$ok) return false;
        return $row;
    }

    public function findImage($id) {
        return $this->db->fetchOne("SELECT * FROM gallery_images WHERE id=" . intval($id));
    }

    public function countImages() {
        $r = $this->db->fetchOne("SELECT COUNT(*) AS c FROM gallery_images");
        return $r ? intval($r['c']) : 0;
    }

    public function countFaces() {
        $r = $this->db->fetchOne("SELECT COUNT(*) AS c FROM image_faces");
        return $r ? intval($r['c']) : 0;
    }

    public function listImages($offset, $limit) {
        return $this->db->fetchAll(
            "SELECT g.*, u.prefix, u.firstname, u.lastname
             FROM gallery_images g LEFT JOIN users u ON u.id = g.uploaded_by
             ORDER BY g.id DESC LIMIT " . intval($offset) . ", " . intval($limit)
        );
    }

    // อ่านใบหน้าทั้งหมดทีละชุด (เรียงตาม id) $afterId = id สุดท้ายของชุดก่อนหน้า (เริ่มต้น 0)
    public function faceChunk($afterId, $size) {
        return $this->db->fetchAll(
            "SELECT id, image_id, descriptor FROM image_faces
             WHERE id > " . intval($afterId) . " ORDER BY id ASC LIMIT " . intval($size)
        );
    }

    // ข้อมูลรูปหลายรูปตาม id (ใช้แสดงผลค้นหา)
    public function imagesByIds($ids) {
        $clean = array();
        foreach ($ids as $i) { $clean[] = intval($i); }
        if (empty($clean)) return array();
        return $this->db->fetchAll(
            "SELECT id, original_name, created_at, face_count FROM gallery_images
             WHERE id IN (" . implode(',', $clean) . ")"
        );
    }
    // ---------- โฟลเดอร์ (ดู sql/face_search_folders.sql) ----------

    // true เมื่อรัน face_search_folders.sql แล้ว (มีคอลัมน์ folder_id และตาราง gallery_folders)
    public function foldersReady() {
        $c = $this->db->fetchOne("SHOW COLUMNS FROM gallery_images LIKE 'folder_id'");
        if (!$c) return false;
        $t = $this->db->fetchOne("SHOW TABLES LIKE 'gallery_folders'");
        return $t ? true : false;
    }

    public function listFolders() {
        return $this->db->fetchAll("SELECT id, name FROM gallery_folders ORDER BY name ASC");
    }

    public function findFolder($id) {
        return $this->db->fetchOne("SELECT id, name FROM gallery_folders WHERE id=" . intval($id));
    }

    // คืน array(folder_id => จำนวนรูป) (0 = ไม่อยู่ในโฟลเดอร์)
    public function folderCounts() {
        $out  = array();
        $rows = $this->db->fetchAll("SELECT folder_id, COUNT(*) AS c FROM gallery_images GROUP BY folder_id");
        foreach ($rows as $r) { $out[intval($r['folder_id'])] = intval($r['c']); }
        return $out;
    }

    // คืน id โฟลเดอร์ใหม่ / 0 = ล้มเหลว / -1 = ชื่อซ้ำ
    public function createFolder($name, $userId) {
        if ($this->folderNameExists($name, 0)) return -1;
        $n  = $this->db->escape($name);
        $ok = $this->db->query("INSERT INTO gallery_folders (name, created_by, created_at) VALUES ('$n', " . intval($userId) . ", '" . date('Y-m-d H:i:s') . "')");
        return $ok ? intval($this->db->insertId()) : 0;
    }

    // true = สำเร็จ / false = ล้มเหลว / -1 = ชื่อซ้ำ
    public function renameFolder($id, $name) {
        if ($this->folderNameExists($name, $id)) return -1;
        $n = $this->db->escape($name);
        return (bool) $this->db->query("UPDATE gallery_folders SET name='$n' WHERE id=" . intval($id));
    }

    private function folderNameExists($name, $exceptId) {
        $n = $this->db->escape($name);
        $r = $this->db->fetchOne("SELECT id FROM gallery_folders WHERE name='$n' AND id<>" . intval($exceptId) . " LIMIT 1");
        return $r ? true : false;
    }

    // ลบโฟลเดอร์ รูปข้างในถูกย้ายกลับเป็น "ไม่อยู่ในโฟลเดอร์" (ไม่ลบรูป) ย้ายรูปสำเร็จก่อนจึงลบโฟลเดอร์
    public function deleteFolder($id) {
        $id = intval($id);
        if (!$this->db->query("UPDATE gallery_images SET folder_id=0 WHERE folder_id=$id")) return false;
        return (bool) $this->db->query("DELETE FROM gallery_folders WHERE id=$id");
    }

    // ย้ายรูปหลายรูปเข้าโฟลเดอร์ (0 = ออกจากโฟลเดอร์) คืนจำนวนรูปที่ย้าย หรือ false เมื่อล้มเหลว
    public function moveImages($ids, $folderId) {
        $clean = array();
        foreach ($ids as $i) { $i = intval($i); if ($i > 0) $clean[$i] = $i; }
        if (empty($clean)) return 0;
        $ok = $this->db->query("UPDATE gallery_images SET folder_id=" . intval($folderId) . " WHERE id IN (" . implode(',', $clean) . ")");
        if (!$ok) return false;
        return count($clean);
    }

    // $folder: 'all' = ทุกรูป, 0 = ไม่อยู่ในโฟลเดอร์, N = โฟลเดอร์ N
    private function folderWhere($folder) {
        return ($folder === 'all') ? '' : ' WHERE folder_id=' . intval($folder);
    }

    public function countByFolder($folder) {
        $r = $this->db->fetchOne("SELECT COUNT(*) AS c FROM gallery_images" . $this->folderWhere($folder));
        return $r ? intval($r['c']) : 0;
    }

    public function listByFolder($folder, $offset, $limit) {
        return $this->db->fetchAll(
            "SELECT id, original_name, thumb, face_count, created_at FROM gallery_images" . $this->folderWhere($folder)
            . " ORDER BY id DESC LIMIT " . intval($offset) . ", " . intval($limit)
        );
    }
}
