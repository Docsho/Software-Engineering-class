<?php
// public/student_update.php
require_once "../config/database.php";
require_once "../core/Auth.php";
require_once "../core/Response.php";
require_once "../core/Audit.php";

try {
    // Authenticate user
    $user = Auth::verifyToken();

    // Only root_admin (1) or admin (2) can update students
    if (!in_array($user->role_id, [1, 2])) {
        Response::json(['error' => 'Forbidden: Only admin or root admin can update students'], 403);
    }

    // Get JSON request
    $input = json_decode(file_get_contents("php://input"), true);

    if (empty($input["student_id"])) {
        Response::json(["error" => "student_id is required"], 400);
    }

    $db = (new Database())->connect();

    // Fetch existing student
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$input["student_id"]]);
    $oldStudent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$oldStudent) {
        Response::json(["error" => "Student not found"], 404);
    }

    // Allowed fields to update
    $allowedFields = ["first_name", "last_name", "program", "level", "email"];

    $setParts = [];
    $values = [];

    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $setParts[] = "$field = ?";
            $values[] = $input[$field];
        }
    }

    if (count($setParts) === 0) {
        Response::json(["error" => "No fields to update"], 400);
    }

    // Add updated_by + student_id for WHERE clause
    $setParts[] = "updated_by = ?";
    $values[] = $user->id;

    $values[] = $input["student_id"]; // WHERE student_id = ?

    // Update student
    $sql = "UPDATE students SET " . implode(", ", $setParts) . " WHERE student_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    // Fetch updated student
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$input["student_id"]]);
    $newStudent = $stmt->fetch(PDO::FETCH_ASSOC);

    // Audit log
    Audit::log(
        $db,
        $user->id,
        "update_student",
        $oldStudent["id"],
        json_encode($oldStudent, JSON_UNESCAPED_UNICODE),
        json_encode($newStudent, JSON_UNESCAPED_UNICODE)
    );

    // Success response
    Response::json([
        "success" => true,
        "student" => $newStudent
    ]);

} catch (Exception $e) {
    Response::json(["error" => "Server error: " . $e->getMessage()], 500);
}
