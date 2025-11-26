CREATE TABLE IF NOT EXISTS `trips` (
  `id` VARCHAR(255) NOT NULL,
  `route_id` VARCHAR(255) NOT NULL,
  `service_id` VARCHAR(255) NOT NULL,
  `trip_headsign` VARCHAR(255) NOT NULL,
  `direction_id` INT NOT NULL,
  `block_id` VARCHAR(255) NOT NULL,
  `shape_id` VARCHAR(255) NOT NULL,
  `wheelchair_accessible` TINYINT NOT NULL,
  `bikes_allowed` TINYINT NOT NULL,
  PRIMARY KEY (`id`, `service_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `stops` (
  `id` VARCHAR(255) PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `lat` DOUBLE NOT NULL,
  `lon` DOUBLE NOT NULL,
  `code` VARCHAR(255) NOT NULL,
  `location_type` TINYINT NOT NULL,
  `location_sub_type` VARCHAR(255) NOT NULL,
  `parent_station` VARCHAR(255) NOT NULL,
  `wheelchair_boarding` TINYINT NOT NULL
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `stop_times` (
  `trip_id` VARCHAR(255) NOT NULL,
  `stop_id` VARCHAR(255) NOT NULL,
  `arrival_time` SMALLINT NOT NULL,
  `departure_time` SMALLINT NOT NULL,
  `stop_sequence` TINYINT NOT NULL,
  `stop_headsign` VARCHAR(255),
  `pickup_type` TINYINT NOT NULL,
  `drop_off_type` TINYINT NOT NULL,
  `shape_dist_traveled` FLOAT NOT NULL,
  PRIMARY KEY (`trip_id`, `stop_id`, `stop_sequence`),
  FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`),
  FOREIGN KEY (`stop_id`) REFERENCES `stops`(`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `shapes` (
  `id` VARCHAR(255) NOT NULL,
  `pt_sequence` INT NOT NULL,
  `pt_lat` DOUBLE NOT NULL,
  `pt_lon` DOUBLE NOT NULL,
  `dist_traveled` DOUBLE NOT NULL,
  PRIMARY KEY (`id`, `pt_sequence`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `agency` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `time_zone` VARCHAR(255) NOT NULL,
  `lang` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `routes` (
  `id` varchar(255) NOT NULL,
  `agency_id` VARCHAR(255) NOT NULL,
  `short_name` VARCHAR(255),
  `long_name` VARCHAR(255),
  `type` TINYINT NOT NULL,
  `desc` VARCHAR(255) NOT NULL,
  `color` VARCHAR(255) NOT NULL,
  `text_color` VARCHAR(255) NOT NULL,
  `sort_order` SMALLINT NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`agency_id`) REFERENCES `agency`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `pathways` (
  `id` VARCHAR(255) NOT NULL,
  `mode` TINYINT NOT NULL,
  `is_bidirectional` BOOLEAN NOT NULL,
  `from_stop_id` VARCHAR(255) NOT NULL,
  `to_stop_id` VARCHAR(255) NOT NULL,
  `traversal_time` INT NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`from_stop_id`) REFERENCES `stops`(`id`),
  FOREIGN KEY (`to_stop_id`) REFERENCES `stops`(`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `feed_info` (
  `id` VARCHAR(255) NOT NULL,
  `publisher_name` VARCHAR(255) NOT NULL,
  `publisher_url` VARCHAR(255) NOT NULL,
  `lang` VARCHAR(255) NOT NULL,
  `start_date` INT NOT NULL,
  `end_date` INT NOT NULL,
  `version` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `calendar_dates` (
  `service_id` VARCHAR(255) NOT NULL,
  `date` INT NOT NULL,
  `exception_type` TINYINT NOT NULL
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT,
  `first_name` VARCHAR(255) DEFAULT NULL,
  `second_name` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `is_guest` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci

