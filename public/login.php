<?php
require_once "../config/database.php";
require_once "../core/Auth.php";
require_once "../core/Response.php";
require_once "../core/cors.php";


$data = json_decode(file_get_contents("php://input"), true);

$db = (new Database())->connect();

$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$data["username"]]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($data["password"], $user["password"])) {
    Response::json(["error" => "Invalid credentials"], 401);
}

$token = Auth::createToken($user);

Response::json([
    "token" => $token,
    "role" => $user["role_id"],
    "full_name" => $user["full_name"]
]);
