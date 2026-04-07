-- =============================================================================
-- ConcertHelper — single database script (graydj1_db or your DB_NAME in connect.php)
-- Import once in phpMyAdmin: select your database → SQL → paste this file → Go.
-- If a step errors (e.g. column already exists), skip that line or comment it out.
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Optional: only if you already have OLD tables missing these columns (run once)
-- -----------------------------------------------------------------------------
-- ALTER TABLE members
--     ADD COLUMN email VARCHAR(255) NULL DEFAULT NULL COMMENT 'Login email; unique when set';
-- CREATE UNIQUE INDEX idx_members_email ON members (email);

-- ALTER TABLE member_parts
--     ADD COLUMN youtube_url VARCHAR(512) NULL DEFAULT NULL COMMENT 'Full https YouTube URL for play button' AFTER audio_file_name;

-- -----------------------------------------------------------------------------
-- Tables (safe to run on empty database)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS members (
    member_id VARCHAR(64) NOT NULL PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Basename only; photo in assets/uploads/members/',
    description VARCHAR(512) NOT NULL DEFAULT '',
    email VARCHAR(255) NULL DEFAULT NULL COMMENT 'Login email; unique when set',
    UNIQUE KEY idx_members_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_parts (
    member_part_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(64) NOT NULL,
    piece_title VARCHAR(255) NOT NULL,
    part_label VARCHAR(128) NOT NULL,
    pdf_file_name VARCHAR(255) NOT NULL COMMENT 'Basename only; file lives in assets/uploads/parts/',
    audio_file_name VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Basename of video/audio in assets/uploads/performances/ — used for play when youtube_url is empty',
    youtube_url VARCHAR(512) NULL DEFAULT NULL COMMENT 'External https video URL (YouTube, Vimeo, …) — takes priority over audio_file_name for play',
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_member_parts_member (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Demo member (login: macid1@mcmaster.ca / concerthelper — password in app.php)
-- -----------------------------------------------------------------------------
INSERT INTO members (member_id, file_name, description, email)
VALUES ('demo_member_1', '', 'Demo Member', 'macid1@mcmaster.ca')
ON DUPLICATE KEY UPDATE email = COALESCE(NULLIF(VALUES(email), ''), email);

-- -----------------------------------------------------------------------------
-- Sample parts: PDFs in parts/; play ▶ uses youtube_url if set, else file in performances/ (audio_file_name)
-- -----------------------------------------------------------------------------
DELETE FROM member_parts WHERE member_id = 'demo_member_1';
INSERT INTO member_parts (member_id, piece_title, part_label, pdf_file_name, audio_file_name, youtube_url, sort_order)
VALUES
    ('demo_member_1', 'The Iron Foundry', 'Tpt 1.', 'Iron_Foundry.pdf', '', 'https://www.youtube.com/watch?v=rq1-_UPwYSM', 1),
    ('demo_member_1', 'Caravan', 'Sax 2.', 'Caravan.pdf', '', 'https://www.youtube.com/watch?v=38CRu1rCaKg', 2);
