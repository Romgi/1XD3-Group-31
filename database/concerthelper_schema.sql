-- ConcertHelper database import
-- Import this file in phpMyAdmin or the MySQL CLI.
-- Default database name matches concerthelper/includes/connect.php.

CREATE DATABASE IF NOT EXISTS graydj1_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE graydj1_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS member_recordings;
DROP TABLE IF EXISTS member_concerts;
DROP TABLE IF EXISTS member_parts;
DROP TABLE IF EXISTS recordings;
DROP TABLE IF EXISTS parts;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS members;
DROP TABLE IF EXISTS concerts;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE concerts (
    concert_id VARCHAR(191) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    concert_date DATE NOT NULL,
    start_time TIME DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    status ENUM('upcoming', 'past') NOT NULL DEFAULT 'upcoming',
    program_file_name VARCHAR(255) DEFAULT NULL,
    performance_file_name VARCHAR(255) DEFAULT NULL,
    performance_url VARCHAR(1000) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (concert_id),
    KEY idx_concerts_date (concert_date),
    KEY idx_concerts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE members (
    member_id VARCHAR(191) NOT NULL,
    name VARCHAR(255) NOT NULL,
    instrument VARCHAR(191) DEFAULT NULL,
    section VARCHAR(191) DEFAULT NULL,
    email VARCHAR(254) DEFAULT NULL,
    file_name VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (member_id),
    KEY idx_members_section (section),
    KEY idx_members_instrument (instrument)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    user_id VARCHAR(191) NOT NULL,
    member_id VARCHAR(191) DEFAULT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uniq_users_email (email),
    KEY idx_users_member_id (member_id),
    KEY idx_users_role (role),
    CONSTRAINT fk_users_member
        FOREIGN KEY (member_id) REFERENCES members (member_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE parts (
    part_id VARCHAR(191) NOT NULL,
    concert_id VARCHAR(191) NOT NULL,
    instrument_part VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (part_id),
    KEY idx_parts_concert_id (concert_id),
    KEY idx_parts_instrument_part (instrument_part),
    CONSTRAINT fk_parts_concert
        FOREIGN KEY (concert_id) REFERENCES concerts (concert_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recordings (
    recording_id VARCHAR(191) NOT NULL,
    concert_id VARCHAR(191) NOT NULL,
    part_id VARCHAR(191) DEFAULT NULL,
    part_name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    recording_url VARCHAR(1000) DEFAULT NULL,
    recording_type ENUM('upload', 'youtube', 'spotify', 'other') NOT NULL DEFAULT 'upload',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (recording_id),
    KEY idx_recordings_concert_id (concert_id),
    KEY idx_recordings_part_id (part_id),
    CONSTRAINT fk_recordings_concert
        FOREIGN KEY (concert_id) REFERENCES concerts (concert_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_recordings_part
        FOREIGN KEY (part_id) REFERENCES parts (part_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE member_parts (
    member_part_id VARCHAR(191) NOT NULL,
    member_id VARCHAR(191) NOT NULL,
    part_id VARCHAR(191) NOT NULL,
    concert_id VARCHAR(191) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (member_part_id),
    KEY idx_member_parts_member_id (member_id),
    KEY idx_member_parts_part_id (part_id),
    KEY idx_member_parts_concert_id (concert_id),
    CONSTRAINT fk_member_parts_member
        FOREIGN KEY (member_id) REFERENCES members (member_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_member_parts_part
        FOREIGN KEY (part_id) REFERENCES parts (part_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_member_parts_concert
        FOREIGN KEY (concert_id) REFERENCES concerts (concert_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE member_concerts (
    member_concert_id VARCHAR(191) NOT NULL,
    member_id VARCHAR(191) NOT NULL,
    concert_id VARCHAR(191) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (member_concert_id),
    KEY idx_member_concerts_member_id (member_id),
    KEY idx_member_concerts_concert_id (concert_id),
    CONSTRAINT fk_member_concerts_member
        FOREIGN KEY (member_id) REFERENCES members (member_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_member_concerts_concert
        FOREIGN KEY (concert_id) REFERENCES concerts (concert_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE member_recordings (
    member_recording_id VARCHAR(191) NOT NULL,
    member_id VARCHAR(191) NOT NULL,
    recording_id VARCHAR(191) NOT NULL,
    concert_id VARCHAR(191) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (member_recording_id),
    KEY idx_member_recordings_member_id (member_id),
    KEY idx_member_recordings_recording_id (recording_id),
    KEY idx_member_recordings_concert_id (concert_id),
    CONSTRAINT fk_member_recordings_member
        FOREIGN KEY (member_id) REFERENCES members (member_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_member_recordings_recording
        FOREIGN KEY (recording_id) REFERENCES recordings (recording_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_member_recordings_concert
        FOREIGN KEY (concert_id) REFERENCES concerts (concert_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo data
-- Password for both accounts: concerthelper

INSERT INTO members (
    member_id,
    name,
    instrument,
    section,
    email,
    file_name,
    description,
    is_active
) VALUES
    ('macid1', 'Demo Member', 'Clarinet', 'Woodwinds', 'macid1@mcmaster.ca', '', 'Demo member account for testing member login.', 1);

INSERT INTO users (
    user_id,
    member_id,
    email,
    password_hash,
    role
) VALUES
    ('admin', NULL, 'admin@mcmaster.ca', '$2y$10$R.J0i5id5NfJfuxpQvdQmuIU/twb2bCBWowqrSI55tro0LVcyHuce', 'admin'),
    ('macid1', 'macid1', 'macid1@mcmaster.ca', '$2y$10$DFRTjrPn3K0Wple5Mo4ap.4WYrZ0/PU4wWkDEA8nLfWiS2X4b6RLC', 'member');

INSERT INTO concerts (
    concert_id,
    title,
    description,
    concert_date,
    start_time,
    location,
    status,
    performance_url
) VALUES
    ('spring_2026', 'Spring Concert', 'Completed demo concert for testing past performance links.', '2026-04-15', '19:30:00', 'McMaster University', 'past', 'https://www.youtube.com/watch?v=rq1-_UPwYSM'),
    ('winter_2026', 'Winter Concert', 'Demo concert for testing member parts and reference recordings.', '2026-11-20', '19:30:00', 'McMaster University', 'upcoming', NULL);

INSERT INTO parts (
    part_id,
    concert_id,
    instrument_part,
    file_name
) VALUES
    ('winter_2026_clarinet_1', 'winter_2026', 'Clarinet 1', 'Iron_Foundry.pdf');

INSERT INTO recordings (
    recording_id,
    concert_id,
    part_id,
    part_name,
    recording_url,
    recording_type
) VALUES
    ('winter_2026_clarinet_1_ref', 'winter_2026', 'winter_2026_clarinet_1', 'Clarinet 1', 'https://www.youtube.com/watch?v=rq1-_UPwYSM', 'youtube');

INSERT INTO member_concerts (
    member_concert_id,
    member_id,
    concert_id
) VALUES
    ('macid1_winter_2026', 'macid1', 'winter_2026');

INSERT INTO member_parts (
    member_part_id,
    member_id,
    part_id,
    concert_id
) VALUES
    ('macid1_winter_2026_clarinet_1', 'macid1', 'winter_2026_clarinet_1', 'winter_2026');

INSERT INTO member_recordings (
    member_recording_id,
    member_id,
    recording_id,
    concert_id
) VALUES
    ('macid1_winter_2026_clarinet_1_ref', 'macid1', 'winter_2026_clarinet_1_ref', 'winter_2026');
