<?php
// public/user_add.php
require_once "../config/database.php";
require_once "../core/Auth.php";
require_once "../core/Response.php";
require_once "../core/Audit.php";
require_once "../core/cors.php";

try {
    // Authenticate the user
    $user = Auth::verifyToken();

    // Only root admin (role_id = 1) can create users
    if ($user->role_id != 1) {
        Response::json(['error' => 'Forbidden: Only root admin can create users'], 403);
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required = ['username', 'password', 'full_name', 'email', 'role_id'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            Response::json(['error' => "$field is required"], 400);
        }
    }

    // Validate role
    if (!in_array((int)$input['role_id'], [1, 2, 3])) {
        Response::json(['error' => 'Invalid role_id. Must be 1, 2, or 3'], 400);
    }

    // Connect to database
    $db = (new Database())->connect();

    // Check for duplicate username
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$input['username']]);
    if ($stmt->fetch()) {
        Response::json(['error' => 'Username already exists'], 409);
    }

    // Hash password
    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);

    // Insert user
    $sql = "INSERT INTO users (username, password, full_name, email, role_id) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $input['username'],
        $hashedPassword,
        $input['full_name'],
        $input['email'],
        (int)$input['role_id']
    ]);

    $userId = (int)$db->lastInsertId();

    // Log audit
    Audit::log(
        $db,
        $user->id,
        'create_user',
        $userId,
        null,
        json_encode($input, JSON_UNESCAPED_UNICODE)
    );

    // Success response
    Response::json([
        'success' => true,
        'id' => $userId
    ], 201);

} catch (Exception $e) {
    Response::json(['error' => 'Server error: ' . $e->getMessage()], 500);
}
