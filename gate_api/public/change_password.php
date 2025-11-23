<?php
// public/change_password.php
require_once "../config/database.php";
require_once "../core/Auth.php";
require_once "../core/Response.php";
require_once "../core/Audit.php";
require_once "../core/cors.php";


try {
    $user = Auth::verifyToken();
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['current_password']) || empty($input['new_password'])) {
        Response::json(['error' => 'current_password and new_password required'], 400);
    }
    if (strlen($input['new_password']) < 6) {
        Response::json(['error' => 'new_password must be at least 6 characters'], 400);
    }

    $db = (new Database())->connect();

    // fetch current user record
    $stmt = $db->prepare("SELECT id, password FROM users WHERE id = ?");
    $stmt->execute([$user->id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) Response::json(['error' => 'User not found'], 404);

    if (!password_verify($input['current_password'], $row['password'])) {
        Response::json(['error' => 'Current password does not match'], 403);
    }

    $newHash = password_hash($input['new_password'], PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$newHash, $user->id]);

    Audit::log($db, $user->id, 'change_password', $user->id, null, null);

    Response::json(['success' => true]);
} catch (Exception $e) {
    Response::json(['error' => 'Server error: ' . $e->getMessage()], 500);
}
