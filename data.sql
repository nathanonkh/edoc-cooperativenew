-- phpMyAdmin SQL Dump
-- version 2.10.2
-- http://www.phpmyadmin.net
-- 
-- โฮสต์: localhost
-- เวลาในการสร้าง: 
-- รุ่นของเซิร์ฟเวอร์: 5.0.45
-- รุ่นของ PHP: 5.2.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- 
-- ฐานข้อมูล: `edoc_cooperative`
-- 

-- --------------------------------------------------------

-- 
-- โครงสร้างตาราง `announcements`
-- 

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL COMMENT 'หัวข้อประกาศ',
  `content` text NOT NULL COMMENT 'เนื้อหาประกาศ',
  `attachment` varchar(255) default NULL COMMENT 'ไฟล์ PDF แนบ (path)',
  `attachment_name` varchar(255) default NULL COMMENT 'ชื่อไฟล์ PDF ต้นฉบับ',
  `image` varchar(255) default NULL COMMENT 'ไฟล์รูปภาพแนบ (path)',
  `image_name` varchar(255) default NULL COMMENT 'ชื่อไฟล์รูปภาพต้นฉบับ',
  `is_pinned` tinyint(1) default '0' COMMENT 'ปักหมุดแสดงบนสุด',
  `is_active` tinyint(1) default '1' COMMENT 'สถานะการแสดงผล',
  `created_by` int(11) NOT NULL COMMENT 'FK: users.id',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- dump ตาราง `announcements`
-- 


-- --------------------------------------------------------

-- 
-- โครงสร้างตาราง `cooperatives`
-- 

CREATE TABLE `cooperatives` (
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(20) NOT NULL COMMENT 'รหัสสหกรณ์ (ภายใน)',
  `registration_no` varchar(50) default NULL COMMENT 'เลขทะเบียนสหกรณ์',
  `regis_13digit` varchar(13) default NULL COMMENT 'รหัส 13 หลัก กรมส่งเสริมสหกรณ์',
  `name` varchar(255) NOT NULL COMMENT 'ชื่อสหกรณ์',
  `type_name` varchar(100) NOT NULL COMMENT 'ประเภทสหกรณ์',
  `register_date` varchar(10) default NULL COMMENT 'วันที่จดทะเบียน รูปแบบ dd/mm/yyyy เช่น 17/04/2538',
  `address` text COMMENT 'ที่ตั้ง',
  `subdistrict` varchar(100) default NULL COMMENT 'ตำบล',
  `district` varchar(100) default NULL COMMENT 'อำเภอ',
  `province` varchar(100) default NULL COMMENT 'จังหวัด',
  `office_name` varchar(255) NOT NULL COMMENT 'สำนักงานตรวจบัญชีสหกรณ์',
  `fiscal_year` varchar(5) default NULL COMMENT 'วันสิ้นปีบัญชี รูปแบบ dd/mm เช่น 31/03',
  `status` enum('active','inactive','ceased','litigation','bankrupt','receivership','dissolved','liquidation') default 'active' COMMENT 'สถานะสหกรณ์',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `updated_at` datetime default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- dump ตาราง `cooperatives`
-- 


-- --------------------------------------------------------

-- 
-- โครงสร้างตาราง `documents`
-- 

CREATE TABLE `documents` (
  `id` int(11) NOT NULL auto_increment,
  `ticket_code` varchar(50) NOT NULL COMMENT 'รหัส Ticket (TKT-ปี-xxxxx)',
  `cooperative_type_name` varchar(100) NOT NULL COMMENT 'ประเภทสหกรณ์',
  `cooperative_id` int(11) NOT NULL COMMENT 'FK: cooperatives.id',
  `cooperative_code` varchar(20) NOT NULL COMMENT 'รหัสสหกรณ์',
  `cooperative_name` varchar(255) NOT NULL COMMENT 'ชื่อสหกรณ์',
  `fiscal_year` varchar(10) NOT NULL COMMENT 'ปีบัญชี พ.ศ.',
  `submitted_date` varchar(20) NOT NULL COMMENT 'วันที่นำส่ง (ไทย)',
  `file_doc1` varchar(255) NOT NULL COMMENT 'ไฟล์ที่ 1',
  `file_doc2` varchar(255) NOT NULL COMMENT 'ไฟล์ที่ 2',
  `file_doc3` varchar(255) NOT NULL COMMENT 'ไฟล์ที่ 3',
  `file_doc4` varchar(255) NOT NULL COMMENT 'ไฟล์ที่ 4',
  `file_doc1_name` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์ที่ 1',
  `file_doc2_name` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์ที่ 2',
  `file_doc3_name` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์ที่ 3',
  `file_doc4_name` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์ที่ 4',
  `submitted_by` int(11) NOT NULL COMMENT 'FK: users.id (ผู้นำส่ง)',
  `office_name` varchar(255) NOT NULL COMMENT 'สำนักงานที่นำส่ง',
  `status` enum('pending','inspecting','approving','operating','revision','completed') default 'pending' COMMENT 'สถานะ',
  `document_number` varchar(100) default NULL,
  `receive_number` varchar(50) default NULL COMMENT 'เลขรับหนังสือ',
  `inspector_note` text COMMENT 'หมายเหตุผู้ตรวจสอบ',
  `approver_note` text COMMENT 'หมายเหตุผู้อนุมัติ',
  `operator_note` text COMMENT 'หมายเหตุผู้ดำเนินการ',
  `revision_note` text COMMENT 'เหตุผลที่ส่งกลับ',
  `revision_by` int(11) default NULL COMMENT 'FK: users.id (ผู้ส่งกลับ)',
  `revision_role` enum('inspector','approver','operator') default NULL COMMENT 'บทบาทผู้ส่งกลับ',
  `inspected_by` int(11) default NULL COMMENT 'FK: users.id (ผู้ตรวจสอบ)',
  `approved_by` int(11) default NULL COMMENT 'FK: users.id (ผู้อนุมัติ)',
  `operated_by` int(11) default NULL COMMENT 'FK: users.id (ผู้ดำเนินการ)',
  `inspected_at` datetime default NULL COMMENT 'วันที่ตรวจสอบ',
  `approved_at` datetime default NULL COMMENT 'วันที่อนุมัติ',
  `operated_at` datetime default NULL COMMENT 'วันที่ดำเนินการ',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
  `updated_at` datetime default NULL COMMENT 'วันที่แก้ไขล่าสุด',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ticket_code` (`ticket_code`),
  KEY `cooperative_id` (`cooperative_id`),
  KEY `submitted_by` (`submitted_by`),
  KEY `inspected_by` (`inspected_by`),
  KEY `approved_by` (`approved_by`),
  KEY `operated_by` (`operated_by`),
  KEY `revision_by` (`revision_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- dump ตาราง `documents`
-- 


-- --------------------------------------------------------

-- 
-- โครงสร้างตาราง `document_logs`
-- 

CREATE TABLE `document_logs` (
  `id` int(11) NOT NULL auto_increment,
  `document_id` int(11) NOT NULL COMMENT 'FK: documents.id',
  `action` varchar(100) NOT NULL COMMENT 'การกระทำ',
  `action_by` int(11) NOT NULL COMMENT 'FK: users.id',
  `note` text COMMENT 'หมายเหตุ',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `document_id` (`document_id`),
  KEY `action_by` (`action_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- dump ตาราง `document_logs`
-- 


-- --------------------------------------------------------

-- 
-- โครงสร้างตาราง `issues`
-- 

CREATE TABLE `issues` (
  `id` int(11) NOT NULL auto_increment,
  `ticket_code` varchar(50) NOT NULL COMMENT 'รหัส Ticket (ISS-ปี-xxxxx)',
  `cooperative_type_name` varchar(100) NOT NULL COMMENT 'ประเภทสหกรณ์',
  `cooperative_id` int(11) NOT NULL COMMENT 'FK: cooperatives.id',
  `cooperative_code` varchar(20) NOT NULL COMMENT 'รหัสสหกรณ์',
  `cooperative_name` varchar(255) NOT NULL COMMENT 'ชื่อสหกรณ์',
  `office_name` varchar(255) NOT NULL COMMENT 'สำนักงานที่รับผิดชอบสหกรณ์นี้ (ใช้กำหนดสิทธิ์การมองเห็น)',
  `issue_type` varchar(50) NOT NULL COMMENT 'ประเภทปัญหา (อ้างอิง helpers/functions.php::getIssueTypeOptions)',
  `program_name` varchar(50) NOT NULL COMMENT 'โปรแกรมที่พบปัญหา (อ้างอิง helpers/functions.php::getProgramOptions)',
  `title` varchar(255) NOT NULL COMMENT 'ชื่อเรื่อง',
  `detail` text NOT NULL COMMENT 'รายละเอียดปัญหา',
  `attachment` varchar(255) default NULL COMMENT 'ไฟล์แนบ (path)',
  `attachment_name` varchar(255) default NULL COMMENT 'ชื่อไฟล์แนบต้นฉบับ',
  `status` enum('pending','sent_central','in_progress','completed') default 'pending' COMMENT 'สถานะ',
  `handled_by_central` tinyint(1) default '0' COMMENT 'เคยถูกส่งต่อไปยังส่วนกลาง (แสดงผลสถานะ .../ส่วนกลาง)',
  `submitted_by` int(11) NOT NULL COMMENT 'FK: users.id (ผู้แจ้งปัญหา)',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT 'วันที่แจ้ง',
  `updated_at` datetime default NULL COMMENT 'วันที่อัปเดตสถานะล่าสุด',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `ticket_code` (`ticket_code`),
  KEY `cooperative_id` (`cooperative_id`),
  KEY `submitted_by` (`submitted_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- dump ตาราง `issues`
-- 


-- --------------------------------------------------------

-- 
-- โครงสร้างตาราง `issue_logs`
-- 

CREATE TABLE `issue_logs` (
  `id` int(11) NOT NULL auto_increment,
  `issue_id` int(11) NOT NULL COMMENT 'FK: issues.id',
  `action` varchar(100) NOT NULL COMMENT 'การกระทำ',
  `action_by` int(11) NOT NULL COMMENT 'FK: users.id',
  `note` text COMMENT 'หมายเหตุ',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `issue_id` (`issue_id`),
  KEY `action_by` (`action_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- dump ตาราง `issue_logs`
-- 


-- --------------------------------------------------------

-- 
-- โครงสร้างตาราง `notifications`
-- 

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL COMMENT 'FK: users.id (ผู้รับแจ้งเตือน)',
  `document_id` int(11) default NULL COMMENT 'FK: documents.id (สำหรับการแจ้งเตือนเอกสาร)',
  `issue_id` int(11) default NULL COMMENT 'FK: issues.id (สำหรับการแจ้งเตือนเรื่องแจ้งปัญหา)',
  `type` varchar(50) NOT NULL COMMENT 'ประเภทการแจ้งเตือน',
  `title` varchar(255) NOT NULL COMMENT 'หัวข้อ',
  `message` text NOT NULL COMMENT 'ข้อความ',
  `is_read` tinyint(1) default '0' COMMENT 'อ่านแล้วหรือไม่',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`),
  KEY `document_id` (`document_id`),
  KEY `notifications_ibfk_3` (`issue_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- dump ตาราง `notifications`
-- 


-- --------------------------------------------------------

-- 
-- โครงสร้างตาราง `tutorial_videos`
-- 

CREATE TABLE `tutorial_videos` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL COMMENT 'หัวข้อวีดีโอ',
  `video_url` varchar(500) NOT NULL COMMENT 'ลิงก์วีดีโอ (แนะนำ YouTube)',
  `description` text COMMENT 'คำอธิบาย',
  `attachment` varchar(255) default NULL COMMENT 'ไฟล์ PDF แนบ (path)',
  `attachment_name` varchar(255) default NULL COMMENT 'ชื่อไฟล์ PDF ต้นฉบับ',
  `is_active` tinyint(1) default '1' COMMENT 'สถานะการแสดงผล',
  `created_by` int(11) NOT NULL COMMENT 'FK: users.id',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- dump ตาราง `tutorial_videos`
-- 


-- --------------------------------------------------------

-- 
-- โครงสร้างตาราง `users`
-- 

CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(50) NOT NULL COMMENT 'ชื่อผู้ใช้',
  `password` varchar(255) NOT NULL,
  `prefix` varchar(20) NOT NULL COMMENT 'คำนำหน้า (นาย/นาง/นางสาว)',
  `firstname` varchar(100) NOT NULL COMMENT 'ชื่อ',
  `lastname` varchar(100) NOT NULL COMMENT 'นามสกุล',
  `roles` varchar(100) NOT NULL default 'submitter' COMMENT 'บทบาท (คั่นด้วย , เช่น inspector,approver)',
  `office_name` varchar(255) NOT NULL COMMENT 'ชื่อสำนักงาน',
  `employee_type` enum('civil','contract','temporary','outsource') NOT NULL COMMENT 'ประเภทพนักงาน',
  `position` varchar(100) NOT NULL COMMENT 'ตำแหน่ง',
  `phone` varchar(20) default NULL COMMENT 'เบอร์โทรศัพท์',
  `email` varchar(100) default NULL COMMENT 'อีเมล',
  `is_active` tinyint(1) default '1' COMMENT 'สถานะการใช้งาน',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `updated_at` datetime default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- dump ตาราง `users`
-- 

