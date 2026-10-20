-- ตารางเก็บผลการประเมินความพึงพอใจ (รันครั้งเดียวบนฐาน edoc_cooperative)
CREATE TABLE IF NOT EXISTS `satisfaction_surveys` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL COMMENT 'FK: users.id (ผู้ประเมิน)',
  `office_name` varchar(255) NOT NULL COMMENT 'สำนักงานของผู้ประเมิน ณ เวลาที่ประเมิน',
  `rating` tinyint(1) NOT NULL COMMENT 'คะแนน 1-5',
  `comment` text COMMENT 'ข้อเสนอแนะ',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`),
  KEY `rating` (`rating`),
  KEY `created_at` (`created_at`),
  KEY `office_name` (`office_name`(100))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
