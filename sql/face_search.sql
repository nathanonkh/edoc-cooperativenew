-- ตารางสำหรับระบบค้นหารูปด้วยใบหน้า (MySQL 5.0 / MyISAM เหมือน data.sql เดิม)
-- รันใน phpMyAdmin บนฐานข้อมูลเดิม ไม่แตะตารางเดิม (users ฯลฯ คงไว้)
-- ใช้ CREATE TABLE ธรรมดา ถ้ามีตารางอยู่แล้วจะ error ไม่เขียนทับ

CREATE TABLE `gallery_images` (
  `id` int(11) NOT NULL auto_increment,
  `filename` varchar(255) NOT NULL COMMENT 'path ใน uploads/ เช่น gallery/xxx.jpg',
  `thumb` varchar(255) default NULL COMMENT 'path รูปย่อ ถ้าไม่มีใช้ไฟล์เดิม',
  `original_name` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์ต้นฉบับ',
  `face_count` int(11) NOT NULL default '0' COMMENT 'จำนวนใบหน้าที่ตรวจพบ',
  `uploaded_by` int(11) NOT NULL COMMENT 'users.id ของ admin ที่นำเข้า',
  `created_at` datetime NOT NULL COMMENT 'วันที่นำเข้า',
  PRIMARY KEY  (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `image_faces` (
  `id` int(11) NOT NULL auto_increment,
  `image_id` int(11) NOT NULL COMMENT 'gallery_images.id',
  `descriptor` text NOT NULL COMMENT 'เวกเตอร์ใบหน้า 128 ค่า คั่นด้วย , (ข้อมูลชีวภาพ: PDPA ข้อมูลอ่อนไหว)',
  `box` varchar(60) default NULL COMMENT 'ตำแหน่งกรอบใบหน้า x,y,w,h (พิกเซลของรูปต้นฉบับ)',
  PRIMARY KEY  (`id`),
  KEY `image_id` (`image_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
