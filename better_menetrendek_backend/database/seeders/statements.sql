CREATE TABLE `trips` (
  `trip_id` VARCHAR(255) NOT NULL,
  `route_id` varchar(255) NOT NULL,
  `service_id` VARCHAR(255) NOT NULL,
  `trip_headsign` VARCHAR(255) NOT NULL,
  `direction_id` INT NOT NULL,
  `block_id` VARCHAR(255) NOT NULL,
  `shape_id` VARCHAR(255) NOT NULL,
  `wheelchair_accessible` TINYINT NOT NULL,
  `bikes_allowed` TINYINT NOT NULL,
  PRIMARY KEY (`trip_id`, `service_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE `stops` (
  `stop_id` INT PRIMARY KEY,
  `stop_name` VARCHAR(255) NOT NULL,
  `stop_lat` DOUBLE NOT NULL,
  `stop_lon` DOUBLE NOT NULL,
  `stop_code` VARCHAR(255) NOT NULL,
  `location_type` TINYINT NOT NULL,
  `location_sub_type` TINYINT NOT NULL,
  `parent_station` INT NOT NULL,
  `wheelchair_boarding` TINYINT NOT NULL
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE `stop_times` (
  `trip_id` VARCHAR(255) NOT NULL,
  `stop_id` INT NOT NULL,
  `arrival_time` SMALLINT NOT NULL,
  `departure_time` SMALLINT NOT NULL,
  `stop_sequence` TINYINT NOT NULL,
  `stop_headsign` VARCHAR(255),
  `pickup_type` TINYINT NOT NULL,
  `drop_off_type` TINYINT NOT NULL,
  `shape_dist_traveled` FLOAT NOT NULL,
  PRIMARY KEY (`trip_id`, `stop_id`, `stop_sequence`),
  FOREIGN KEY (`trip_id`) REFERENCES `trips`(`trip_id`),
  FOREIGN KEY (`stop_id`) REFERENCES `stops`(`stop_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE `shapes` (
  `shape_id` VARCHAR(255) NOT NULL,
  `shape_pt_sequence` INT NOT NULL,
  `shape_pt_lat` DOUBLE NOT NULL,
  `shape_pt_lon` DOUBLE NOT NULL,
  `shape_dist_traveled` DOUBLE NOT NULL,
  PRIMARY KEY (`shape_id`, `shape_pt_sequence`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE `agency` (
  `agency_id` VARCHAR(255) NOT NULL,
  `agency_name` VARCHAR(255) NOT NULL,
  `agency_url` VARCHAR(255) NOT NULL,
  `agency_timezone` VARCHAR(255) NOT NULL,
  `agency_lang` VARCHAR(255) NOT NULL,
  `agency_phone` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`agency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE `routes` (
  `route_id` varchar(255) NOT NULL,
  `agency_id` VARCHAR(255) NOT NULL,
  `route_short_name` VARCHAR(255),
  `route_long_name` VARCHAR(255),
  `route_type` TINYINT NOT NULL,
  `route_desc` VARCHAR(255) NOT NULL,
  `route_color` VARCHAR(255) NOT NULL,
  `route_text_color` VARCHAR(255) NOT NULL,
  `route_sort_order` SMALLINT NOT NULL,
  PRIMARY KEY (`route_id`),
  FOREIGN KEY (`agency_id`) REFERENCES `agency`(`agency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE `pathways` (
  `pathway_id` INT NOT NULL,
  `pathway_mode` TINYINT NOT NULL,
  `is_bidirectional` BOOLEAN NOT NULL,
  `from_stop_id` INT NOT NULL,
  `to_stop_id` INT NOT NULL,
  `traversal_time` INT NOT NULL,
  PRIMARY KEY (`pathway_id`),
  FOREIGN KEY (`from_stop_id`) REFERENCES `stops`(`stop_id`),
  FOREIGN KEY (`to_stop_id`) REFERENCES `stops`(`stop_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE `feed_info` (
  `feed_id` VARCHAR(255) NOT NULL,
  `feed_publisher_name` VARCHAR(255) NOT NULL,
  `feed_publisher_url` VARCHAR(255) NOT NULL,
  `feed_lang` VARCHAR(255) NOT NULL,
  `feed_start_date` INT NOT NULL,
  `feed_end_date` INT NOT NULL,
  `feed_version` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`feed_id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE `calendar_dates` (
  `service_id` VARCHAR(255) NOT NULL,
  `date` INT NOT NULL,
  `exception_type` TINYINT NOT NULL
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT,
  `first_name` VARCHAR(255) DEFAULT NULL,
  `second_name` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `is_guest` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci

