<?php
declare(strict_types=1);

require_once __DIR__ . "/connect.php";

const APP_NAME = "ConcertHelper";
const ROLE_ADMIN = "admin";
const ROLE_MEMBER = "member";
const MEMBER_PHOTO_UPLOAD_DIR = __DIR__ . "/../assets/uploads/members";
const MEMBER_PHOTO_UPLOAD_URL = "assets/uploads/members";

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

    if ($password !== "concerthelper") {
        return null;
    }

    if ($email === "admin@mcmaster.ca") {
        return ROLE_ADMIN;
    }

    if ($email === "macid1@mcmaster.ca") {
        return ROLE_MEMBER;
    }

    return null;
}

function signIn(string $role): void
{
    startAppSession();
    $_SESSION["role"] = $role;
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
        "SELECT member_id, file_name, description
         FROM members
         ORDER BY member_id ASC"
    );

    return $statement->fetchAll();
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
