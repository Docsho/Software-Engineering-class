<?php
// public/logout.php

require_once "../config/database.php";
require_once "../core/Auth.php";
require_once "../core/Response.php";
require_once "../core/Audit.php";

try {
    // Authenticate user via JWT
    $user = Auth::verifyToken(); // returns object {id, role_id, exp}

    // Correct database connection
    $database = new Database();
    $db = $database->connect();   // <-- FIXED

    // Log logout in audit logs
    Audit::log(
        $db,
        $user->id,
        "logout",
        $user->id,
        null,
        null
    );

    // Client must delete token on their side
    Response::json([
        "success" => true,
        "message" => "Logged out successfully. Token invalidated client-side."
    ]);
    
} catch (Exception $e) {
    Response::json([
        "error" => "Server error: " . $e->getMessage()
    ], 500);
}
