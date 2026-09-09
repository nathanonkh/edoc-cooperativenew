-- ============================================
-- เพิ่มเติมจาก install_additions.sql / _2 / _3 / _4 / _5
-- เมนูใหม่: "การให้บริการภายในสำนักงาน" (internal_services)
-- แนวทางเดียวกับ "แจ้งปัญหา" (issues) แต่:
--   - ไม่มีการเลือกประเภทสหกรณ์ / สหกรณ์
--   - เพิ่ม "ผู้ขอใช้บริการ" (requester_id) เป็นสมาชิกในสำนักงานเดียวกับผู้บันทึกเท่านั้น
--   - สถานะ 3 ขั้น: รอตรวจสอบ -> กำลังดำเนินการ -> เสร็จสิ้น (ไม่มี "ส่งส่วนกลาง")
-- MySQL 5.0.45 syntax เท่านั้น
-- ============================================

USE edoc_cooperative;

-- --------------------------------------------------------
-- ตาราง `internal_services` - คำขอใช้บริการภายในสำนักงาน
-- --------------------------------------------------------
CREATE TABLE `internal_services` (
  `id` int(11) NOT NULL auto_increment,
  `ticket_code` varchar(50) NOT NULL COMMENT 'รหัส Ticket (SRV-ปี-xxxxx)',
  `service_type` varchar(50) NOT NULL COMMENT 'ประเภทบริการ (อ้างอิง helpers/functions.php::getServiceTypeOptions)',
  `title` varchar(255) NOT NULL COMMENT 'ชื่อเรื่อง',
  `detail` text NOT NULL COMMENT 'รายละเอียดคำขอ',
  `attachment` varchar(255) default NULL COMMENT 'ไฟล์แนบ (path)',
  `attachment_name` varchar(255) default NULL COMMENT 'ชื่อไฟล์แนบต้นฉบับ',
  `requester_id` int(11) NOT NULL COMMENT 'FK: users.id (ผู้ขอใช้บริการ - ต้องอยู่สำนักงานเดียวกับผู้บันทึก)',
  `office_name` varchar(255) NOT NULL COMMENT 'สำนักงานที่ให้บริการ (ใช้กำหนดสิทธิ์การมองเห็น)',
  `status` enum('pending','in_progress','completed') default 'pending' COMMENT 'สถานะ',
  `submitted_by` int(11) NOT NULL COMMENT 'FK: users.id (ผู้บันทึกคำขอ)',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT 'วันที่ยื่นคำขอ',
  `updated_at` datetime default NULL COMMENT 'วันที่อัปเดตสถานะล่าสุด',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ticket_code` (`ticket_code`),
  KEY `requester_id` (`requester_id`),
  KEY `submitted_by` (`submitted_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------
-- ตาราง `internal_service_logs` - ประวัติการดำเนินการ
-- --------------------------------------------------------
CREATE TABLE `internal_service_logs` (
  `id` int(11) NOT NULL auto_increment,
  `service_id` int(11) NOT NULL COMMENT 'FK: internal_services.id',
  `action` varchar(100) NOT NULL COMMENT 'การกระทำ',
  `action_by` int(11) NOT NULL COMMENT 'FK: users.id',
  `note` text COMMENT 'หมายเหตุ',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `service_id` (`service_id`),
  KEY `action_by` (`action_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------
-- ทำให้ notifications รองรับการแจ้งเตือนของบริการภายในสำนักงานได้ด้วย
-- --------------------------------------------------------
ALTER TABLE `notifications`
  ADD COLUMN `service_id` int(11) NULL COMMENT 'FK: internal_services.id (สำหรับการแจ้งเตือนบริการภายในสำนักงาน)' AFTER `issue_id`;

-- --------------------------------------------------------
-- Constraints
-- --------------------------------------------------------
ALTER TABLE `internal_services`
  ADD CONSTRAINT `internal_services_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `internal_services_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`);

ALTER TABLE `internal_service_logs`
  ADD CONSTRAINT `internal_service_logs_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `internal_services` (`id`),
  ADD CONSTRAINT `internal_service_logs_ibfk_2` FOREIGN KEY (`action_by`) REFERENCES `users` (`id`);

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_4` FOREIGN KEY (`service_id`) REFERENCES `internal_services` (`id`);
