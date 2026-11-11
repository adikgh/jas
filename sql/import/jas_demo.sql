-- JAS ACADEMY: схема + демо деректер. phpMyAdmin-де `jas` базасына Import жасаңыз.
-- Демо кіру: 87000000002, құпия сөз jas2026

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `name` varchar(80) NOT NULL,
  `icon` varchar(40) DEFAULT 'fa-medal',
  `points` smallint(6) NOT NULL DEFAULT 30,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ach_name` (`organization_id`,`name`),
  CONSTRAINT `fk_ach_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `achievements` WRITE;
/*!40000 ALTER TABLE `achievements` DISABLE KEYS */;
INSERT INTO `achievements` VALUES
(1,1,'Апта үздігі','fa-star',30),
(2,1,'Тәртіп чемпионы','fa-shield-check',30),
(3,1,'Үздік гол','fa-futbol',30);
/*!40000 ALTER TABLE `achievements` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `assessment_criteria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_criteria` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(80) NOT NULL,
  `position` enum('gk','df','mf','fw') DEFAULT NULL,
  `sort` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_crit` (`organization_id`,`code`,`position`),
  CONSTRAINT `fk_crit_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `assessment_criteria` WRITE;
/*!40000 ALTER TABLE `assessment_criteria` DISABLE KEYS */;
INSERT INTO `assessment_criteria` VALUES
(1,1,'ball_control','Допты бақылау',NULL,1,1),
(2,1,'passing','Пас дәлдігі',NULL,2,1),
(3,1,'shooting','Соққы',NULL,3,1),
(4,1,'dribbling','Дриблинг',NULL,4,1),
(5,1,'speed','Жылдамдық',NULL,5,1),
(6,1,'stamina','Төзімділік',NULL,6,1),
(7,1,'game_iq','Ойын түсінігі',NULL,7,1),
(8,1,'positioning','Позиция таңдау',NULL,8,1),
(9,1,'tactics','Тактикалық шешім',NULL,9,1),
(10,1,'discipline','Тәртіп',NULL,10,1);
/*!40000 ALTER TABLE `assessment_criteria` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `assessment_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_scores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(10) unsigned NOT NULL,
  `criterion_id` int(10) unsigned NOT NULL,
  `score` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_score` (`assessment_id`,`criterion_id`),
  KEY `fk_sc_crit` (`criterion_id`),
  CONSTRAINT `fk_sc_as` FOREIGN KEY (`assessment_id`) REFERENCES `player_assessments` (`id`),
  CONSTRAINT `fk_sc_crit` FOREIGN KEY (`criterion_id`) REFERENCES `assessment_criteria` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `assessment_scores` WRITE;
/*!40000 ALTER TABLE `assessment_scores` DISABLE KEYS */;
/*!40000 ALTER TABLE `assessment_scores` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `assignment_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignment_submissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `assignment_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `status` enum('done','review','accepted','rejected') NOT NULL DEFAULT 'done',
  `video_key` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `coach_comment` text DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sub_asg` (`assignment_id`,`participant_id`),
  KEY `fk_as_org` (`organization_id`),
  KEY `fk_as_part` (`participant_id`),
  CONSTRAINT `fk_as_asg` FOREIGN KEY (`assignment_id`) REFERENCES `training_assignments` (`id`),
  CONSTRAINT `fk_as_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_as_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `assignment_submissions` WRITE;
/*!40000 ALTER TABLE `assignment_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment_submissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `session_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `status` enum('present','absent','excused','frozen') NOT NULL,
  `marked_by` int(10) unsigned DEFAULT NULL,
  `marked_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_att` (`session_id`,`participant_id`),
  KEY `ix_att_part` (`participant_id`,`status`),
  KEY `fk_att_org` (`organization_id`),
  KEY `fk_att_user` (`marked_by`),
  CONSTRAINT `fk_att_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_att_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
  CONSTRAINT `fk_att_sess` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
  CONSTRAINT `fk_att_user` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=397 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES
(1,1,1,1,'absent',3,'2026-11-11 13:42:33'),
(2,1,5,1,'present',3,'2026-11-11 13:42:33'),
(3,1,9,1,'present',3,'2026-11-11 13:42:33'),
(4,1,13,1,'present',3,'2026-11-11 13:42:33'),
(5,1,17,1,'present',3,'2026-11-11 13:42:33'),
(6,1,21,1,'absent',3,'2026-11-11 13:42:33'),
(7,1,25,1,'present',3,'2026-11-11 13:42:33'),
(8,1,29,1,'present',3,'2026-11-11 13:42:33'),
(9,1,33,1,'absent',3,'2026-11-11 13:42:33'),
(10,1,3,2,'excused',3,'2026-11-11 13:42:33'),
(11,1,7,2,'present',3,'2026-11-11 13:42:33'),
(12,1,11,2,'present',3,'2026-11-11 13:42:33'),
(13,1,15,2,'present',3,'2026-11-11 13:42:33'),
(14,1,19,2,'present',3,'2026-11-11 13:42:33'),
(15,1,23,2,'absent',3,'2026-11-11 13:42:33'),
(16,1,27,2,'present',3,'2026-11-11 13:42:33'),
(17,1,31,2,'present',3,'2026-11-11 13:42:33'),
(18,1,35,2,'present',3,'2026-11-11 13:42:33'),
(19,1,2,3,'present',4,'2026-11-11 13:42:33'),
(20,1,6,3,'absent',4,'2026-11-11 13:42:33'),
(21,1,10,3,'present',4,'2026-11-11 13:42:33'),
(22,1,14,3,'absent',4,'2026-11-11 13:42:33'),
(23,1,18,3,'present',4,'2026-11-11 13:42:33'),
(24,1,22,3,'excused',4,'2026-11-11 13:42:33'),
(25,1,26,3,'absent',4,'2026-11-11 13:42:33'),
(26,1,30,3,'present',4,'2026-11-11 13:42:33'),
(27,1,34,3,'excused',4,'2026-11-11 13:42:33'),
(28,1,4,4,'present',4,'2026-11-11 13:42:33'),
(29,1,8,4,'excused',4,'2026-11-11 13:42:33'),
(30,1,12,4,'present',4,'2026-11-11 13:42:33'),
(31,1,16,4,'excused',4,'2026-11-11 13:42:33'),
(32,1,20,4,'present',4,'2026-11-11 13:42:33'),
(33,1,24,4,'excused',4,'2026-11-11 13:42:33'),
(34,1,28,4,'present',4,'2026-11-11 13:42:33'),
(35,1,32,4,'present',4,'2026-11-11 13:42:33'),
(36,1,36,4,'present',4,'2026-11-11 13:42:33'),
(37,1,1,5,'present',3,'2026-11-11 13:42:33'),
(38,1,5,5,'present',3,'2026-11-11 13:42:33'),
(39,1,9,5,'present',3,'2026-11-11 13:42:33'),
(40,1,13,5,'absent',3,'2026-11-11 13:42:33'),
(41,1,17,5,'present',3,'2026-11-11 13:42:33'),
(42,1,21,5,'excused',3,'2026-11-11 13:42:33'),
(43,1,25,5,'excused',3,'2026-11-11 13:42:33'),
(44,1,29,5,'present',3,'2026-11-11 13:42:33'),
(45,1,33,5,'present',3,'2026-11-11 13:42:33'),
(46,1,3,6,'present',3,'2026-11-11 13:42:33'),
(47,1,7,6,'absent',3,'2026-11-11 13:42:33'),
(48,1,11,6,'present',3,'2026-11-11 13:42:33'),
(49,1,15,6,'excused',3,'2026-11-11 13:42:33'),
(50,1,19,6,'present',3,'2026-11-11 13:42:33'),
(51,1,23,6,'excused',3,'2026-11-11 13:42:33'),
(52,1,27,6,'present',3,'2026-11-11 13:42:33'),
(53,1,31,6,'present',3,'2026-11-11 13:42:33'),
(54,1,35,6,'present',3,'2026-11-11 13:42:33'),
(55,1,2,7,'present',4,'2026-11-11 13:42:33'),
(56,1,6,7,'excused',4,'2026-11-11 13:42:33'),
(57,1,10,7,'present',4,'2026-11-11 13:42:33'),
(58,1,14,7,'present',4,'2026-11-11 13:42:33'),
(59,1,18,7,'present',4,'2026-11-11 13:42:33'),
(60,1,22,7,'absent',4,'2026-11-11 13:42:33'),
(61,1,26,7,'absent',4,'2026-11-11 13:42:33'),
(62,1,30,7,'excused',4,'2026-11-11 13:42:33'),
(63,1,34,7,'present',4,'2026-11-11 13:42:33'),
(64,1,4,8,'absent',4,'2026-11-11 13:42:33'),
(65,1,8,8,'present',4,'2026-11-11 13:42:33'),
(66,1,12,8,'present',4,'2026-11-11 13:42:33'),
(67,1,16,8,'present',4,'2026-11-11 13:42:33'),
(68,1,20,8,'present',4,'2026-11-11 13:42:33'),
(69,1,24,8,'present',4,'2026-11-11 13:42:33'),
(70,1,28,8,'present',4,'2026-11-11 13:42:33'),
(71,1,32,8,'present',4,'2026-11-11 13:42:33'),
(72,1,36,8,'present',4,'2026-11-11 13:42:33'),
(73,1,1,9,'present',3,'2026-11-11 13:42:33'),
(74,1,5,9,'excused',3,'2026-11-11 13:42:33'),
(75,1,9,9,'present',3,'2026-11-11 13:42:33'),
(76,1,13,9,'present',3,'2026-11-11 13:42:33'),
(77,1,17,9,'present',3,'2026-11-11 13:42:33'),
(78,1,21,9,'present',3,'2026-11-11 13:42:33'),
(79,1,25,9,'absent',3,'2026-11-11 13:42:33'),
(80,1,29,9,'present',3,'2026-11-11 13:42:33'),
(81,1,33,9,'present',3,'2026-11-11 13:42:33'),
(82,1,3,10,'present',3,'2026-11-11 13:42:33'),
(83,1,7,10,'present',3,'2026-11-11 13:42:33'),
(84,1,11,10,'present',3,'2026-11-11 13:42:33'),
(85,1,15,10,'present',3,'2026-11-11 13:42:33'),
(86,1,19,10,'absent',3,'2026-11-11 13:42:33'),
(87,1,23,10,'present',3,'2026-11-11 13:42:33'),
(88,1,27,10,'present',3,'2026-11-11 13:42:33'),
(89,1,31,10,'present',3,'2026-11-11 13:42:33'),
(90,1,35,10,'present',3,'2026-11-11 13:42:33'),
(91,1,2,11,'present',4,'2026-11-11 13:42:33'),
(92,1,6,11,'present',4,'2026-11-11 13:42:33'),
(93,1,10,11,'present',4,'2026-11-11 13:42:33'),
(94,1,14,11,'present',4,'2026-11-11 13:42:33'),
(95,1,18,11,'present',4,'2026-11-11 13:42:33'),
(96,1,22,11,'present',4,'2026-11-11 13:42:33'),
(97,1,26,11,'present',4,'2026-11-11 13:42:33'),
(98,1,30,11,'present',4,'2026-11-11 13:42:33'),
(99,1,34,11,'present',4,'2026-11-11 13:42:33'),
(100,1,4,12,'present',4,'2026-11-11 13:42:33'),
(101,1,8,12,'excused',4,'2026-11-11 13:42:33'),
(102,1,12,12,'present',4,'2026-11-11 13:42:33'),
(103,1,16,12,'present',4,'2026-11-11 13:42:33'),
(104,1,20,12,'present',4,'2026-11-11 13:42:33'),
(105,1,24,12,'present',4,'2026-11-11 13:42:33'),
(106,1,28,12,'present',4,'2026-11-11 13:42:33'),
(107,1,32,12,'present',4,'2026-11-11 13:42:33'),
(108,1,36,12,'excused',4,'2026-11-11 13:42:33'),
(109,1,1,13,'present',3,'2026-11-11 13:42:33'),
(110,1,5,13,'excused',3,'2026-11-11 13:42:33'),
(111,1,9,13,'excused',3,'2026-11-11 13:42:33'),
(112,1,13,13,'present',3,'2026-11-11 13:42:33'),
(113,1,17,13,'absent',3,'2026-11-11 13:42:33'),
(114,1,21,13,'present',3,'2026-11-11 13:42:33'),
(115,1,25,13,'present',3,'2026-11-11 13:42:33'),
(116,1,29,13,'absent',3,'2026-11-11 13:42:33'),
(117,1,33,13,'present',3,'2026-11-11 13:42:33'),
(118,1,3,14,'present',3,'2026-11-11 13:42:33'),
(119,1,7,14,'present',3,'2026-11-11 13:42:33'),
(120,1,11,14,'excused',3,'2026-11-11 13:42:33'),
(121,1,15,14,'present',3,'2026-11-11 13:42:33'),
(122,1,19,14,'present',3,'2026-11-11 13:42:33'),
(123,1,23,14,'present',3,'2026-11-11 13:42:33'),
(124,1,27,14,'absent',3,'2026-11-11 13:42:33'),
(125,1,31,14,'absent',3,'2026-11-11 13:42:33'),
(126,1,35,14,'present',3,'2026-11-11 13:42:33'),
(127,1,2,15,'present',4,'2026-11-11 13:42:33'),
(128,1,6,15,'absent',4,'2026-11-11 13:42:33'),
(129,1,10,15,'present',4,'2026-11-11 13:42:33'),
(130,1,14,15,'present',4,'2026-11-11 13:42:33'),
(131,1,18,15,'present',4,'2026-11-11 13:42:33'),
(132,1,22,15,'present',4,'2026-11-11 13:42:33'),
(133,1,26,15,'present',4,'2026-11-11 13:42:33'),
(134,1,30,15,'excused',4,'2026-11-11 13:42:33'),
(135,1,34,15,'present',4,'2026-11-11 13:42:33'),
(136,1,4,16,'absent',4,'2026-11-11 13:42:33'),
(137,1,8,16,'present',4,'2026-11-11 13:42:33'),
(138,1,12,16,'present',4,'2026-11-11 13:42:33'),
(139,1,16,16,'present',4,'2026-11-11 13:42:33'),
(140,1,20,16,'present',4,'2026-11-11 13:42:33'),
(141,1,24,16,'absent',4,'2026-11-11 13:42:33'),
(142,1,28,16,'present',4,'2026-11-11 13:42:33'),
(143,1,32,16,'present',4,'2026-11-11 13:42:33'),
(144,1,36,16,'absent',4,'2026-11-11 13:42:33'),
(145,1,1,17,'present',3,'2026-11-11 13:42:33'),
(146,1,5,17,'present',3,'2026-11-11 13:42:33'),
(147,1,9,17,'present',3,'2026-11-11 13:42:33'),
(148,1,13,17,'present',3,'2026-11-11 13:42:33'),
(149,1,17,17,'excused',3,'2026-11-11 13:42:33'),
(150,1,21,17,'present',3,'2026-11-11 13:42:33'),
(151,1,25,17,'excused',3,'2026-11-11 13:42:33'),
(152,1,29,17,'present',3,'2026-11-11 13:42:33'),
(153,1,33,17,'present',3,'2026-11-11 13:42:33'),
(154,1,3,18,'absent',3,'2026-11-11 13:42:33'),
(155,1,7,18,'present',3,'2026-11-11 13:42:33'),
(156,1,11,18,'present',3,'2026-11-11 13:42:33'),
(157,1,15,18,'present',3,'2026-11-11 13:42:33'),
(158,1,19,18,'absent',3,'2026-11-11 13:42:33'),
(159,1,23,18,'present',3,'2026-11-11 13:42:33'),
(160,1,27,18,'absent',3,'2026-11-11 13:42:33'),
(161,1,31,18,'present',3,'2026-11-11 13:42:33'),
(162,1,35,18,'excused',3,'2026-11-11 13:42:33'),
(163,1,2,19,'present',4,'2026-11-11 13:42:33'),
(164,1,6,19,'present',4,'2026-11-11 13:42:33'),
(165,1,10,19,'present',4,'2026-11-11 13:42:33'),
(166,1,14,19,'present',4,'2026-11-11 13:42:33'),
(167,1,18,19,'absent',4,'2026-11-11 13:42:33'),
(168,1,22,19,'present',4,'2026-11-11 13:42:33'),
(169,1,26,19,'present',4,'2026-11-11 13:42:33'),
(170,1,30,19,'absent',4,'2026-11-11 13:42:33'),
(171,1,34,19,'present',4,'2026-11-11 13:42:33'),
(172,1,4,20,'present',4,'2026-11-11 13:42:33'),
(173,1,8,20,'present',4,'2026-11-11 13:42:33'),
(174,1,12,20,'present',4,'2026-11-11 13:42:33'),
(175,1,16,20,'present',4,'2026-11-11 13:42:33'),
(176,1,20,20,'present',4,'2026-11-11 13:42:33'),
(177,1,24,20,'present',4,'2026-11-11 13:42:33'),
(178,1,28,20,'present',4,'2026-11-11 13:42:33'),
(179,1,32,20,'excused',4,'2026-11-11 13:42:33'),
(180,1,36,20,'excused',4,'2026-11-11 13:42:33'),
(181,1,1,21,'present',3,'2026-11-11 13:42:33'),
(182,1,5,21,'present',3,'2026-11-11 13:42:33'),
(183,1,9,21,'excused',3,'2026-11-11 13:42:33'),
(184,1,13,21,'excused',3,'2026-11-11 13:42:33'),
(185,1,17,21,'present',3,'2026-11-11 13:42:33'),
(186,1,21,21,'absent',3,'2026-11-11 13:42:33'),
(187,1,25,21,'absent',3,'2026-11-11 13:42:33'),
(188,1,29,21,'present',3,'2026-11-11 13:42:33'),
(189,1,33,21,'excused',3,'2026-11-11 13:42:33'),
(190,1,3,22,'excused',3,'2026-11-11 13:42:33'),
(191,1,7,22,'absent',3,'2026-11-11 13:42:33'),
(192,1,11,22,'present',3,'2026-11-11 13:42:33'),
(193,1,15,22,'absent',3,'2026-11-11 13:42:33'),
(194,1,19,22,'excused',3,'2026-11-11 13:42:33'),
(195,1,23,22,'present',3,'2026-11-11 13:42:33'),
(196,1,27,22,'present',3,'2026-11-11 13:42:33'),
(197,1,31,22,'present',3,'2026-11-11 13:42:33'),
(198,1,35,22,'excused',3,'2026-11-11 13:42:33'),
(199,1,2,23,'present',4,'2026-11-11 13:42:33'),
(200,1,6,23,'present',4,'2026-11-11 13:42:33'),
(201,1,10,23,'present',4,'2026-11-11 13:42:33'),
(202,1,14,23,'present',4,'2026-11-11 13:42:33'),
(203,1,18,23,'excused',4,'2026-11-11 13:42:33'),
(204,1,22,23,'present',4,'2026-11-11 13:42:33'),
(205,1,26,23,'present',4,'2026-11-11 13:42:33'),
(206,1,30,23,'present',4,'2026-11-11 13:42:33'),
(207,1,34,23,'excused',4,'2026-11-11 13:42:33'),
(208,1,4,24,'present',4,'2026-11-11 13:42:33'),
(209,1,8,24,'excused',4,'2026-11-11 13:42:33'),
(210,1,12,24,'excused',4,'2026-11-11 13:42:33'),
(211,1,16,24,'present',4,'2026-11-11 13:42:33'),
(212,1,20,24,'present',4,'2026-11-11 13:42:33'),
(213,1,24,24,'excused',4,'2026-11-11 13:42:33'),
(214,1,28,24,'present',4,'2026-11-11 13:42:33'),
(215,1,32,24,'present',4,'2026-11-11 13:42:33'),
(216,1,36,24,'present',4,'2026-11-11 13:42:33'),
(217,1,1,25,'present',3,'2026-11-11 13:42:33'),
(218,1,5,25,'present',3,'2026-11-11 13:42:33'),
(219,1,9,25,'present',3,'2026-11-11 13:42:33'),
(220,1,13,25,'present',3,'2026-11-11 13:42:33'),
(221,1,17,25,'present',3,'2026-11-11 13:42:33'),
(222,1,21,25,'excused',3,'2026-11-11 13:42:33'),
(223,1,25,25,'present',3,'2026-11-11 13:42:33'),
(224,1,29,25,'present',3,'2026-11-11 13:42:33'),
(225,1,33,25,'excused',3,'2026-11-11 13:42:33'),
(226,1,3,26,'excused',3,'2026-11-11 13:42:33'),
(227,1,7,26,'present',3,'2026-11-11 13:42:33'),
(228,1,11,26,'present',3,'2026-11-11 13:42:33'),
(229,1,15,26,'present',3,'2026-11-11 13:42:33'),
(230,1,19,26,'present',3,'2026-11-11 13:42:33'),
(231,1,23,26,'present',3,'2026-11-11 13:42:33'),
(232,1,27,26,'present',3,'2026-11-11 13:42:33'),
(233,1,31,26,'present',3,'2026-11-11 13:42:33'),
(234,1,35,26,'absent',3,'2026-11-11 13:42:33'),
(235,1,2,27,'present',4,'2026-11-11 13:42:33'),
(236,1,6,27,'present',4,'2026-11-11 13:42:33'),
(237,1,10,27,'present',4,'2026-11-11 13:42:33'),
(238,1,14,27,'absent',4,'2026-11-11 13:42:33'),
(239,1,18,27,'present',4,'2026-11-11 13:42:33'),
(240,1,22,27,'absent',4,'2026-11-11 13:42:33'),
(241,1,26,27,'present',4,'2026-11-11 13:42:33'),
(242,1,30,27,'present',4,'2026-11-11 13:42:33'),
(243,1,34,27,'present',4,'2026-11-11 13:42:33'),
(244,1,4,28,'present',4,'2026-11-11 13:42:33'),
(245,1,8,28,'absent',4,'2026-11-11 13:42:33'),
(246,1,12,28,'excused',4,'2026-11-11 13:42:33'),
(247,1,16,28,'present',4,'2026-11-11 13:42:33'),
(248,1,20,28,'excused',4,'2026-11-11 13:42:33'),
(249,1,24,28,'absent',4,'2026-11-11 13:42:33'),
(250,1,28,28,'excused',4,'2026-11-11 13:42:33'),
(251,1,32,28,'present',4,'2026-11-11 13:42:33'),
(252,1,36,28,'present',4,'2026-11-11 13:42:33'),
(253,1,1,29,'excused',3,'2026-11-11 13:42:33'),
(254,1,5,29,'present',3,'2026-11-11 13:42:33'),
(255,1,9,29,'present',3,'2026-11-11 13:42:33'),
(256,1,13,29,'excused',3,'2026-11-11 13:42:33'),
(257,1,17,29,'present',3,'2026-11-11 13:42:33'),
(258,1,21,29,'present',3,'2026-11-11 13:42:33'),
(259,1,25,29,'present',3,'2026-11-11 13:42:33'),
(260,1,29,29,'present',3,'2026-11-11 13:42:33'),
(261,1,33,29,'absent',3,'2026-11-11 13:42:33'),
(262,1,3,30,'present',3,'2026-11-11 13:42:33'),
(263,1,7,30,'present',3,'2026-11-11 13:42:33'),
(264,1,11,30,'present',3,'2026-11-11 13:42:33'),
(265,1,15,30,'excused',3,'2026-11-11 13:42:33'),
(266,1,19,30,'present',3,'2026-11-11 13:42:33'),
(267,1,23,30,'absent',3,'2026-11-11 13:42:33'),
(268,1,27,30,'present',3,'2026-11-11 13:42:33'),
(269,1,31,30,'excused',3,'2026-11-11 13:42:33'),
(270,1,35,30,'present',3,'2026-11-11 13:42:33'),
(271,1,2,31,'excused',4,'2026-11-11 13:42:33'),
(272,1,6,31,'present',4,'2026-11-11 13:42:33'),
(273,1,10,31,'absent',4,'2026-11-11 13:42:33'),
(274,1,14,31,'absent',4,'2026-11-11 13:42:33'),
(275,1,18,31,'present',4,'2026-11-11 13:42:33'),
(276,1,22,31,'absent',4,'2026-11-11 13:42:33'),
(277,1,26,31,'present',4,'2026-11-11 13:42:33'),
(278,1,30,31,'present',4,'2026-11-11 13:42:33'),
(279,1,34,31,'excused',4,'2026-11-11 13:42:33'),
(280,1,4,32,'present',4,'2026-11-11 13:42:33'),
(281,1,8,32,'excused',4,'2026-11-11 13:42:33'),
(282,1,12,32,'excused',4,'2026-11-11 13:42:33'),
(283,1,16,32,'excused',4,'2026-11-11 13:42:33'),
(284,1,20,32,'present',4,'2026-11-11 13:42:33'),
(285,1,24,32,'present',4,'2026-11-11 13:42:33'),
(286,1,28,32,'excused',4,'2026-11-11 13:42:33'),
(287,1,32,32,'present',4,'2026-11-11 13:42:33'),
(288,1,36,32,'present',4,'2026-11-11 13:42:33'),
(289,1,1,33,'present',3,'2026-11-11 13:42:33'),
(290,1,5,33,'present',3,'2026-11-11 13:42:33'),
(291,1,9,33,'present',3,'2026-11-11 13:42:33'),
(292,1,13,33,'present',3,'2026-11-11 13:42:33'),
(293,1,17,33,'present',3,'2026-11-11 13:42:33'),
(294,1,21,33,'absent',3,'2026-11-11 13:42:33'),
(295,1,25,33,'present',3,'2026-11-11 13:42:33'),
(296,1,29,33,'present',3,'2026-11-11 13:42:33'),
(297,1,33,33,'absent',3,'2026-11-11 13:42:33'),
(298,1,3,34,'present',3,'2026-11-11 13:42:33'),
(299,1,7,34,'present',3,'2026-11-11 13:42:33'),
(300,1,11,34,'present',3,'2026-11-11 13:42:33'),
(301,1,15,34,'absent',3,'2026-11-11 13:42:33'),
(302,1,19,34,'present',3,'2026-11-11 13:42:33'),
(303,1,23,34,'present',3,'2026-11-11 13:42:33'),
(304,1,27,34,'present',3,'2026-11-11 13:42:33'),
(305,1,31,34,'present',3,'2026-11-11 13:42:33'),
(306,1,35,34,'present',3,'2026-11-11 13:42:33'),
(307,1,2,35,'absent',4,'2026-11-11 13:42:33'),
(308,1,6,35,'present',4,'2026-11-11 13:42:33'),
(309,1,10,35,'excused',4,'2026-11-11 13:42:33'),
(310,1,14,35,'present',4,'2026-11-11 13:42:33'),
(311,1,18,35,'present',4,'2026-11-11 13:42:33'),
(312,1,22,35,'excused',4,'2026-11-11 13:42:33'),
(313,1,26,35,'present',4,'2026-11-11 13:42:33'),
(314,1,30,35,'absent',4,'2026-11-11 13:42:33'),
(315,1,34,35,'present',4,'2026-11-11 13:42:33'),
(316,1,4,36,'present',4,'2026-11-11 13:42:33'),
(317,1,8,36,'present',4,'2026-11-11 13:42:33'),
(318,1,12,36,'present',4,'2026-11-11 13:42:33'),
(319,1,16,36,'present',4,'2026-11-11 13:42:33'),
(320,1,20,36,'absent',4,'2026-11-11 13:42:33'),
(321,1,24,36,'excused',4,'2026-11-11 13:42:33'),
(322,1,28,36,'present',4,'2026-11-11 13:42:33'),
(323,1,32,36,'present',4,'2026-11-11 13:42:33'),
(324,1,36,36,'excused',4,'2026-11-11 13:42:33'),
(325,1,1,37,'present',3,'2026-11-11 13:42:33'),
(326,1,5,37,'present',3,'2026-11-11 13:42:33'),
(327,1,9,37,'present',3,'2026-11-11 13:42:33'),
(328,1,13,37,'excused',3,'2026-11-11 13:42:33'),
(329,1,17,37,'excused',3,'2026-11-11 13:42:33'),
(330,1,21,37,'present',3,'2026-11-11 13:42:33'),
(331,1,25,37,'excused',3,'2026-11-11 13:42:33'),
(332,1,29,37,'present',3,'2026-11-11 13:42:33'),
(333,1,33,37,'present',3,'2026-11-11 13:42:33'),
(334,1,3,38,'present',3,'2026-11-11 13:42:33'),
(335,1,7,38,'absent',3,'2026-11-11 13:42:33'),
(336,1,11,38,'present',3,'2026-11-11 13:42:33'),
(337,1,15,38,'present',3,'2026-11-11 13:42:33'),
(338,1,19,38,'excused',3,'2026-11-11 13:42:33'),
(339,1,23,38,'present',3,'2026-11-11 13:42:33'),
(340,1,27,38,'excused',3,'2026-11-11 13:42:33'),
(341,1,31,38,'excused',3,'2026-11-11 13:42:33'),
(342,1,35,38,'absent',3,'2026-11-11 13:42:33'),
(343,1,2,39,'absent',4,'2026-11-11 13:42:33'),
(344,1,6,39,'absent',4,'2026-11-11 13:42:33'),
(345,1,10,39,'present',4,'2026-11-11 13:42:33'),
(346,1,14,39,'present',4,'2026-11-11 13:42:33'),
(347,1,18,39,'excused',4,'2026-11-11 13:42:33'),
(348,1,22,39,'present',4,'2026-11-11 13:42:33'),
(349,1,26,39,'present',4,'2026-11-11 13:42:33'),
(350,1,30,39,'absent',4,'2026-11-11 13:42:33'),
(351,1,34,39,'present',4,'2026-11-11 13:42:33'),
(352,1,4,40,'present',4,'2026-11-11 13:42:33'),
(353,1,8,40,'excused',4,'2026-11-11 13:42:33'),
(354,1,12,40,'present',4,'2026-11-11 13:42:33'),
(355,1,16,40,'absent',4,'2026-11-11 13:42:33'),
(356,1,20,40,'present',4,'2026-11-11 13:42:33'),
(357,1,24,40,'present',4,'2026-11-11 13:42:33'),
(358,1,28,40,'present',4,'2026-11-11 13:42:33'),
(359,1,32,40,'present',4,'2026-11-11 13:42:33'),
(360,1,36,40,'present',4,'2026-11-11 13:42:33'),
(361,1,1,41,'present',3,'2026-11-11 13:42:33'),
(362,1,5,41,'present',3,'2026-11-11 13:42:33'),
(363,1,9,41,'present',3,'2026-11-11 13:42:33'),
(364,1,13,41,'present',3,'2026-11-11 13:42:33'),
(365,1,17,41,'excused',3,'2026-11-11 13:42:33'),
(366,1,21,41,'present',3,'2026-11-11 13:42:33'),
(367,1,25,41,'absent',3,'2026-11-11 13:42:33'),
(368,1,29,41,'present',3,'2026-11-11 13:42:33'),
(369,1,33,41,'absent',3,'2026-11-11 13:42:33'),
(370,1,3,42,'present',3,'2026-11-11 13:42:33'),
(371,1,7,42,'present',3,'2026-11-11 13:42:33'),
(372,1,11,42,'excused',3,'2026-11-11 13:42:33'),
(373,1,15,42,'present',3,'2026-11-11 13:42:33'),
(374,1,19,42,'excused',3,'2026-11-11 13:42:33'),
(375,1,23,42,'excused',3,'2026-11-11 13:42:33'),
(376,1,27,42,'present',3,'2026-11-11 13:42:33'),
(377,1,31,42,'excused',3,'2026-11-11 13:42:33'),
(378,1,35,42,'present',3,'2026-11-11 13:42:33'),
(379,1,2,43,'present',4,'2026-11-11 13:42:33'),
(380,1,6,43,'absent',4,'2026-11-11 13:42:33'),
(381,1,10,43,'present',4,'2026-11-11 13:42:33'),
(382,1,14,43,'absent',4,'2026-11-11 13:42:33'),
(383,1,18,43,'present',4,'2026-11-11 13:42:33'),
(384,1,22,43,'excused',4,'2026-11-11 13:42:33'),
(385,1,26,43,'present',4,'2026-11-11 13:42:33'),
(386,1,30,43,'present',4,'2026-11-11 13:42:33'),
(387,1,34,43,'absent',4,'2026-11-11 13:42:33'),
(388,1,4,44,'present',4,'2026-11-11 13:42:33'),
(389,1,8,44,'excused',4,'2026-11-11 13:42:33'),
(390,1,12,44,'present',4,'2026-11-11 13:42:33'),
(391,1,16,44,'absent',4,'2026-11-11 13:42:33'),
(392,1,20,44,'present',4,'2026-11-11 13:42:33'),
(393,1,24,44,'present',4,'2026-11-11 13:42:33'),
(394,1,28,44,'excused',4,'2026-11-11 13:42:33'),
(395,1,32,44,'present',4,'2026-11-11 13:42:33'),
(396,1,36,44,'absent',4,'2026-11-11 13:42:33');
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `entity` varchar(40) NOT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `before_data` text DEFAULT NULL,
  `after_data` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_audit_entity` (`entity`,`entity_id`),
  KEY `ix_audit_org` (`organization_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaigns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `channel` enum('instagram','tiktok','threads','other') NOT NULL,
  `budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_camp_org` (`organization_id`),
  CONSTRAINT `fk_camp_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `campaigns` WRITE;
/*!40000 ALTER TABLE `campaigns` DISABLE KEYS */;
INSERT INTO `campaigns` VALUES
(1,1,'Күзгі қабылдау','instagram',60000.00,'2026-11-01',NULL);
/*!40000 ALTER TABLE `campaigns` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `competitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `competitions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `season` varchar(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comp` (`organization_id`,`name`,`season`),
  CONSTRAINT `fk_comp_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `competitions` WRITE;
/*!40000 ALTER TABLE `competitions` DISABLE KEYS */;
INSERT INTO `competitions` VALUES
(1,1,'ЛЛФ Шымкент','2026','2026-09-12',NULL);
/*!40000 ALTER TABLE `competitions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `content_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `channel` enum('instagram','tiktok','threads') NOT NULL,
  `title` varchar(160) NOT NULL,
  `publish_at` datetime DEFAULT NULL,
  `status` enum('idea','in_work','ready','published') NOT NULL DEFAULT 'idea',
  `assignee_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_ci_org` (`organization_id`,`publish_at`),
  CONSTRAINT `fk_ci_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `content_items` WRITE;
/*!40000 ALTER TABLE `content_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `content_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `enrollments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_enr_part` (`participant_id`,`end_date`),
  KEY `ix_enr_group` (`group_id`,`end_date`),
  KEY `fk_enr_org` (`organization_id`),
  CONSTRAINT `fk_enr_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  CONSTRAINT `fk_enr_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_enr_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
INSERT INTO `enrollments` VALUES
(1,1,1,1,'2026-09-19',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(2,1,2,2,'2026-09-20',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(3,1,3,3,'2026-02-10',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(4,1,4,4,'2026-08-26',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(5,1,5,1,'2025-12-10',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(6,1,6,2,'2026-08-14',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(7,1,7,3,'2026-04-10',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(8,1,8,4,'2026-02-13',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(9,1,9,1,'2025-10-15',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(10,1,10,2,'2026-06-27',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(11,1,11,3,'2026-10-13',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(12,1,12,4,'2026-04-03',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(13,1,13,1,'2026-05-20',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(14,1,14,2,'2025-11-22',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(15,1,15,3,'2026-09-07',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(16,1,16,4,'2026-08-18',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(17,1,17,1,'2025-11-18',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(18,1,18,2,'2026-02-20',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(19,1,19,3,'2026-01-08',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(20,1,20,4,'2026-10-11',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(21,1,21,1,'2026-09-26',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(22,1,22,2,'2026-05-15',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(23,1,23,3,'2025-10-16',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(24,1,24,4,'2025-12-01',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(25,1,25,1,'2025-12-20',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(26,1,26,2,'2026-03-15',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(27,1,27,3,'2026-10-06',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(28,1,28,4,'2026-01-22',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(29,1,29,1,'2026-01-14',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(30,1,30,2,'2026-05-17',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(31,1,31,3,'2026-05-30',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(32,1,32,4,'2026-05-03',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(33,1,33,1,'2026-01-16',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(34,1,34,2,'2026-06-06',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(35,1,35,3,'2025-11-13',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(36,1,36,4,'2025-11-03',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(37,1,37,1,'2026-01-31',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(38,1,38,2,'2026-03-04',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(39,1,39,3,'2026-02-06',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(40,1,40,4,'2026-01-01',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(41,1,41,1,'2026-03-18',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(42,1,42,2,'2025-11-16',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(43,1,43,3,'2026-06-07',NULL,NULL,NULL,'2026-11-11 13:42:33'),
(44,1,44,4,'2025-10-17',NULL,NULL,NULL,'2026-11-11 13:42:33');
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `category` enum('rent','salary','equipment','marketing','tournament','other') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `spent_at` date NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_exp_org_date` (`organization_id`,`spent_at`),
  CONSTRAINT `fk_exp_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES
(1,1,'rent',350000.00,'2026-11-05','Алаң жалдау',1,'2026-11-11 13:42:33'),
(2,1,'salary',600000.00,'2026-11-05','Бапкерлер айлығы',1,'2026-11-11 13:42:33'),
(3,1,'equipment',85000.00,'2026-11-05','Доптар, форма',1,'2026-11-11 13:42:33'),
(4,1,'marketing',60000.00,'2026-11-05','Instagram жарнама',1,'2026-11-11 13:42:33');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `freezes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `freezes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `subscription_id` int(10) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days` tinyint(3) unsigned NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` enum('requested','approved','rejected') NOT NULL DEFAULT 'requested',
  `requested_by` int(10) unsigned DEFAULT NULL,
  `decided_by` int(10) unsigned DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_freeze_sub` (`subscription_id`,`status`),
  KEY `fk_frz_org` (`organization_id`),
  CONSTRAINT `fk_frz_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_frz_sub` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `freezes` WRITE;
/*!40000 ALTER TABLE `freezes` DISABLE KEYS */;
INSERT INTO `freezes` VALUES
(1,1,41,'2026-11-12','2026-11-14',3,'Ауырып қалды','requested',5,NULL,NULL,'2026-11-11 13:42:33');
/*!40000 ALTER TABLE `freezes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `name` varchar(80) NOT NULL,
  `direction` enum('kids','adult') NOT NULL,
  `age_from` tinyint(3) unsigned DEFAULT NULL,
  `age_to` tinyint(3) unsigned DEFAULT NULL,
  `coach_id` int(10) unsigned DEFAULT NULL,
  `venue_id` int(10) unsigned DEFAULT NULL,
  `capacity` smallint(5) unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_group_name` (`organization_id`,`name`),
  KEY `ix_group_coach` (`coach_id`),
  KEY `fk_group_venue` (`venue_id`),
  CONSTRAINT `fk_group_coach` FOREIGN KEY (`coach_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_group_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_group_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
INSERT INTO `groups` VALUES
(1,1,'KIDS U8','kids',6,8,3,1,20,1,'2026-11-11 13:42:33'),
(2,1,'KIDS U11','kids',9,11,3,1,20,1,'2026-11-11 13:42:33'),
(3,1,'ADULT A','adult',18,45,4,2,20,1,'2026-11-11 13:42:33'),
(4,1,'ADULT B','adult',18,45,4,2,20,1,'2026-11-11 13:42:33');
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guardians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guardians` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `phone` varchar(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_guardian_phone` (`organization_id`,`phone`),
  KEY `ix_guardian_user` (`user_id`),
  CONSTRAINT `fk_guard_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_guard_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guardians` WRITE;
/*!40000 ALTER TABLE `guardians` DISABLE KEYS */;
INSERT INTO `guardians` VALUES
(1,1,5,'Гүлнар Ата-ана','87000000005','2026-11-11 13:42:33'),
(2,1,NULL,'Ата-анасы','87011432415','2026-11-11 13:42:33'),
(3,1,NULL,'Ата-анасы','87010066563','2026-11-11 13:42:33'),
(4,1,NULL,'Ата-анасы','87016530171','2026-11-11 13:42:33'),
(5,1,NULL,'Ата-анасы','87011196576','2026-11-11 13:42:33'),
(6,1,NULL,'Ата-анасы','87019353836','2026-11-11 13:42:33'),
(7,1,NULL,'Ата-анасы','87018128733','2026-11-11 13:42:33'),
(8,1,NULL,'Ата-анасы','87012081736','2026-11-11 13:42:33'),
(9,1,NULL,'Ата-анасы','87019238666','2026-11-11 13:42:33'),
(10,1,NULL,'Ата-анасы','87010974642','2026-11-11 13:42:33'),
(11,1,NULL,'Ата-анасы','87018609574','2026-11-11 13:42:33'),
(12,1,NULL,'Ата-анасы','87011931844','2026-11-11 13:42:33'),
(13,1,NULL,'Ата-анасы','87010184696','2026-11-11 13:42:33'),
(14,1,NULL,'Ата-анасы','87015392724','2026-11-11 13:42:33'),
(15,1,NULL,'Ата-анасы','87013719143','2026-11-11 13:42:33'),
(16,1,NULL,'Ата-анасы','87011626206','2026-11-11 13:42:33'),
(17,1,NULL,'Ата-анасы','87013180168','2026-11-11 13:42:33'),
(18,1,NULL,'Ата-анасы','87016217921','2026-11-11 13:42:33'),
(19,1,NULL,'Ата-анасы','87011753228','2026-11-11 13:42:33'),
(20,1,NULL,'Ата-анасы','87018691390','2026-11-11 13:42:33'),
(21,1,NULL,'Ата-анасы','87015575098','2026-11-11 13:42:33');
/*!40000 ALTER TABLE `guardians` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `number` varchar(30) NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `subscription_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('open','paid','partial','void') NOT NULL DEFAULT 'open',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoice_number` (`organization_id`,`number`),
  KEY `ix_inv_part` (`participant_id`,`status`),
  KEY `fk_inv_sub` (`subscription_id`),
  CONSTRAINT `fk_inv_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_inv_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
  CONSTRAINT `fk_inv_sub` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `phone` varchar(11) NOT NULL,
  `direction` enum('kids','adult') DEFAULT NULL,
  `child_age` tinyint(3) unsigned DEFAULT NULL,
  `source` enum('instagram','tiktok','threads','whatsapp','referral','walk_in','other') NOT NULL DEFAULT 'other',
  `campaign_id` int(10) unsigned DEFAULT NULL,
  `status` enum('new','contacted','trial_booked','trial_done','converted','lost') NOT NULL DEFAULT 'new',
  `trial_session_id` int(10) unsigned DEFAULT NULL,
  `participant_id` int(10) unsigned DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_lead_org_status` (`organization_id`,`status`,`created_at`),
  KEY `ix_lead_phone` (`organization_id`,`phone`),
  KEY `fk_lead_camp` (`campaign_id`),
  KEY `fk_lead_sess` (`trial_session_id`),
  KEY `fk_lead_part` (`participant_id`),
  CONSTRAINT `fk_lead_camp` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`),
  CONSTRAINT `fk_lead_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_lead_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
  CONSTRAINT `fk_lead_sess` FOREIGN KEY (`trial_session_id`) REFERENCES `sessions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES
(1,1,'Айгерім','87020000000','kids',NULL,'instagram',1,'new',NULL,NULL,NULL,'2026-11-11 13:42:33',NULL),
(2,1,'Бауыржан','87020000001','adult',NULL,'tiktok',1,'new',NULL,NULL,NULL,'2026-11-11 13:42:33',NULL),
(3,1,'Сәуле','87020000002','kids',NULL,'instagram',1,'new',NULL,NULL,NULL,'2026-11-11 13:42:33',NULL),
(4,1,'Қанат','87020000003','adult',NULL,'whatsapp',1,'contacted',NULL,NULL,NULL,'2026-11-11 13:42:33',NULL),
(5,1,'Дана','87020000004','kids',NULL,'referral',1,'trial_booked',NULL,NULL,NULL,'2026-11-11 13:42:33',NULL),
(6,1,'Тимур','87020000005','adult',NULL,'instagram',1,'converted',NULL,NULL,NULL,'2026-11-11 13:42:33',NULL);
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `live_streams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_streams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `match_id` int(10) unsigned DEFAULT NULL,
  `platform` enum('youtube','instagram','tiktok') NOT NULL,
  `title` varchar(160) NOT NULL,
  `url` varchar(255) NOT NULL,
  `youtube_id` varchar(20) DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `status` enum('scheduled','live','ended') NOT NULL DEFAULT 'scheduled',
  PRIMARY KEY (`id`),
  KEY `ix_live_org` (`organization_id`,`starts_at`),
  KEY `fk_live_match` (`match_id`),
  CONSTRAINT `fk_live_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`),
  CONSTRAINT `fk_live_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `live_streams` WRITE;
/*!40000 ALTER TABLE `live_streams` DISABLE KEYS */;
/*!40000 ALTER TABLE `live_streams` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_login_phone` (`phone`,`created_at`),
  KEY `ix_login_ip` (`ip`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `match_player_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `match_player_stats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `match_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `is_starter` tinyint(1) NOT NULL DEFAULT 0,
  `minute_in` tinyint(3) unsigned DEFAULT NULL,
  `minute_out` tinyint(3) unsigned DEFAULT NULL,
  `goals` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `assists` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `yellow` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `red` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mps` (`match_id`,`participant_id`),
  KEY `ix_mps_part` (`participant_id`),
  KEY `fk_mps_org` (`organization_id`),
  CONSTRAINT `fk_mps_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`),
  CONSTRAINT `fk_mps_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_mps_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `match_player_stats` WRITE;
/*!40000 ALTER TABLE `match_player_stats` DISABLE KEYS */;
INSERT INTO `match_player_stats` VALUES
(1,1,1,3,1,0,90,0,0,0,0),
(2,1,1,4,1,0,90,0,0,0,0),
(3,1,1,12,1,0,90,0,0,0,0),
(4,1,1,24,1,0,90,1,0,1,0),
(5,1,1,31,1,0,90,1,0,0,0),
(6,1,1,32,1,0,90,1,1,0,0),
(7,1,1,43,1,0,90,0,1,0,0),
(8,1,2,3,1,0,90,0,0,0,0),
(9,1,2,4,1,0,90,0,0,0,0),
(10,1,2,12,1,0,90,0,0,0,0),
(11,1,2,24,1,0,90,1,0,1,0),
(12,1,2,31,1,0,90,1,0,0,0),
(13,1,2,32,1,0,90,0,1,0,0),
(14,1,2,43,1,0,90,0,1,0,0);
/*!40000 ALTER TABLE `match_player_stats` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `matches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `competition_id` int(10) unsigned DEFAULT NULL,
  `team_id` int(10) unsigned NOT NULL,
  `opponent_id` int(10) unsigned NOT NULL,
  `round` varchar(30) DEFAULT NULL,
  `match_at` datetime NOT NULL,
  `venue` varchar(160) DEFAULT NULL,
  `is_home` tinyint(1) NOT NULL DEFAULT 1,
  `goals_for` tinyint(3) unsigned DEFAULT NULL,
  `goals_against` tinyint(3) unsigned DEFAULT NULL,
  `status` enum('scheduled','live','finished','cancelled') NOT NULL DEFAULT 'scheduled',
  `mvp_participant_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_match_org_date` (`organization_id`,`match_at`),
  KEY `fk_m_comp` (`competition_id`),
  KEY `fk_m_team` (`team_id`),
  KEY `fk_m_opp` (`opponent_id`),
  KEY `fk_m_mvp` (`mvp_participant_id`),
  CONSTRAINT `fk_m_comp` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`),
  CONSTRAINT `fk_m_mvp` FOREIGN KEY (`mvp_participant_id`) REFERENCES `participants` (`id`),
  CONSTRAINT `fk_m_opp` FOREIGN KEY (`opponent_id`) REFERENCES `opponents` (`id`),
  CONSTRAINT `fk_m_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_m_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `matches` WRITE;
/*!40000 ALTER TABLE `matches` DISABLE KEYS */;
INSERT INTO `matches` VALUES
(1,1,1,1,1,'1-тур','2026-10-28 19:00:00','JAS Arena',1,3,1,'finished',24),
(2,1,1,1,2,'2-тур','2026-11-04 19:00:00','Тұран алаңы',1,2,2,'finished',31),
(3,1,1,1,3,'3-тур','2026-11-14 20:00:00','JAS Arena',1,NULL,NULL,'scheduled',NULL),
(4,1,1,1,4,'4-тур','2026-11-21 20:00:00','JAS Arena',0,NULL,NULL,'scheduled',NULL),
(5,1,1,1,5,'5-тур','2026-11-28 20:00:00','JAS Arena',1,NULL,NULL,'scheduled',NULL);
/*!40000 ALTER TABLE `matches` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `media_albums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_albums` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `group_id` int(10) unsigned DEFAULT NULL,
  `match_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_alb_org` (`organization_id`),
  CONSTRAINT `fk_alb_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `media_albums` WRITE;
/*!40000 ALTER TABLE `media_albums` DISABLE KEYS */;
/*!40000 ALTER TABLE `media_albums` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `media_asset_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_asset_participants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `media_asset_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_map` (`media_asset_id`,`participant_id`),
  KEY `ix_map_part` (`participant_id`),
  CONSTRAINT `fk_map_ma` FOREIGN KEY (`media_asset_id`) REFERENCES `media_assets` (`id`),
  CONSTRAINT `fk_map_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `media_asset_participants` WRITE;
/*!40000 ALTER TABLE `media_asset_participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `media_asset_participants` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `media_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_assets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `album_id` int(10) unsigned DEFAULT NULL,
  `type` enum('photo','video') NOT NULL,
  `kind` enum('general','highlight','analysis') NOT NULL DEFAULT 'general',
  `storage_key` varchar(255) NOT NULL,
  `title` varchar(160) DEFAULT NULL,
  `group_id` int(10) unsigned DEFAULT NULL,
  `match_id` int(10) unsigned DEFAULT NULL,
  `visibility` enum('staff','tagged','parents','public') NOT NULL DEFAULT 'staff',
  `uploaded_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_media_key` (`storage_key`),
  KEY `ix_media_org` (`organization_id`,`created_at`),
  KEY `fk_ma_alb` (`album_id`),
  CONSTRAINT `fk_ma_alb` FOREIGN KEY (`album_id`) REFERENCES `media_albums` (`id`),
  CONSTRAINT `fk_ma_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `media_assets` WRITE;
/*!40000 ALTER TABLE `media_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `media_assets` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `media_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_consents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `guardian_id` int(10) unsigned DEFAULT NULL,
  `scope` enum('internal','social') NOT NULL,
  `granted_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `recorded_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_cons_part` (`participant_id`,`scope`),
  KEY `fk_cons_org` (`organization_id`),
  KEY `fk_cons_guard` (`guardian_id`),
  CONSTRAINT `fk_cons_guard` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`),
  CONSTRAINT `fk_cons_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_cons_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `media_consents` WRITE;
/*!40000 ALTER TABLE `media_consents` DISABLE KEYS */;
/*!40000 ALTER TABLE `media_consents` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `memberships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `role` enum('owner','admin','coach','parent','participant') NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_membership` (`organization_id`,`user_id`,`role`),
  KEY `ix_membership_user` (`user_id`),
  CONSTRAINT `fk_mem_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_mem_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `memberships` WRITE;
/*!40000 ALTER TABLE `memberships` DISABLE KEYS */;
INSERT INTO `memberships` VALUES
(1,1,1,'owner',1,'2026-11-11 13:42:33'),
(2,1,2,'admin',1,'2026-11-11 13:42:33'),
(3,1,3,'coach',1,'2026-11-11 13:42:33'),
(4,1,4,'coach',1,'2026-11-11 13:42:33'),
(5,1,5,'parent',1,'2026-11-11 13:42:33'),
(6,1,6,'participant',1,'2026-11-11 13:42:33');
/*!40000 ALTER TABLE `memberships` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `name` varchar(120) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
('001_schema.sql','2026-11-11 13:42:33');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `type` enum('birthday','sub_expiring','schedule_changed','assessment','lesson','match','other') NOT NULL,
  `title` varchar(160) NOT NULL,
  `body` varchar(500) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `dedupe_key` varchar(80) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notif_dedupe` (`user_id`,`dedupe_key`),
  KEY `ix_notif_user` (`user_id`,`read_at`),
  KEY `fk_n_org` (`organization_id`),
  CONSTRAINT `fk_n_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_n_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `opponents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `opponents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `logo` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_opp` (`organization_id`,`name`),
  CONSTRAINT `fk_opp_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `opponents` WRITE;
/*!40000 ALTER TABLE `opponents` DISABLE KEYS */;
INSERT INTO `opponents` VALUES
(1,1,'Ордабасы Ветераны',NULL),
(2,1,'Тұран FC',NULL),
(3,1,'Жастар',NULL),
(4,1,'Арсенал Шымкент',NULL),
(5,1,'Қазығұрт FC',NULL);
/*!40000 ALTER TABLE `opponents` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `city` varchar(60) DEFAULT 'Шымкент',
  `timezone` varchar(40) NOT NULL DEFAULT 'Asia/Almaty',
  `phone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `instagram` varchar(120) DEFAULT NULL,
  `color` varchar(10) DEFAULT '#0e6b3a',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `organizations` WRITE;
/*!40000 ALTER TABLE `organizations` DISABLE KEYS */;
INSERT INTO `organizations` VALUES
(1,'JAS ACADEMY','jas','Шымкент','Asia/Almaty','87000000000','77000000000','jas.academy','#0e6b3a','2026-11-11 13:42:33');
/*!40000 ALTER TABLE `organizations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `participant_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `participant_achievements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `achievement_id` int(10) unsigned NOT NULL,
  `awarded_by` int(10) unsigned DEFAULT NULL,
  `awarded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_pa_part` (`participant_id`),
  KEY `fk_pa_org` (`organization_id`),
  KEY `fk_pa_ach` (`achievement_id`),
  CONSTRAINT `fk_pa_ach` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`),
  CONSTRAINT `fk_pa_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_pa_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `participant_achievements` WRITE;
/*!40000 ALTER TABLE `participant_achievements` DISABLE KEYS */;
/*!40000 ALTER TABLE `participant_achievements` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `participant_guardians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `participant_guardians` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `guardian_id` int(10) unsigned NOT NULL,
  `relation` enum('mother','father','other') DEFAULT 'other',
  `is_primary` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_part_guard` (`participant_id`,`guardian_id`),
  KEY `ix_pg_guardian` (`guardian_id`),
  KEY `fk_pg_org` (`organization_id`),
  CONSTRAINT `fk_pg_guard` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`),
  CONSTRAINT `fk_pg_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_pg_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `participant_guardians` WRITE;
/*!40000 ALTER TABLE `participant_guardians` DISABLE KEYS */;
INSERT INTO `participant_guardians` VALUES
(1,1,1,1,'mother',1),
(2,1,2,1,'mother',1),
(3,1,5,2,'mother',1),
(4,1,6,3,'mother',1),
(5,1,9,4,'mother',1),
(6,1,10,5,'mother',1),
(7,1,13,6,'mother',1),
(8,1,14,7,'mother',1),
(9,1,17,8,'mother',1),
(10,1,18,9,'mother',1),
(11,1,21,10,'mother',1),
(12,1,22,11,'mother',1),
(13,1,25,12,'mother',1),
(14,1,26,13,'mother',1),
(15,1,29,14,'mother',1),
(16,1,30,15,'mother',1),
(17,1,33,16,'mother',1),
(18,1,34,17,'mother',1),
(19,1,37,18,'mother',1),
(20,1,38,19,'mother',1),
(21,1,41,20,'mother',1),
(22,1,42,21,'mother',1);
/*!40000 ALTER TABLE `participant_guardians` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `participants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `birth_date` date NOT NULL,
  `gender` enum('m','f') DEFAULT 'm',
  `phone` varchar(11) DEFAULT NULL,
  `img` varchar(120) DEFAULT NULL,
  `direction` enum('kids','adult') NOT NULL,
  `position` enum('gk','df','mf','fw') DEFAULT NULL,
  `foot` enum('right','left','both') DEFAULT NULL,
  `number` tinyint(3) unsigned DEFAULT NULL,
  `joined_at` date NOT NULL,
  `status` enum('active','paused','left') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_participant_user` (`organization_id`,`user_id`),
  KEY `ix_part_org_status` (`organization_id`,`status`,`direction`),
  KEY `ix_part_name` (`organization_id`,`last_name`,`first_name`),
  KEY `ix_part_phone` (`organization_id`,`phone`),
  KEY `ix_part_birth` (`organization_id`,`birth_date`),
  KEY `fk_part_user` (`user_id`),
  KEY `fk_part_created` (`created_by`),
  CONSTRAINT `fk_part_created` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_part_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_part_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `participants` WRITE;
/*!40000 ALTER TABLE `participants` DISABLE KEYS */;
INSERT INTO `participants` VALUES
(1,1,NULL,'Алихан','Омаров',NULL,'2018-11-12','m',NULL,NULL,'kids','fw','right',8,'2026-09-19','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(2,1,NULL,'Нұрсұлтан','Әбдіразақов',NULL,'2018-11-14','m',NULL,NULL,'kids','mf','left',85,'2026-09-20','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(3,1,6,'Ерасыл','Бекболатов',NULL,'2002-11-16','m','87074094176',NULL,'adult','mf','both',41,'2026-02-10','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(4,1,NULL,'Әлинұр','Сейітов',NULL,'1995-01-15','m','87071550963',NULL,'adult','mf','right',21,'2026-08-26','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(5,1,NULL,'Бекзат','Бекболатов',NULL,'2020-06-01','m',NULL,NULL,'kids','df','right',42,'2025-12-10','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(6,1,NULL,'Темірлан','Исаев',NULL,'2017-03-07','m',NULL,NULL,'kids','fw','right',76,'2026-08-14','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(7,1,NULL,'Дінмұхамед','Жұмабаев',NULL,'1992-02-18','m','87073332401',NULL,'adult','mf','left',18,'2026-04-10','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(8,1,NULL,'Санжар','Омаров',NULL,'2000-08-20','m','87075773606',NULL,'adult','df','right',41,'2026-02-13','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(9,1,NULL,'Ақжол','Омаров',NULL,'2019-03-09','m',NULL,NULL,'kids','df','right',64,'2025-10-15','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(10,1,NULL,'Мирас','Сейітов',NULL,'2016-04-21','m',NULL,NULL,'kids','mf','right',20,'2026-06-27','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(11,1,NULL,'Асылжан','Мұратов',NULL,'1993-10-27','m','87072421134',NULL,'adult','fw','right',61,'2026-10-13','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(12,1,NULL,'Ернұр','Тұрсынов',NULL,'1997-11-13','m','87071511664',NULL,'adult','df','right',8,'2026-04-03','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(13,1,NULL,'Жанибек','Бекболатов',NULL,'2019-03-06','m',NULL,NULL,'kids','mf','left',22,'2026-05-20','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(14,1,NULL,'Әміржан','Тұрсынов',NULL,'2017-12-09','m',NULL,NULL,'kids','fw','right',8,'2025-11-22','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(15,1,NULL,'Арсен','Бекболатов',NULL,'1992-07-18','m','87077841749',NULL,'adult','mf','both',32,'2026-09-07','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(16,1,NULL,'Батыр','Исаев',NULL,'2002-06-27','m','87075744422',NULL,'adult','gk','left',61,'2026-08-18','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(17,1,NULL,'Елдос','Жұмабаев',NULL,'2018-05-07','m',NULL,NULL,'kids','gk','right',19,'2025-11-18','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(18,1,NULL,'Нұрислам','Сәрсенов',NULL,'2015-10-27','m',NULL,NULL,'kids','mf','right',96,'2026-02-20','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(19,1,NULL,'Райымбек','Тұрсынов',NULL,'1998-06-09','m','87075951046',NULL,'adult','mf','left',11,'2026-01-08','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(20,1,NULL,'Жансерік','Бекболатов',NULL,'2004-12-19','m','87078647072',NULL,'adult','fw','right',14,'2026-10-11','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(21,1,NULL,'Алихан','Омаров',NULL,'2019-11-24','m',NULL,NULL,'kids','mf','right',48,'2026-09-26','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(22,1,NULL,'Нұрсұлтан','Тұрсынов',NULL,'2015-12-19','m',NULL,NULL,'kids','gk','right',17,'2026-05-15','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(23,1,NULL,'Ерасыл','Бекболатов',NULL,'1999-10-04','m','87075628461',NULL,'adult','df','right',37,'2025-10-16','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(24,1,NULL,'Әлинұр','Исаев',NULL,'2004-08-02','m','87077538483',NULL,'adult','gk','left',77,'2025-12-01','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(25,1,NULL,'Бекзат','Құдайбергенов',NULL,'2019-07-14','m',NULL,NULL,'kids','mf','both',98,'2025-12-20','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(26,1,NULL,'Темірлан','Мұратов',NULL,'2015-09-24','m',NULL,NULL,'kids','df','both',61,'2026-03-15','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(27,1,NULL,'Дінмұхамед','Бекболатов',NULL,'2001-12-18','m','87076713822',NULL,'adult','gk','right',47,'2026-10-06','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(28,1,NULL,'Санжар','Әбдіразақов',NULL,'1997-11-01','m','87076437188',NULL,'adult','mf','right',24,'2026-01-22','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(29,1,NULL,'Ақжол','Сәрсенов',NULL,'2020-04-11','m',NULL,NULL,'kids','df','both',94,'2026-01-14','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(30,1,NULL,'Мирас','Мұратов',NULL,'2016-08-19','m',NULL,NULL,'kids','mf','right',56,'2026-05-17','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(31,1,NULL,'Асылжан','Исаев',NULL,'1993-04-11','m','87070877103',NULL,'adult','mf','right',72,'2026-05-30','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(32,1,NULL,'Ернұр','Жұмабаев',NULL,'1998-09-20','m','87072268071',NULL,'adult','df','left',37,'2026-05-03','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(33,1,NULL,'Жанибек','Омаров',NULL,'2020-05-22','m',NULL,NULL,'kids','mf','left',14,'2026-01-16','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(34,1,NULL,'Әміржан','Мұратов',NULL,'2015-03-06','m',NULL,NULL,'kids','mf','right',5,'2026-06-06','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(35,1,NULL,'Арсен','Бекболатов',NULL,'1999-12-13','m','87076189220',NULL,'adult','fw','right',33,'2025-11-13','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(36,1,NULL,'Батыр','Исаев',NULL,'2004-02-03','m','87075020979',NULL,'adult','df','left',45,'2025-11-03','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(37,1,NULL,'Елдос','Сәрсенов',NULL,'2018-12-04','m',NULL,NULL,'kids','df','right',52,'2026-01-31','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(38,1,NULL,'Нұрислам','Әбдіразақов',NULL,'2017-11-15','m',NULL,NULL,'kids','mf','right',55,'2026-03-04','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(39,1,NULL,'Райымбек','Құдайбергенов',NULL,'1992-08-22','m','87070468532',NULL,'adult','mf','both',34,'2026-02-06','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(40,1,NULL,'Жансерік','Әбдіразақов',NULL,'2002-11-18','m','87079598957',NULL,'adult','df','right',67,'2026-01-01','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(41,1,NULL,'Алихан','Тұрсынов',NULL,'2018-09-16','m',NULL,NULL,'kids','gk','left',49,'2026-03-18','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(42,1,NULL,'Нұрсұлтан','Жұмабаев',NULL,'2017-04-16','m',NULL,NULL,'kids','mf','left',45,'2025-11-16','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(43,1,NULL,'Ерасыл','Бекболатов',NULL,'1996-08-09','m','87075360574',NULL,'adult','mf','left',71,'2026-06-07','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL),
(44,1,NULL,'Әлинұр','Бекболатов',NULL,'2000-03-06','m','87075001794',NULL,'adult','df','right',15,'2025-10-17','active',NULL,2,'2026-11-11 13:42:33',NULL,NULL);
/*!40000 ALTER TABLE `participants` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_allocations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `payment_id` int(10) unsigned NOT NULL,
  `subscription_id` int(10) unsigned NOT NULL,
  `invoice_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_alloc` (`payment_id`,`subscription_id`),
  KEY `ix_alloc_sub` (`subscription_id`),
  KEY `fk_al_org` (`organization_id`),
  KEY `fk_al_inv` (`invoice_id`),
  CONSTRAINT `fk_al_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `fk_al_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_al_pay` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  CONSTRAINT `fk_al_sub` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payment_allocations` WRITE;
/*!40000 ALTER TABLE `payment_allocations` DISABLE KEYS */;
INSERT INTO `payment_allocations` VALUES
(1,1,1,1,NULL,25000.00,'2026-11-11 13:42:33'),
(2,1,2,2,NULL,25000.00,'2026-11-11 13:42:33'),
(3,1,3,3,NULL,20000.00,'2026-11-11 13:42:33'),
(4,1,4,4,NULL,20000.00,'2026-11-11 13:42:33'),
(5,1,5,5,NULL,25000.00,'2026-11-11 13:42:33'),
(6,1,6,6,NULL,25000.00,'2026-11-11 13:42:33'),
(7,1,7,7,NULL,10000.00,'2026-11-11 13:42:33'),
(8,1,8,8,NULL,20000.00,'2026-11-11 13:42:33'),
(9,1,9,9,NULL,25000.00,'2026-11-11 13:42:33'),
(10,1,10,10,NULL,25000.00,'2026-11-11 13:42:33'),
(11,1,11,12,NULL,20000.00,'2026-11-11 13:42:33'),
(12,1,12,13,NULL,25000.00,'2026-11-11 13:42:33'),
(13,1,13,14,NULL,12500.00,'2026-11-11 13:42:33'),
(14,1,14,15,NULL,20000.00,'2026-11-11 13:42:33'),
(15,1,15,16,NULL,20000.00,'2026-11-11 13:42:33'),
(16,1,16,17,NULL,25000.00,'2026-11-11 13:42:33'),
(17,1,17,18,NULL,25000.00,'2026-11-11 13:42:33'),
(18,1,18,19,NULL,20000.00,'2026-11-11 13:42:33'),
(19,1,19,20,NULL,20000.00,'2026-11-11 13:42:33'),
(20,1,20,21,NULL,12500.00,'2026-11-11 13:42:33'),
(21,1,21,23,NULL,20000.00,'2026-11-11 13:42:33'),
(22,1,22,24,NULL,20000.00,'2026-11-11 13:42:33'),
(23,1,23,25,NULL,25000.00,'2026-11-11 13:42:33'),
(24,1,24,26,NULL,25000.00,'2026-11-11 13:42:33'),
(25,1,25,27,NULL,20000.00,'2026-11-11 13:42:33'),
(26,1,26,28,NULL,10000.00,'2026-11-11 13:42:33'),
(27,1,27,29,NULL,25000.00,'2026-11-11 13:42:33'),
(28,1,28,30,NULL,25000.00,'2026-11-11 13:42:33'),
(29,1,29,31,NULL,20000.00,'2026-11-11 13:42:33'),
(30,1,30,32,NULL,20000.00,'2026-11-11 13:42:33'),
(31,1,31,34,NULL,25000.00,'2026-11-11 13:42:33'),
(32,1,32,35,NULL,10000.00,'2026-11-11 13:42:33'),
(33,1,33,36,NULL,20000.00,'2026-11-11 13:42:33'),
(34,1,34,37,NULL,25000.00,'2026-11-11 13:42:33'),
(35,1,35,38,NULL,25000.00,'2026-11-11 13:42:33'),
(36,1,36,39,NULL,20000.00,'2026-11-11 13:42:33'),
(37,1,37,40,NULL,20000.00,'2026-11-11 13:42:33'),
(38,1,38,41,NULL,25000.00,'2026-11-11 13:42:33'),
(39,1,39,42,NULL,12500.00,'2026-11-11 13:42:33'),
(40,1,40,43,NULL,20000.00,'2026-11-11 13:42:33'),
(41,1,41,14,NULL,12500.00,'2026-11-11 13:42:33'),
(42,1,42,42,NULL,12500.00,'2026-11-11 13:42:33');
/*!40000 ALTER TABLE `payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payment_method_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_method_fees` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `method` enum('kaspi','kaspi_red','bank','cash') NOT NULL,
  `plan_id` int(10) unsigned DEFAULT NULL,
  `fee_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fee` (`organization_id`,`method`,`plan_id`),
  KEY `fk_fee_plan` (`plan_id`),
  CONSTRAINT `fk_fee_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_fee_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payment_method_fees` WRITE;
/*!40000 ALTER TABLE `payment_method_fees` DISABLE KEYS */;
INSERT INTO `payment_method_fees` VALUES
(1,1,'kaspi_red',NULL,8.00);
/*!40000 ALTER TABLE `payment_method_fees` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `fee_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `method` enum('kaspi','kaspi_red','bank','cash') NOT NULL,
  `source` enum('manual','integration') NOT NULL DEFAULT 'manual',
  `kind` enum('payment','refund','reversal','adjustment') NOT NULL DEFAULT 'payment',
  `status` enum('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  `external_ref` varchar(80) DEFAULT NULL,
  `idempotency_key` varchar(64) NOT NULL,
  `reversal_of_id` int(10) unsigned DEFAULT NULL,
  `paid_at` datetime NOT NULL,
  `entered_by` int(10) unsigned NOT NULL,
  `entered_at` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmed_by` int(10) unsigned DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pay_idem` (`organization_id`,`idempotency_key`),
  UNIQUE KEY `uq_pay_reversal` (`reversal_of_id`),
  UNIQUE KEY `uq_pay_extref` (`organization_id`,`method`,`external_ref`),
  KEY `ix_pay_part` (`participant_id`,`status`),
  KEY `ix_pay_org_date` (`organization_id`,`status`,`paid_at`),
  KEY `fk_pay_enter` (`entered_by`),
  KEY `fk_pay_conf` (`confirmed_by`),
  CONSTRAINT `fk_pay_conf` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_pay_enter` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_pay_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_pay_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
  CONSTRAINT `fk_pay_rev` FOREIGN KEY (`reversal_of_id`) REFERENCES `payments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES
(1,1,1,25000.00,0.00,'cash','manual','payment','confirmed',NULL,'8ce35777a4be64ccdeabfc952f398a9a',NULL,'2026-11-10 12:00:00',2,'2026-11-11 13:42:33',2,'2026-11-10 12:05:00',NULL),
(2,1,2,25000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100002','74d5ed21db3a7dd8310e54879daa2c62',NULL,'2026-10-26 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-26 12:05:00',NULL),
(3,1,3,20000.00,0.00,'bank','manual','payment','confirmed','DEMO100003','065afd2804f24a2705e1b9277fb471fa',NULL,'2026-10-29 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-29 12:05:00',NULL),
(4,1,4,20000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100004','af7e6a6e7346c44ed3999cbff3a3a5f6',NULL,'2026-10-17 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-17 12:05:00',NULL),
(5,1,5,25000.00,2000.00,'kaspi_red','manual','payment','confirmed','DEMO100005','1583cb6bef989036a91826437f7354d5',NULL,'2026-11-01 12:00:00',2,'2026-11-11 13:42:33',2,'2026-11-01 12:05:00',NULL),
(6,1,6,25000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100006','69b119187b7cd17b09d86a5a83ead68b',NULL,'2026-10-21 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-21 12:05:00',NULL),
(7,1,7,10000.00,800.00,'kaspi_red','manual','payment','confirmed','DEMO100007','b81c04815c73747cceda3bdde4f401f0',NULL,'2026-11-10 12:00:00',2,'2026-11-11 13:42:33',2,'2026-11-10 12:05:00',NULL),
(8,1,8,20000.00,0.00,'bank','manual','payment','confirmed','DEMO100008','33ffed6e5629c61fa75c1fb1c4204c3a',NULL,'2026-10-25 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-25 12:05:00',NULL),
(9,1,9,25000.00,0.00,'cash','manual','payment','confirmed',NULL,'0cc170565f11a41abed98a560f08c987',NULL,'2026-10-16 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-16 12:05:00',NULL),
(10,1,10,25000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100010','13afcc75fcdce79e039ab18147ebb0cd',NULL,'2026-11-05 12:00:00',2,'2026-11-11 13:42:33',2,'2026-11-05 12:05:00',NULL),
(11,1,12,20000.00,0.00,'bank','manual','payment','confirmed','DEMO100012','39a6cfcefa9124225b7cd384bc76b70c',NULL,'2026-10-21 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-21 12:05:00',NULL),
(12,1,13,25000.00,0.00,'cash','manual','payment','confirmed',NULL,'7413cbe4ad2407ce42854cf42de4d2e2',NULL,'2026-11-08 12:00:00',2,'2026-11-11 13:42:33',2,'2026-11-08 12:05:00',NULL),
(13,1,14,12500.00,0.00,'bank','manual','payment','confirmed','DEMO100014','66763afb1f3621443197351d1368222d',NULL,'2026-10-15 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-15 12:05:00',NULL),
(14,1,15,20000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100015','750cc7032210407268af0407eeaf65fc',NULL,'2026-10-26 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-26 12:05:00',NULL),
(15,1,16,20000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100016','20ce7f34bb788c0578a4300d4e38a941',NULL,'2026-10-31 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-31 12:05:00',NULL),
(16,1,17,25000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100017','011f2c4f3437423176083f3e052b97fd',NULL,'2026-10-20 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-20 12:05:00',NULL),
(17,1,18,25000.00,0.00,'bank','manual','payment','confirmed','DEMO100018','b392cca990c17d6310714254fa3e9e28',NULL,'2026-10-18 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-18 12:05:00',NULL),
(18,1,19,20000.00,0.00,'cash','manual','payment','confirmed',NULL,'7a77bc055803cf7d417f6f23eb8cb4c2',NULL,'2026-10-25 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-25 12:05:00',NULL),
(19,1,20,20000.00,0.00,'cash','manual','payment','confirmed',NULL,'0f8d32ffb965c93a1294e71f1bbc65df',NULL,'2026-10-29 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-29 12:05:00',NULL),
(20,1,21,12500.00,1000.00,'kaspi_red','manual','payment','confirmed','DEMO100021','b877a3b975170dc58eabfe693044b305',NULL,'2026-11-06 12:00:00',2,'2026-11-11 13:42:33',2,'2026-11-06 12:05:00',NULL),
(21,1,23,20000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100023','999e1fe6b2752d5bdde25a52a7308444',NULL,'2026-10-26 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-26 12:05:00',NULL),
(22,1,24,20000.00,1600.00,'kaspi_red','manual','payment','confirmed','DEMO100024','ce473ab7184c3092f1a36cd81bd52723',NULL,'2026-10-26 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-26 12:05:00',NULL),
(23,1,25,25000.00,2000.00,'kaspi_red','manual','payment','confirmed','DEMO100025','b4a409c0f3f05354a67639e9d4a608c5',NULL,'2026-10-19 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-19 12:05:00',NULL),
(24,1,26,25000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100026','a393227db42a7ad37ed43938b5a87db9',NULL,'2026-10-15 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-15 12:05:00',NULL),
(25,1,27,20000.00,1600.00,'kaspi_red','manual','payment','confirmed','DEMO100027','e6b5585e9122c46086deb1aefaa735d7',NULL,'2026-10-18 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-18 12:05:00',NULL),
(26,1,28,10000.00,800.00,'kaspi_red','manual','payment','confirmed','DEMO100028','1f90e030728a2f24439a91e126649f97',NULL,'2026-10-21 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-21 12:05:00',NULL),
(27,1,29,25000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100029','1b6655d2fabf3ee181f483e5a087b45b',NULL,'2026-11-07 12:00:00',2,'2026-11-11 13:42:33',2,'2026-11-07 12:05:00',NULL),
(28,1,30,25000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100030','85430c5dc9318e0a4e300edf729e2143',NULL,'2026-11-08 12:00:00',2,'2026-11-11 13:42:33',2,'2026-11-08 12:05:00',NULL),
(29,1,31,20000.00,0.00,'bank','manual','payment','confirmed','DEMO100031','4742728d962141201818d18cb212abc3',NULL,'2026-10-20 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-20 12:05:00',NULL),
(30,1,32,20000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100032','f0cc3069ada43b155205701c01610f52',NULL,'2026-10-21 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-21 12:05:00',NULL),
(31,1,34,25000.00,0.00,'bank','manual','payment','confirmed','DEMO100034','8c22fb6780896e67ce5d8ce9e09240b4',NULL,'2026-10-23 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-23 12:05:00',NULL),
(32,1,35,10000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100035','6b78307bbca6e029f0e8aca81302a407',NULL,'2026-10-23 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-23 12:05:00',NULL),
(33,1,36,20000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100036','35131d1d87c81fb7885cf2d5edfaf815',NULL,'2026-10-20 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-20 12:05:00',NULL),
(34,1,37,25000.00,0.00,'cash','manual','payment','confirmed',NULL,'b176ebc94b008545c062b7a77fdd93ff',NULL,'2026-10-16 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-16 12:05:00',NULL),
(35,1,38,25000.00,0.00,'cash','manual','payment','confirmed',NULL,'f2661e78e5040823e7fbb8039e54ed6e',NULL,'2026-10-17 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-17 12:05:00',NULL),
(36,1,39,20000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100039','efe79472ce8605e4e6782ea42d0d6b9e',NULL,'2026-10-30 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-30 12:05:00',NULL),
(37,1,40,20000.00,0.00,'kaspi','manual','payment','confirmed','DEMO100040','4f2193c0f11794f2d7a44e45edb3944a',NULL,'2026-10-17 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-17 12:05:00',NULL),
(38,1,41,25000.00,2000.00,'kaspi_red','manual','payment','confirmed','DEMO100041','de1c91461f317d4cf514b91b6be1d9bd',NULL,'2026-10-28 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-28 12:05:00',NULL),
(39,1,42,12500.00,0.00,'cash','manual','payment','confirmed',NULL,'b931d77445e4ff452093826804832181',NULL,'2026-10-15 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-15 12:05:00',NULL),
(40,1,43,20000.00,0.00,'bank','manual','payment','confirmed','DEMO100043','ae6152866be833bfb9112bc0dcfba5f2',NULL,'2026-10-18 12:00:00',2,'2026-11-11 13:42:33',2,'2026-10-18 12:05:00',NULL),
(41,1,14,12500.00,0.00,'kaspi','manual','payment','pending','DEMO90','605f5f1e5328e676d86b52c7ba3785f6',NULL,'2026-11-11 13:42:33',2,'2026-11-11 13:42:33',NULL,NULL,NULL),
(42,1,42,12500.00,0.00,'kaspi','manual','payment','pending','DEMO91','a5dc66e777068c9ab16f58da7056070a',NULL,'2026-11-11 13:42:33',2,'2026-11-11 13:42:33',NULL,NULL,NULL);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `name` varchar(80) NOT NULL,
  `direction` enum('kids','adult') NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `sessions_limit` smallint(5) unsigned DEFAULT NULL,
  `freeze_days` tinyint(3) unsigned NOT NULL DEFAULT 7,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plan_name` (`organization_id`,`name`),
  CONSTRAINT `fk_plan_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES
(1,1,'KIDS 12 жаттығу','kids',25000.00,12,7,1),
(2,1,'ADULT 12 жаттығу','adult',20000.00,12,7,1),
(3,1,'ADULT шексіз','adult',30000.00,NULL,7,1);
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `player_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_assessments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `coach_id` int(10) unsigned NOT NULL,
  `assessed_at` date NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_pas_part` (`participant_id`,`assessed_at`),
  KEY `fk_pas_org` (`organization_id`),
  KEY `fk_pas_coach` (`coach_id`),
  CONSTRAINT `fk_pas_coach` FOREIGN KEY (`coach_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_pas_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_pas_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `player_assessments` WRITE;
/*!40000 ALTER TABLE `player_assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `player_assessments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `rating_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rating_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `scope` enum('kids','adult') NOT NULL,
  `rule` enum('attendance','streak5','streak10','month100','coach_award','progress','manual') NOT NULL,
  `points` smallint(6) NOT NULL,
  `source_type` varchar(30) NOT NULL,
  `source_id` int(10) unsigned NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rating` (`participant_id`,`rule`,`source_type`,`source_id`),
  KEY `ix_rating_org` (`organization_id`,`scope`,`created_at`),
  CONSTRAINT `fk_re_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_re_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=288 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `rating_events` WRITE;
/*!40000 ALTER TABLE `rating_events` DISABLE KEYS */;
INSERT INTO `rating_events` VALUES
(1,1,1,'kids','attendance',10,'attendance',2,NULL,NULL,'2026-10-23 16:00:00'),
(2,1,1,'kids','attendance',10,'attendance',3,NULL,NULL,'2026-10-26 16:00:00'),
(3,1,1,'kids','attendance',10,'attendance',4,NULL,NULL,'2026-10-28 16:00:00'),
(4,1,1,'kids','attendance',10,'attendance',5,NULL,NULL,'2026-10-30 16:00:00'),
(5,1,1,'kids','attendance',10,'attendance',7,NULL,NULL,'2026-11-04 16:00:00'),
(6,1,1,'kids','attendance',10,'attendance',8,NULL,NULL,'2026-11-06 16:00:00'),
(7,1,2,'kids','attendance',10,'attendance',11,NULL,NULL,'2026-10-24 11:00:00'),
(8,1,2,'kids','attendance',10,'attendance',12,NULL,NULL,'2026-10-27 17:00:00'),
(9,1,2,'kids','attendance',10,'attendance',13,NULL,NULL,'2026-10-29 17:00:00'),
(10,1,2,'kids','attendance',10,'attendance',14,NULL,NULL,'2026-10-31 11:00:00'),
(11,1,2,'kids','attendance',10,'attendance',16,NULL,NULL,'2026-11-05 17:00:00'),
(12,1,2,'kids','attendance',10,'attendance',17,NULL,NULL,'2026-11-07 11:00:00'),
(13,1,2,'kids','attendance',10,'attendance',18,NULL,NULL,'2026-11-10 17:00:00'),
(14,1,3,'adult','attendance',10,'attendance',19,NULL,NULL,'2026-10-21 20:00:00'),
(15,1,3,'adult','attendance',10,'attendance',21,NULL,NULL,'2026-10-26 20:00:00'),
(16,1,3,'adult','attendance',10,'attendance',23,NULL,NULL,'2026-10-30 20:00:00'),
(17,1,3,'adult','attendance',10,'attendance',26,NULL,NULL,'2026-11-06 20:00:00'),
(18,1,4,'adult','attendance',10,'attendance',28,NULL,NULL,'2026-10-22 21:00:00'),
(19,1,4,'adult','attendance',10,'attendance',30,NULL,NULL,'2026-10-27 21:00:00'),
(20,1,4,'adult','attendance',10,'attendance',32,NULL,NULL,'2026-11-01 19:00:00'),
(21,1,4,'adult','attendance',10,'attendance',34,NULL,NULL,'2026-11-05 21:00:00'),
(22,1,4,'adult','attendance',10,'attendance',35,NULL,NULL,'2026-11-08 19:00:00'),
(23,1,4,'adult','streak5',25,'attendance',35,NULL,NULL,'2026-11-08 19:00:00'),
(24,1,4,'adult','attendance',10,'attendance',36,NULL,NULL,'2026-11-10 21:00:00'),
(25,1,5,'kids','attendance',10,'attendance',37,NULL,NULL,'2026-10-21 16:00:00'),
(26,1,5,'kids','attendance',10,'attendance',38,NULL,NULL,'2026-10-23 16:00:00'),
(27,1,5,'kids','attendance',10,'attendance',39,NULL,NULL,'2026-10-26 16:00:00'),
(28,1,5,'kids','attendance',10,'attendance',41,NULL,NULL,'2026-10-30 16:00:00'),
(29,1,5,'kids','attendance',10,'attendance',44,NULL,NULL,'2026-11-06 16:00:00'),
(30,1,5,'kids','attendance',10,'attendance',45,NULL,NULL,'2026-11-09 16:00:00'),
(31,1,6,'kids','attendance',10,'attendance',46,NULL,NULL,'2026-10-22 17:00:00'),
(32,1,6,'kids','attendance',10,'attendance',48,NULL,NULL,'2026-10-27 17:00:00'),
(33,1,6,'kids','attendance',10,'attendance',50,NULL,NULL,'2026-10-31 11:00:00'),
(34,1,6,'kids','attendance',10,'attendance',52,NULL,NULL,'2026-11-05 17:00:00'),
(35,1,6,'kids','attendance',10,'attendance',53,NULL,NULL,'2026-11-07 11:00:00'),
(36,1,6,'kids','attendance',10,'attendance',54,NULL,NULL,'2026-11-10 17:00:00'),
(37,1,6,'kids','streak5',25,'attendance',54,NULL,NULL,'2026-11-10 17:00:00'),
(38,1,7,'adult','attendance',10,'attendance',55,NULL,NULL,'2026-10-21 20:00:00'),
(39,1,7,'adult','attendance',10,'attendance',57,NULL,NULL,'2026-10-26 20:00:00'),
(40,1,7,'adult','attendance',10,'attendance',58,NULL,NULL,'2026-10-28 20:00:00'),
(41,1,7,'adult','attendance',10,'attendance',59,NULL,NULL,'2026-10-30 20:00:00'),
(42,1,7,'adult','attendance',10,'attendance',63,NULL,NULL,'2026-11-09 20:00:00'),
(43,1,8,'adult','attendance',10,'attendance',65,NULL,NULL,'2026-10-25 19:00:00'),
(44,1,8,'adult','attendance',10,'attendance',66,NULL,NULL,'2026-10-27 21:00:00'),
(45,1,8,'adult','attendance',10,'attendance',67,NULL,NULL,'2026-10-29 21:00:00'),
(46,1,8,'adult','attendance',10,'attendance',68,NULL,NULL,'2026-11-01 19:00:00'),
(47,1,8,'adult','attendance',10,'attendance',69,NULL,NULL,'2026-11-03 21:00:00'),
(48,1,8,'adult','streak5',25,'attendance',69,NULL,NULL,'2026-11-03 21:00:00'),
(49,1,8,'adult','attendance',10,'attendance',70,NULL,NULL,'2026-11-05 21:00:00'),
(50,1,8,'adult','attendance',10,'attendance',71,NULL,NULL,'2026-11-08 19:00:00'),
(51,1,8,'adult','attendance',10,'attendance',72,NULL,NULL,'2026-11-10 21:00:00'),
(52,1,9,'kids','attendance',10,'attendance',73,NULL,NULL,'2026-10-21 16:00:00'),
(53,1,9,'kids','attendance',10,'attendance',75,NULL,NULL,'2026-10-26 16:00:00'),
(54,1,9,'kids','attendance',10,'attendance',76,NULL,NULL,'2026-10-28 16:00:00'),
(55,1,9,'kids','attendance',10,'attendance',77,NULL,NULL,'2026-10-30 16:00:00'),
(56,1,9,'kids','attendance',10,'attendance',78,NULL,NULL,'2026-11-02 16:00:00'),
(57,1,9,'kids','streak5',25,'attendance',78,NULL,NULL,'2026-11-02 16:00:00'),
(58,1,9,'kids','attendance',10,'attendance',80,NULL,NULL,'2026-11-06 16:00:00'),
(59,1,9,'kids','attendance',10,'attendance',81,NULL,NULL,'2026-11-09 16:00:00'),
(60,1,10,'kids','attendance',10,'attendance',82,NULL,NULL,'2026-10-22 17:00:00'),
(61,1,10,'kids','attendance',10,'attendance',83,NULL,NULL,'2026-10-24 11:00:00'),
(62,1,10,'kids','attendance',10,'attendance',84,NULL,NULL,'2026-10-27 17:00:00'),
(63,1,10,'kids','attendance',10,'attendance',85,NULL,NULL,'2026-10-29 17:00:00'),
(64,1,10,'kids','attendance',10,'attendance',87,NULL,NULL,'2026-11-03 17:00:00'),
(65,1,10,'kids','attendance',10,'attendance',88,NULL,NULL,'2026-11-05 17:00:00'),
(66,1,10,'kids','attendance',10,'attendance',89,NULL,NULL,'2026-11-07 11:00:00'),
(67,1,10,'kids','attendance',10,'attendance',90,NULL,NULL,'2026-11-10 17:00:00'),
(68,1,11,'adult','attendance',10,'attendance',91,NULL,NULL,'2026-10-21 20:00:00'),
(69,1,11,'adult','attendance',10,'attendance',92,NULL,NULL,'2026-10-23 20:00:00'),
(70,1,11,'adult','attendance',10,'attendance',93,NULL,NULL,'2026-10-26 20:00:00'),
(71,1,11,'adult','attendance',10,'attendance',94,NULL,NULL,'2026-10-28 20:00:00'),
(72,1,11,'adult','attendance',10,'attendance',95,NULL,NULL,'2026-10-30 20:00:00'),
(73,1,11,'adult','streak5',25,'attendance',95,NULL,NULL,'2026-10-30 20:00:00'),
(74,1,11,'adult','attendance',10,'attendance',96,NULL,NULL,'2026-11-02 20:00:00'),
(75,1,11,'adult','attendance',10,'attendance',97,NULL,NULL,'2026-11-04 20:00:00'),
(76,1,11,'adult','attendance',10,'attendance',98,NULL,NULL,'2026-11-06 20:00:00'),
(77,1,11,'adult','attendance',10,'attendance',99,NULL,NULL,'2026-11-09 20:00:00'),
(78,1,12,'adult','attendance',10,'attendance',100,NULL,NULL,'2026-10-22 21:00:00'),
(79,1,12,'adult','attendance',10,'attendance',102,NULL,NULL,'2026-10-27 21:00:00'),
(80,1,12,'adult','attendance',10,'attendance',103,NULL,NULL,'2026-10-29 21:00:00'),
(81,1,12,'adult','attendance',10,'attendance',104,NULL,NULL,'2026-11-01 19:00:00'),
(82,1,12,'adult','attendance',10,'attendance',105,NULL,NULL,'2026-11-03 21:00:00'),
(83,1,12,'adult','streak5',25,'attendance',105,NULL,NULL,'2026-11-03 21:00:00'),
(84,1,12,'adult','attendance',10,'attendance',106,NULL,NULL,'2026-11-05 21:00:00'),
(85,1,12,'adult','attendance',10,'attendance',107,NULL,NULL,'2026-11-08 19:00:00'),
(86,1,13,'kids','attendance',10,'attendance',109,NULL,NULL,'2026-10-21 16:00:00'),
(87,1,13,'kids','attendance',10,'attendance',112,NULL,NULL,'2026-10-28 16:00:00'),
(88,1,13,'kids','attendance',10,'attendance',114,NULL,NULL,'2026-11-02 16:00:00'),
(89,1,13,'kids','attendance',10,'attendance',115,NULL,NULL,'2026-11-04 16:00:00'),
(90,1,13,'kids','attendance',10,'attendance',117,NULL,NULL,'2026-11-09 16:00:00'),
(91,1,14,'kids','attendance',10,'attendance',118,NULL,NULL,'2026-10-22 17:00:00'),
(92,1,14,'kids','attendance',10,'attendance',119,NULL,NULL,'2026-10-24 11:00:00'),
(93,1,14,'kids','attendance',10,'attendance',121,NULL,NULL,'2026-10-29 17:00:00'),
(94,1,14,'kids','attendance',10,'attendance',122,NULL,NULL,'2026-10-31 11:00:00'),
(95,1,14,'kids','attendance',10,'attendance',123,NULL,NULL,'2026-11-03 17:00:00'),
(96,1,14,'kids','streak5',25,'attendance',123,NULL,NULL,'2026-11-03 17:00:00'),
(97,1,14,'kids','attendance',10,'attendance',126,NULL,NULL,'2026-11-10 17:00:00'),
(98,1,15,'adult','attendance',10,'attendance',127,NULL,NULL,'2026-10-21 20:00:00'),
(99,1,15,'adult','attendance',10,'attendance',129,NULL,NULL,'2026-10-26 20:00:00'),
(100,1,15,'adult','attendance',10,'attendance',130,NULL,NULL,'2026-10-28 20:00:00'),
(101,1,15,'adult','attendance',10,'attendance',131,NULL,NULL,'2026-10-30 20:00:00'),
(102,1,15,'adult','attendance',10,'attendance',132,NULL,NULL,'2026-11-02 20:00:00'),
(103,1,15,'adult','attendance',10,'attendance',133,NULL,NULL,'2026-11-04 20:00:00'),
(104,1,15,'adult','streak5',25,'attendance',133,NULL,NULL,'2026-11-04 20:00:00'),
(105,1,15,'adult','attendance',10,'attendance',135,NULL,NULL,'2026-11-09 20:00:00'),
(106,1,16,'adult','attendance',10,'attendance',137,NULL,NULL,'2026-10-25 19:00:00'),
(107,1,16,'adult','attendance',10,'attendance',138,NULL,NULL,'2026-10-27 21:00:00'),
(108,1,16,'adult','attendance',10,'attendance',139,NULL,NULL,'2026-10-29 21:00:00'),
(109,1,16,'adult','attendance',10,'attendance',140,NULL,NULL,'2026-11-01 19:00:00'),
(110,1,16,'adult','attendance',10,'attendance',142,NULL,NULL,'2026-11-05 21:00:00'),
(111,1,16,'adult','attendance',10,'attendance',143,NULL,NULL,'2026-11-08 19:00:00'),
(112,1,17,'kids','attendance',10,'attendance',145,NULL,NULL,'2026-10-21 16:00:00'),
(113,1,17,'kids','attendance',10,'attendance',146,NULL,NULL,'2026-10-23 16:00:00'),
(114,1,17,'kids','attendance',10,'attendance',147,NULL,NULL,'2026-10-26 16:00:00'),
(115,1,17,'kids','attendance',10,'attendance',148,NULL,NULL,'2026-10-28 16:00:00'),
(116,1,17,'kids','attendance',10,'attendance',150,NULL,NULL,'2026-11-02 16:00:00'),
(117,1,17,'kids','streak5',25,'attendance',150,NULL,NULL,'2026-11-02 16:00:00'),
(118,1,17,'kids','attendance',10,'attendance',152,NULL,NULL,'2026-11-06 16:00:00'),
(119,1,17,'kids','attendance',10,'attendance',153,NULL,NULL,'2026-11-09 16:00:00'),
(120,1,18,'kids','attendance',10,'attendance',155,NULL,NULL,'2026-10-24 11:00:00'),
(121,1,18,'kids','attendance',10,'attendance',156,NULL,NULL,'2026-10-27 17:00:00'),
(122,1,18,'kids','attendance',10,'attendance',157,NULL,NULL,'2026-10-29 17:00:00'),
(123,1,18,'kids','attendance',10,'attendance',159,NULL,NULL,'2026-11-03 17:00:00'),
(124,1,18,'kids','attendance',10,'attendance',161,NULL,NULL,'2026-11-07 11:00:00'),
(125,1,19,'adult','attendance',10,'attendance',163,NULL,NULL,'2026-10-21 20:00:00'),
(126,1,19,'adult','attendance',10,'attendance',164,NULL,NULL,'2026-10-23 20:00:00'),
(127,1,19,'adult','attendance',10,'attendance',165,NULL,NULL,'2026-10-26 20:00:00'),
(128,1,19,'adult','attendance',10,'attendance',166,NULL,NULL,'2026-10-28 20:00:00'),
(129,1,19,'adult','attendance',10,'attendance',168,NULL,NULL,'2026-11-02 20:00:00'),
(130,1,19,'adult','attendance',10,'attendance',169,NULL,NULL,'2026-11-04 20:00:00'),
(131,1,19,'adult','attendance',10,'attendance',171,NULL,NULL,'2026-11-09 20:00:00'),
(132,1,20,'adult','attendance',10,'attendance',172,NULL,NULL,'2026-10-22 21:00:00'),
(133,1,20,'adult','attendance',10,'attendance',173,NULL,NULL,'2026-10-25 19:00:00'),
(134,1,20,'adult','attendance',10,'attendance',174,NULL,NULL,'2026-10-27 21:00:00'),
(135,1,20,'adult','attendance',10,'attendance',175,NULL,NULL,'2026-10-29 21:00:00'),
(136,1,20,'adult','attendance',10,'attendance',176,NULL,NULL,'2026-11-01 19:00:00'),
(137,1,20,'adult','streak5',25,'attendance',176,NULL,NULL,'2026-11-01 19:00:00'),
(138,1,20,'adult','attendance',10,'attendance',177,NULL,NULL,'2026-11-03 21:00:00'),
(139,1,20,'adult','attendance',10,'attendance',178,NULL,NULL,'2026-11-05 21:00:00'),
(140,1,21,'kids','attendance',10,'attendance',181,NULL,NULL,'2026-10-21 16:00:00'),
(141,1,21,'kids','attendance',10,'attendance',182,NULL,NULL,'2026-10-23 16:00:00'),
(142,1,21,'kids','attendance',10,'attendance',185,NULL,NULL,'2026-10-30 16:00:00'),
(143,1,21,'kids','attendance',10,'attendance',188,NULL,NULL,'2026-11-06 16:00:00'),
(144,1,22,'kids','attendance',10,'attendance',192,NULL,NULL,'2026-10-27 17:00:00'),
(145,1,22,'kids','attendance',10,'attendance',195,NULL,NULL,'2026-11-03 17:00:00'),
(146,1,22,'kids','attendance',10,'attendance',196,NULL,NULL,'2026-11-05 17:00:00'),
(147,1,22,'kids','attendance',10,'attendance',197,NULL,NULL,'2026-11-07 11:00:00'),
(148,1,23,'adult','attendance',10,'attendance',199,NULL,NULL,'2026-10-21 20:00:00'),
(149,1,23,'adult','attendance',10,'attendance',200,NULL,NULL,'2026-10-23 20:00:00'),
(150,1,23,'adult','attendance',10,'attendance',201,NULL,NULL,'2026-10-26 20:00:00'),
(151,1,23,'adult','attendance',10,'attendance',202,NULL,NULL,'2026-10-28 20:00:00'),
(152,1,23,'adult','attendance',10,'attendance',204,NULL,NULL,'2026-11-02 20:00:00'),
(153,1,23,'adult','streak5',25,'attendance',204,NULL,NULL,'2026-11-02 20:00:00'),
(154,1,23,'adult','attendance',10,'attendance',205,NULL,NULL,'2026-11-04 20:00:00'),
(155,1,23,'adult','attendance',10,'attendance',206,NULL,NULL,'2026-11-06 20:00:00'),
(156,1,24,'adult','attendance',10,'attendance',208,NULL,NULL,'2026-10-22 21:00:00'),
(157,1,24,'adult','attendance',10,'attendance',211,NULL,NULL,'2026-10-29 21:00:00'),
(158,1,24,'adult','attendance',10,'attendance',212,NULL,NULL,'2026-11-01 19:00:00'),
(159,1,24,'adult','attendance',10,'attendance',214,NULL,NULL,'2026-11-05 21:00:00'),
(160,1,24,'adult','attendance',10,'attendance',215,NULL,NULL,'2026-11-08 19:00:00'),
(161,1,24,'adult','streak5',25,'attendance',215,NULL,NULL,'2026-11-08 19:00:00'),
(162,1,24,'adult','attendance',10,'attendance',216,NULL,NULL,'2026-11-10 21:00:00'),
(163,1,25,'kids','attendance',10,'attendance',217,NULL,NULL,'2026-10-21 16:00:00'),
(164,1,25,'kids','attendance',10,'attendance',218,NULL,NULL,'2026-10-23 16:00:00'),
(165,1,25,'kids','attendance',10,'attendance',219,NULL,NULL,'2026-10-26 16:00:00'),
(166,1,25,'kids','attendance',10,'attendance',220,NULL,NULL,'2026-10-28 16:00:00'),
(167,1,25,'kids','attendance',10,'attendance',221,NULL,NULL,'2026-10-30 16:00:00'),
(168,1,25,'kids','streak5',25,'attendance',221,NULL,NULL,'2026-10-30 16:00:00'),
(169,1,25,'kids','attendance',10,'attendance',223,NULL,NULL,'2026-11-04 16:00:00'),
(170,1,25,'kids','attendance',10,'attendance',224,NULL,NULL,'2026-11-06 16:00:00'),
(171,1,26,'kids','attendance',10,'attendance',227,NULL,NULL,'2026-10-24 11:00:00'),
(172,1,26,'kids','attendance',10,'attendance',228,NULL,NULL,'2026-10-27 17:00:00'),
(173,1,26,'kids','attendance',10,'attendance',229,NULL,NULL,'2026-10-29 17:00:00'),
(174,1,26,'kids','attendance',10,'attendance',230,NULL,NULL,'2026-10-31 11:00:00'),
(175,1,26,'kids','attendance',10,'attendance',231,NULL,NULL,'2026-11-03 17:00:00'),
(176,1,26,'kids','streak5',25,'attendance',231,NULL,NULL,'2026-11-03 17:00:00'),
(177,1,26,'kids','attendance',10,'attendance',232,NULL,NULL,'2026-11-05 17:00:00'),
(178,1,26,'kids','attendance',10,'attendance',233,NULL,NULL,'2026-11-07 11:00:00'),
(179,1,27,'adult','attendance',10,'attendance',235,NULL,NULL,'2026-10-21 20:00:00'),
(180,1,27,'adult','attendance',10,'attendance',236,NULL,NULL,'2026-10-23 20:00:00'),
(181,1,27,'adult','attendance',10,'attendance',237,NULL,NULL,'2026-10-26 20:00:00'),
(182,1,27,'adult','attendance',10,'attendance',239,NULL,NULL,'2026-10-30 20:00:00'),
(183,1,27,'adult','attendance',10,'attendance',241,NULL,NULL,'2026-11-04 20:00:00'),
(184,1,27,'adult','attendance',10,'attendance',242,NULL,NULL,'2026-11-06 20:00:00'),
(185,1,27,'adult','attendance',10,'attendance',243,NULL,NULL,'2026-11-09 20:00:00'),
(186,1,28,'adult','attendance',10,'attendance',244,NULL,NULL,'2026-10-22 21:00:00'),
(187,1,28,'adult','attendance',10,'attendance',247,NULL,NULL,'2026-10-29 21:00:00'),
(188,1,28,'adult','attendance',10,'attendance',251,NULL,NULL,'2026-11-08 19:00:00'),
(189,1,28,'adult','attendance',10,'attendance',252,NULL,NULL,'2026-11-10 21:00:00'),
(190,1,29,'kids','attendance',10,'attendance',254,NULL,NULL,'2026-10-23 16:00:00'),
(191,1,29,'kids','attendance',10,'attendance',255,NULL,NULL,'2026-10-26 16:00:00'),
(192,1,29,'kids','attendance',10,'attendance',257,NULL,NULL,'2026-10-30 16:00:00'),
(193,1,29,'kids','attendance',10,'attendance',258,NULL,NULL,'2026-11-02 16:00:00'),
(194,1,29,'kids','attendance',10,'attendance',259,NULL,NULL,'2026-11-04 16:00:00'),
(195,1,29,'kids','streak5',25,'attendance',259,NULL,NULL,'2026-11-04 16:00:00'),
(196,1,29,'kids','attendance',10,'attendance',260,NULL,NULL,'2026-11-06 16:00:00'),
(197,1,30,'kids','attendance',10,'attendance',262,NULL,NULL,'2026-10-22 17:00:00'),
(198,1,30,'kids','attendance',10,'attendance',263,NULL,NULL,'2026-10-24 11:00:00'),
(199,1,30,'kids','attendance',10,'attendance',264,NULL,NULL,'2026-10-27 17:00:00'),
(200,1,30,'kids','attendance',10,'attendance',266,NULL,NULL,'2026-10-31 11:00:00'),
(201,1,30,'kids','attendance',10,'attendance',268,NULL,NULL,'2026-11-05 17:00:00'),
(202,1,30,'kids','attendance',10,'attendance',270,NULL,NULL,'2026-11-10 17:00:00'),
(203,1,31,'adult','attendance',10,'attendance',272,NULL,NULL,'2026-10-23 20:00:00'),
(204,1,31,'adult','attendance',10,'attendance',275,NULL,NULL,'2026-10-30 20:00:00'),
(205,1,31,'adult','attendance',10,'attendance',277,NULL,NULL,'2026-11-04 20:00:00'),
(206,1,31,'adult','attendance',10,'attendance',278,NULL,NULL,'2026-11-06 20:00:00'),
(207,1,32,'adult','attendance',10,'attendance',280,NULL,NULL,'2026-10-22 21:00:00'),
(208,1,32,'adult','attendance',10,'attendance',284,NULL,NULL,'2026-11-01 19:00:00'),
(209,1,32,'adult','attendance',10,'attendance',285,NULL,NULL,'2026-11-03 21:00:00'),
(210,1,32,'adult','attendance',10,'attendance',287,NULL,NULL,'2026-11-08 19:00:00'),
(211,1,32,'adult','attendance',10,'attendance',288,NULL,NULL,'2026-11-10 21:00:00'),
(212,1,32,'adult','streak5',25,'attendance',288,NULL,NULL,'2026-11-10 21:00:00'),
(213,1,33,'kids','attendance',10,'attendance',289,NULL,NULL,'2026-10-21 16:00:00'),
(214,1,33,'kids','attendance',10,'attendance',290,NULL,NULL,'2026-10-23 16:00:00'),
(215,1,33,'kids','attendance',10,'attendance',291,NULL,NULL,'2026-10-26 16:00:00'),
(216,1,33,'kids','attendance',10,'attendance',292,NULL,NULL,'2026-10-28 16:00:00'),
(217,1,33,'kids','attendance',10,'attendance',293,NULL,NULL,'2026-10-30 16:00:00'),
(218,1,33,'kids','streak5',25,'attendance',293,NULL,NULL,'2026-10-30 16:00:00'),
(219,1,33,'kids','attendance',10,'attendance',295,NULL,NULL,'2026-11-04 16:00:00'),
(220,1,33,'kids','attendance',10,'attendance',296,NULL,NULL,'2026-11-06 16:00:00'),
(221,1,34,'kids','attendance',10,'attendance',298,NULL,NULL,'2026-10-22 17:00:00'),
(222,1,34,'kids','attendance',10,'attendance',299,NULL,NULL,'2026-10-24 11:00:00'),
(223,1,34,'kids','attendance',10,'attendance',300,NULL,NULL,'2026-10-27 17:00:00'),
(224,1,34,'kids','attendance',10,'attendance',302,NULL,NULL,'2026-10-31 11:00:00'),
(225,1,34,'kids','attendance',10,'attendance',303,NULL,NULL,'2026-11-03 17:00:00'),
(226,1,34,'kids','attendance',10,'attendance',304,NULL,NULL,'2026-11-05 17:00:00'),
(227,1,34,'kids','attendance',10,'attendance',305,NULL,NULL,'2026-11-07 11:00:00'),
(228,1,34,'kids','attendance',10,'attendance',306,NULL,NULL,'2026-11-10 17:00:00'),
(229,1,34,'kids','streak5',25,'attendance',306,NULL,NULL,'2026-11-10 17:00:00'),
(230,1,35,'adult','attendance',10,'attendance',308,NULL,NULL,'2026-10-23 20:00:00'),
(231,1,35,'adult','attendance',10,'attendance',310,NULL,NULL,'2026-10-28 20:00:00'),
(232,1,35,'adult','attendance',10,'attendance',311,NULL,NULL,'2026-10-30 20:00:00'),
(233,1,35,'adult','attendance',10,'attendance',313,NULL,NULL,'2026-11-04 20:00:00'),
(234,1,35,'adult','attendance',10,'attendance',315,NULL,NULL,'2026-11-09 20:00:00'),
(235,1,36,'adult','attendance',10,'attendance',316,NULL,NULL,'2026-10-22 21:00:00'),
(236,1,36,'adult','attendance',10,'attendance',317,NULL,NULL,'2026-10-25 19:00:00'),
(237,1,36,'adult','attendance',10,'attendance',318,NULL,NULL,'2026-10-27 21:00:00'),
(238,1,36,'adult','attendance',10,'attendance',319,NULL,NULL,'2026-10-29 21:00:00'),
(239,1,36,'adult','attendance',10,'attendance',322,NULL,NULL,'2026-11-05 21:00:00'),
(240,1,36,'adult','attendance',10,'attendance',323,NULL,NULL,'2026-11-08 19:00:00'),
(241,1,37,'kids','attendance',10,'attendance',325,NULL,NULL,'2026-10-21 16:00:00'),
(242,1,37,'kids','attendance',10,'attendance',326,NULL,NULL,'2026-10-23 16:00:00'),
(243,1,37,'kids','attendance',10,'attendance',327,NULL,NULL,'2026-10-26 16:00:00'),
(244,1,37,'kids','attendance',10,'attendance',330,NULL,NULL,'2026-11-02 16:00:00'),
(245,1,37,'kids','attendance',10,'attendance',332,NULL,NULL,'2026-11-06 16:00:00'),
(246,1,37,'kids','streak5',25,'attendance',332,NULL,NULL,'2026-11-06 16:00:00'),
(247,1,37,'kids','attendance',10,'attendance',333,NULL,NULL,'2026-11-09 16:00:00'),
(248,1,38,'kids','attendance',10,'attendance',334,NULL,NULL,'2026-10-22 17:00:00'),
(249,1,38,'kids','attendance',10,'attendance',336,NULL,NULL,'2026-10-27 17:00:00'),
(250,1,38,'kids','attendance',10,'attendance',337,NULL,NULL,'2026-10-29 17:00:00'),
(251,1,38,'kids','attendance',10,'attendance',339,NULL,NULL,'2026-11-03 17:00:00'),
(252,1,39,'adult','attendance',10,'attendance',345,NULL,NULL,'2026-10-26 20:00:00'),
(253,1,39,'adult','attendance',10,'attendance',346,NULL,NULL,'2026-10-28 20:00:00'),
(254,1,39,'adult','attendance',10,'attendance',348,NULL,NULL,'2026-11-02 20:00:00'),
(255,1,39,'adult','attendance',10,'attendance',349,NULL,NULL,'2026-11-04 20:00:00'),
(256,1,39,'adult','attendance',10,'attendance',351,NULL,NULL,'2026-11-09 20:00:00'),
(257,1,40,'adult','attendance',10,'attendance',352,NULL,NULL,'2026-10-22 21:00:00'),
(258,1,40,'adult','attendance',10,'attendance',354,NULL,NULL,'2026-10-27 21:00:00'),
(259,1,40,'adult','attendance',10,'attendance',356,NULL,NULL,'2026-11-01 19:00:00'),
(260,1,40,'adult','attendance',10,'attendance',357,NULL,NULL,'2026-11-03 21:00:00'),
(261,1,40,'adult','attendance',10,'attendance',358,NULL,NULL,'2026-11-05 21:00:00'),
(262,1,40,'adult','attendance',10,'attendance',359,NULL,NULL,'2026-11-08 19:00:00'),
(263,1,40,'adult','attendance',10,'attendance',360,NULL,NULL,'2026-11-10 21:00:00'),
(264,1,40,'adult','streak5',25,'attendance',360,NULL,NULL,'2026-11-10 21:00:00'),
(265,1,41,'kids','attendance',10,'attendance',361,NULL,NULL,'2026-10-21 16:00:00'),
(266,1,41,'kids','attendance',10,'attendance',362,NULL,NULL,'2026-10-23 16:00:00'),
(267,1,41,'kids','attendance',10,'attendance',363,NULL,NULL,'2026-10-26 16:00:00'),
(268,1,41,'kids','attendance',10,'attendance',364,NULL,NULL,'2026-10-28 16:00:00'),
(269,1,41,'kids','attendance',10,'attendance',366,NULL,NULL,'2026-11-02 16:00:00'),
(270,1,41,'kids','streak5',25,'attendance',366,NULL,NULL,'2026-11-02 16:00:00'),
(271,1,41,'kids','attendance',10,'attendance',368,NULL,NULL,'2026-11-06 16:00:00'),
(272,1,42,'kids','attendance',10,'attendance',370,NULL,NULL,'2026-10-22 17:00:00'),
(273,1,42,'kids','attendance',10,'attendance',371,NULL,NULL,'2026-10-24 11:00:00'),
(274,1,42,'kids','attendance',10,'attendance',373,NULL,NULL,'2026-10-29 17:00:00'),
(275,1,42,'kids','attendance',10,'attendance',376,NULL,NULL,'2026-11-05 17:00:00'),
(276,1,42,'kids','attendance',10,'attendance',378,NULL,NULL,'2026-11-10 17:00:00'),
(277,1,42,'kids','streak5',25,'attendance',378,NULL,NULL,'2026-11-10 17:00:00'),
(278,1,43,'adult','attendance',10,'attendance',379,NULL,NULL,'2026-10-21 20:00:00'),
(279,1,43,'adult','attendance',10,'attendance',381,NULL,NULL,'2026-10-26 20:00:00'),
(280,1,43,'adult','attendance',10,'attendance',383,NULL,NULL,'2026-10-30 20:00:00'),
(281,1,43,'adult','attendance',10,'attendance',385,NULL,NULL,'2026-11-04 20:00:00'),
(282,1,43,'adult','attendance',10,'attendance',386,NULL,NULL,'2026-11-06 20:00:00'),
(283,1,44,'adult','attendance',10,'attendance',388,NULL,NULL,'2026-10-22 21:00:00'),
(284,1,44,'adult','attendance',10,'attendance',390,NULL,NULL,'2026-10-27 21:00:00'),
(285,1,44,'adult','attendance',10,'attendance',392,NULL,NULL,'2026-11-01 19:00:00'),
(286,1,44,'adult','attendance',10,'attendance',393,NULL,NULL,'2026-11-03 21:00:00'),
(287,1,44,'adult','attendance',10,'attendance',395,NULL,NULL,'2026-11-08 19:00:00');
/*!40000 ALTER TABLE `rating_events` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `schedule_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedule_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `weekday` tinyint(3) unsigned NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue_id` int(10) unsigned DEFAULT NULL,
  `coach_id` int(10) unsigned DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_to` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tpl_group` (`group_id`,`weekday`),
  KEY `fk_tpl_org` (`organization_id`),
  KEY `fk_tpl_venue` (`venue_id`),
  KEY `fk_tpl_coach` (`coach_id`),
  CONSTRAINT `fk_tpl_coach` FOREIGN KEY (`coach_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_tpl_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  CONSTRAINT `fk_tpl_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_tpl_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `schedule_templates` WRITE;
/*!40000 ALTER TABLE `schedule_templates` DISABLE KEYS */;
INSERT INTO `schedule_templates` VALUES
(1,1,1,1,'16:00:00','17:30:00',1,3,'2026-09-12',NULL),
(2,1,1,3,'16:00:00','17:30:00',1,3,'2026-09-12',NULL),
(3,1,1,5,'16:00:00','17:30:00',1,3,'2026-09-12',NULL),
(4,1,2,2,'17:00:00','18:30:00',1,3,'2026-09-12',NULL),
(5,1,2,4,'17:00:00','18:30:00',1,3,'2026-09-12',NULL),
(6,1,2,6,'11:00:00','12:30:00',1,3,'2026-09-12',NULL),
(7,1,3,1,'20:00:00','21:30:00',2,4,'2026-09-12',NULL),
(8,1,3,3,'20:00:00','21:30:00',2,4,'2026-09-12',NULL),
(9,1,3,5,'20:00:00','21:30:00',2,4,'2026-09-12',NULL),
(10,1,4,2,'21:00:00','22:30:00',2,4,'2026-09-12',NULL),
(11,1,4,4,'21:00:00','22:30:00',2,4,'2026-09-12',NULL),
(12,1,4,7,'19:00:00','20:30:00',2,4,'2026-09-12',NULL);
/*!40000 ALTER TABLE `schedule_templates` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `selection_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `selection_campaigns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `target` enum('first_team','city_league') NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_selc_org` (`organization_id`),
  CONSTRAINT `fk_selc_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `selection_campaigns` WRITE;
/*!40000 ALTER TABLE `selection_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `selection_campaigns` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `selection_candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `selection_candidates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `campaign_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `status` enum('watching','candidate','invited','passed','failed','accepted') NOT NULL DEFAULT 'watching',
  `notes` text DEFAULT NULL,
  `decided_by` int(10) unsigned DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cand` (`campaign_id`,`participant_id`),
  KEY `fk_cand_org` (`organization_id`),
  KEY `fk_cand_part` (`participant_id`),
  CONSTRAINT `fk_cand_camp` FOREIGN KEY (`campaign_id`) REFERENCES `selection_campaigns` (`id`),
  CONSTRAINT `fk_cand_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_cand_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `selection_candidates` WRITE;
/*!40000 ALTER TABLE `selection_candidates` DISABLE KEYS */;
/*!40000 ALTER TABLE `selection_candidates` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned DEFAULT NULL,
  `template_id` int(10) unsigned DEFAULT NULL,
  `type` enum('training','extra','match') NOT NULL DEFAULT 'training',
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `venue_id` int(10) unsigned DEFAULT NULL,
  `coach_id` int(10) unsigned DEFAULT NULL,
  `status` enum('planned','done','cancelled') NOT NULL DEFAULT 'planned',
  `moved_from_id` int(10) unsigned DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session_tpl` (`template_id`,`starts_at`),
  KEY `ix_sess_org_date` (`organization_id`,`starts_at`),
  KEY `ix_sess_group` (`group_id`,`starts_at`),
  KEY `ix_sess_venue` (`venue_id`,`starts_at`),
  KEY `ix_sess_coach` (`coach_id`,`starts_at`),
  KEY `fk_sess_moved` (`moved_from_id`),
  CONSTRAINT `fk_sess_coach` FOREIGN KEY (`coach_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_sess_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  CONSTRAINT `fk_sess_moved` FOREIGN KEY (`moved_from_id`) REFERENCES `sessions` (`id`),
  CONSTRAINT `fk_sess_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_sess_tpl` FOREIGN KEY (`template_id`) REFERENCES `schedule_templates` (`id`),
  CONSTRAINT `fk_sess_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES
(1,1,1,2,'training','2026-10-21 16:00:00','2026-10-21 17:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(2,1,3,8,'training','2026-10-21 20:00:00','2026-10-21 21:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(3,1,2,5,'training','2026-10-22 17:00:00','2026-10-22 18:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(4,1,4,11,'training','2026-10-22 21:00:00','2026-10-22 22:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(5,1,1,3,'training','2026-10-23 16:00:00','2026-10-23 17:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(6,1,3,9,'training','2026-10-23 20:00:00','2026-10-23 21:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(7,1,2,6,'training','2026-10-24 11:00:00','2026-10-24 12:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(8,1,4,12,'training','2026-10-25 19:00:00','2026-10-25 20:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(9,1,1,1,'training','2026-10-26 16:00:00','2026-10-26 17:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(10,1,3,7,'training','2026-10-26 20:00:00','2026-10-26 21:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(11,1,2,4,'training','2026-10-27 17:00:00','2026-10-27 18:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(12,1,4,10,'training','2026-10-27 21:00:00','2026-10-27 22:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(13,1,1,2,'training','2026-10-28 16:00:00','2026-10-28 17:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(14,1,3,8,'training','2026-10-28 20:00:00','2026-10-28 21:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(15,1,2,5,'training','2026-10-29 17:00:00','2026-10-29 18:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(16,1,4,11,'training','2026-10-29 21:00:00','2026-10-29 22:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(17,1,1,3,'training','2026-10-30 16:00:00','2026-10-30 17:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(18,1,3,9,'training','2026-10-30 20:00:00','2026-10-30 21:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(19,1,2,6,'training','2026-10-31 11:00:00','2026-10-31 12:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(20,1,4,12,'training','2026-11-01 19:00:00','2026-11-01 20:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(21,1,1,1,'training','2026-11-02 16:00:00','2026-11-02 17:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(22,1,3,7,'training','2026-11-02 20:00:00','2026-11-02 21:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(23,1,2,4,'training','2026-11-03 17:00:00','2026-11-03 18:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(24,1,4,10,'training','2026-11-03 21:00:00','2026-11-03 22:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(25,1,1,2,'training','2026-11-04 16:00:00','2026-11-04 17:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(26,1,3,8,'training','2026-11-04 20:00:00','2026-11-04 21:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(27,1,2,5,'training','2026-11-05 17:00:00','2026-11-05 18:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(28,1,4,11,'training','2026-11-05 21:00:00','2026-11-05 22:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(29,1,1,3,'training','2026-11-06 16:00:00','2026-11-06 17:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(30,1,3,9,'training','2026-11-06 20:00:00','2026-11-06 21:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(31,1,2,6,'training','2026-11-07 11:00:00','2026-11-07 12:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(32,1,4,12,'training','2026-11-08 19:00:00','2026-11-08 20:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(33,1,1,1,'training','2026-11-09 16:00:00','2026-11-09 17:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(34,1,3,7,'training','2026-11-09 20:00:00','2026-11-09 21:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(35,1,2,4,'training','2026-11-10 17:00:00','2026-11-10 18:30:00',1,3,'done',NULL,NULL,'2026-11-11 13:42:33'),
(36,1,4,10,'training','2026-11-10 21:00:00','2026-11-10 22:30:00',2,4,'done',NULL,NULL,'2026-11-11 13:42:33'),
(37,1,1,2,'training','2026-11-11 16:00:00','2026-11-11 17:30:00',1,3,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(38,1,3,8,'training','2026-11-11 20:00:00','2026-11-11 21:30:00',2,4,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(39,1,2,5,'training','2026-11-12 17:00:00','2026-11-12 18:30:00',1,3,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(40,1,4,11,'training','2026-11-12 21:00:00','2026-11-12 22:30:00',2,4,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(41,1,1,3,'training','2026-11-13 16:00:00','2026-11-13 17:30:00',1,3,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(42,1,3,9,'training','2026-11-13 20:00:00','2026-11-13 21:30:00',2,4,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(43,1,2,6,'training','2026-11-14 11:00:00','2026-11-14 12:30:00',1,3,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(44,1,4,12,'training','2026-11-15 19:00:00','2026-11-15 20:30:00',2,4,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(45,1,1,1,'training','2026-11-16 16:00:00','2026-11-16 17:30:00',1,3,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(46,1,3,7,'training','2026-11-16 20:00:00','2026-11-16 21:30:00',2,4,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(47,1,2,4,'training','2026-11-17 17:00:00','2026-11-17 18:30:00',1,3,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(48,1,4,10,'training','2026-11-17 21:00:00','2026-11-17 22:30:00',2,4,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(49,1,1,2,'training','2026-11-18 16:00:00','2026-11-18 17:30:00',1,3,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(50,1,3,8,'training','2026-11-18 20:00:00','2026-11-18 21:30:00',2,4,'planned',NULL,NULL,'2026-11-11 13:42:33'),
(51,1,3,NULL,'extra','2026-11-11 10:00:00','2026-11-11 11:30:00',1,4,'planned',NULL,NULL,'2026-11-11 13:42:33');
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `plan_id` int(10) unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sessions_limit` smallint(5) unsigned DEFAULT NULL,
  `freeze_days_limit` tinyint(3) unsigned NOT NULL DEFAULT 7,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','active','frozen','expired','cancelled') NOT NULL DEFAULT 'pending',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_sub_part` (`participant_id`,`status`),
  KEY `ix_sub_org_status` (`organization_id`,`status`,`end_date`),
  KEY `fk_sub_plan` (`plan_id`),
  CONSTRAINT `fk_sub_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_sub_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
  CONSTRAINT `fk_sub_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES
(1,1,1,1,25000.00,0.00,12,7,'2026-11-10','2026-12-10','active',2,'2026-11-11 13:42:33'),
(2,1,2,1,25000.00,0.00,12,7,'2026-10-26','2026-11-26','active',2,'2026-11-11 13:42:33'),
(3,1,3,2,20000.00,0.00,12,7,'2026-10-29','2026-11-29','active',2,'2026-11-11 13:42:33'),
(4,1,4,2,20000.00,0.00,12,7,'2026-10-17','2026-11-17','active',2,'2026-11-11 13:42:33'),
(5,1,5,1,25000.00,0.00,12,7,'2026-11-01','2026-12-01','active',2,'2026-11-11 13:42:33'),
(6,1,6,1,25000.00,0.00,12,7,'2026-10-21','2026-11-21','active',2,'2026-11-11 13:42:33'),
(7,1,7,2,20000.00,0.00,12,7,'2026-11-10','2026-12-10','active',2,'2026-11-11 13:42:33'),
(8,1,8,2,20000.00,0.00,12,7,'2026-10-25','2026-11-25','active',2,'2026-11-11 13:42:33'),
(9,1,9,1,25000.00,0.00,12,7,'2026-10-16','2026-11-16','active',2,'2026-11-11 13:42:33'),
(10,1,10,1,25000.00,0.00,12,7,'2026-11-05','2026-12-05','active',2,'2026-11-11 13:42:33'),
(11,1,11,2,20000.00,0.00,12,7,NULL,NULL,'pending',2,'2026-11-11 13:42:33'),
(12,1,12,2,20000.00,0.00,12,7,'2026-10-21','2026-11-21','active',2,'2026-11-11 13:42:33'),
(13,1,13,1,25000.00,0.00,12,7,'2026-11-08','2026-12-08','active',2,'2026-11-11 13:42:33'),
(14,1,14,1,25000.00,0.00,12,7,'2026-10-15','2026-11-15','active',2,'2026-11-11 13:42:33'),
(15,1,15,2,20000.00,0.00,12,7,'2026-10-26','2026-11-26','active',2,'2026-11-11 13:42:33'),
(16,1,16,2,20000.00,0.00,12,7,'2026-10-31','2026-12-01','active',2,'2026-11-11 13:42:33'),
(17,1,17,1,25000.00,0.00,12,7,'2026-10-20','2026-11-20','active',2,'2026-11-11 13:42:33'),
(18,1,18,1,25000.00,0.00,12,7,'2026-10-18','2026-11-18','active',2,'2026-11-11 13:42:33'),
(19,1,19,2,20000.00,0.00,12,7,'2026-10-25','2026-11-25','active',2,'2026-11-11 13:42:33'),
(20,1,20,2,20000.00,0.00,12,7,'2026-10-29','2026-11-29','active',2,'2026-11-11 13:42:33'),
(21,1,21,1,25000.00,0.00,12,7,'2026-11-06','2026-12-06','active',2,'2026-11-11 13:42:33'),
(22,1,22,1,25000.00,0.00,12,7,NULL,NULL,'pending',2,'2026-11-11 13:42:33'),
(23,1,23,2,20000.00,0.00,12,7,'2026-10-26','2026-11-26','active',2,'2026-11-11 13:42:33'),
(24,1,24,2,20000.00,0.00,12,7,'2026-10-26','2026-11-26','active',2,'2026-11-11 13:42:33'),
(25,1,25,1,25000.00,0.00,12,7,'2026-10-19','2026-11-19','active',2,'2026-11-11 13:42:33'),
(26,1,26,1,25000.00,0.00,12,7,'2026-10-15','2026-11-15','active',2,'2026-11-11 13:42:33'),
(27,1,27,2,20000.00,0.00,12,7,'2026-10-18','2026-11-18','active',2,'2026-11-11 13:42:33'),
(28,1,28,2,20000.00,0.00,12,7,'2026-10-21','2026-11-21','active',2,'2026-11-11 13:42:33'),
(29,1,29,1,25000.00,0.00,12,7,'2026-11-07','2026-12-07','active',2,'2026-11-11 13:42:33'),
(30,1,30,1,25000.00,0.00,12,7,'2026-11-08','2026-12-08','active',2,'2026-11-11 13:42:33'),
(31,1,31,2,20000.00,0.00,12,7,'2026-10-20','2026-11-20','active',2,'2026-11-11 13:42:33'),
(32,1,32,2,20000.00,0.00,12,7,'2026-10-21','2026-11-21','active',2,'2026-11-11 13:42:33'),
(33,1,33,1,25000.00,0.00,12,7,NULL,NULL,'pending',2,'2026-11-11 13:42:33'),
(34,1,34,1,25000.00,0.00,12,7,'2026-10-23','2026-11-23','active',2,'2026-11-11 13:42:33'),
(35,1,35,2,20000.00,0.00,12,7,'2026-10-23','2026-11-23','active',2,'2026-11-11 13:42:33'),
(36,1,36,2,20000.00,0.00,12,7,'2026-10-20','2026-11-20','active',2,'2026-11-11 13:42:33'),
(37,1,37,1,25000.00,0.00,12,7,'2026-10-16','2026-11-16','active',2,'2026-11-11 13:42:33'),
(38,1,38,1,25000.00,0.00,12,7,'2026-10-17','2026-11-17','active',2,'2026-11-11 13:42:33'),
(39,1,39,2,20000.00,0.00,12,7,'2026-10-30','2026-11-30','active',2,'2026-11-11 13:42:33'),
(40,1,40,2,20000.00,0.00,12,7,'2026-10-17','2026-11-17','active',2,'2026-11-11 13:42:33'),
(41,1,41,1,25000.00,0.00,12,7,'2026-10-28','2026-11-28','active',2,'2026-11-11 13:42:33'),
(42,1,42,1,25000.00,0.00,12,7,'2026-10-15','2026-11-15','active',2,'2026-11-11 13:42:33'),
(43,1,43,2,20000.00,0.00,12,7,'2026-10-18','2026-11-18','active',2,'2026-11-11 13:42:33'),
(44,1,44,2,20000.00,0.00,12,7,NULL,NULL,'pending',2,'2026-11-11 13:42:33');
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `team_rosters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `team_rosters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `team_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `number` tinyint(3) unsigned DEFAULT NULL,
  `position` enum('gk','df','mf','fw') DEFAULT NULL,
  `joined_at` date NOT NULL,
  `left_at` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_roster_team` (`team_id`,`left_at`),
  KEY `ix_roster_part` (`participant_id`),
  KEY `fk_ros_org` (`organization_id`),
  CONSTRAINT `fk_ros_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_ros_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
  CONSTRAINT `fk_ros_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `team_rosters` WRITE;
/*!40000 ALTER TABLE `team_rosters` DISABLE KEYS */;
INSERT INTO `team_rosters` VALUES
(1,1,1,3,NULL,NULL,'2026-08-13',NULL),
(2,1,1,4,NULL,NULL,'2026-08-13',NULL),
(3,1,1,12,NULL,NULL,'2026-08-13',NULL),
(4,1,1,24,NULL,NULL,'2026-08-13',NULL),
(5,1,1,31,NULL,NULL,'2026-08-13',NULL),
(6,1,1,32,NULL,NULL,'2026-08-13',NULL),
(7,1,1,43,NULL,NULL,'2026-08-13',NULL);
/*!40000 ALTER TABLE `team_rosters` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `name` varchar(80) NOT NULL,
  `type` enum('first_team','other') NOT NULL DEFAULT 'first_team',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_team_name` (`organization_id`,`name`),
  CONSTRAINT `fk_team_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `teams` WRITE;
/*!40000 ALTER TABLE `teams` DISABLE KEYS */;
INSERT INTO `teams` VALUES
(1,1,'JAS FIRST TEAM','first_team');
/*!40000 ALTER TABLE `teams` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `training_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `lesson_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned DEFAULT NULL,
  `participant_id` int(10) unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_ta_group` (`group_id`),
  KEY `ix_ta_part` (`participant_id`),
  KEY `fk_ta_org` (`organization_id`),
  KEY `fk_ta_lesson` (`lesson_id`),
  CONSTRAINT `fk_ta_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  CONSTRAINT `fk_ta_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `training_lessons` (`id`),
  CONSTRAINT `fk_ta_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_ta_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `training_assignments` WRITE;
/*!40000 ALTER TABLE `training_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_assignments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `training_lessons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_lessons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `category` enum('technique','physical','tactics','ball','individual') NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `video_key` varchar(255) DEFAULT NULL,
  `duration_min` smallint(5) unsigned DEFAULT NULL,
  `level` enum('easy','medium','hard') NOT NULL DEFAULT 'easy',
  `reps` varchar(60) DEFAULT NULL,
  `coach_tip` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_tl_org` (`organization_id`,`category`),
  CONSTRAINT `fk_tl_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `training_lessons` WRITE;
/*!40000 ALTER TABLE `training_lessons` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_lessons` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(11) NOT NULL,
  `mail` varchar(120) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `surname` varchar(80) DEFAULT NULL,
  `img` varchar(120) DEFAULT NULL,
  `session_ver` int(10) unsigned NOT NULL DEFAULT 1,
  `locked_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_phone` (`phone`),
  UNIQUE KEY `uq_users_mail` (`mail`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'87000000001',NULL,'$2y$10$bFk.V421LmI6dHNRMMfA1ObgPIOzXYLGoEjpB.B3Yit4CBCeIExNG','Ерлан','Иесі',NULL,1,NULL,NULL,'2026-11-11 13:42:33',NULL),
(2,'87000000002',NULL,'$2y$10$bFk.V421LmI6dHNRMMfA1ObgPIOzXYLGoEjpB.B3Yit4CBCeIExNG','Әсем','Әкімші',NULL,1,NULL,NULL,'2026-11-11 13:42:33',NULL),
(3,'87000000003',NULL,'$2y$10$bFk.V421LmI6dHNRMMfA1ObgPIOzXYLGoEjpB.B3Yit4CBCeIExNG','Нұрлан','Бапкер',NULL,1,NULL,NULL,'2026-11-11 13:42:33',NULL),
(4,'87000000004',NULL,'$2y$10$bFk.V421LmI6dHNRMMfA1ObgPIOzXYLGoEjpB.B3Yit4CBCeIExNG','Даурен','Бапкер',NULL,1,NULL,NULL,'2026-11-11 13:42:33',NULL),
(5,'87000000005',NULL,'$2y$10$bFk.V421LmI6dHNRMMfA1ObgPIOzXYLGoEjpB.B3Yit4CBCeIExNG','Гүлнар','Ата-ана',NULL,1,NULL,NULL,'2026-11-11 13:42:33',NULL),
(6,'87000000006',NULL,'$2y$10$bFk.V421LmI6dHNRMMfA1ObgPIOzXYLGoEjpB.B3Yit4CBCeIExNG','Арман','Футболшы',NULL,1,NULL,NULL,'2026-11-11 13:42:33',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `venues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `venues` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_venue_name` (`organization_id`,`name`),
  CONSTRAINT `fk_venue_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `venues` WRITE;
/*!40000 ALTER TABLE `venues` DISABLE KEYS */;
INSERT INTO `venues` VALUES
(1,1,'JAS Arena','Шымкент, демо мекенжай 1',1),
(2,1,'Мектеп алаңы','Шымкент, демо мекенжай 2',1);
/*!40000 ALTER TABLE `venues` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

