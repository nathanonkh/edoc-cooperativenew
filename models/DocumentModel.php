<?php
class DocumentModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // รวม logic การ build WHERE clause จาก filters ที่เดิมซ้ำกันใน getList() และ countList()
    private function buildFilterClause($user, $filters = array()) {
        $where = buildDocumentWhereClause($user);

        if (!empty($filters['status'])) {
            $s = $this->db->escape($filters['status']);
            $where .= " AND d.status = '$s'";
        }
        if (!empty($filters['keyword'])) {
            $kw = $this->db->escape($filters['keyword']);
            $where .= " AND (d.cooperative_name LIKE '%$kw%' OR d.ticket_code LIKE '%$kw%')";
        }
        if (!empty($filters['date_from'])) {
            $df = $this->db->escape($filters['date_from']);
            $where .= " AND DATE(d.created_at) >= '$df'";
        }
        if (!empty($filters['date_to'])) {
            $dt = $this->db->escape($filters['date_to']);
            $where .= " AND DATE(d.created_at) <= '$dt'";
        }

        return $where;
    }

    public function getList($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);

        $perPage = isset($filters['per_page']) ? intval($filters['per_page']) : 30;
        $page    = isset($filters['page_num']) ? intval($filters['page_num']) : 1;
        if ($perPage < 1) $perPage = 30;
        if ($page < 1)    $page    = 1;
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT d.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS submitter_name
                FROM documents d
                JOIN users u ON u.id = d.submitted_by
                WHERE $where
                ORDER BY d.created_at DESC
                LIMIT $perPage OFFSET $offset";

        return $this->db->fetchAll($sql);
    }

    public function countList($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters);

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM documents d JOIN users u ON u.id = d.submitted_by WHERE $where"
        );
        return intval($row['cnt']);
    }

    public function getById($id) {
        $id = intval($id);
        return $this->db->fetchOne(
            "SELECT d.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS submitter_name
             FROM documents d
             JOIN users u ON u.id = d.submitted_by
             WHERE d.id = $id"
        );
    }

    public function getLogs($documentId) {
        return ActivityLog::get($this->db, 'document_logs', 'document_id', $documentId);
    }

    public function addLog($documentId, $action, $actionBy, $note = '') {
        ActivityLog::add($this->db, 'document_logs', 'document_id', $documentId, $action, $actionBy, $note);
    }
}
