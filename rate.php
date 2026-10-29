<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$resourceId = (int)($_POST["resource_id"] ?? 0);
$rating = (int)($_POST["rating"] ?? 0);

if ($resourceId <= 0 || $rating < 1 || $rating > 5) {
    header("Location: index.php");
    exit;
}

/* Check whether the resource exists and is approved */
$stmt = $conn->prepare(
    "SELECT id FROM resources
     WHERE id = ? AND is_approved = 1
     LIMIT 1"
);

$stmt->bind_param("i", $resourceId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: index.php");
    exit;
}

$stmt->close();

/* Insert or update the student's rating */
$stmt = $conn->prepare(
    "INSERT INTO resource_ratings
     (resource_id, user_id, rating)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE
     rating = VALUES(rating)"
);

$stmt->bind_param("iii", $resourceId, $userId, $rating);
$stmt->execute();
$stmt->close();

/* Recalculate average rating */
$stmt = $conn->prepare(
    "SELECT AVG(rating) AS average_rating,
            COUNT(*) AS rating_count
     FROM resource_ratings
     WHERE resource_id = ?"
);

$stmt->bind_param("i", $resourceId);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

$averageRating = (float)$data["average_rating"];
$ratingCount = (int)$data["rating_count"];

$stmt->close();

/* Update the resource statistics */
$stmt = $conn->prepare(
    "UPDATE resources
     SET average_rating = ?, rating_count = ?
     WHERE id = ?"
);

$stmt->bind_param(
    "dii",
    $averageRating,
    $ratingCount,
    $resourceId
);

$stmt->execute();
$stmt->close();

header("Location: index.php");
exit;
?>