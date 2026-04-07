<?php
declare(strict_types=1);

require_once __DIR__ . "/connect.php";

const APP_NAME = "ConcertHelper";
const ROLE_ADMIN = "admin";
const ROLE_MEMBER = "member";
const MEMBER_PHOTO_UPLOAD_DIR = __DIR__ . "/../assets/uploads/members";
const MEMBER_PHOTO_UPLOAD_URL = "assets/uploads/members";
const PARTS_UPLOAD_DIR = __DIR__ . "/../assets/uploads/parts";
const PARTS_UPLOAD_URL = "assets/uploads/parts";
const PERFORMANCES_UPLOAD_DIR = __DIR__ . "/../assets/uploads/performances";
const PERFORMANCES_UPLOAD_URL = "assets/uploads/performances";

function startAppSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? "", ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function redirectTo(string $location): never
{
    header("Location: {$location}");
    exit;
}

function loginRole(string $email, string $password): ?string
{
    $email = strtolower(trim($email));

    $db = getDb();
    $statement = $db->prepare(
        "SELECT role, password_hash
         FROM users
         WHERE email = :email
         LIMIT 1"
    );
    $statement->execute(["email" => $email]);
    $user = $statement->fetch();

    if (!is_array($user) || !password_verify($password, (string) ($user["password_hash"] ?? ""))) {
        return null;
    }

    $role = (string) ($user["role"] ?? "");

    return in_array($role, [ROLE_ADMIN, ROLE_MEMBER], true) ? $role : null;
}

function signIn(string $role, ?string $memberId = null): void
{
    startAppSession();
    $_SESSION["role"] = $role;
    if ($memberId !== null && $memberId !== "") {
        $_SESSION["member_id"] = $memberId;
    } else {
        unset($_SESSION["member_id"]);
    }
}

function currentMemberId(): ?string
{
    startAppSession();
    $id = $_SESSION["member_id"] ?? null;

    return is_string($id) && $id !== "" ? $id : null;
}

function currentRole(): ?string
{
    startAppSession();
    $role = $_SESSION["role"] ?? null;

    return is_string($role) ? $role : null;
}

function signOut(): void
{
    startAppSession();
    $_SESSION = [];
    session_destroy();
}

function dashboardUrlForRole(?string $role): string
{
    return $role === ROLE_ADMIN ? "admin-dashboard.php" : "member-dashboard.php";
}

function currentDashboardUrl(): string
{
    return dashboardUrlForRole(currentRole());
}

/**
 * @param array<int, string> $allowedRoles
 */
function requireRole(array $allowedRoles): void
{
    $role = currentRole();

    if ($role === null) {
        redirectTo("login.php");
    }

    if (!in_array($role, $allowedRoles, true)) {
        redirectTo(currentDashboardUrl());
    }
}

/**
 * @return array<int, array<string, string>>
 */
function getMembers(): array
{
    $db = getDb();
    $statement = $db->query(
        "SELECT member_id, name, instrument, section, file_name, description, email
         FROM members
         ORDER BY member_id ASC"
    );

    return $statement->fetchAll();
}

function getMemberIdByEmail(string $email): ?string
{
    $email = strtolower(trim($email));
    $db = getDb();
    $statement = $db->prepare(
        "SELECT u.member_id
         FROM users u
         INNER JOIN members m ON m.member_id = u.member_id
         WHERE LOWER(TRIM(u.email)) = :email
         LIMIT 1"
    );
    $statement->execute([":email" => $email]);
    $row = $statement->fetch();

    if ($row === false) {
        return null;
    }

    return (string) $row["member_id"];
}

/**
 * @return array<int, array<string, mixed>>
 */
function getMemberParts(string $memberId): array
{
    $db = getDb();
    $statement = $db->prepare(
        "SELECT
            mp.member_part_id,
            c.title AS piece_title,
            p.instrument_part AS part_label,
            p.file_name AS pdf_file_name,
            COALESCE(r.file_name, '') AS audio_file_name,
            r.recording_url AS youtube_url
         FROM member_parts mp
         INNER JOIN parts p ON p.part_id = mp.part_id
         INNER JOIN concerts c ON c.concert_id = mp.concert_id
         LEFT JOIN recordings r
            ON r.concert_id = mp.concert_id
            AND (r.part_id = mp.part_id OR r.part_id IS NULL)
         WHERE mp.member_id = :member_id
         ORDER BY c.concert_date ASC, c.title ASC, p.instrument_part ASC, mp.member_part_id ASC"
    );
    $statement->execute([":member_id" => $memberId]);

    return $statement->fetchAll();
}

function getMemberDisplayName(string $memberId): string
{
    $db = getDb();
    $statement = $db->prepare(
        "SELECT description FROM members WHERE member_id = :id LIMIT 1"
    );
    $statement->execute([":id" => $memberId]);
    $row = $statement->fetch();
    if ($row === false) {
        return "Member";
    }
    $description = trim((string) ($row["description"] ?? ""));
    if ($description !== "") {
        return $description;
    }

    return "Member";
}

function partDisplayLabel(array $row): string
{
    $title = trim((string) ($row["piece_title"] ?? ""));
    $label = trim((string) ($row["part_label"] ?? ""));

    return trim($title . " " . $label);
}

function partPdfUrl(?string $fileName): ?string
{
    $safe = basename(str_replace("\\", "/", trim($fileName ?? "")));
    if ($safe === "" || !is_file(PARTS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $safe)) {
        return null;
    }

    return PARTS_UPLOAD_URL . "/" . rawurlencode($safe);
}

/**
 * Safe external video URL (YouTube, Vimeo, etc.). DB column is still `youtube_url`.
 * Blocks localhost and non-http(s) schemes.
 */
function partExternalVideoUrl(?string $url): ?string
{
    $url = trim((string) ($url ?? ""));
    if ($url === "") {
        return null;
    }
    if (!preg_match('#^https?://#i', $url)) {
        if (preg_match('#youtube\.com|youtu\.be|vimeo\.com#i', $url)) {
            $url = "https://" . preg_replace('#^[/\s]+#', "", $url);
        } else {
            return null;
        }
    }
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return null;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if ($scheme !== "http" && $scheme !== "https") {
        return null;
    }
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === "" || $host === "localhost" || $host === "127.0.0.1" || $host === "::1") {
        return null;
    }

    return $url;
}

/**
 * Local video/audio file under assets/uploads/performances/ (basename only).
 */
function partPerformanceFileUrl(?string $fileName): ?string
{
    $safe = basename(str_replace("\\", "/", trim($fileName ?? "")));
    if ($safe === "" || !is_file(PERFORMANCES_UPLOAD_DIR . DIRECTORY_SEPARATOR . $safe)) {
        return null;
    }

    return PERFORMANCES_UPLOAD_URL . "/" . rawurlencode($safe);
}

/**
 * Reads youtube_url from a DB row (PDO may use different key casing).
 *
 * @param array<string, mixed> $row
 */
function partRowYoutubeRaw(array $row): ?string
{
    foreach ($row as $key => $value) {
        if (!is_string($key)) {
            continue;
        }
        if (strcasecmp($key, "youtube_url") !== 0) {
            continue;
        }
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    return null;
}

/**
 * Reads audio_file_name (performance file basename) from a DB row.
 *
 * @param array<string, mixed> $row
 */
function partRowAudioFileRaw(array $row): ?string
{
    foreach ($row as $key => $value) {
        if (!is_string($key)) {
            continue;
        }
        if (strcasecmp($key, "audio_file_name") !== 0) {
            continue;
        }
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    return null;
}

/**
 * Play: external link (youtube_url column) if set and valid, else file in performances/.
 *
 * @param array<string, mixed> $row
 */
function partPlayUrl(array $row): ?string
{
    $external = partExternalVideoUrl(partRowYoutubeRaw($row));
    if ($external !== null) {
        return $external;
    }

    return partPerformanceFileUrl(partRowAudioFileRaw($row));
}

function memberPhotoUrl(?string $fileName): ?string
{
    $safeFileName = basename(str_replace("\\", "/", trim($fileName ?? "")));

    if ($safeFileName === "" || !is_file(MEMBER_PHOTO_UPLOAD_DIR . DIRECTORY_SEPARATOR . $safeFileName)) {
        return null;
    }

    return MEMBER_PHOTO_UPLOAD_URL . "/" . rawurlencode($safeFileName);
}

function memberInitials(string $memberId): string
{
    $letters = preg_replace("/[^A-Za-z0-9]+/", "", $memberId) ?? "";
    $initials = strtoupper(substr($letters, 0, 2));

    return $initials !== "" ? $initials : "MC";
}

function appNavClass(string $page, string $activePage): string
{
    return $page === $activePage ? "nav-link active" : "nav-link";
}

function adminSlugId(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace("/[^a-z0-9]+/", "_", $slug) ?? "";
    $slug = trim($slug, "_");

    return $slug !== "" ? $slug : uniqid("item_", false);
}

/**
 * @return array<int, array<string, string>>
 */
function getAdminConcertOptions(): array
{
    $db = getDb();
    $statement = $db->query(
        "SELECT concert_id, title, concert_date
         FROM concerts
         ORDER BY concert_date DESC, title ASC"
    );

    return $statement->fetchAll();
}

/**
 * @return array<int, array<string, string>>
 */
function getAdminPartOptions(): array
{
    $db = getDb();
    $statement = $db->query(
        "SELECT p.part_id, p.instrument_part, c.title AS concert_title
         FROM parts p
         INNER JOIN concerts c ON c.concert_id = p.concert_id
         ORDER BY c.concert_date DESC, c.title ASC, p.instrument_part ASC"
    );

    return $statement->fetchAll();
}

/**
 * @return array<int, array<string, string>>
 */
function getAdminRecordingOptions(): array
{
    $db = getDb();
    $statement = $db->query(
        "SELECT recording_id, part_name
         FROM recordings
         ORDER BY created_at DESC, part_name ASC"
    );

    return $statement->fetchAll();
}

/**
 * @return array<int, array<string, string>>
 */
function getAdminMemberOptions(): array
{
    return getMembers();
}

/**
 * @param array<int, string> $allowedExtensions
 */
function saveUploadedFile(string $field, string $uploadDir, array $allowedExtensions): ?string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }

    $file = $_FILES[$field];
    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Upload failed.");
    }

    $extension = strtolower(pathinfo((string) ($file["name"] ?? ""), PATHINFO_EXTENSION));
    if ($extension === "" || !in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException("Unsupported file type.");
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        throw new RuntimeException("Upload folder could not be created.");
    }

    $fileName = uniqid("", true) . "." . $extension;
    $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file((string) $file["tmp_name"], $target)) {
        throw new RuntimeException("Uploaded file could not be saved.");
    }

    return $fileName;
}

function adminJsonResponse(bool $ok, string $message, int $status = 200): never
{
    http_response_code($status);
    header("Content-Type: application/json");
    echo json_encode(["ok" => $ok, "message" => $message]);
    exit;
}



function getConcertsByStatus($status)
{
    $db = getDb();

    $cmd = "SELECT * FROM concerts WHERE status = ? ORDER BY concert_date ASC";
    $stmt = $db->prepare($cmd);

    $stmt->execute([$status]);

    return $stmt;
}
