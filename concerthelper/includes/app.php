<?php
declare(strict_types=1);

require_once __DIR__ . "/connect.php";

const APP_NAME = "ConcertHelper";
const ROLE_ADMIN = "admin";
const ROLE_MEMBER = "member";
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

function appBasePath(): string
{
    $scriptName = str_replace("\\", "/", (string) ($_SERVER["SCRIPT_NAME"] ?? ""));
    $directory = str_replace("\\", "/", dirname($scriptName));

    if ($directory === "." || $directory === "/") {
        $directory = "";
    }

    if (str_ends_with($directory, "/actions")) {
        $directory = substr($directory, 0, -strlen("/actions"));
    }

    return rtrim($directory, "/");
}

function appUrl(string $path = ""): string
{
    $base = appBasePath();
    $normalizedPath = ltrim($path, "/");

    if ($normalizedPath === "") {
        return $base !== "" ? $base . "/" : "/";
    }

    return ($base !== "" ? $base : "") . "/" . $normalizedPath;
}

function redirectTo(string $location): never
{
    if (!preg_match('#^(?:[a-z][a-z0-9+.-]*:|/)#i', $location)) {
        $location = appUrl($location);
    }

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
    return appUrl($role === ROLE_ADMIN ? "admin-dashboard.php" : "member-dashboard.php");
}

function currentDashboardUrl(): string
{
    return dashboardUrlForRole(currentRole());
}

function requestWantsJson(): bool
{
    $accept = strtolower((string) ($_SERVER["HTTP_ACCEPT"] ?? ""));

    return str_contains($accept, "application/json");
}

/**
 * @param array<int, string> $allowedRoles
 */
function requireRole(array $allowedRoles): void
{
    $role = currentRole();

    if ($role === null) {
        if (requestWantsJson()) {
            adminJsonResponse(false, "Please sign in again.", 401);
        }
        redirectTo("login.php");
    }

    if (!in_array($role, $allowedRoles, true)) {
        if (requestWantsJson()) {
            adminJsonResponse(false, "You do not have access to this action.", 403);
        }
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
        "SELECT member_id, name, instrument, section, description, email
         FROM members
         WHERE is_active = 1
         ORDER BY name ASC, member_id ASC"
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
            COALESCE(
                (
                    SELECT r.file_name
                    FROM recordings r
                    WHERE r.concert_id = mp.concert_id
                      AND r.part_id = mp.part_id
                    ORDER BY r.created_at DESC
                    LIMIT 1
                ),
                (
                    SELECT r.file_name
                    FROM recordings r
                    WHERE r.concert_id = mp.concert_id
                      AND r.part_id IS NULL
                    ORDER BY r.created_at DESC
                    LIMIT 1
                ),
                ''
            ) AS audio_file_name,
            COALESCE(
                (
                    SELECT r.recording_url
                    FROM recordings r
                    WHERE r.concert_id = mp.concert_id
                      AND r.part_id = mp.part_id
                    ORDER BY r.created_at DESC
                    LIMIT 1
                ),
                (
                    SELECT r.recording_url
                    FROM recordings r
                    WHERE r.concert_id = mp.concert_id
                      AND r.part_id IS NULL
                    ORDER BY r.created_at DESC
                    LIMIT 1
                )
            ) AS youtube_url
         FROM member_parts mp
         INNER JOIN parts p ON p.part_id = mp.part_id
         INNER JOIN concerts c ON c.concert_id = mp.concert_id
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
        "SELECT name FROM members WHERE member_id = :id LIMIT 1"
    );
    $statement->execute([":id" => $memberId]);
    $row = $statement->fetch();
    if ($row === false) {
        return "Member";
    }
    $name = trim((string) ($row["name"] ?? ""));
    if ($name !== "") {
        return $name;
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

    return appUrl(PARTS_UPLOAD_URL . "/" . rawurlencode($safe));
}

function rowStringValue(array $row, string $column): ?string
{
    foreach ($row as $key => $value) {
        if (!is_string($key)) {
            continue;
        }
        if (strcasecmp($key, $column) !== 0) {
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
 * Safe external media URL (YouTube, Vimeo, Spotify, etc.).
 * Blocks localhost and non-http(s) schemes.
 */
function normalizeExternalMediaUrl(?string $url): ?string
{
    $url = trim((string) ($url ?? ""));
    if ($url === "") {
        return null;
    }
    if (!preg_match('#^https?://#i', $url)) {
        if (preg_match('#youtube\.com|youtu\.be|vimeo\.com|spotify\.com#i', $url)) {
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
 * Safe external video URL (YouTube, Vimeo, etc.). DB column is still `youtube_url`.
 */
function partExternalVideoUrl(?string $url): ?string
{
    return normalizeExternalMediaUrl($url);
}

function recordingTypeForUrl(?string $url): string
{
    $normalizedUrl = normalizeExternalMediaUrl($url);
    if ($normalizedUrl === null) {
        return "upload";
    }

    $host = strtolower((string) parse_url($normalizedUrl, PHP_URL_HOST));
    if (str_contains($host, "youtube.com") || str_contains($host, "youtu.be")) {
        return "youtube";
    }
    if (str_contains($host, "spotify.com")) {
        return "spotify";
    }

    return "other";
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

    return appUrl(PERFORMANCES_UPLOAD_URL . "/" . rawurlencode($safe));
}

/**
 * Play: external link (youtube_url column) if set and valid, else file in performances/.
 *
 * @param array<string, mixed> $row
 */
function partPlayUrl(array $row): ?string
{
    $external = partExternalVideoUrl(rowStringValue($row, "youtube_url"));
    if ($external !== null) {
        return $external;
    }

    return partPerformanceFileUrl(rowStringValue($row, "audio_file_name"));
}

/**
 * @param array<string, mixed> $row
 */
function concertPerformanceUrl(array $row): ?string
{
    $external = normalizeExternalMediaUrl(rowStringValue($row, "performance_url"));
    if ($external !== null) {
        return $external;
    }

    return partPerformanceFileUrl(rowStringValue($row, "performance_file_name"));
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
 * @return array<int, array<string, string>>
 */
function getAdminUserOptions(): array
{
    $db = getDb();
    $statement = $db->query(
        "SELECT
            u.user_id,
            u.email,
            u.role,
            u.member_id,
            COALESCE(NULLIF(m.name, ''), NULLIF(u.member_id, ''), u.user_id, u.email) AS display_name
         FROM users u
         LEFT JOIN members m ON m.member_id = u.member_id
         ORDER BY
            CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END,
            display_name ASC,
            u.email ASC"
    );

    return $statement->fetchAll();
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

    $uploadError = (int) ($file["error"] ?? UPLOAD_ERR_OK);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $message = match ($uploadError) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "The uploaded file is too large.",
            UPLOAD_ERR_PARTIAL => "The uploaded file did not finish uploading.",
            UPLOAD_ERR_NO_TMP_DIR => "The server is missing a temporary upload folder.",
            UPLOAD_ERR_CANT_WRITE => "The uploaded file could not be written to disk.",
            UPLOAD_ERR_EXTENSION => "The uploaded file was blocked by the server.",
            default => "Upload failed.",
        };
        throw new RuntimeException($message);
    }

    $extension = strtolower(pathinfo((string) ($file["name"] ?? ""), PATHINFO_EXTENSION));
    if ($extension === "" || !in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException("Unsupported file type.");
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        throw new RuntimeException("Upload folder could not be created.");
    }

    if (!is_writable($uploadDir)) {
        throw new RuntimeException("Upload folder is not writable.");
    }

    $tmpName = (string) ($file["tmp_name"] ?? "");
    if ($tmpName === "" || !is_uploaded_file($tmpName)) {
        throw new RuntimeException("Uploaded file is not available.");
    }

    $fileName = uniqid("", true) . "." . $extension;
    $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

    if (move_uploaded_file($tmpName, $target)) {
        return $fileName;
    }

    if (@rename($tmpName, $target)) {
        return $fileName;
    }

    if (@copy($tmpName, $target)) {
        @unlink($tmpName);
        return $fileName;
    }

    throw new RuntimeException("Uploaded file could not be saved.");
}

function adminJsonResponse(bool $ok, string $message, int $status = 200): never
{
    http_response_code($status);
    header("Content-Type: application/json");
    echo json_encode(["ok" => $ok, "message" => $message]);
    exit;
}

function isValidDateInput(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat("Y-m-d", $value);

    return $date !== false && $date->format("Y-m-d") === $value;
}

function isValidTimeInput(string $value): bool
{
    $time = DateTimeImmutable::createFromFormat("H:i", $value);
    if ($time !== false && $time->format("H:i") === $value) {
        return true;
    }

    $timeWithSeconds = DateTimeImmutable::createFromFormat("H:i:s", $value);

    return $timeWithSeconds !== false && $timeWithSeconds->format("H:i:s") === $value;
}

function validatePasswordInput(string $password): ?string
{
    if ($password === "") {
        return "Enter a new password.";
    }

    if (strlen($password) < 8) {
        return "Password must be at least 8 characters.";
    }

    if (strlen($password) > 72) {
        return "Password must be 72 characters or fewer.";
    }

    return null;
}

function getConcertsByStatus(string $status): PDOStatement
{
    $db = getDb();

    $cmd = "SELECT * FROM concerts WHERE status = ? ORDER BY concert_date ASC";
    $stmt = $db->prepare($cmd);

    $stmt->execute([$status]);

    return $stmt;
}
