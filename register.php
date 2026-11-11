<?php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}
require_once "config/db.php";
$error = "";
$success = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    if ($name === "" || $email === "" || $password === "")
        $error = "Please fill in all fields.";
    elseif (strlen($name) > 100)
        $error = "Please use a name under 100 characters.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $error = "Please enter a valid email address.";
    elseif (strlen($password) < 8)
        $error = "Password must be at least 8 characters.";
    else {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch())
            $error = "An account with this email already exists.";
        else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $success = "Your account is ready! You can log in now.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an account | PantryPal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600;700&family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="auth-page">
    <main class="auth-shell">
        <a class="auth-brand" href="register.php"><span
                class="auth-brand-mark">P</span><span>pantry<span>pal</span><small>your little pantry
                    journal</small></span></a>
        <div class="auth-card register-card">
            <div class="auth-art" role="img" aria-label="Illustrated pantry shelves filled with jars and ingredients">
                <span class="auth-sticker">A FRESH LITTLE START</span>
                <div class="auth-art-copy"><span>make room for</span><strong>good things.</strong>
                    <p>Keep your ingredients, plans and kitchen notes in one happy place.</p>
                </div>
            </div>
            <div class="auth-content"><span class="eyebrow">WELCOME TO YOUR KITCHEN</span>
                <h1>Let’s get <em>started.</em></h1>
                <p class="auth-subtitle">Create your free PantryPal account.</p>
                <?php if ($error): ?>
                    <div class="auth-alert" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="auth-success" role="status"><?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?> <a
                            href="login.php">Log in</a></div><?php endif; ?>
                <form method="POST" class="auth-form"><label>Your name<input type="text" name="name"
                            placeholder="What should we call you?" maxlength="100"
                            value="<?= htmlspecialchars($_POST["name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            autocomplete="name" required></label><label>Email address<input type="email" name="email"
                            placeholder="you@example.com"
                            value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            autocomplete="email" required></label><label>Password<input type="password" name="password"
                            placeholder="At least 8 characters" minlength="8" autocomplete="new-password"
                            required></label><button class="primary-btn auth-submit" type="submit">Create my account
                        <span>↗</span></button></form>
                <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
            </div>
        </div>
        <p class="auth-footer">MADE FOR A HAPPIER KITCHEN · PANTRYPAL</p>
    </main>
</body>

</html>