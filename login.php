
<?php
session_start();
require_once "db.php";

$message = "";

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
        $message = "Please enter a valid email and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role
             FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_role"] = $user["role"];

                header("Location: index.php");
                exit;
            }
        }

        $message = "Incorrect email or password.";
        $stmt->close();
    }
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | StudyShare</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    font-family: Arial, sans-serif;
    color: #24344d;
    background: #f1f5f9;
}
.login-box {
    width: 100%;
    max-width: 410px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 32px;
}
.logo {
    text-align: center;
    color: #19365c;
    font-size: 26px;
    font-weight: bold;
}
.logo span { color: #5b83b4; }
h1 { font-size: 25px; margin: 26px 0 8px; }
.subtitle { color: #64748b; font-size: 14px; margin-bottom: 25px; }
label { display: block; font-size: 14px; font-weight: bold; margin: 16px 0 7px; }
input {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 5px;
    font-size: 14px;
}
input:focus { outline: 2px solid #dceaf7; border-color: #5b83b4; }
button {
    width: 100%;
    margin-top: 23px;
    padding: 12px;
    border: 0;
    border-radius: 5px;
    background: #19365c;
    color: white;
    font-size: 15px;
    cursor: pointer;
}
button:hover { background: #284c78; }
.message {
    background: #fef2f2;
    color: #991b1b;
    padding: 11px;
    border-radius: 5px;
    font-size: 14px;
}
.links { text-align: center; margin-top: 20px; font-size: 14px; }
a { color: #315c8a; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="login-box">
    <div class="logo">Study<span>Share</span></div>
    <h1>Welcome back</h1>
    <p class="subtitle">Log in to access your study resources.</p>

    <?php if ($message !== ""): ?>
        <p class="message"><?php echo e($message); ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required
               autocomplete="email">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required
               autocomplete="current-password">

        <button type="submit">Log In</button>
    </form>

    <div class="links">
        Don't have an account? <a href="register.php">Register here</a>
        <p><a href="index.php">Back to homepage</a></p>
    </div>
</div>
</body>
</html>
