<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $semester = (int)($_POST["semester"] ?? 0);
    $subject = trim($_POST["subject"] ?? "");
    $unit = trim($_POST["unit_name"] ?? "");
    $type = $_POST["resource_type"] ?? "";

    $allowedTypes = [
        "Notes",
        "Question Paper",
        "Practical File",
        "Assignment"
    ];

    if (
        $title === "" ||
        $subject === "" ||
        $unit === "" ||
        $semester < 1 ||
        $semester > 8 ||
        !in_array($type, $allowedTypes, true)
    ) {
        $message = "Please fill in all required fields correctly.";
    } elseif (
        !isset($_FILES["resource_file"]) ||
        $_FILES["resource_file"]["error"] !== UPLOAD_ERR_OK
    ) {
        $message = "Please select a PDF file to upload.";
    } elseif ($_FILES["resource_file"]["size"] > 5 * 1024 * 1024) {
        $message = "The PDF file must be smaller than 5 MB.";
    } else {
        $originalName = $_FILES["resource_file"]["name"];
        $temporaryFile = $_FILES["resource_file"]["tmp_name"];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($temporaryFile);

        if ($extension !== "pdf" || $mimeType !== "application/pdf") {
            $message = "Only valid PDF files are allowed.";
        } else {
            $uploadFolder = __DIR__ . "/uploads/";

            if (!is_dir($uploadFolder)) {
                mkdir($uploadFolder, 0755, true);
            }

            $newFileName = uniqid("study_", true) . ".pdf";
            $filePath = "uploads/" . $newFileName;
            $fullPath = $uploadFolder . $newFileName;

            if (move_uploaded_file($temporaryFile, $fullPath)) {
                $userId = (int)$_SESSION["user_id"];
                $approved = 0;

                $stmt = $conn->prepare(
                    "INSERT INTO resources
                    (user_id, title, description, semester, subject,
                     unit_name, resource_type, file_name, file_path,
                     is_approved)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "ississsssi",
                    $userId,
                    $title,
                    $description,
                    $semester,
                    $subject,
                    $unit,
                    $type,
                    $originalName,
                    $filePath,
                    $approved
                );

                if ($stmt->execute()) {
                    $message = "Resource uploaded successfully! It is waiting for admin approval.";
                    $success = true;
                } else {
                    unlink($fullPath);
                    $message = "Could not save the resource. Please try again.";
                }

                $stmt->close();
            } else {
                $message = "The file could not be uploaded. Please try again.";
            }
        }
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
<title>Upload Resource | StudyShare</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f1f5f9;
    color: #24344d;
    font-family: Arial, sans-serif;
}

header {
    background: #19365c;
    color: white;
    padding: 18px 7%;
}

.logo {
    font-size: 25px;
    font-weight: bold;
}

.logo span {
    color: #b9d4f1;
}

header a {
    color: white;
    text-decoration: none;
    margin-right: 20px;
    font-size: 14px;
}

nav {
    margin-top: 15px;
}

.container {
    max-width: 760px;
    margin: 35px auto;
    padding: 0 18px;
}

.form-box {
    background: white;
    border: 1px solid #dce4ed;
    border-radius: 8px;
    padding: 30px;
}

h1 {
    margin-top: 0;
    color: #19365c;
    font-size: 27px;
}

.subtitle {
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 25px;
}

label {
    display: block;
    font-size: 14px;
    font-weight: bold;
    margin: 17px 0 7px;
}

input, select, textarea {
    width: 100%;
    padding: 11px;
    border: 1px solid #cbd5e1;
    border-radius: 5px;
    font-size: 14px;
    background: white;
}

textarea {
    min-height: 85px;
    resize: vertical;
}

input:focus, select:focus, textarea:focus {
    outline: 2px solid #dceaf7;
    border-color: #5b83b4;
}

button {
    margin-top: 24px;
    padding: 12px 20px;
    background: #19365c;
    color: white;
    border: 0;
    border-radius: 5px;
    font-size: 15px;
    cursor: pointer;
}

button:hover {
    background: #284c78;
}

.message {
    padding: 13px;
    border-radius: 5px;
    background: #eff6ff;
    color: #19365c;
    font-size: 14px;
    line-height: 1.5;
}

.note {
    margin-top: 18px;
    color: #64748b;
    font-size: 13px;
    line-height: 1.5;
}

footer {
    text-align: center;
    color: #64748b;
    padding: 20px;
    font-size: 13px;
}
</style>
</head>

<body>

<header>
    <div class="logo">Study<span>Share</span></div>
    <nav>
        <a href="index.php">Home</a>
        <a href="upload.php">Upload Resource</a>
        <a href="login.php">Login</a>
    </nav>
</header>

<div class="container">
    <div class="form-box">
        <h1>Upload a Study Resource</h1>
        <p class="subtitle">
            Share useful academic materials with Computer Engineering students.
        </p>

        <?php if ($message !== ""): ?>
            <p class="message"><?php echo e($message); ?></p>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form action="upload.php" method="POST" enctype="multipart/form-data">

            <label for="title">Resource Title *</label>
            <input type="text" id="title" name="title"
                   placeholder="Example: Operating System Unit 1 Notes"
                   maxlength="150" required>

            <label for="description">Description</label>
            <textarea id="description" name="description"
                      placeholder="Briefly describe this resource"></textarea>

            <label for="semester">Semester *</label>
            <select id="semester" name="semester" required>
                <option value="">Select semester</option>
                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?php echo $i; ?>">
                        Semester <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>

            <label for="subject">Subject *</label>
            <input type="text" id="subject" name="subject"
                   placeholder="Example: Operating System"
                   maxlength="100" required>

            <label for="unit_name">Unit / Chapter *</label>
            <input type="text" id="unit_name" name="unit_name"
                   placeholder="Example: Unit 1 - Introduction"
                   maxlength="100" required>

            <label for="resource_type">Resource Type *</label>
            <select id="resource_type" name="resource_type" required>
                <option value="">Select resource type</option>
                <option value="Notes">Notes</option>
                <option value="Question Paper">Question Paper</option>
                <option value="Practical File">Practical File</option>
                <option value="Assignment">Assignment</option>
            </select>

            <label for="resource_file">Choose PDF File *</label>
            <input type="file" id="resource_file" name="resource_file"
                   accept=".pdf,application/pdf" required>

            <p class="note">
                PDF files only. Maximum size: 5 MB.
                Your resource will be reviewed before it appears publicly.
            </p>

            <button type="submit">Upload Resource</button>
        </form>
        <?php else: ?>
            <p><a href="index.php">Return to homepage</a></p>
            <p><a href="upload.php">Upload another resource</a></p>
        <?php endif; ?>
    </div>
</div>

<footer>
    StudyShare — Computer Engineering Academic Resource Portal
</footer>

</body>
</html>