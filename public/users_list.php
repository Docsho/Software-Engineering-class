<?php
// public/users_list.php

require_once "../config/database.php";
require_once "../core/Auth.php";
require_once "../core/Response.php";

try {
    // Authenticate via JWT
    $user = Auth::verifyToken();

    // Only root admin can view users
    if ((int)$user->role_id !== 1) {
        Response::json(['error' => 'Forbidden: Only root admin can view users'], 403);
    }

    // Connect to database
    $db = (new Database())->connect();

    // Fetch all users
    $stmt = $db->query("SELECT id, username, full_name, email, role_id FROM users ORDER BY id ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::json([
        "success" => true,
        "count" => count($users),
        "users" => $users
    ]);

} catch (Exception $e) {
    Response::json(['error' => "Server error: " . $e->getMessage()], 500);
}
