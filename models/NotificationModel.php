<?php
class NotificationModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getByUser($userId, $limit = 50) {
        $userId = intval($userId);
        $limit  = intval($limit);
        return $this->db->fetchAll(
            "SELECT n.*,
                    COALESCE(d.ticket_code, i.ticket_code, s.ticket_code) AS ticket_code,
                    CASE WHEN n.document_id IS NOT NULL THEN 'document'
                         WHEN n.issue_id IS NOT NULL THEN 'issue'
                         ELSE 'service' END AS target_type,
                    COALESCE(n.document_id, n.issue_id, n.service_id) AS target_id
             FROM notifications n
             LEFT JOIN documents d ON d.id = n.document_id
             LEFT JOIN issues i ON i.id = n.issue_id
             LEFT JOIN internal_services s ON s.id = n.service_id
             WHERE n.user_id = $userId
             ORDER BY n.created_at DESC LIMIT $limit"
        );
    }
}
