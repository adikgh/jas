-- JAS ACADEMY — негізгі схема (MySQL 5.7+ / MariaDB 10.3+)
-- Барлық кестеде organization_id бар (multi-tenant).
-- Ақшалар: decimal(12,2), теңге. Уақыт: Asia/Almaty (+05:00).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ============================================================
-- 1. Ұйым, қолданушылар, рөлдер
-- ============================================================

CREATE TABLE `organizations` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(120) NOT NULL,
	`slug` varchar(60) NOT NULL,
	`city` varchar(60) DEFAULT 'Шымкент',
	`timezone` varchar(40) NOT NULL DEFAULT 'Asia/Almaty',
	`phone` varchar(20) DEFAULT NULL,
	`whatsapp` varchar(20) DEFAULT NULL,
	`instagram` varchar(120) DEFAULT NULL,
	`color` varchar(10) DEFAULT '#0e6b3a',
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_org_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`phone` varchar(11) NOT NULL,
	`mail` varchar(120) DEFAULT NULL,
	`password_hash` varchar(255) DEFAULT NULL,
	`name` varchar(80) NOT NULL,
	`surname` varchar(80) DEFAULT NULL,
	`img` varchar(120) DEFAULT NULL,
	`session_ver` int unsigned NOT NULL DEFAULT 1,
	`locked_at` datetime DEFAULT NULL,
	`last_login_at` datetime DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_users_phone` (`phone`),
	UNIQUE KEY `uq_users_mail` (`mail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Бір адамның бір ұйымда бірнеше рөлі болуы мүмкін (мысалы coach + parent)
CREATE TABLE `memberships` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`user_id` int unsigned NOT NULL,
	`role` enum('owner','admin','coach','parent','participant') NOT NULL,
	`active` tinyint(1) NOT NULL DEFAULT 1,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_membership` (`organization_id`,`user_id`,`role`),
	KEY `ix_membership_user` (`user_id`),
	CONSTRAINT `fk_mem_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_mem_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Кіру әрекеттері (brute-force қорғаныс)
CREATE TABLE `login_attempts` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`phone` varchar(11) NOT NULL,
	`ip` varchar(45) NOT NULL,
	`success` tinyint(1) NOT NULL DEFAULT 0,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `ix_login_phone` (`phone`,`created_at`),
	KEY `ix_login_ip` (`ip`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. Қатысушылар, ата-аналар
-- ============================================================

CREATE TABLE `participants` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`user_id` int unsigned DEFAULT NULL,             -- ересек қатысушының өз аккаунты
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
	`number` tinyint unsigned DEFAULT NULL,
	`joined_at` date NOT NULL,
	`status` enum('active','paused','left') NOT NULL DEFAULT 'active',
	`notes` text,
	`created_by` int unsigned DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	`archived_at` datetime DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_participant_user` (`organization_id`,`user_id`),
	KEY `ix_part_org_status` (`organization_id`,`status`,`direction`),
	KEY `ix_part_name` (`organization_id`,`last_name`,`first_name`),
	KEY `ix_part_phone` (`organization_id`,`phone`),
	KEY `ix_part_birth` (`organization_id`,`birth_date`),
	CONSTRAINT `fk_part_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_part_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
	CONSTRAINT `fk_part_created` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `guardians` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`user_id` int unsigned DEFAULT NULL,             -- ата-ана кабинетіне кіру үшін
	`name` varchar(120) NOT NULL,
	`phone` varchar(11) NOT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_guardian_phone` (`organization_id`,`phone`),
	KEY `ix_guardian_user` (`user_id`),
	CONSTRAINT `fk_guard_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_guard_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Бір ата-анаға бірнеше бала
CREATE TABLE `participant_guardians` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`guardian_id` int unsigned NOT NULL,
	`relation` enum('mother','father','other') DEFAULT 'other',
	`is_primary` tinyint(1) NOT NULL DEFAULT 1,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_part_guard` (`participant_id`,`guardian_id`),
	KEY `ix_pg_guardian` (`guardian_id`),
	CONSTRAINT `fk_pg_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_pg_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
	CONSTRAINT `fk_pg_guard` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. Алаңдар, топтар, топқа тіркелу тарихы
-- ============================================================

CREATE TABLE `venues` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`name` varchar(120) NOT NULL,
	`address` varchar(255) DEFAULT NULL,
	`active` tinyint(1) NOT NULL DEFAULT 1,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_venue_name` (`organization_id`,`name`),
	CONSTRAINT `fk_venue_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `groups` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`name` varchar(80) NOT NULL,
	`direction` enum('kids','adult') NOT NULL,
	`age_from` tinyint unsigned DEFAULT NULL,
	`age_to` tinyint unsigned DEFAULT NULL,
	`coach_id` int unsigned DEFAULT NULL,            -- users.id
	`venue_id` int unsigned DEFAULT NULL,
	`capacity` smallint unsigned DEFAULT NULL,
	`active` tinyint(1) NOT NULL DEFAULT 1,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_group_name` (`organization_id`,`name`),
	KEY `ix_group_coach` (`coach_id`),
	CONSTRAINT `fk_group_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_group_coach` FOREIGN KEY (`coach_id`) REFERENCES `users` (`id`),
	CONSTRAINT `fk_group_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Топқа тіркелу. end_date IS NULL = қазіргі топ. Ауысқанда ескісі жабылады, тарих сақталады.
CREATE TABLE `enrollments` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`group_id` int unsigned NOT NULL,
	`start_date` date NOT NULL,
	`end_date` date DEFAULT NULL,
	`reason` varchar(255) DEFAULT NULL,
	`created_by` int unsigned DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `ix_enr_part` (`participant_id`,`end_date`),
	KEY `ix_enr_group` (`group_id`,`end_date`),
	CONSTRAINT `fk_enr_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_enr_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
	CONSTRAINT `fk_enr_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. Кесте, жаттығулар, қатысу
-- ============================================================

CREATE TABLE `schedule_templates` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`group_id` int unsigned NOT NULL,
	`weekday` tinyint unsigned NOT NULL,            -- 1 = дүйсенбі ... 7 = жексенбі
	`start_time` time NOT NULL,
	`end_time` time NOT NULL,
	`venue_id` int unsigned DEFAULT NULL,
	`coach_id` int unsigned DEFAULT NULL,
	`valid_from` date NOT NULL,
	`valid_to` date DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `ix_tpl_group` (`group_id`,`weekday`),
	CONSTRAINT `fk_tpl_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_tpl_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
	CONSTRAINT `fk_tpl_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`),
	CONSTRAINT `fk_tpl_coach` FOREIGN KEY (`coach_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Нақты жаттығу/матч оқиғасы
CREATE TABLE `sessions` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`group_id` int unsigned DEFAULT NULL,
	`template_id` int unsigned DEFAULT NULL,
	`type` enum('training','extra','match') NOT NULL DEFAULT 'training',
	`starts_at` datetime NOT NULL,
	`ends_at` datetime NOT NULL,
	`venue_id` int unsigned DEFAULT NULL,
	`coach_id` int unsigned DEFAULT NULL,
	`status` enum('planned','done','cancelled') NOT NULL DEFAULT 'planned',
	`moved_from_id` int unsigned DEFAULT NULL,
	`cancel_reason` varchar(255) DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_session_tpl` (`template_id`,`starts_at`),
	KEY `ix_sess_org_date` (`organization_id`,`starts_at`),
	KEY `ix_sess_group` (`group_id`,`starts_at`),
	KEY `ix_sess_venue` (`venue_id`,`starts_at`),
	KEY `ix_sess_coach` (`coach_id`,`starts_at`),
	CONSTRAINT `fk_sess_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_sess_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
	CONSTRAINT `fk_sess_tpl` FOREIGN KEY (`template_id`) REFERENCES `schedule_templates` (`id`),
	CONSTRAINT `fk_sess_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`),
	CONSTRAINT `fk_sess_coach` FOREIGN KEY (`coach_id`) REFERENCES `users` (`id`),
	CONSTRAINT `fk_sess_moved` FOREIGN KEY (`moved_from_id`) REFERENCES `sessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Бір жаттығуда бір қатысушыға бір жазба
CREATE TABLE `attendance` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`session_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`status` enum('present','absent','excused','frozen') NOT NULL,
	`marked_by` int unsigned DEFAULT NULL,
	`marked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_att` (`session_id`,`participant_id`),
	KEY `ix_att_part` (`participant_id`,`status`),
	CONSTRAINT `fk_att_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_att_sess` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
	CONSTRAINT `fk_att_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
	CONSTRAINT `fk_att_user` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. Абонемент, заморозка, төлем
-- ============================================================

CREATE TABLE `plans` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`name` varchar(80) NOT NULL,
	`direction` enum('kids','adult') NOT NULL,
	`price` decimal(12,2) NOT NULL,
	`sessions_limit` smallint unsigned DEFAULT NULL, -- NULL = шексіз
	`freeze_days` tinyint unsigned NOT NULL DEFAULT 7,
	`active` tinyint(1) NOT NULL DEFAULT 1,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_plan_name` (`organization_id`,`name`),
	CONSTRAINT `fk_plan_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- start_date = алғашқы расталған төлем күні, end_date = +1 күнтізбелік ай (+ заморозка күндері)
CREATE TABLE `subscriptions` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`plan_id` int unsigned NOT NULL,
	`price` decimal(12,2) NOT NULL,                 -- сату сәтіндегі баға
	`discount` decimal(12,2) NOT NULL DEFAULT 0,
	`sessions_limit` smallint unsigned DEFAULT NULL,
	`freeze_days_limit` tinyint unsigned NOT NULL DEFAULT 7,
	`start_date` date DEFAULT NULL,
	`end_date` date DEFAULT NULL,
	`status` enum('pending','active','frozen','expired','cancelled') NOT NULL DEFAULT 'pending',
	`created_by` int unsigned DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `ix_sub_part` (`participant_id`,`status`),
	KEY `ix_sub_org_status` (`organization_id`,`status`,`end_date`),
	CONSTRAINT `fk_sub_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_sub_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
	CONSTRAINT `fk_sub_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `freezes` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`subscription_id` int unsigned NOT NULL,
	`start_date` date NOT NULL,
	`end_date` date NOT NULL,
	`days` tinyint unsigned NOT NULL,
	`reason` varchar(255) DEFAULT NULL,
	`status` enum('requested','approved','rejected') NOT NULL DEFAULT 'requested',
	`requested_by` int unsigned DEFAULT NULL,
	`decided_by` int unsigned DEFAULT NULL,
	`decided_at` datetime DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `ix_freeze_sub` (`subscription_id`,`status`),
	CONSTRAINT `fk_frz_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_frz_sub` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invoices` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`number` varchar(30) NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`subscription_id` int unsigned DEFAULT NULL,
	`amount` decimal(12,2) NOT NULL,
	`due_date` date DEFAULT NULL,
	`status` enum('open','paid','partial','void') NOT NULL DEFAULT 'open',
	`created_by` int unsigned DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_invoice_number` (`organization_id`,`number`),
	KEY `ix_inv_part` (`participant_id`,`status`),
	CONSTRAINT `fk_inv_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_inv_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
	CONSTRAINT `fk_inv_sub` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kaspi RED комиссиясы: тариф бойынша (plan_id NULL = барлық тарифке)
CREATE TABLE `payment_method_fees` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`method` enum('kaspi','kaspi_red','bank','cash') NOT NULL,
	`plan_id` int unsigned DEFAULT NULL,
	`fee_percent` decimal(5,2) NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_fee` (`organization_id`,`method`,`plan_id`),
	CONSTRAINT `fk_fee_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_fee_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Төлемдер ешқашан өшірілмейді. Қате болса — reversal (кері жазба, теріс сома).
CREATE TABLE `payments` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`amount` decimal(12,2) NOT NULL,                -- reversal үшін теріс
	`fee_amount` decimal(12,2) NOT NULL DEFAULT 0,  -- RED комиссиясы
	`method` enum('kaspi','kaspi_red','bank','cash') NOT NULL,
	`source` enum('manual','integration') NOT NULL DEFAULT 'manual',
	`kind` enum('payment','refund','reversal','adjustment') NOT NULL DEFAULT 'payment',
	`status` enum('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
	`external_ref` varchar(80) DEFAULT NULL,         -- операция нөмірі
	`idempotency_key` varchar(64) NOT NULL,
	`reversal_of_id` int unsigned DEFAULT NULL,
	`paid_at` datetime NOT NULL,
	`entered_by` int unsigned NOT NULL,
	`entered_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`confirmed_by` int unsigned DEFAULT NULL,
	`confirmed_at` datetime DEFAULT NULL,
	`comment` varchar(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_pay_idem` (`organization_id`,`idempotency_key`),
	UNIQUE KEY `uq_pay_reversal` (`reversal_of_id`),
	UNIQUE KEY `uq_pay_extref` (`organization_id`,`method`,`external_ref`),
	KEY `ix_pay_part` (`participant_id`,`status`),
	KEY `ix_pay_org_date` (`organization_id`,`status`,`paid_at`),
	CONSTRAINT `fk_pay_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_pay_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
	CONSTRAINT `fk_pay_rev` FOREIGN KEY (`reversal_of_id`) REFERENCES `payments` (`id`),
	CONSTRAINT `fk_pay_enter` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`),
	CONSTRAINT `fk_pay_conf` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Төлемді абонементке бөлу (ішінара төлем)
CREATE TABLE `payment_allocations` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`payment_id` int unsigned NOT NULL,
	`subscription_id` int unsigned NOT NULL,
	`invoice_id` int unsigned DEFAULT NULL,
	`amount` decimal(12,2) NOT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_alloc` (`payment_id`,`subscription_id`),
	KEY `ix_alloc_sub` (`subscription_id`),
	CONSTRAINT `fk_al_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_al_pay` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
	CONSTRAINT `fk_al_sub` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`),
	CONSTRAINT `fk_al_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expenses` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`category` enum('rent','salary','equipment','marketing','tournament','other') NOT NULL,
	`amount` decimal(12,2) NOT NULL,
	`spent_at` date NOT NULL,
	`comment` varchar(255) DEFAULT NULL,
	`created_by` int unsigned DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `ix_exp_org_date` (`organization_id`,`spent_at`),
	CONSTRAINT `fk_exp_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. JAS RATING, марапаттар
-- ============================================================

-- Бір әрекетке бір ұпай: uq_rating (participant, rule, source) қайталауды бөгейді.
CREATE TABLE `rating_events` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`scope` enum('kids','adult') NOT NULL,
	`rule` enum('attendance','streak5','streak10','month100','coach_award','progress','manual') NOT NULL,
	`points` smallint NOT NULL,
	`source_type` varchar(30) NOT NULL,             -- attendance / session / month / achievement / assessment
	`source_id` int unsigned NOT NULL,
	`comment` varchar(255) DEFAULT NULL,
	`created_by` int unsigned DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_rating` (`participant_id`,`rule`,`source_type`,`source_id`),
	KEY `ix_rating_org` (`organization_id`,`scope`,`created_at`),
	CONSTRAINT `fk_re_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_re_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `achievements` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`name` varchar(80) NOT NULL,
	`icon` varchar(40) DEFAULT 'fa-medal',
	`points` smallint NOT NULL DEFAULT 30,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_ach_name` (`organization_id`,`name`),
	CONSTRAINT `fk_ach_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `participant_achievements` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`achievement_id` int unsigned NOT NULL,
	`awarded_by` int unsigned DEFAULT NULL,
	`awarded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`comment` varchar(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `ix_pa_part` (`participant_id`),
	CONSTRAINT `fk_pa_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_pa_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
	CONSTRAINT `fk_pa_ach` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. PLAYER DEVELOPMENT
-- ============================================================

-- Бағалау санаттары (кейін позиция бойынша бөлек баптауға болады: position NULL = барлығына)
CREATE TABLE `assessment_criteria` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`code` varchar(30) NOT NULL,
	`name` varchar(80) NOT NULL,
	`position` enum('gk','df','mf','fw') DEFAULT NULL,
	`sort` tinyint unsigned NOT NULL DEFAULT 0,
	`active` tinyint(1) NOT NULL DEFAULT 1,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_crit` (`organization_id`,`code`,`position`),
	CONSTRAINT `fk_crit_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `player_assessments` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`coach_id` int unsigned NOT NULL,
	`assessed_at` date NOT NULL,
	`comment` text,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `ix_pas_part` (`participant_id`,`assessed_at`),
	CONSTRAINT `fk_pas_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_pas_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
	CONSTRAINT `fk_pas_coach` FOREIGN KEY (`coach_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `assessment_scores` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`assessment_id` int unsigned NOT NULL,
	`criterion_id` int unsigned NOT NULL,
	`score` tinyint unsigned NOT NULL,              -- 1..10 (PHP-да тексеріледі)
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_score` (`assessment_id`,`criterion_id`),
	CONSTRAINT `fk_sc_as` FOREIGN KEY (`assessment_id`) REFERENCES `player_assessments` (`id`),
	CONSTRAINT `fk_sc_crit` FOREIGN KEY (`criterion_id`) REFERENCES `assessment_criteria` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. FIRST TEAM, ЛЛФ, матчтар
-- ============================================================

CREATE TABLE `teams` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`name` varchar(80) NOT NULL,
	`type` enum('first_team','other') NOT NULL DEFAULT 'first_team',
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_team_name` (`organization_id`,`name`),
	CONSTRAINT `fk_team_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Құрам тарихы: left_at IS NULL = қазір құрамда
CREATE TABLE `team_rosters` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`team_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`number` tinyint unsigned DEFAULT NULL,
	`position` enum('gk','df','mf','fw') DEFAULT NULL,
	`joined_at` date NOT NULL,
	`left_at` date DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `ix_roster_team` (`team_id`,`left_at`),
	KEY `ix_roster_part` (`participant_id`),
	CONSTRAINT `fk_ros_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_ros_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`),
	CONSTRAINT `fk_ros_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `competitions` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`name` varchar(120) NOT NULL,                   -- ЛЛФ ...
	`season` varchar(20) NOT NULL,                  -- 2026 / 2026-2027
	`start_date` date DEFAULT NULL,
	`end_date` date DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_comp` (`organization_id`,`name`,`season`),
	CONSTRAINT `fk_comp_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `opponents` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`name` varchar(120) NOT NULL,
	`logo` varchar(120) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_opp` (`organization_id`,`name`),
	CONSTRAINT `fk_opp_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `matches` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`competition_id` int unsigned DEFAULT NULL,
	`team_id` int unsigned NOT NULL,
	`opponent_id` int unsigned NOT NULL,
	`round` varchar(30) DEFAULT NULL,
	`match_at` datetime NOT NULL,
	`venue` varchar(160) DEFAULT NULL,
	`is_home` tinyint(1) NOT NULL DEFAULT 1,
	`goals_for` tinyint unsigned DEFAULT NULL,
	`goals_against` tinyint unsigned DEFAULT NULL,
	`status` enum('scheduled','live','finished','cancelled') NOT NULL DEFAULT 'scheduled',
	`mvp_participant_id` int unsigned DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `ix_match_org_date` (`organization_id`,`match_at`),
	CONSTRAINT `fk_m_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_m_comp` FOREIGN KEY (`competition_id`) REFERENCES `competitions` (`id`),
	CONSTRAINT `fk_m_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`),
	CONSTRAINT `fk_m_opp` FOREIGN KEY (`opponent_id`) REFERENCES `opponents` (`id`),
	CONSTRAINT `fk_m_mvp` FOREIGN KEY (`mvp_participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Матчқа қатысу абонемент лимитіне әсер етпейді (attendance кестесіне жазылмайды)
CREATE TABLE `match_player_stats` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`match_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`is_starter` tinyint(1) NOT NULL DEFAULT 0,
	`minute_in` tinyint unsigned DEFAULT NULL,
	`minute_out` tinyint unsigned DEFAULT NULL,
	`goals` tinyint unsigned NOT NULL DEFAULT 0,
	`assists` tinyint unsigned NOT NULL DEFAULT 0,
	`yellow` tinyint unsigned NOT NULL DEFAULT 0,
	`red` tinyint unsigned NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_mps` (`match_id`,`participant_id`),
	KEY `ix_mps_part` (`participant_id`),
	CONSTRAINT `fk_mps_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_mps_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`),
	CONSTRAINT `fk_mps_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 9. JAS SCOUTING
-- ============================================================

CREATE TABLE `selection_campaigns` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`name` varchar(120) NOT NULL,
	`target` enum('first_team','city_league') NOT NULL,
	`start_date` date DEFAULT NULL,
	`end_date` date DEFAULT NULL,
	`status` enum('open','closed') NOT NULL DEFAULT 'open',
	`created_by` int unsigned DEFAULT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `fk_selc_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Шешімді тек бапкер қабылдайды (decided_by), рейтинг автоматты қабылдамайды
CREATE TABLE `selection_candidates` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`campaign_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`status` enum('watching','candidate','invited','passed','failed','accepted') NOT NULL DEFAULT 'watching',
	`notes` text,
	`decided_by` int unsigned DEFAULT NULL,
	`decided_at` datetime DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_cand` (`campaign_id`,`participant_id`),
	CONSTRAINT `fk_cand_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_cand_camp` FOREIGN KEY (`campaign_id`) REFERENCES `selection_campaigns` (`id`),
	CONSTRAINT `fk_cand_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 10. JAS MEDIA
-- ============================================================

CREATE TABLE `media_albums` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`name` varchar(120) NOT NULL,
	`group_id` int unsigned DEFAULT NULL,
	`match_id` int unsigned DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	CONSTRAINT `fk_alb_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_assets` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`album_id` int unsigned DEFAULT NULL,
	`type` enum('photo','video') NOT NULL,
	`kind` enum('general','highlight','analysis') NOT NULL DEFAULT 'general',
	`storage_key` varchar(255) NOT NULL,            -- object storage кілті
	`title` varchar(160) DEFAULT NULL,
	`group_id` int unsigned DEFAULT NULL,
	`match_id` int unsigned DEFAULT NULL,
	`visibility` enum('staff','tagged','parents','public') NOT NULL DEFAULT 'staff',
	`uploaded_by` int unsigned DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_media_key` (`storage_key`),
	KEY `ix_media_org` (`organization_id`,`created_at`),
	CONSTRAINT `fk_ma_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_ma_alb` FOREIGN KEY (`album_id`) REFERENCES `media_albums` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media_asset_participants` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`media_asset_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_map` (`media_asset_id`,`participant_id`),
	KEY `ix_map_part` (`participant_id`),
	CONSTRAINT `fk_map_ma` FOREIGN KEY (`media_asset_id`) REFERENCES `media_assets` (`id`),
	CONSTRAINT `fk_map_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Балалардың медиаға келісімі (revoked_at толса — келісім кері алынған)
CREATE TABLE `media_consents` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`guardian_id` int unsigned DEFAULT NULL,
	`scope` enum('internal','social') NOT NULL,     -- тек платформа / әлеуметтік желі
	`granted_at` datetime NOT NULL,
	`revoked_at` datetime DEFAULT NULL,
	`recorded_by` int unsigned DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `ix_cons_part` (`participant_id`,`scope`),
	CONSTRAINT `fk_cons_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_cons_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`),
	CONSTRAINT `fk_cons_guard` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 11. TRAINING HUB
-- ============================================================

CREATE TABLE `training_lessons` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`category` enum('technique','physical','tactics','ball','individual') NOT NULL,
	`title` varchar(160) NOT NULL,
	`description` text,
	`video_key` varchar(255) DEFAULT NULL,
	`duration_min` smallint unsigned DEFAULT NULL,
	`level` enum('easy','medium','hard') NOT NULL DEFAULT 'easy',
	`reps` varchar(60) DEFAULT NULL,
	`coach_tip` text,
	`created_by` int unsigned DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `ix_tl_org` (`organization_id`,`category`),
	CONSTRAINT `fk_tl_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `training_assignments` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`lesson_id` int unsigned NOT NULL,
	`group_id` int unsigned DEFAULT NULL,
	`participant_id` int unsigned DEFAULT NULL,
	`due_date` date DEFAULT NULL,
	`created_by` int unsigned DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `ix_ta_group` (`group_id`),
	KEY `ix_ta_part` (`participant_id`),
	CONSTRAINT `fk_ta_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_ta_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `training_lessons` (`id`),
	CONSTRAINT `fk_ta_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
	CONSTRAINT `fk_ta_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `assignment_submissions` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`assignment_id` int unsigned NOT NULL,
	`participant_id` int unsigned NOT NULL,
	`status` enum('done','review','accepted','rejected') NOT NULL DEFAULT 'done',
	`video_key` varchar(255) DEFAULT NULL,
	`comment` text,
	`coach_comment` text,
	`reviewed_by` int unsigned DEFAULT NULL,
	`reviewed_at` datetime DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_sub_asg` (`assignment_id`,`participant_id`),
	CONSTRAINT `fk_as_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_as_asg` FOREIGN KEY (`assignment_id`) REFERENCES `training_assignments` (`id`),
	CONSTRAINT `fk_as_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 12. MARKETING CRM
-- ============================================================

CREATE TABLE `campaigns` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`name` varchar(120) NOT NULL,
	`channel` enum('instagram','tiktok','threads','other') NOT NULL,
	`budget` decimal(12,2) NOT NULL DEFAULT 0,
	`start_date` date DEFAULT NULL,
	`end_date` date DEFAULT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `fk_camp_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `leads` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`name` varchar(120) NOT NULL,
	`phone` varchar(11) NOT NULL,
	`direction` enum('kids','adult') DEFAULT NULL,
	`child_age` tinyint unsigned DEFAULT NULL,
	`source` enum('instagram','tiktok','threads','whatsapp','referral','walk_in','other') NOT NULL DEFAULT 'other',
	`campaign_id` int unsigned DEFAULT NULL,
	`status` enum('new','contacted','trial_booked','trial_done','converted','lost') NOT NULL DEFAULT 'new',
	`trial_session_id` int unsigned DEFAULT NULL,
	`participant_id` int unsigned DEFAULT NULL,     -- ақылы қатысушыға айналса
	`comment` varchar(255) DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `ix_lead_org_status` (`organization_id`,`status`,`created_at`),
	KEY `ix_lead_phone` (`organization_id`,`phone`),
	CONSTRAINT `fk_lead_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_lead_camp` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`),
	CONSTRAINT `fk_lead_sess` FOREIGN KEY (`trial_session_id`) REFERENCES `sessions` (`id`),
	CONSTRAINT `fk_lead_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `content_items` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`channel` enum('instagram','tiktok','threads') NOT NULL,
	`title` varchar(160) NOT NULL,
	`publish_at` datetime DEFAULT NULL,
	`status` enum('idea','in_work','ready','published') NOT NULL DEFAULT 'idea',
	`assignee_id` int unsigned DEFAULT NULL,
	PRIMARY KEY (`id`),
	KEY `ix_ci_org` (`organization_id`,`publish_at`),
	CONSTRAINT `fk_ci_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 13. JAS LIVE, хабарламалар, audit
-- ============================================================

-- YouTube Live ендіріледі; Instagram / TikTok — тек сыртқы сілтеме
CREATE TABLE `live_streams` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`match_id` int unsigned DEFAULT NULL,
	`platform` enum('youtube','instagram','tiktok') NOT NULL,
	`title` varchar(160) NOT NULL,
	`url` varchar(255) NOT NULL,
	`youtube_id` varchar(20) DEFAULT NULL,
	`starts_at` datetime NOT NULL,
	`status` enum('scheduled','live','ended') NOT NULL DEFAULT 'scheduled',
	PRIMARY KEY (`id`),
	KEY `ix_live_org` (`organization_id`,`starts_at`),
	CONSTRAINT `fk_live_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_live_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`user_id` int unsigned NOT NULL,
	`type` enum('birthday','sub_expiring','schedule_changed','assessment','lesson','match','other') NOT NULL,
	`title` varchar(160) NOT NULL,
	`body` varchar(500) DEFAULT NULL,
	`link` varchar(255) DEFAULT NULL,
	`dedupe_key` varchar(80) DEFAULT NULL,
	`read_at` datetime DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_notif_dedupe` (`user_id`,`dedupe_key`),
	KEY `ix_notif_user` (`user_id`,`read_at`),
	CONSTRAINT `fk_n_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
	CONSTRAINT `fk_n_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
	`id` bigint unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned DEFAULT NULL,
	`user_id` int unsigned DEFAULT NULL,
	`action` varchar(40) NOT NULL,                  -- create / update / archive / login / reverse ...
	`entity` varchar(40) NOT NULL,
	`entity_id` int unsigned DEFAULT NULL,
	`before_data` text,
	`after_data` text,
	`ip` varchar(45) DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `ix_audit_entity` (`entity`,`entity_id`),
	KEY `ix_audit_org` (`organization_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
