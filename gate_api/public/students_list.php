<?php
// public/students_list.php

require_once "../config/database.php";
require_once "../core/Auth.php";
require_once "../core/Response.php";

try {
    $user = Auth::verifyToken();

    if (!in_array((int)$user->role_id, [1, 2])) {
        Response::json(['error' => 'Forbidden: Only admin or root admin can view students'], 403);
    }

    $db = (new Database())->connect();

    $limit  = isset($_GET['limit'])  ? max(1, intval($_GET['limit']))  : 50;
    $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;

    $search = isset($_GET['search']) ? trim($_GET['search']) : "";

    if ($search !== "") {

        $searchLike = "%$search%";

        $stmt = $db->prepare("
            SELECT id, student_id, first_name, last_name, program, level, email
            FROM students
            WHERE student_id LIKE ? 
               OR first_name LIKE ?
               OR last_name LIKE ?
               OR email LIKE ?
            ORDER BY student_id ASC
            LIMIT :limit OFFSET :offset
        ");

        // Bind LIMIT and OFFSET as integers
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        // Execute search values
        $stmt->execute([$searchLike, $searchLike, $searchLike, $searchLike]);

        // Count total
        $countStmt = $db->prepare("
            SELECT COUNT(*) FROM students
            WHERE student_id LIKE ? 
               OR first_name LIKE ?
               OR last_name LIKE ?
               OR email LIKE ?
        ");
        $countStmt->execute([$searchLike, $searchLike, $searchLike, $searchLike]);
        $total = (int)$countStmt->fetchColumn();

    } else {

        $stmt = $db->prepare("
            SELECT id, student_id, first_name, last_name, program, level, email
            FROM students
            ORDER BY student_id ASC
            LIMIT :limit OFFSET :offset
        ");

        // Bind LIMIT and OFFSET as integers
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $total = (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn();
    }

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::json([
        "success" => true,
        "count" => count($students),
        "total" => $total,
        "limit" => $limit,
        "offset" => $offset,
        "students" => $students
    ]);

} catch (Exception $e) {
    Response::json(['error' => "Server error: " . $e->getMessage()], 500);
}
