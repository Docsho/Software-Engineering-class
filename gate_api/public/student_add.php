<?php
// public/student_add.php
require_once "../config/database.php";
require_once "../core/Auth.php";
require_once "../core/Response.php";
require_once "../core/Audit.php";

try {
    // Authenticate user
    $user = Auth::verifyToken();

    // Only root_admin (1) or admin (2) can add students
    if (!in_array($user->role_id, [1, 2])) {
        Response::json(['error' => 'Forbidden: Only admin or root admin can add students'], 403);
    }

    // Get JSON request body
    $input = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    $required = ['student_id', 'first_name', 'last_name', 'program', 'level', 'email'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            Response::json(['error' => "$field is required"], 400);
        }
    }

    // Connect to DB
    $db = (new Database())->connect();

    // Check if student_id already exists
    $stmt = $db->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt->execute([$input['student_id']]);
    if ($stmt->fetch()) {
        Response::json(['error' => 'Student ID already exists'], 409);
    }

    // Insert student with manual email
    $sql = "INSERT INTO students 
        (student_id, first_name, last_name, program, level, email, created_by, updated_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        $input['student_id'],
        $input['first_name'],
        $input['last_name'],
        $input['program'],
        $input['level'],
        $input['email'],       // <-- YOU manually send this
        $user->id,             // created_by
        $user->id              // updated_by
    ]);

    $studentId = (int)$db->lastInsertId();

    // Audit logging
    Audit::log(
        $db,
        $user->id,
        "create_student",
        $studentId,
        null,
        json_encode($input, JSON_UNESCAPED_UNICODE)
    );

    // Success response
    Response::json([
        "success" => true,
        "id" => $studentId
    ], 201);

} catch (Exception $e) {
    Response::json(["error" => "Server error: " . $e->getMessage()], 500);
}
