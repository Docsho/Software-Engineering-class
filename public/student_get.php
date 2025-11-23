<?php
// public/student_get.php

require_once "../config/database.php";
require_once "../core/Auth.php";
require_once "../core/Response.php";
require_once "../core/cors.php";

try {
    // Authenticate any logged-in user (root, admin, gatekeeper)
    $user = Auth::verifyToken();

    // student_id required in query
    if (!isset($_GET["student_id"]) || empty($_GET["student_id"])) {
        Response::json(["error" => "student_id is required"], 400);
    }

    $student_id = $_GET["student_id"];

    // Connect to DB
    $db = (new Database())->connect();

    // Fetch student
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        Response::json(["found" => false]);
    }

    // Success
    Response::json([
        "found" => true,
        "student" => $student
    ]);

} catch (Exception $e) {
    Response::json(["error" => "Server error: " . $e->getMessage()], 500);
}
