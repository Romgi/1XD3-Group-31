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
        "SELECT member_id, file_name, description, email
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
        "SELECT member_id FROM members WHERE LOWER(TRIM(email)) = :email LIMIT 1"
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
    // SELECT * so older DBs without youtube_url still load; partPlayUrl() uses the column when present.
    $statement = $db->prepare(
        "SELECT *
         FROM member_parts
         WHERE member_id = :member_id
         ORDER BY sort_order ASC, member_part_id ASC"
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
