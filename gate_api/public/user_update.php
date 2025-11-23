<?php
// public/user_update.php
require_once "../config/database.php";
require_once "../core/Auth.php";
require_once "../core/Response.php";
require_once "../core/Audit.php";

try {
    // Authenticate user
    $user = Auth::verifyToken();

    // Only root admin allowed
    if ($user->role_id != 1) {
        Response::json(['error' => 'Forbidden: Only root admin can update users'], 403);
    }

    // Read JSON body
    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);

    if (!$input) {
        Response::json(['error' => 'Invalid JSON body'], 400);
    }

    if (empty($input['user_id'])) {
        Response::json(['error' => 'user_id is required'], 400);
    }

    $user_id = (int)$input['user_id'];

    // Root admin cannot update himself through this endpoint
    if ($user_id === (int)$user->id) {
        Response::json(['error' => 'Root admin cannot update himself here'], 403);
    }

    $db = (new Database())->connect();

    // Fetch current user
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$old) {
        Response::json(['error' => 'User not found'], 404);
    }

    // Build dynamic update
    $updates = [];
    $values = [];

    $allowed = ['username', 'full_name', 'email', 'role_id', 'password'];

    foreach ($allowed as $field) {
        if (isset($input[$field])) {

            if ($field === "password") {
                $updates[] = "password = ?";
                $values[] = password_hash($input[$field], PASSWORD_DEFAULT);
            } else {
                $updates[] = "$field = ?";
                $values[] = $input[$field];
            }
        }
    }

    if (empty($updates)) {
        Response::json(['error' => 'No fields to update'], 400);
    }

    // Append ID for WHERE clause
    $values[] = $user_id;

    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    // Fetch updated user
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $new = $stmt->fetch(PDO::FETCH_ASSOC);

    // Audit log
    Audit::log(
        $db,
        $user->id,
        "update_user",
        $user_id,
        json_encode($old, JSON_UNESCAPED_UNICODE),
        json_encode($new, JSON_UNESCAPED_UNICODE)
    );

    Response::json([
        "success" => true,
        "user" => $new
    ]);

} catch (Exception $e) {
    Response::json(["error" => "Server error: " . $e->getMessage()], 500);
}
