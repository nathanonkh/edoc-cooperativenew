<?php
// ฐานร่วมของ AnnouncementModel / VideoModel (เดิมเมธอด getActive/getAll/getById/delete ต่างกันแค่ตาราง alias และ ORDER BY)
// create() ของแต่ละตัวต่างกัน (ฟิลด์ไม่เหมือนกัน) จึงคงไว้ในคลาสลูก
class ContentModel {

    protected $db;
    protected $table       = '';
    protected $alias       = '';
    protected $orderBy     = '';
    protected $activeLimit = 10;

    public function __construct($db) {
        $this->db = $db;
    }

    private function baseSelect() {
        $a = $this->alias;
        return "SELECT $a.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS author_name
             FROM {$this->table} $a
             JOIN users u ON u.id = $a.created_by";
    }

    public function getActive($limit = null) {
        $limit = ($limit === null) ? $this->activeLimit : intval($limit);
        $a = $this->alias;
        return $this->db->fetchAll(
            $this->baseSelect() . "
             WHERE $a.is_active = 1
             ORDER BY {$this->orderBy}
             LIMIT $limit"
        );
    }

    public function getAll() {
        return $this->db->fetchAll(
            $this->baseSelect() . "
             ORDER BY {$this->orderBy}"
        );
    }

    public function getById($id) {
        $id = intval($id);
        $a  = $this->alias;
        return $this->db->fetchOne(
            $this->baseSelect() . "
             WHERE $a.id = $id"
        );
    }

    public function delete($id) {
        $this->db->query("UPDATE {$this->table} SET is_active = 0 WHERE id=" . intval($id));
    }
}
