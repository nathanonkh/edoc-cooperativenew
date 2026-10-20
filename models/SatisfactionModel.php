<?php
class SatisfactionModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // คืน true เมื่อบันทึกสำเร็จ
    public function create($userId, $officeName, $rating, $comment) {
        $userId = intval($userId);
        $rating = intval($rating);
        $office = $this->db->escape($officeName);
        $cm     = $this->db->escape($comment);
        $r = $this->db->query(
            "INSERT INTO satisfaction_surveys (user_id, office_name, rating, comment)
             VALUES ($userId, '$office', $rating, '$cm')"
        );
        return $r !== false;
    }

    // จำนวนวินาทีนับจากที่ผู้ใช้ส่งแบบประเมินล่าสุด (ไม่เคยส่ง = ค่ามาก) ใช้กันกดส่งซ้ำ
    public function secondsSinceLast($userId) {
        $userId = intval($userId);
        $row = $this->db->fetchOne(
            "SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(MAX(created_at)) AS s
             FROM satisfaction_surveys WHERE user_id = $userId"
        );
        if (!$row || $row['s'] === null) return 999999;
        return intval($row['s']);
    }

    public function getByUser($userId, $limit = 5) {
        $userId = intval($userId);
        $limit  = intval($limit);
        return $this->db->fetchAll(
            "SELECT * FROM satisfaction_surveys WHERE user_id = $userId ORDER BY created_at DESC LIMIT $limit"
        );
    }

    private function buildWhere($filters) {
        $where = '1=1';
        if (!empty($filters['rating'])) {
            $where .= ' AND s.rating = ' . intval($filters['rating']);
        }
        if (!empty($filters['office_name'])) {
            $where .= " AND s.office_name = '" . $this->db->escape($filters['office_name']) . "'";
        }
        if (!empty($filters['date_from'])) {
            $where .= " AND DATE(s.created_at) >= '" . $this->db->escape($filters['date_from']) . "'";
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND DATE(s.created_at) <= '" . $this->db->escape($filters['date_to']) . "'";
        }
        if (!empty($filters['has_comment'])) {
            $where .= " AND s.comment IS NOT NULL AND s.comment <> ''";
        }
        return $where;
    }

    // สรุปผล: จำนวน, ค่าเฉลี่ย, การกระจายคะแนน 1-5
    public function getSummary($filters = array()) {
        $where = $this->buildWhere($filters);
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt, AVG(s.rating) AS avg_rating,
                    SUM(s.rating = 1) AS c1, SUM(s.rating = 2) AS c2, SUM(s.rating = 3) AS c3,
                    SUM(s.rating = 4) AS c4, SUM(s.rating = 5) AS c5
             FROM satisfaction_surveys s WHERE $where"
        );
        $dist = array();
        for ($i = 1; $i <= 5; $i++) {
            $dist[$i] = ($row && $row['c' . $i] !== null) ? intval($row['c' . $i]) : 0;
        }
        return array(
            'count' => $row ? intval($row['cnt']) : 0,
            'avg'   => ($row && $row['avg_rating'] !== null) ? floatval($row['avg_rating']) : 0,
            'dist'  => $dist,
        );
    }

    public function getList($filters, $perPage, $page) {
        $where   = $this->buildWhere($filters);
        $perPage = max(1, intval($perPage));
        $offset  = (max(1, intval($page)) - 1) * $perPage;
        return $this->db->fetchAll(
            "SELECT s.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS user_name, u.position AS user_position
             FROM satisfaction_surveys s
             JOIN users u ON u.id = s.user_id
             WHERE $where
             ORDER BY s.created_at DESC
             LIMIT $perPage OFFSET $offset"
        );
    }

    public function countList($filters) {
        $where = $this->buildWhere($filters);
        $row = $this->db->fetchOne("SELECT COUNT(*) AS cnt FROM satisfaction_surveys s WHERE $where");
        return $row ? intval($row['cnt']) : 0;
    }

    public function getAllForExport($filters) {
        $where = $this->buildWhere($filters);
        return $this->db->fetchAll(
            "SELECT s.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS user_name
             FROM satisfaction_surveys s
             JOIN users u ON u.id = s.user_id
             WHERE $where
             ORDER BY s.created_at DESC"
        );
    }
}
