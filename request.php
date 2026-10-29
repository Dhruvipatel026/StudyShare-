
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $semester = (int)($_POST["semester"] ?? 0);
    $subject = trim($_POST["subject"] ?? "");
    $unitName = trim($_POST["unit_name"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($semester < 1 || $semester > 8 ||
        $subject === "" || $unitName === "" || $description === "") {
        $message = "Please fill in all fields correctly.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO note_requests
            (user_id, semester, subject, unit_name, description, status)
            VALUES (?, ?, ?, ?, ?, 'Open')"
        );

        $stmt->bind_param(
            "iisss",
            $userId,
            $semester,
            $subject,
            $unitName,
            $description
        );

        if ($stmt->execute()) {
            $message = "Your note request has been submitted successfully!";
        } else {
            $message = "Unable to submit your request. Please try again.";
        }

        $stmt->close();
    }
}

$stmt = $conn->prepare(
    "SELECT nr.semester, nr.subject, nr.unit_name,
            nr.description, nr.status, nr.created_at, u.name
     FROM note_requests nr
     JOIN users u ON nr.user_id = u.id
     ORDER BY nr.created_at DESC"
);

$stmt->execute();
$requests = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note Requests | StudyShare</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f6fa;
            color: #243247;
        }

        header {
            background: #142d4e;
            color: white;
            padding: 20px 7%;
        }

        header h2 {
            margin: 0;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            font-size: 14px;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .panel {
            background: white;
            padding: 25px;
            border: 1px solid #e0e6ee;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        h2 {
            color: #142d4e;
            margin-top: 0;
        }

        label {
            display: block;
            margin: 15px 0 7px;
            font-weight: bold;
            font-size: 14px;
        }

        input, select, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            font-size: 14px;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        button {
            background: #142d4e;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 18px;
        }

        button:hover {
            background: #234a78;
        }

        .message {
            background: #edf5ff;
            border: 1px solid #c7ddf7;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 18px;
        }

        .request {
            border: 1px solid #e0e6ee;
            padding: 18px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .status {
            display: inline-block;
            background: #fff1c7;
            color: #805b00;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .details {
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 0 12px;
            }

            .panel {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>StudyShare</h2>
    <p>Request academic resources from your college community.</p>
    <a href="index.php">Home</a>
    <a href="upload.php">Upload Resource</a>
</header>

<div class="container">

    <div class="panel">
        <h2>Request Notes or Study Material</h2>

        <?php if ($message !== ""): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="request.php">
            <label for="semester">Semester</label>
            <select name="semester" id="semester" required>
                <option value="">Select Semester</option>
                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?php echo $i; ?>">
                        Semester <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>

            <label for="subject">Subject</label>
            <input type="text" name="subject" id="subject"
                   placeholder="e.g. Database Management System" required>

            <label for="unit_name">Unit</label>
            <input type="text" name="unit_name" id="unit_name"
                   placeholder="e.g. Unit 1 - Introduction" required>

            <label for="description">What material do you need?</label>
            <textarea name="description" id="description"
                      placeholder="Describe the notes, question paper or practical file you need."
                      required></textarea>

            <button type="submit">Submit Request</button>
        </form>
    </div>

    <div class="panel">
        <h2>Student Note Requests</h2>
        <p class="details">
            See what study material students are looking for.
        </p>

        <?php if ($requests->num_rows > 0): ?>

            <?php while ($request = $requests->fetch_assoc()): ?>
                <div class="request">
                    <h3>
                        <?php echo htmlspecialchars($request["subject"]); ?>
                    </h3>

                    <p>
                        <strong>Semester:</strong>
                        <?php echo (int)$request["semester"]; ?>
                    </p>

                    <p>
                        <strong>Unit:</strong>
                        <?php echo htmlspecialchars($request["unit_name"]); ?>
                    </p>

                    <p>
                        <?php echo nl2br(htmlspecialchars($request["description"])); ?>
                    </p>

                    <p class="details">
                        Requested by:
                        <?php echo htmlspecialchars($request["name"]); ?>
                        |
                        <?php echo date("d M Y", strtotime($request["created_at"])); ?>
                    </p>

                    <span class="status">
                        <?php echo htmlspecialchars($request["status"]); ?>
                    </span>
                </div>
            <?php endwhile; ?>

        <?php else: ?>
            <p>No requests yet. Be the first to request study material!</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>

<?php
$stmt->close();
?>
