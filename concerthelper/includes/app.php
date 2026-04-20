<?php
/*
    Name(s): Jonathan, Marco, Charles, Hanzhi
    Date Created: April 2026
    File Description: Stores shared constants and helper functions used by the ConcertHelper PHP pages and admin actions.
*/
declare(strict_types=1);

require_once __DIR__ . "/connect.php";

const APP_NAME = "ConcertHelper";
const ROLE_ADMIN = "admin";
const ROLE_MEMBER = "member";
const PARTS_UPLOAD_DIR = __DIR__ . "/../assets/uploads/parts";
const PARTS_UPLOAD_URL = "assets/uploads/parts";
const PERFORMANCES_UPLOAD_DIR = __DIR__ . "/../assets/uploads/performances";
const PERFORMANCES_UPLOAD_URL = "assets/uploads/performances";

/**
 * Starts the PHP session if one is not already active.
 *
 * @return void This function does not return a value.
 */
function startAppSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Escapes text for safe HTML output.
 *
 * @param ?string $value The value to escape before printing into HTML.
 * @return string The escaped string value.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? "", ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

/**
 * Determines the base URL path for the current app install.
 *
 * @return string The normalized base path for generated application URLs.
 */
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

/**
 * Builds an application-relative URL.
 *
 * @param string $path The path to append to the application base URL.
 * @return string The completed URL for the requested app resource.
 */
function appUrl(string $path = ""): string
{
    $base = appBasePath();
    $normalizedPath = ltrim($path, "/");

    if ($normalizedPath === "") {
        return $base !== "" ? $base . "/" : "/";
    }

    return ($base !== "" ? $base : "") . "/" . $normalizedPath;
}

/**
 * Redirects the browser to another application URL and stops execution.
 *
 * @param string $location The target location or app-relative path to redirect to.
 * @return never This function ends the request and does not return.
 */
function redirectTo(string $location): never
{
    if (!preg_match('#^(?:[a-z][a-z0-9+.-]*:|/)#i', $location)) {
        $location = appUrl($location);
    }

    header("Location: {$location}");
    exit;
}

/**
 * Checks a submitted email and password against the users table.
 *
 * @param string $email The email address entered by the user.
 * @param string $password The plain-text password entered by the user.
 * @return ?string The matched role when authentication succeeds, or null when it fails.
 */
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

/**
 * Stores the signed-in user's role and optional member ID in the session.
 *
 * @param string $role The authenticated role to store in the session.
 * @param ?string $memberId The linked member ID for member accounts, or null for admins.
 * @return void This function does not return a value.
 */
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

/**
 * Reads the current member ID from the session.
 *
 * @return ?string The stored member ID, or null when none is available.
 */
function currentMemberId(): ?string
{
    startAppSession();
    $id = $_SESSION["member_id"] ?? null;

    return is_string($id) && $id !== "" ? $id : null;
}

/**
 * Reads the current signed-in role from the session.
 *
 * @return ?string The current user role, or null when nobody is signed in.
 */
function currentRole(): ?string
{
    startAppSession();
    $role = $_SESSION["role"] ?? null;

    return is_string($role) ? $role : null;
}

/**
 * Clears the current session and signs the user out.
 *
 * @return void This function does not return a value.
 */
function signOut(): void
{
    startAppSession();
    $_SESSION = [];
    session_destroy();
}

/**
 * Chooses the correct dashboard URL for a role.
 *
 * @param ?string $role The role to map to a dashboard URL.
 * @return string The dashboard URL for the supplied role.
 */
function dashboardUrlForRole(?string $role): string
{
    return appUrl($role === ROLE_ADMIN ? "admin-dashboard.php" : "member-dashboard.php");
}

/**
 * Returns the dashboard URL for the currently signed-in user.
 *
 * @return string The current user's dashboard URL.
 */
function currentDashboardUrl(): string
{
    return dashboardUrlForRole(currentRole());
}

/**
 * Checks whether the request expects a JSON response.
 *
 * @return bool True when the request accepts JSON, otherwise false.
 */
function requestWantsJson(): bool
{
    $accept = strtolower((string) ($_SERVER["HTTP_ACCEPT"] ?? ""));

    return str_contains($accept, "application/json");
}

/**
 * Confirms that the current user has one of the allowed roles.
 *
 * @param array<int, string> $allowedRoles
 *     The list of roles that are allowed to access the current page or action.
 * @return void This function redirects or exits when access is not allowed.
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
 * Loads the active member roster from the database.
 *
 * @return array<int, array<string, string>>
 *     The active member records sorted by section, instrument, and name.
 */
function getMembers(): array
{
    $db = getDb();
    $statement = $db->query(
        "SELECT member_id, name, instrument, section, description, email
         FROM members
         WHERE is_active = 1
         ORDER BY
            COALESCE(NULLIF(section, ''), 'ZZZ') ASC,
            COALESCE(NULLIF(instrument, ''), 'ZZZ') ASC,
            name ASC,
            member_id ASC"
    );

    return $statement->fetchAll();
}

/**
 * Finds the member ID associated with a login email.
 *
 * @param string $email The user email address to look up.
 * @return ?string The linked member ID, or null when no match exists.
 */
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
 * Loads the parts and recordings assigned to a specific member.
 *
 * @param string $memberId The member ID whose assignments should be loaded.
 * @return array<int, array<string, mixed>>
 *     The assigned part rows, including related concert and recording data.
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

/**
 * Gets the display name for a member account.
 *
 * @param string $memberId The member ID to look up.
 * @return string The member's name, or "Member" when no name is available.
 */
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

/**
 * Combines a concert title and part name into one display label.
 *
 * @param array<string, mixed> $row The database row containing piece and part labels.
 * @return string The combined display label for the assigned part.
 */
function partDisplayLabel(array $row): string
{
    $title = trim((string) ($row["piece_title"] ?? ""));
    $label = trim((string) ($row["part_label"] ?? ""));

    return trim($title . " " . $label);
}

/**
 * Builds the public URL for a stored PDF part file.
 *
 * @param ?string $fileName The saved part PDF filename.
 * @return ?string The public URL for the PDF, or null when the file is missing.
 */
function partPdfUrl(?string $fileName): ?string
{
    $safe = basename(str_replace("\\", "/", trim($fileName ?? "")));
    if ($safe === "" || !is_file(PARTS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $safe)) {
        return null;
    }

    return appUrl(PARTS_UPLOAD_URL . "/" . rawurlencode($safe));
}

/**
 * Reads a string value from an associative database row without case sensitivity.
 *
 * @param array<string, mixed> $row The database row to inspect.
 * @param string $column The column name to retrieve.
 * @return ?string The string value for the requested column, or null when absent.
 */
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
 * Normalizes and validates an external media URL.
 *
 * @param ?string $url The URL entered by the user.
 * @return ?string A safe external URL, or null when the value is invalid.
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
 * Normalizes the external recording URL stored for a part assignment.
 *
 * @param ?string $url The recording URL from the database row.
 * @return ?string The safe external URL, or null when it is invalid.
 */
function partExternalVideoUrl(?string $url): ?string
{
    return normalizeExternalMediaUrl($url);
}

/**
 * Determines the recording type label for a submitted recording URL.
 *
 * @param ?string $url The external recording URL provided by the admin.
 * @return string The recording type string stored in the database.
 */
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
 * Builds the public URL for an uploaded performance media file.
 *
 * @param ?string $fileName The saved recording or performance filename.
 * @return ?string The public media URL, or null when the file is missing.
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
 * Chooses the best playback URL for a member part.
 *
 * @param array<string, mixed> $row
 *     The assignment row containing external and uploaded recording fields.
 * @return ?string The preferred playback URL, or null when no recording exists.
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
 * Chooses the best performance URL for a concert card.
 *
 * @param array<string, mixed> $row
 *     The concert row containing external and uploaded performance fields.
 * @return ?string The performance URL, or null when none is available.
 */
function concertPerformanceUrl(array $row): ?string
{
    $external = normalizeExternalMediaUrl(rowStringValue($row, "performance_url"));
    if ($external !== null) {
        return $external;
    }

    return partPerformanceFileUrl(rowStringValue($row, "performance_file_name"));
}

/**
 * Returns the CSS class for a navigation link.
 *
 * @param string $page The page identifier for the link being rendered.
 * @param string $activePage The current active page identifier.
 * @return string The CSS class string for the navigation link.
 */
function appNavClass(string $page, string $activePage): string
{
    return $page === $activePage ? "nav-link active" : "nav-link";
}

/**
 * Converts a label into a slug suitable for IDs and keys.
 *
 * @param string $value The source value to convert into a slug.
 * @return string The generated slug.
 */
function adminSlugId(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace("/[^a-z0-9]+/", "_", $slug) ?? "";
    $slug = trim($slug, "_");

    return $slug !== "" ? $slug : uniqid("item_", false);
}

/**
 * Loads the concerts used in admin dropdown menus.
 *
 * @return array<int, array<string, string>>
 *     The concert option rows for the admin dashboard.
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
 * Loads the parts used in admin dropdown menus.
 *
 * @return array<int, array<string, string>>
 *     The part option rows for the admin dashboard.
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
 * Loads the recordings used in admin dropdown menus.
 *
 * @return array<int, array<string, string>>
 *     The recording option rows for the admin dashboard.
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
 * Loads the members used in admin dropdown menus.
 *
 * @return array<int, array<string, string>>
 *     The member option rows for the admin dashboard.
 */
function getAdminMemberOptions(): array
{
    return getMembers();
}

/**
 * Loads the user accounts used in the admin password manager.
 *
 * @return array<int, array<string, string>>
 *     The user option rows for the admin dashboard.
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
 * Saves an uploaded file into the requested upload directory.
 *
 * @param string $field The name of the uploaded file field in the request.
 * @param string $uploadDir The server directory where the file should be saved.
 * @param array<int, string> $allowedExtensions
 *     The lowercase list of allowed file extensions.
 * @return ?string The generated saved filename, or null when no file was uploaded.
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

/**
 * Sends a JSON response for admin form submissions and ends execution.
 *
 * @param bool $ok True when the request succeeded, otherwise false.
 * @param string $message The response message to send back to the browser.
 * @param int $status The HTTP status code for the response.
 * @return never This function outputs JSON and terminates the request.
 */
function adminJsonResponse(bool $ok, string $message, int $status = 200): never
{
    http_response_code($status);
    header("Content-Type: application/json");
    echo json_encode(["ok" => $ok, "message" => $message]);
    exit;
}

/**
 * Validates a date string in YYYY-MM-DD format.
 *
 * @param string $value The date string to validate.
 * @return bool True when the date is valid, otherwise false.
 */
function isValidDateInput(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat("Y-m-d", $value);

    return $date !== false && $date->format("Y-m-d") === $value;
}

/**
 * Validates a time string in HH:MM or HH:MM:SS format.
 *
 * @param string $value The time string to validate.
 * @return bool True when the time is valid, otherwise false.
 */
function isValidTimeInput(string $value): bool
{
    $time = DateTimeImmutable::createFromFormat("H:i", $value);
    if ($time !== false && $time->format("H:i") === $value) {
        return true;
    }

    $timeWithSeconds = DateTimeImmutable::createFromFormat("H:i:s", $value);

    return $timeWithSeconds !== false && $timeWithSeconds->format("H:i:s") === $value;
}

/**
 * Validates a submitted password against the app password rules.
 *
 * @param string $password The plain-text password to validate.
 * @return ?string An error message when validation fails, or null when valid.
 */
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

/**
 * Loads concerts filtered by status for the public concerts page.
 *
 * @param string $status The concert status to load, such as upcoming or past.
 * @return PDOStatement The prepared statement containing the matching concert rows.
 */
function getConcertsByStatus(string $status): PDOStatement
{
    $db = getDb();

    $cmd = "SELECT * FROM concerts WHERE status = ? ORDER BY concert_date ASC";
    $stmt = $db->prepare($cmd);

    $stmt->execute([$status]);

    return $stmt;
}
