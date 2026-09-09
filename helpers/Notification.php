<?php
class Notification {

    // สร้างการแจ้งเตือน 1 รายการ ผูกกับ document / issue / service ตาม $targetType
    // $targetType: 'document' | 'issue' | 'service'
    public static function createFor($db, $userId, $targetId, $targetType, $type, $title, $message) {
        $userId  = intval($userId);
        $targetId = intval($targetId);
        $type    = $db->escape($type);
        $title   = $db->escape($title);
        $message = $db->escape($message);

        if ($targetType === 'issue') {
            $db->query("INSERT INTO notifications (user_id, issue_id, type, title, message)
                        VALUES ($userId, $targetId, '$type', '$title', '$message')");
        } elseif ($targetType === 'service') {
            $db->query("INSERT INTO notifications (user_id, service_id, type, title, message)
                        VALUES ($userId, $targetId, '$type', '$title', '$message')");
        } else {
            $db->query("INSERT INTO notifications (user_id, document_id, type, title, message)
                        VALUES ($userId, $targetId, '$type', '$title', '$message')");
        }
    }

    public static function createForMultiple($db, $userIds, $targetId, $targetType, $type, $title, $message) {
        if (!is_array($userIds) || empty($userIds)) return;
        foreach ($userIds as $userId) {
            self::createFor($db, $userId, $targetId, $targetType, $type, $title, $message);
        }
    }

    // เดิม: create() / createForIssue() — คงชื่อเดิมไว้เป็น wrapper เพื่อไม่ต้องแก้ไฟล์ที่เรียกใช้ทั่วระบบ
    public static function create($db, $userId, $documentId, $type, $title, $message) {
        self::createFor($db, $userId, $documentId, 'document', $type, $title, $message);
    }

    public static function createForIssue($db, $userId, $issueId, $type, $title, $message) {
        self::createFor($db, $userId, $issueId, 'issue', $type, $title, $message);
    }

    public static function createForService($db, $userId, $serviceId, $type, $title, $message) {
        self::createFor($db, $userId, $serviceId, 'service', $type, $title, $message);
    }

    // เดิม: createForMultiple() (document) — คงชื่อเดิมไว้เป็น wrapper
    public static function createForMultipleDocs($db, $userIds, $documentId, $type, $title, $message) {
        self::createForMultiple($db, $userIds, $documentId, 'document', $type, $title, $message);
    }

    public static function createForMultipleIssue($db, $userIds, $issueId, $type, $title, $message) {
        self::createForMultiple($db, $userIds, $issueId, 'issue', $type, $title, $message);
    }

    public static function createForMultipleService($db, $userIds, $serviceId, $type, $title, $message) {
        self::createForMultiple($db, $userIds, $serviceId, 'service', $type, $title, $message);
    }

    // รวม getUsersByRole() / getUsersByRoleOfficeOnly() เป็นเมธอดเดียว
    // $includeHQ = true  -> เดิมคือ getUsersByRole()          (รวมส่วนกลางเสมอ)
    // $includeHQ = false -> เดิมคือ getUsersByRoleOfficeOnly() (เฉพาะสำนักงานนั้น)
    public static function getUsersByRole($db, $role, $officeName, $includeHQ = true) {
        $roleEsc = $db->escape($role);
        $offEsc  = $db->escape($officeName);

        if ($includeHQ) {
            $hqEsc = $db->escape(HQ_OFFICE);
            $rows = $db->fetchAll(
                "SELECT id FROM users
                 WHERE FIND_IN_SET('$roleEsc', REPLACE(roles, ', ', ','))
                   AND is_active = 1
                   AND (office_name = '$offEsc' OR office_name = '$hqEsc')"
            );
        } else {
            $rows = $db->fetchAll(
                "SELECT id FROM users
                 WHERE FIND_IN_SET('$roleEsc', REPLACE(roles, ', ', ','))
                   AND is_active = 1
                   AND office_name = '$offEsc'"
            );
        }
        $ids = array();
        foreach ($rows as $row) { $ids[] = $row['id']; }
        return $ids;
    }

    // wrapper คงชื่อเดิมไว้เพื่อ backward compatibility
    public static function getUsersByRoleOfficeOnly($db, $role, $officeName) {
        return self::getUsersByRole($db, $role, $officeName, false);
    }

    public static function notifyStatusChange($db, $doc, $newStatus, $actorId) {
        $docId      = intval($doc['id']);
        $ticket     = $doc['ticket_code'];
        $officeName = $doc['office_name'];
        $submitter  = intval($doc['submitted_by']);

        switch ($newStatus) {
            case 'inspecting':
                $ids = self::getUsersByRole($db, 'inspector', $officeName);
                self::createForMultipleDocs($db, $ids, $docId, 'status_changed',
                    'มีเอกสารใหม่รอตรวจสอบ',
                    'เอกสาร ' . $ticket . ' รอการตรวจสอบ กรุณาดำเนินการ');
                break;

            case 'approving':
                $ids = self::getUsersByRole($db, 'approver', $officeName);
                self::createForMultipleDocs($db, $ids, $docId, 'status_changed',
                    'เอกสารรออนุมัติ',
                    'เอกสาร ' . $ticket . ' ผ่านการตรวจสอบแล้ว รออนุมัติ');
                self::create($db, $submitter, $docId, 'info',
                    'เอกสารผ่านการตรวจสอบ',
                    'เอกสาร ' . $ticket . ' ผ่านการตรวจสอบ กำลังรอขั้นตอนอนุมัติ');
                break;

            case 'operating':
                $ids = self::getUsersByRole($db, 'operator', $officeName);
                self::createForMultipleDocs($db, $ids, $docId, 'status_changed',
                    'เอกสารรอดำเนินการ',
                    'เอกสาร ' . $ticket . ' อนุมัติแล้ว รอดำเนินการ');
                self::create($db, $submitter, $docId, 'info',
                    'เอกสารผ่านการอนุมัติ',
                    'เอกสาร ' . $ticket . ' ผ่านการอนุมัติ กำลังดำเนินการ');
                break;

            case 'completed':
                self::create($db, $submitter, $docId, 'completed',
                    'เอกสารเสร็จสิ้น',
                    'เอกสาร ' . $ticket . ' ดำเนินการเสร็จสิ้นแล้ว');
                $ids = self::getUsersByRole($db, 'inspector', $officeName);
                self::createForMultipleDocs($db, $ids, $docId, 'completed',
                    'เอกสารเสร็จสิ้น',
                    'เอกสาร ' . $ticket . ' ดำเนินการเสร็จสิ้นแล้ว');
                break;

            case 'revision':
                self::create($db, $submitter, $docId, 'revision',
                    'เอกสารถูกส่งกลับแก้ไข',
                    'เอกสาร ' . $ticket . ' ถูกส่งกลับ กรุณาแก้ไขและส่งใหม่');
                break;

            case 'pending':
                $ids = self::getUsersByRole($db, 'inspector', $officeName);
                self::createForMultipleDocs($db, $ids, $docId, 'resubmitted',
                    'เอกสารถูกส่งใหม่หลังแก้ไข',
                    'เอกสาร ' . $ticket . ' แก้ไขและส่งใหม่แล้ว รอตรวจสอบ');
                break;
        }
    }

    public static function countUnread($db, $userId) {
        $userId = intval($userId);
        $row    = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $userId AND is_read = 0"
        );
        return $row ? intval($row['cnt']) : 0;
    }

    public static function getRecent($db, $userId, $limit) {
        $userId = intval($userId);
        $limit  = intval($limit);
        return $db->fetchAll(
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

    public static function markRead($db, $notificationId, $userId) {
        $notificationId = intval($notificationId);
        $userId         = intval($userId);
        $db->query(
            "UPDATE notifications SET is_read = 1
             WHERE id = $notificationId AND user_id = $userId"
        );
    }

    public static function markAllRead($db, $userId) {
        $userId = intval($userId);
        $db->query(
            "UPDATE notifications SET is_read = 1
             WHERE user_id = $userId AND is_read = 0"
        );
    }
}
