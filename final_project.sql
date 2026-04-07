-- phpMyAdmin SQL Dump
-- Database: `graydj1_db`
--
-- ConcertHelper schema updated to support:
-- - member/admin login accounts
-- - public member listings by instrument/section
-- - upcoming and past concerts
-- - uploaded music parts and performance files
-- - external reference recording links
-- - member dashboard part assignments

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

USE `graydj1_db`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `member_id` varchar(500) NOT NULL,
  `name` varchar(500) NOT NULL,
  `instrument` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `email` varchar(254) DEFAULT NULL,
  `file_name` varchar(500) NOT NULL,
  `description` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- `password_hash` should store the output from PHP password_hash(), not a plain-text password.

CREATE TABLE `users` (
  `user_id` varchar(500) NOT NULL,
  `member_id` varchar(500) DEFAULT NULL,
  `email` varchar(254) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','member') NOT NULL DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `concerts`
--

CREATE TABLE `concerts` (
  `concert_id` varchar(500) NOT NULL,
  `title` varchar(500) NOT NULL,
  `description` text NOT NULL,
  `concert_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `location` varchar(500) DEFAULT NULL,
  `status` enum('upcoming','past') NOT NULL DEFAULT 'upcoming',
  `program_file_name` varchar(500) DEFAULT NULL,
  `performance_file_name` varchar(500) DEFAULT NULL,
  `performance_url` varchar(1000) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parts`
--

CREATE TABLE `parts` (
  `part_id` varchar(500) NOT NULL,
  `concert_id` varchar(500) NOT NULL,
  `instrument_part` varchar(500) NOT NULL,
  `file_name` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recordings`
--

CREATE TABLE `recordings` (
  `recording_id` varchar(500) NOT NULL,
  `concert_id` varchar(500) NOT NULL,
  `part_id` varchar(500) DEFAULT NULL,
  `part_name` varchar(500) NOT NULL,
  `file_name` varchar(500) DEFAULT NULL,
  `recording_url` varchar(1000) DEFAULT NULL,
  `recording_type` enum('upload','youtube','spotify','other') NOT NULL DEFAULT 'upload',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_parts`
--
-- Main assignment table for the member dashboard: which music part a member plays for a concert.

CREATE TABLE `member_parts` (
  `member_part_id` varchar(500) NOT NULL,
  `member_id` varchar(500) NOT NULL,
  `part_id` varchar(500) NOT NULL,
  `concert_id` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_concerts`
--
-- Optional table if you want to track concert membership separately from assigned parts.

CREATE TABLE `member_concerts` (
  `member_concert_id` varchar(500) NOT NULL,
  `member_id` varchar(500) NOT NULL,
  `concert_id` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_recordings`
--
-- Optional table if recordings must be assigned to specific members instead of through their part.

CREATE TABLE `member_recordings` (
  `member_recording_id` varchar(500) NOT NULL,
  `member_id` varchar(500) NOT NULL,
  `recording_id` varchar(500) NOT NULL,
  `concert_id` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `idx_members_section` (`section`),
  ADD KEY `idx_members_instrument` (`instrument`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uniq_users_email` (`email`),
  ADD KEY `idx_users_member_id` (`member_id`),
  ADD KEY `idx_users_role` (`role`);

ALTER TABLE `concerts`
  ADD PRIMARY KEY (`concert_id`),
  ADD KEY `idx_concerts_date` (`concert_date`),
  ADD KEY `idx_concerts_status` (`status`);

ALTER TABLE `parts`
  ADD PRIMARY KEY (`part_id`),
  ADD KEY `idx_parts_concert_id` (`concert_id`),
  ADD KEY `idx_parts_instrument_part` (`instrument_part`);

ALTER TABLE `recordings`
  ADD PRIMARY KEY (`recording_id`),
  ADD KEY `idx_recordings_concert_id` (`concert_id`),
  ADD KEY `idx_recordings_part_id` (`part_id`);

ALTER TABLE `member_parts`
  ADD PRIMARY KEY (`member_part_id`),
  ADD KEY `idx_member_parts_member_id` (`member_id`),
  ADD KEY `idx_member_parts_part_id` (`part_id`),
  ADD KEY `idx_member_parts_concert_id` (`concert_id`);

ALTER TABLE `member_concerts`
  ADD PRIMARY KEY (`member_concert_id`),
  ADD KEY `idx_member_concerts_member_id` (`member_id`),
  ADD KEY `idx_member_concerts_concert_id` (`concert_id`);

ALTER TABLE `member_recordings`
  ADD PRIMARY KEY (`member_recording_id`),
  ADD KEY `idx_member_recordings_member_id` (`member_id`),
  ADD KEY `idx_member_recordings_recording_id` (`recording_id`),
  ADD KEY `idx_member_recordings_concert_id` (`concert_id`);

-- --------------------------------------------------------

--
-- Foreign key constraints for dumped tables
--

ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `parts`
  ADD CONSTRAINT `fk_parts_concert` FOREIGN KEY (`concert_id`) REFERENCES `concerts` (`concert_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `recordings`
  ADD CONSTRAINT `fk_recordings_concert` FOREIGN KEY (`concert_id`) REFERENCES `concerts` (`concert_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recordings_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`part_id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `member_parts`
  ADD CONSTRAINT `fk_member_parts_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_member_parts_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`part_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_member_parts_concert` FOREIGN KEY (`concert_id`) REFERENCES `concerts` (`concert_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `member_concerts`
  ADD CONSTRAINT `fk_member_concerts_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_member_concerts_concert` FOREIGN KEY (`concert_id`) REFERENCES `concerts` (`concert_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `member_recordings`
  ADD CONSTRAINT `fk_member_recordings_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_member_recordings_recording` FOREIGN KEY (`recording_id`) REFERENCES `recordings` (`recording_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_member_recordings_concert` FOREIGN KEY (`concert_id`) REFERENCES `concerts` (`concert_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- --------------------------------------------------------

--
-- Test accounts
--
-- Password for both accounts: concerthelper

INSERT INTO `members` (`member_id`, `name`, `instrument`, `section`, `email`, `file_name`, `description`, `is_active`) VALUES
('macid1', 'Demo Member', 'Clarinet', 'Woodwinds', 'macid1@mcmaster.ca', '', 'Demo member account for testing member login.', 1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `instrument` = VALUES(`instrument`),
  `section` = VALUES(`section`),
  `email` = VALUES(`email`),
  `description` = VALUES(`description`),
  `is_active` = VALUES(`is_active`);

INSERT INTO `users` (`user_id`, `member_id`, `email`, `password_hash`, `role`) VALUES
('admin', NULL, 'admin@mcmaster.ca', '$2y$10$R.J0i5id5NfJfuxpQvdQmuIU/twb2bCBWowqrSI55tro0LVcyHuce', 'admin'),
('macid1', 'macid1', 'macid1@mcmaster.ca', '$2y$10$DFRTjrPn3K0Wple5Mo4ap.4WYrZ0/PU4wWkDEA8nLfWiS2X4b6RLC', 'member')
ON DUPLICATE KEY UPDATE
  `member_id` = VALUES(`member_id`),
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `role` = VALUES(`role`);

INSERT INTO `concerts` (`concert_id`, `title`, `description`, `concert_date`, `start_time`, `location`, `status`, `performance_url`) VALUES
('spring_2026', 'Spring Concert', 'Demo concert for testing member parts and reference recordings.', '2026-04-15', '19:30:00', 'McMaster University', 'upcoming', 'https://www.youtube.com/watch?v=rq1-_UPwYSM')
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `description` = VALUES(`description`),
  `concert_date` = VALUES(`concert_date`),
  `start_time` = VALUES(`start_time`),
  `location` = VALUES(`location`),
  `status` = VALUES(`status`),
  `performance_url` = VALUES(`performance_url`);

INSERT INTO `parts` (`part_id`, `concert_id`, `instrument_part`, `file_name`) VALUES
('spring_2026_clarinet_1', 'spring_2026', 'Clarinet 1', 'Iron_Foundry.pdf')
ON DUPLICATE KEY UPDATE
  `concert_id` = VALUES(`concert_id`),
  `instrument_part` = VALUES(`instrument_part`),
  `file_name` = VALUES(`file_name`);

INSERT INTO `recordings` (`recording_id`, `concert_id`, `part_id`, `part_name`, `recording_url`, `recording_type`) VALUES
('spring_2026_clarinet_1_ref', 'spring_2026', 'spring_2026_clarinet_1', 'Clarinet 1', 'https://www.youtube.com/watch?v=rq1-_UPwYSM', 'youtube')
ON DUPLICATE KEY UPDATE
  `concert_id` = VALUES(`concert_id`),
  `part_id` = VALUES(`part_id`),
  `part_name` = VALUES(`part_name`),
  `recording_url` = VALUES(`recording_url`),
  `recording_type` = VALUES(`recording_type`);

INSERT INTO `member_parts` (`member_part_id`, `member_id`, `part_id`, `concert_id`) VALUES
('macid1_spring_2026_clarinet_1', 'macid1', 'spring_2026_clarinet_1', 'spring_2026')
ON DUPLICATE KEY UPDATE
  `member_id` = VALUES(`member_id`),
  `part_id` = VALUES(`part_id`),
  `concert_id` = VALUES(`concert_id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
