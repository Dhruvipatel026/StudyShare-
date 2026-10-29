
<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$message = "";

/* Approve a resource */
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["approve_resource"])) {

    $resourceId = (int)$_POST["approve_resource"];

    if ($resourceId > 0) {
        $stmt = $conn->prepare(
            "UPDATE resources
             SET is_approved = 1
             WHERE id = ? AND is_approved = 0"
        );

        $stmt->bind_param("i", $resourceId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "Resource approved successfully!";
        } else {
            $message = "Resource was not found or is already approved.";
        }

        $stmt->close();
    }
}

/* Update note request status */
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_request"])) {

    $requestId = (int)($_POST["request_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    if ($requestId > 0 &&
        in_array($status, ["Open", "Fulfilled", "Closed"], true)) {

        $stmt = $conn->prepare(
            "UPDATE note_requests
             SET status = ?
             WHERE id = ?"
        );

        $stmt->bind_param("si", $status, $requestId);

        if ($stmt->execute()) {
            $message = "Note request status updated successfully!";
        } else {
            $message = "Unable to update the note request.";
        }

        $stmt->close();
    } else {
        $message = "Please select a valid request status.";
    }
}

/* Fetch resources awaiting approval */
$resources = $conn->query(
    "SELECT resources.*, users.name
     FROM resources
     JOIN users ON resources.user_id = users.id
     WHERE resources.is_approved = 0
     ORDER BY resources.created_at DESC"
);

/* Fetch all student note requests */
$requests = $conn->query(
    "SELECT note_requests.*, users.name
     FROM note_requests
     JOIN users ON note_requests.user_id = users.id
     ORDER BY note_requests.created_at DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Panel | StudyShare</title>

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
            padding: 22px 7%;
        }

        header h2 {
            margin: 0 0 12px;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            font-size: 14px;
        }

        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .panel {
            background: white;
            padding: 24px;
            border: 1px solid #e0e6ee;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        h2 {
            color: #142d4e;
            margin-top: 0;
        }

        .item {
            border: 1px solid #e0e6ee;
            padding: 18px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .details {
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }

        button {
            background: #142d4e;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #234a78;
        }

        select {
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            margin: 8px 8px 8px 0;
            background: white;
        }

        .message {
            background: #edf5ff;
            border: 1px solid #c7ddf7;
            padding: 13px;
            border-radius: 5px;
            margin-bottom: 20px;
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

        .empty {
            color: #64748b;
            padding: 10px 0;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 0 12px;
            }

            .panel {
                padding: 17px;
            }

            select {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>StudyShare Admin Panel</h2>
    <a href="index.php">Home</a>
    <a href="request.php">Note Requests</a>
    <a href="upload.php">Upload Resource</a>
    <a href="logout.php">Logout</a>
</header>

<div class="container">

    <?php if ($message !== ""): ?>
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Resource Approval Section -->
    <div class="panel">
        <h2>Pending Resource Approvals</h2>
        <p class="details">
            Review student-uploaded resources before making them available.
        </p>

        <?php if ($resources && $resources->num_rows > 0): ?>

            <?php while ($resource = $resources->fetch_assoc()): ?>
                <div class="item">
                    <h3>
                        <?php echo htmlspecialchars($resource["title"]); ?>
                    </h3>

                    <p>
                        <?php echo htmlspecialchars($resource["description"]); ?>
                    </p>

                    <p class="details">
                        Uploaded by:
                        <?php echo htmlspecialchars($resource["name"]); ?>
                        <br>
                        Semester:
                        <?php echo (int)$resource["semester"]; ?>
                        <br>
                        Subject:
                        <?php echo htmlspecialchars($resource["subject"]); ?>
                        <br>
                        Type:
                        <?php echo htmlspecialchars($resource["resource_type"]); ?>
                    </p>

                    <form method="POST" action="admin.php">
                        <button type="submit"
                                name="approve_resource"
                                value="<?php echo (int)$resource["id"]; ?>">
                            Approve Resource
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>

        <?php else: ?>
            <p class="empty">There are no resources waiting for approval.</p>
        <?php endif; ?>
    </div>

    <!-- Note Request Management Section -->
    <div class="panel">
        <h2>Student Note Requests</h2>
        <p class="details">
            View student requests and update their status when the requested
            study material has been provided or the request is closed.
        </p>

        <?php if ($requests && $requests->num_rows > 0): ?>

            <?php while ($request = $requests->fetch_assoc()): ?>
                <div class="item">

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
                        <strong>Material needed:</strong><br>
                        <?php echo nl2br(htmlspecialchars($request["description"])); ?>
                    </p>

                    <p class="details">
                        Requested by:
                        <?php echo htmlspecialchars($request["name"]); ?>
                        <br>
                        Date:
                        <?php echo date("d M Y", strtotime($request["created_at"])); ?>
                    </p>

                    <p>
                        Current status:
                        <span class="status">
                            <?php echo htmlspecialchars($request["status"]); ?>
                        </span>
                    </p>

                    <form method="POST" action="admin.php">
                        <input type="hidden"
                               name="request_id"
                               value="<?php echo (int)$request["id"]; ?>">

                        <label for="status_<?php echo (int)$request["id"]; ?>">
                            Change Request Status
                        </label>

                        <select name="status"
                                id="status_<?php echo (int)$request["id"]; ?>"
                                required>

                            <option value="Open"
                                <?php if ($request["status"] === "Open") echo "selected"; ?>>
                                Open
                            </option>

                            <option value="Fulfilled"
                                <?php if ($request["status"] === "Fulfilled") echo "selected"; ?>>
                                Fulfilled
                            </option>

                            <option value="Closed"
                                <?php if ($request["status"] === "Closed") echo "selected"; ?>>
                                Closed
                            </option>
                        </select>

                        <button type="submit" name="update_request" value="1">
                            Update Status
                        </button>
                    </form>

                </div>
            <?php endwhile; ?>

        <?php else: ?>
            <p class="empty">No student note requests have been submitted.</p>
        <?php endif; ?>
    </div>

</div>

<footer>
    StudyShare — Computer Engineering Academic Resource Portal
</footer>

</body>
</html>

<?php
if ($resources) {
    $resources->free();
}

if ($requests) {
    $requests->free();
}

$conn->close();
?>
