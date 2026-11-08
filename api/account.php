<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../config/db.php";

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
if (!isset($_SESSION["user_id"])) respond(401, ["success" => false, "message" => "Please log in again."]);
if ($_SERVER["REQUEST_METHOD"] !== "POST") respond(405, ["success" => false, "message" => "Method not allowed."]);
$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) respond(400, ["success" => false, "message" => "Invalid request."]);
$userId = (int) $_SESSION["user_id"];
$action = $input["action"] ?? "";
try {
    if ($action === "profile") {
        $name = trim((string)($input["name"] ?? ""));
        $email = strtolower(trim((string)($input["email"] ?? "")));
        if ($name === "" || strlen($name) > 100) respond(422, ["success" => false, "message" => "Enter a name (up to 100 characters)."]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) respond(422, ["success" => false, "message" => "Enter a valid email address."]);
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id <> ?");
        $check->execute([$email, $userId]);
        if ($check->fetch()) respond(409, ["success" => false, "message" => "That email is already linked to another account."]);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
        $stmt->execute([$name, $email, $userId]);
        $_SESSION["user_name"] = $name;
        $_SESSION["user_email"] = $email;
        respond(200, ["success" => true, "message" => "Your profile has been updated.", "name" => $name, "email" => $email]);
    }
    if ($action === "password") {
        $current = (string)($input["current_password"] ?? "");
        $new = (string)($input["new_password"] ?? "");
        if (strlen($new) < 8) respond(422, ["success" => false, "message" => "Your new password must contain at least 8 characters."]);
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($current, $user["password_hash"])) respond(422, ["success" => false, "message" => "Your current password is incorrect."]);
        $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $update->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
        respond(200, ["success" => true, "message" => "Password changed successfully."]);
    }
    respond(400, ["success" => false, "message" => "Unknown account action."]);
} catch (PDOException $e) {
    respond(500, ["success" => false, "message" => "Could not update your account right now."]);
}
