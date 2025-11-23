<?php
// public/user_delete.php
require_once "../config/database.php";
require_once "../core/Auth.php";
require_once "../core/Response.php";
require_once "../core/Audit.php";
require_once "../core/cors.php";

try {
    // Authenticate user via JWT
    $user = Auth::verifyToken();

    // Only root admin can delete users
    if ($user->role_id != 1) {
        Response::json(['error' => 'Forbidden: Only root admin can delete users'], 403);
    }

    // Read JSON body
    $input = json_decode(file_get_contents("php://input"), true);
    $user_id = $input['user_id'] ?? null;

    if (empty($user_id)) {
        Response::json(['error' => 'user_id is required'], 400);
    }

    // Root admin cannot delete himself
    if ((int)$user_id === (int)$user->id) {
        Response::json(['error' => 'Root admin cannot delete himself'], 403);
    }

    $db = (new Database())->connect();

    // Fetch existing user to log old data
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$old) {
        Response::json(['error' => 'User not found'], 404);
    }

    // Delete user
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    // Audit log
    Audit::log(
        $db,
        $user->id,
        "delete_user",
        (int)$user_id,
        json_encode($old, JSON_UNESCAPED_UNICODE),
        null
    );

    Response::json(['success' => true]);

} catch (Exception $e) {
    Response::json(['error' => 'Server error: ' . $e->getMessage()], 500);
}
