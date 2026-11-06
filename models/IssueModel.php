<?php
class IssueModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // รวม logic การ build WHERE clause จาก filters ที่เดิมซ้ำกันใน getList() และ countList()
    private function buildFilterClause($user, $filters = array()) {
        $where = buildIssueWhereClause($user);

        if (!empty($filters['status'])) {
            $s = $this->db->escape($filters['status']);
            $where .= " AND i.status = '$s'";
        }
        if (!empty($filters['keyword'])) {
            $kw = $this->db->escape($filters['keyword']);
            $where .= " AND (i.title LIKE '%$kw%' OR i.cooperative_name LIKE '%$kw%' OR i.ticket_code LIKE '%$kw%')";
        }
        $where .= dateRangeClause($this->db, 'i.created_at', $filters);

        return $where;
    }

    public function getList($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);

        $limit = limitClause($filters, 30);

        $sql = "SELECT i.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS submitter_name
                FROM issues i
                JOIN users u ON u.id = i.submitted_by
                WHERE $where
                ORDER BY i.created_at DESC
                $limit";

        return $this->db->fetchAll($sql);
    }

    public function countList($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM issues i JOIN users u ON u.id = i.submitted_by WHERE $where"
        );
        return intval($row['cnt']);
    }

    public function getById($id) {
        $id = intval($id);
        return $this->db->fetchOne(
            "SELECT i.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS submitter_name
             FROM issues i
             JOIN users u ON u.id = i.submitted_by
             WHERE i.id = $id"
        );
    }

    public function getLogs($issueId) {
        return ActivityLog::get($this->db, 'issue_logs', 'issue_id', $issueId);
    }

    public function addLog($issueId, $action, $actionBy, $note = '') {
        ActivityLog::add($this->db, 'issue_logs', 'issue_id', $issueId, $action, $actionBy, $note);
    }
}
