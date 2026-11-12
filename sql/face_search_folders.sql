-- เพิ่มโฟลเดอร์ให้คลังรูป (MySQL 5.0 / MyISAM) รันใน phpMyAdmin หลังรัน face_search.sql แล้ว
-- ไม่แตะตารางเดิม รูปเดิมทั้งหมดจะอยู่ที่ "ไม่อยู่ในโฟลเดอร์" (folder_id = 0)
-- รันครั้งเดียว ถ้ารันซ้ำจะ error (ตารางหรือคอลัมน์มีอยู่แล้ว) ไม่เขียนทับข้อมูล

CREATE TABLE `gallery_folders` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL COMMENT 'ชื่อโฟลเดอร์ (ชั้นเดียว ไม่ซ้ำกัน)',
  `created_by` int(11) NOT NULL COMMENT 'users.id ของ admin ที่สร้าง',
  `created_at` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `gallery_images`
  ADD `folder_id` int(11) NOT NULL default '0' COMMENT 'gallery_folders.id (0 = ไม่อยู่ในโฟลเดอร์)',
  ADD KEY `folder_id` (`folder_id`);
