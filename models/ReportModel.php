<?php
class ReportModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // รวม logic การ build WHERE clause จาก filters ที่เดิมซ้ำกันใน getSummaryByStatus() และ getDetailReport()
    // $includeStatusDate = true เมื่อต้องการรวมเงื่อนไข status/date_from/date_to ด้วย (ใช้กับ getDetailReport)
    private function buildFilterClause($user, $filters = array(), $includeStatusDate = false) {
        $where = buildDocumentWhereClause($user);

        if (!empty($filters['fiscal_year'])) {
            $fy = $this->db->escape($filters['fiscal_year']);
            $where .= " AND d.fiscal_year = '$fy'";
        }
        if (!empty($filters['office_name'])) {
            $on = $this->db->escape($filters['office_name']);
            $where .= " AND d.office_name = '$on'";
        }

        if ($includeStatusDate) {
            if (!empty($filters['status'])) {
                $s = $this->db->escape($filters['status']);
                $where .= " AND d.status = '$s'";
            }
            $where .= dateRangeClause($this->db, 'd.created_at', $filters);
        }

        return $where;
    }

    public function getSummaryByStatus($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters, false);
        return $this->db->fetchAll(
            "SELECT d.status, COUNT(*) AS cnt
             FROM documents d
             JOIN users u ON u.id = d.submitted_by
             WHERE $where
             GROUP BY d.status"
        );
    }

    public function getDetailReport($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters, true);
        return $this->db->fetchAll(
            "SELECT d.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS submitter_name,
                    d.inspected_at AS received_at
             FROM documents d
             JOIN users u ON u.id = d.submitted_by
             WHERE $where
             ORDER BY d.created_at DESC"
        );
    }
}