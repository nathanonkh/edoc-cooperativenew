-- =====================================================================
-- migration.sql (รอบที่ 2) — รันกับฐานข้อมูล edoc_cooperative
-- ติดตั้งใหม่: รัน data.sql ก่อน แล้วค่อยรันไฟล์นี้
-- ฐานเดิม: สำรองข้อมูลก่อน แล้วรันไฟล์นี้ "ครั้งเดียว"
-- (ALTER ... ADD COLUMN ของ notifications จะ error ถ้าเคยรันแล้ว)
-- =====================================================================

-- 1) ขยายคอลัมน์รหัสผ่านให้รองรับ hash รูปแบบใหม่ (pbkdf2)
ALTER TABLE `users` MODIFY `password` varchar(255) NOT NULL;

-- 2) ตารางระบบ "การให้บริการภายในสำนักงาน" (โค้ดใช้งานอยู่แต่ data.sql ไม่มี)
CREATE TABLE IF NOT EXISTS `internal_services` (
  `id` int(11) NOT NULL auto_increment,
  `ticket_code` varchar(50) NOT NULL COMMENT 'รหัส (SRV-ปี-xxxxx)',
  `service_type` varchar(50) NOT NULL COMMENT 'ประเภทบริการ (getServiceTypeOptions)',
  `title` varchar(255) NOT NULL,
  `detail` text NOT NULL,
  `attachment` varchar(255) default NULL,
  `attachment_name` varchar(255) default NULL,
  `requester_id` int(11) NOT NULL COMMENT 'FK: users.id (ผู้ขอใช้บริการ)',
  `office_name` varchar(255) NOT NULL,
  `submitted_by` int(11) NOT NULL COMMENT 'FK: users.id (ผู้บันทึก)',
  `status` enum('pending','in_progress','completed') default 'pending',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `updated_at` datetime default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ticket_code` (`ticket_code`),
  KEY `requester_id` (`requester_id`),
  KEY `submitted_by` (`submitted_by`),
  KEY `office_name` (`office_name`(100))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `internal_service_logs` (
  `id` int(11) NOT NULL auto_increment,
  `service_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `action_by` int(11) NOT NULL,
  `note` text,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `service_id` (`service_id`),
  KEY `action_by` (`action_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 3) คอลัมน์ service_id ในตาราง notifications (Notification::createFor / getRecent ใช้งาน)
ALTER TABLE `notifications`
  ADD COLUMN `service_id` int(11) default NULL COMMENT 'FK: internal_services.id' AFTER `issue_id`,
  ADD KEY `service_id` (`service_id`);

-- 4) ตารางบันทึกการ login ผิด สำหรับจำกัดจำนวนครั้ง (rate limit)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`),
  KEY `ip_address` (`ip_address`),
  KEY `created_at` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
