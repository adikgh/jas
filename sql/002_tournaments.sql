-- Турнирлер: ішкі (2 апта сайын), жексенбілік, сыртқы. Тек жаңа кестелер (ескілері өзгермейді).

CREATE TABLE IF NOT EXISTS `tournaments` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`organization_id` int unsigned NOT NULL,
	`name` varchar(120) NOT NULL,
	`kind` enum('internal','sunday','external') NOT NULL DEFAULT 'sunday',
	`direction` enum('kids','adult','mixed') NOT NULL DEFAULT 'adult',
	`starts_at` datetime NOT NULL,
	`venue` varchar(160) DEFAULT NULL,
	`entry_fee` decimal(12,2) NOT NULL DEFAULT 0,
	`max_teams` tinyint unsigned DEFAULT NULL,
	`prize` varchar(255) DEFAULT NULL,
	`status` enum('registration','running','finished','cancelled') NOT NULL DEFAULT 'registration',
	`join_code` char(10) NOT NULL,
	`created_by` int unsigned DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_tour_code` (`join_code`),
	KEY `ix_tour_org` (`organization_id`,`starts_at`),
	CONSTRAINT `fk_trn_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tournament_teams` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`tournament_id` int unsigned NOT NULL,
	`name` varchar(80) NOT NULL,
	`captain` varchar(120) DEFAULT NULL,
	`phone` varchar(11) DEFAULT NULL,
	`paid` tinyint(1) NOT NULL DEFAULT 0,
	`place` tinyint unsigned DEFAULT NULL,
	`source` enum('staff','public') NOT NULL DEFAULT 'staff',
	`ip` varchar(45) DEFAULT NULL,
	`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_tt_name` (`tournament_id`,`name`),
	CONSTRAINT `fk_trt_tour` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tournament_games` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`tournament_id` int unsigned NOT NULL,
	`home_id` int unsigned NOT NULL,
	`away_id` int unsigned NOT NULL,
	`stage` varchar(40) DEFAULT NULL,
	`home_goals` tinyint unsigned DEFAULT NULL,
	`away_goals` tinyint unsigned DEFAULT NULL,
	`status` enum('planned','finished') NOT NULL DEFAULT 'planned',
	PRIMARY KEY (`id`),
	KEY `ix_tg_tour` (`tournament_id`),
	CONSTRAINT `fk_trg_tour` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`),
	CONSTRAINT `fk_trg_home` FOREIGN KEY (`home_id`) REFERENCES `tournament_teams` (`id`),
	CONSTRAINT `fk_trg_away` FOREIGN KEY (`away_id`) REFERENCES `tournament_teams` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tournament_awards` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`tournament_id` int unsigned NOT NULL,
	`title` varchar(80) NOT NULL,
	`person` varchar(120) NOT NULL,
	`participant_id` int unsigned DEFAULT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `fk_trw_tour` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`),
	CONSTRAINT `fk_trw_part` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
