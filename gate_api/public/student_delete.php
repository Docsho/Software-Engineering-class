<?php
// public/student_delete.php
require_once "../config/database.php";
require_once "../core/Auth.php";
require_once "../core/Response.php";
require_once "../core/Audit.php";

try {
    // Authenticate user (expects Authorization: Bearer <token>)
    $user = Auth::verifyToken();

    // Only root_admin (1) or admin (2) allowed to delete students
    if (!in_array($user->role_id, [1, 2])) {
        Response::json(['error' => 'Forbidden: Only admin or root admin can delete students'], 403);
    }

    // Read JSON body (works for DELETE, POST, PUT)
    $input = json_decode(file_get_contents("php://input"), true);
    $student_id = $input['student_id'] ?? null;
    if (empty($student_id)) {
        Response::json(['error' => 'student_id is required'], 400);
    }

    $db = (new Database())->connect();

    // Fetch existing student to log old values
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$old) {
        Response::json(['error' => 'Student not found'], 404);
    }

    // Delete student
    $stmt = $db->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);

    // Audit log: record who deleted and the old data
    Audit::log(
        $db,
        $user->id,
        "delete_student",
        (int)$old['id'],
        json_encode($old, JSON_UNESCAPED_UNICODE),
        null
    );

    Response::json(['success' => true]);

} catch (Exception $e) {
    Response::json(['error' => 'Server error: ' . $e->getMessage()], 500);
}
