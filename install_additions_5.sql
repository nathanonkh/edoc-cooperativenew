-- ============================================
-- เพิ่มเติมจาก install_additions.sql / _2 / _3 / _4
-- เพิ่มไฟล์แนบให้ "ประกาศ" (PDF + รูปภาพ) และ "วีดีโอสอนการใช้งาน" (PDF)
-- พร้อมรองรับหน้ารายละเอียด (คลิกเข้าไปดูเนื้อหาเต็มได้)
-- MySQL 5.0.45 syntax เท่านั้น
-- ============================================

USE edoc_cooperative;

ALTER TABLE `announcements`
  ADD COLUMN `attachment` varchar(255) default NULL COMMENT 'ไฟล์ PDF แนบ (path)' AFTER `content`,
  ADD COLUMN `attachment_name` varchar(255) default NULL COMMENT 'ชื่อไฟล์ PDF ต้นฉบับ' AFTER `attachment`,
  ADD COLUMN `image` varchar(255) default NULL COMMENT 'ไฟล์รูปภาพแนบ (path)' AFTER `attachment_name`,
  ADD COLUMN `image_name` varchar(255) default NULL COMMENT 'ชื่อไฟล์รูปภาพต้นฉบับ' AFTER `image`;

ALTER TABLE `tutorial_videos`
  ADD COLUMN `attachment` varchar(255) default NULL COMMENT 'ไฟล์ PDF แนบ (path)' AFTER `description`,
  ADD COLUMN `attachment_name` varchar(255) default NULL COMMENT 'ชื่อไฟล์ PDF ต้นฉบับ' AFTER `attachment`;
