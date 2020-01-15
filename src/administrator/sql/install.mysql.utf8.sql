CREATE TABLE IF NOT EXISTS `#__timelog_activity_type` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`title` VARCHAR(255)  NOT NULL ,
`description` TEXT NOT NULL ,
`state` TINYINT(1)  NOT NULL ,
`created_by` INT(11)  NOT NULL ,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__timelog_activities` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`activity_type_id` INT(11)  NOT NULL ,
`client` VARCHAR(255)  NOT NULL ,
`client_id` INT(11)  NOT NULL ,
`activity_note` TEXT NOT NULL ,
`created_date` DATETIME NOT NULL ,
`timelog` TIME ,
`state` TINYINT(1)  NOT NULL ,
`attachment` VARCHAR(255)  NOT NULL ,
`created_by` INT(11)  NOT NULL ,
`modified_date` DATETIME NOT NULL ,
`modified_by` INT(11)  NOT NULL ,
PRIMARY KEY (`id`),
INDEX `client` (`client`,`client_id`),
INDEX `activity_type_id` (`activity_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

