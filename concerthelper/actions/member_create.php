<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/app.php";

requireRole([ROLE_ADMIN]);

/**
 * @return array<string, array<string, mixed>>
 */
function getMembersTableColumns(PDO $db): array
{
    $statement = $db->query("SHOW COLUMNS FROM members");
    $columns = [];

    foreach ($statement->fetchAll() as $column) {
        $name = strtolower(trim((string) ($column["Field"] ?? "")));
        if ($name === "") {
            continue;
        }
        $columns[$name] = $column;
    }

    return $columns;
}

function memberColumnMaxLength(array $column): ?int
{
    $type = strtolower(trim((string) ($column["Type"] ?? "")));
    if (!preg_match('/^(?:varchar|char)\((\d+)\)/', $type, $matches)) {
        return null;
    }

    return (int) $matches[1];
}

function memberLengthError(string $label, string $value, array $columns, string $column): ?string
{
    if (!isset($columns[$column])) {
        return null;
    }

    $maxLength = memberColumnMaxLength($columns[$column]);
    $valueLength = function_exists("mb_strlen") ? mb_strlen($value, "UTF-8") : strlen($value);
    if ($maxLength === null || $valueLength <= $maxLength) {
        return null;
    }

    return "{$label} must be {$maxLength} characters or fewer.";
}

/**
 * @return array{0: string, 1: array<int, string>}
 */
function buildMemberSaveQuery(array $columns): array
{
    $insertColumns = ["member_id"];
    $insertValues = [":member_id"];
    $updateAssignments = [];
    $usedBindings = [":member_id"];

    $columnBindings = [
        "name" => ":name",
        "instrument" => ":instrument",
        "section" => ":section",
        "email" => ":email",
        "file_name" => ":file_name",
        "description" => ":description",
    ];

    foreach ($columnBindings as $column => $binding) {
        if (!isset($columns[$column])) {
            continue;
        }

        $insertColumns[] = $column;
        $insertValues[] = $binding;
        $usedBindings[] = $binding;

        if ($column === "file_name") {
            $updateAssignments[] =
                "file_name = CASE
                    WHEN VALUES(file_name) <> '' THEN VALUES(file_name)
                    ELSE file_name
                END";
            continue;
        }

        $updateAssignments[] = "{$column} = VALUES({$column})";
    }

    if (isset($columns["is_active"])) {
        $insertColumns[] = "is_active";
        $insertValues[] = "1";
        $updateAssignments[] = "is_active = 1";
    }

    if ($updateAssignments === []) {
        $updateAssignments[] = "member_id = VALUES(member_id)";
    }

    $sql = sprintf(
        "INSERT INTO members (%s)
         VALUES (%s)
         ON DUPLICATE KEY UPDATE
            %s",
        implode(", ", $insertColumns),
        implode(", ", $insertValues),
        implode(",\n            ", $updateAssignments)
    );

    return [$sql, $usedBindings];
}

function deleteUploadedMemberPhoto(?string $photoFileName): void
{
    if ($photoFileName === null) {
        return;
    }

    $uploadedFile = MEMBER_PHOTO_UPLOAD_DIR . DIRECTORY_SEPARATOR . $photoFileName;
    if (is_file($uploadedFile)) {
        unlink($uploadedFile);
    }
}

function memberSaveErrorMessage(PDOException $exception): string
{
    $sqlState = strtoupper((string) $exception->getCode());
    $message = strtolower($exception->getMessage());

    if ($sqlState === "23000") {
        if (str_contains($message, "email")) {
            return "That email is already in use.";
        }
        if (str_contains($message, "member_id")) {
            return "That member ID is already in use.";
        }

        return "That member conflicts with an existing database record.";
    }

    if ($sqlState === "22001") {
        return "One of the member fields is too long for the database.";
    }

    if ($sqlState === "42S02" || $sqlState === "42S22") {
        return "The members table is out of date. Re-import database/concerthelper_schema.sql.";
    }

    if ($sqlState === "42000" || $sqlState === "28000") {
        return "The database user does not have permission to save members.";
    }

    return "Member could not be saved.";
}

function memberSaveErrorStatus(PDOException $exception): int
{
    return match (strtoupper((string) $exception->getCode())) {
        "22001", "23000" => 422,
        default => 500,
    };
}

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
    adminJsonResponse(false, "POST is required.", 405);
}

$memberName = trim((string) ($_POST["member_name"] ?? ""));
$memberId = trim((string) ($_POST["member_id"] ?? ""));
$email = trim((string) ($_POST["email"] ?? ""));
$instrument = trim((string) ($_POST["instrument"] ?? ""));
$section = trim((string) ($_POST["section"] ?? ""));

if ($memberName === "") {
    adminJsonResponse(false, "Enter a member name.", 422);
}

if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    adminJsonResponse(false, "Enter a valid email address.", 422);
}

if ($memberId === "") {
    $memberId = adminSlugId($memberName);
}

$photoFileName = null;

try {
    $db = getDb();
    $columns = getMembersTableColumns($db);

    $memberIdError = memberLengthError("Member ID", $memberId, $columns, "member_id");
    if ($memberIdError !== null) {
        adminJsonResponse(false, $memberIdError, 422);
    }

    $memberNameError = memberLengthError("Member name", $memberName, $columns, "name");
    if ($memberNameError !== null) {
        adminJsonResponse(false, $memberNameError, 422);
    }

    $emailError = memberLengthError("Email", $email, $columns, "email");
    if ($email !== "" && $emailError !== null) {
        adminJsonResponse(false, $emailError, 422);
    }

    $instrumentError = memberLengthError("Instrument", $instrument, $columns, "instrument");
    if ($instrument !== "" && $instrumentError !== null) {
        adminJsonResponse(false, $instrumentError, 422);
    }

    $sectionError = memberLengthError("Section", $section, $columns, "section");
    if ($section !== "" && $sectionError !== null) {
        adminJsonResponse(false, $sectionError, 422);
    }

    if ($email !== "" && isset($columns["email"])) {
        $emailStatement = $db->prepare(
            "SELECT member_id
             FROM members
             WHERE LOWER(TRIM(email)) = LOWER(TRIM(:email))
             LIMIT 1"
        );
        $emailStatement->execute([":email" => $email]);
        $existingMember = $emailStatement->fetch();

        if ($existingMember !== false && (string) ($existingMember["member_id"] ?? "") !== $memberId) {
            adminJsonResponse(false, "That email is already assigned to another member.", 422);
        }
    }

    $photoFileName = saveUploadedFile("member_photo", MEMBER_PHOTO_UPLOAD_DIR, ["jpg", "jpeg", "png", "gif", "webp"]);

    [$query, $usedBindings] = buildMemberSaveQuery($columns);
    $statement = $db->prepare($query);
    $allValues = [
        ":member_id" => $memberId,
        ":name" => $memberName,
        ":instrument" => $instrument !== "" ? $instrument : null,
        ":section" => $section !== "" ? $section : null,
        ":email" => $email !== "" ? $email : null,
        ":file_name" => $photoFileName ?? "",
        ":description" => "",
    ];
    $queryValues = [];
    foreach ($usedBindings as $binding) {
        $queryValues[$binding] = $allValues[$binding];
    }
    $statement->execute($queryValues);
} catch (RuntimeException $exception) {
    deleteUploadedMemberPhoto($photoFileName);
    $message = $exception->getMessage();
    if ($message === "Unsupported file type.") {
        $message = "Member photo must be a JPG, PNG, GIF, or WebP file.";
    }
    error_log("ConcertHelper member create upload: " . $exception->getMessage());
    adminJsonResponse(false, $message, 422);
} catch (PDOException $exception) {
    deleteUploadedMemberPhoto($photoFileName);
    error_log("ConcertHelper member create SQL [" . $exception->getCode() . "]: " . $exception->getMessage());
    adminJsonResponse(false, memberSaveErrorMessage($exception), memberSaveErrorStatus($exception));
} catch (Throwable $exception) {
    deleteUploadedMemberPhoto($photoFileName);
    error_log("ConcertHelper member create: " . $exception->getMessage());
    adminJsonResponse(false, "Member could not be saved.", 500);
}

adminJsonResponse(true, "Member saved.");
