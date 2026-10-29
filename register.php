
<?php
require_once "db.php";

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $semester = (int)($_POST["semester"] ?? 0);
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter your name and a valid email address.";
    } elseif ($semester < 1 || $semester > 8) {
        $message = "Please select a valid semester.";
    } elseif (strlen($password) < 8) {
        $message = "Password must contain at least 8 characters.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $existing = $check->get_result();

        if ($existing->num_rows > 0) {
            $message = "This email is already registered. Please use another email.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = "student";

            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password, semester, role)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssis", $name, $email, $hashedPassword, $semester, $role);

            if ($stmt->execute()) {
                $success = true;
                $message = "Your account has been created successfully!";
            } else {
                $message = "Registration could not be completed. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
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
<title>Join StudyShare</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0; font-family: Arial, sans-serif; min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: 25px; color: #172554;
    background: linear-gradient(135deg, #dbeafe, #ede9fe, #f8fafc);
}
.card {
    width: 100%; max-width: 470px; padding: 32px;
    background: white; border-radius: 22px;
    box-shadow: 0 15px 45px #4338ca18;
}
.logo { font-size: 25px; font-weight: bold; color: #4338ca; text-align: center; }
.logo span { color: #0ea5e9; }
h1 { text-align: center; margin: 15px 0 8px; font-size: 27px; }
.subtitle { text-align: center; color: #64748b; margin-bottom: 25px; font-size: 14px; }
label { display: block; font-weight: bold; font-size: 13px; margin: 15px 0 7px; }
input, select {
    width: 100%; padding: 12px; border: 1px solid #cbd5e1;
    border-radius: 9px; font-size: 14px; background: white;
}
input:focus, select:focus { outline: 2px solid #c7d2fe; border-color: #6366f1; }
button {
    width: 100%; padding: 13px; margin-top: 23px; border: none;
    border-radius: 10px; color: white; background: #4338ca;
    font-weight: bold; font-size: 15px; cursor: pointer;
}
button:hover { background: #3730a3; }
.message { padding: 12px; margin: 15px 0; border-radius: 9px; font-size: 14px; background: #fff7ed; color: #9a3412; }
.message.success { background: #dcfce7; color: #166534; }
.back { text-align: center; margin-top: 20px; font-size: 14px; }
.back a { color: #4338ca; font-weight: bold; text-decoration: none; }
</style>
</head>
<body>
<div class="card">
    <div class="logo">Study<span>Share</span> 📚</div>
    <h1>Create your account</h1>
    <p class="subtitle">Join your Computer Engineering learning community.</p>

    <?php if ($message !== ""): ?>
        <div class="message <?php echo $success ? 'success' : ''; ?>">
            <?php echo e($message); ?>
            <?php if ($success): ?>
                <p><a href="index.php">Go to StudyShare homepage</a></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="register.php">
        <label for="name">Full name</label>
        <input id="name" name="name" type="text" maxlength="100" required
               value="<?php echo e($_POST['name'] ?? ''); ?>">

        <label for="email">Email address</label>
        <input id="email" name="email" type="email" maxlength="150" required
               value="<?php echo e($_POST['email'] ?? ''); ?>">

        <label for="semester">Current semester</label>
        <select id="semester" name="semester" required>
            <option value="">Select your semester</option>
            <?php for ($i = 1; $i <= 8; $i++): ?>
                <option value="<?php echo $i; ?>"
                    <?php echo (isset($_POST['semester']) && (int)$_POST['semester'] === $i) ? 'selected' : ''; ?>>
                    Semester <?php echo $i; ?>
                </option>
            <?php endfor; ?>
        </select>

        <label for="password">Password (at least 8 characters)</label>
        <input id="password" name="password" type="password" minlength="8" required>

        <label for="confirm_password">Confirm password</label>
        <input id="confirm_password" name="confirm_password" type="password" minlength="8" required>

        <button type="submit">Create Account →</button>
    </form>
    <?php endif; ?>

    <div class="back"><a href="index.php">← Back to homepage</a></div>
</div>
</body>
</html>
