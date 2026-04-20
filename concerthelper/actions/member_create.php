<?php
/*
    Name(s): Jonathan, Marco, Charles, Hanzhi
    Date Created: April 2026
    File Description: Creates or updates member records and ensures linked login accounts exist when an email is provided.
*/
declare(strict_types=1);

require_once __DIR__ . "/../includes/app.php";

requireRole([ROLE_ADMIN]);

const NEW_MEMBER_TEMP_PASSWORD = "temporarypassword";

/**
 * Reads the column definitions for the members table.
 *
 * @param PDO $db The database connection used to inspect the table structure.
 * @return array<string, array<string, mixed>>
 *     The member table column metadata keyed by lowercase column name.
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

/**
 * Determines the maximum character length for a column definition.
 *
 * @param array<string, mixed> $column The column metadata returned by SHOW COLUMNS.
 * @return ?int The maximum length for varchar or char columns, or null otherwise.
 */
function memberColumnMaxLength(array $column): ?int
{
    $type = strtolower(trim((string) ($column["Type"] ?? "")));
    if (!preg_match('/^(?:varchar|char)\((\d+)\)/', $type, $matches)) {
        return null;
    }

    return (int) $matches[1];
}

/**
 * Builds a validation error message when a member field exceeds its column length.
 *
 * @param string $label The human-readable label for the field.
 * @param string $value The submitted value to validate.
 * @param array<string, array<string, mixed>> $columns The members table column metadata.
 * @param string $column The database column name to validate against.
 * @return ?string An error message when the field is too long, or null when valid.
 */
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
 * Builds the SQL statement and bindings needed to save a member row.
 *
 * @param array<string, array<string, mixed>> $columns The members table column metadata.
 * @return array{0: string, 1: array<int, string>}
 *     The SQL string and the list of parameter bindings used in that query.
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
        "description" => ":description",
    ];

    foreach ($columnBindings as $column => $binding) {
        if (!isset($columns[$column])) {
            continue;
        }

        $insertColumns[] = $column;
        $insertValues[] = $binding;
        $usedBindings[] = $binding;

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

/**
 * Converts a PDO exception into a user-facing member save error message.
 *
 * @param PDOException $exception The database exception raised during member save.
 * @return string The user-facing error message.
 */
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

/**
 * Converts a PDO exception into an HTTP status code for member save failures.
 *
 * @param PDOException $exception The database exception raised during member save.
 * @return int The HTTP status code that best matches the error.
 */
function memberSaveErrorStatus(PDOException $exception): int
{
    return match (strtoupper((string) $exception->getCode())) {
        "22001", "23000" => 422,
        default => 500,
    };
}

/**
 * Creates or updates the linked user account for a member with an email address.
 *
 * @param PDO $db The database connection used to manage the user account.
 * @param string $memberId The member ID that should own the login account.
 * @param string $email The email address to store for the user account.
 * @return void This function does not return a value.
 */
function ensureMemberUserAccount(PDO $db, string $memberId, string $email): void
{
    $existingEmailStatement = $db->prepare(
        "SELECT user_id, member_id
         FROM users
         WHERE LOWER(TRIM(email)) = LOWER(TRIM(:email))
         LIMIT 1"
    );
    $existingEmailStatement->execute([":email" => $email]);
    $emailOwner = $existingEmailStatement->fetch();

    if ($emailOwner !== false) {
        $emailOwnerUserId = (string) ($emailOwner["user_id"] ?? "");
        $emailOwnerMemberId = $emailOwner["member_id"] !== null ? (string) $emailOwner["member_id"] : "";
        if ($emailOwnerUserId !== $memberId && $emailOwnerMemberId !== $memberId) {
            adminJsonResponse(false, "That email is already used by another user account.", 422);
        }
    }

    $userStatement = $db->prepare(
        "SELECT user_id
         FROM users
         WHERE user_id = :user_id OR member_id = :member_id
         LIMIT 1"
    );
    $userStatement->execute([
        ":user_id" => $memberId,
        ":member_id" => $memberId,
    ]);
    $existingUser = $userStatement->fetch();

    if ($existingUser === false) {
        $hash = password_hash(NEW_MEMBER_TEMP_PASSWORD, PASSWORD_DEFAULT);
        if (!is_string($hash) || $hash === "") {
            throw new RuntimeException("Password hashing failed.");
        }

        $insertUser = $db->prepare(
            "INSERT INTO users (user_id, member_id, email, password_hash, role)
             VALUES (:user_id, :member_id, :email, :password_hash, :role)"
        );
        $insertUser->execute([
            ":user_id" => $memberId,
            ":member_id" => $memberId,
            ":email" => $email,
            ":password_hash" => $hash,
            ":role" => ROLE_MEMBER,
        ]);

        return;
    }

    $updateUser = $db->prepare(
        "UPDATE users
         SET member_id = :member_id,
             email = :email,
             role = :role
         WHERE user_id = :user_id OR member_id = :member_id"
    );
    $updateUser->execute([
        ":member_id" => $memberId,
        ":email" => $email,
        ":role" => ROLE_MEMBER,
        ":user_id" => $memberId,
    ]);
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

    $db->beginTransaction();

    [$query, $usedBindings] = buildMemberSaveQuery($columns);
    $statement = $db->prepare($query);
    $allValues = [
        ":member_id" => $memberId,
        ":name" => $memberName,
        ":instrument" => $instrument !== "" ? $instrument : null,
        ":section" => $section !== "" ? $section : null,
        ":email" => $email !== "" ? $email : null,
        ":description" => "",
    ];
    $queryValues = [];
    foreach ($usedBindings as $binding) {
        $queryValues[$binding] = $allValues[$binding];
    }
    $statement->execute($queryValues);

    if ($email !== "") {
        ensureMemberUserAccount($db, $memberId, $email);
    }

    $db->commit();
} catch (PDOException $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("ConcertHelper member create SQL [" . $exception->getCode() . "]: " . $exception->getMessage());
    adminJsonResponse(false, memberSaveErrorMessage($exception), memberSaveErrorStatus($exception));
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("ConcertHelper member create: " . $exception->getMessage());
    adminJsonResponse(false, "Member could not be saved.", 500);
}

$message = "Member saved.";
if ($email !== "") {
    $message = "Member saved. New login password: " . NEW_MEMBER_TEMP_PASSWORD;
}

adminJsonResponse(true, $message);
