<?php
require_once __DIR__."/../config/jwt.php";
require_once __DIR__."/../vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth {
    public static function createToken($user) {
        global $JWT_SECRET, $JWT_EXPIRE;

        $payload = [
            "id" => $user["id"],
            "role_id" => $user["role_id"],
            "exp" => time() + $JWT_EXPIRE
        ];

        return JWT::encode($payload, $JWT_SECRET, 'HS256');
    }

    public static function verifyToken() {
    global $JWT_SECRET;

    $headers = getallheaders();

    // Accept both "Authorization" and "authorization"
    $authHeader = $headers["Authorization"] 
        ?? $headers["authorization"] 
        ?? null;

    if (!$authHeader) {
        Response::json(["error" => "No token provided"], 401);
    }

    // Must start with "Bearer "
    if (strpos($authHeader, "Bearer ") !== 0) {
        Response::json(["error" => "Invalid authorization format"], 401);
    }

    // Remove "Bearer " prefix
    $token = trim(str_replace("Bearer ", "", $authHeader));

    try {
        $decoded = JWT::decode($token, new Key($JWT_SECRET, 'HS256'));
        return $decoded;  // contains ->id and ->role_id

    } catch (Exception $e) {
        Response::json(["error" => "Invalid token"], 401);
    }
}
}