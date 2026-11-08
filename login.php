<?php
session_start();
if (isset($_SESSION["user_id"])) { header("Location: index.php"); exit(); }
require_once "config/db.php";
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user["password_hash"])) {
        session_regenerate_id(true);
        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_email"] = $user["email"];
        header("Location: index.php"); exit();
    }
    $error = "That email and password combination doesn't look right. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome back | PantryPal</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600;700&family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css"></head>
<body class="auth-page"><main class="auth-shell">
 <a class="auth-brand" href="login.php"><span class="auth-brand-mark">P</span><span>pantry<span>pal</span><small>your little pantry journal</small></span></a>
 <div class="auth-card"><div class="auth-art" role="img" aria-label="Illustrated pantry shelves filled with jars and ingredients">
   <span class="auth-sticker">YOUR KITCHEN, IN ONE PLACE</span>
   <div class="auth-art-copy"><span>good food,</span><strong>good days.</strong><p>A calmer little corner for everything in your kitchen.</p></div>
  </div>
 <div class="auth-content"><span class="eyebrow">YOUR KITCHEN IS WAITING</span><h1>Welcome <em>back.</em></h1><p class="auth-subtitle">Sign in to pick up where you left off.</p>
 <?php if ($error): ?><div class="auth-alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
 <form method="POST" class="auth-form"><label>Email address<input type="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>" autocomplete="email" required></label><label>Password<input type="password" name="password" placeholder="Your password" autocomplete="current-password" required></label><button class="primary-btn auth-submit" type="submit">Log in to PantryPal <span>↗</span></button></form>
 <p class="auth-switch">New to PantryPal? <a href="register.php">Create an account</a></p></div></div>
 <p class="auth-footer">MADE FOR A HAPPIER KITCHEN · PANTRYPAL</p></main></body></html>
