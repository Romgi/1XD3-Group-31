<?php
/*
    Name(s): Jonathan, Marco, Charles, Hanzhi
    Date Created: April 2026
    File Description: Signs the current user out of the application and redirects back to the login page.
*/
declare(strict_types=1);

require_once __DIR__ . "/includes/app.php";

signOut();
redirectTo("login.php");
