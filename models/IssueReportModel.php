<?php
class IssueReportModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // รวม logic การ build WHERE clause จาก filters ที่เดิมซ้ำกันใน getSummaryByStatus() และ getDetailReport()
    // $includeStatusDate = true เมื่อต้องการรวมเงื่อนไข status/date_from/date_to ด้วย (ใช้กับ getDetailReport)
    private function buildFilterClause($user, $filters = array(), $includeStatusDate = false) {
        $where = buildIssueWhereClause($user);

        if (!empty($filters['office_name'])) {
            $on = $this->db->escape($filters['office_name']);
            $where .= " AND i.office_name = '$on'";
        }
        if (!empty($filters['issue_type'])) {
            $it = $this->db->escape($filters['issue_type']);
            $where .= " AND i.issue_type = '$it'";
        }
        if (!empty($filters['program_name'])) {
            $pn = $this->db->escape($filters['program_name']);
            $where .= " AND i.program_name = '$pn'";
        }

        if ($includeStatusDate) {
            if (!empty($filters['status'])) {
                $s = $this->db->escape($filters['status']);
                $where .= " AND i.status = '$s'";
            }
            if (!empty($filters['date_from'])) {
                $df = $this->db->escape($filters['date_from']);
                $where .= " AND DATE(i.created_at) >= '$df'";
            }
            if (!empty($filters['date_to'])) {
                $dt = $this->db->escape($filters['date_to']);
                $where .= " AND DATE(i.created_at) <= '$dt'";
            }
        }

        return $where;
    }

    public function getSummaryByStatus($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters, false);
        return $this->db->fetchAll(
            "SELECT i.status, COUNT(*) AS cnt
             FROM issues i
             JOIN users u ON u.id = i.submitted_by
             WHERE $where
             GROUP BY i.status"
        );
    }

    public function getDetailReport($user, $filters = array()) {
        $where = $this->buildFilterClause($user, $filters, true);
        return $this->db->fetchAll(
            "SELECT i.*, CONCAT(u.prefix,' ',u.firstname,' ',u.lastname) AS submitter_name,
                    (SELECT MIN(l.created_at) FROM issue_logs l
                      WHERE l.issue_id = i.id
                        AND l.action IN ('รับเรื่องดำเนินการเอง','ส่วนกลางรับเรื่องดำเนินการ')) AS started_at,
                    (SELECT MAX(l.created_at) FROM issue_logs l
                      WHERE l.issue_id = i.id AND l.action = 'ดำเนินการสำเร็จ') AS completed_at,
                    (SELECT l.note FROM issue_logs l
                      WHERE l.issue_id = i.id AND l.action = 'ดำเนินการสำเร็จ'
                      ORDER BY l.id DESC LIMIT 1) AS result_note
             FROM issues i
             JOIN users u ON u.id = i.submitted_by
             WHERE $where
             ORDER BY i.created_at DESC"
        );
    }
}