
<?php
session_start();
require_once "db.php";

$resourceId = (int)($_GET["id"] ?? 0);

if ($resourceId <= 0) {
    header("Location: index.php");
    exit;
}

/* Find an approved resource */
$stmt = $conn->prepare(
    "SELECT title, file_name, file_path
     FROM resources
     WHERE id = ? AND is_approved = 1
     LIMIT 1"
);

$stmt->bind_param("i", $resourceId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    die("Resource not found or not approved.");
}

$resource = $result->fetch_assoc();
$stmt->close();

/* Check the file path */
$filePath = str_replace("\\", "/", $resource["file_path"]);
$filePath = ltrim($filePath, "/");

if (
    strpos($filePath, "uploads/") !== 0 ||
    strpos($filePath, "..") !== false
) {
    die("Invalid resource file path.");
}

$fullPath = __DIR__ . "/" . $filePath;

if (!is_file($fullPath)) {
    die("The PDF file could not be found.");
}

/* Update the download count */
$stmt = $conn->prepare(
    "UPDATE resources
     SET downloads = downloads + 1
     WHERE id = ? AND is_approved = 1"
);

$stmt->bind_param("i", $resourceId);

if (!$stmt->execute()) {
    $stmt->close();
    die("Could not update the download count.");
}

$stmt->close();

/* Send the PDF to the browser */
$safeName = basename($resource["file_name"]);
header("Content-Type: application/pdf");
if (isset($_GET["download"]) && $_GET["download"] == "1") {
    header('Content-Disposition: attachment; filename="' . str_replace('"', "", $safeName) . '"');
} else {
    header('Content-Disposition: inline; filename="' . str_replace('"', "", $safeName) . '"');
}
header("Content-Length: " . filesize($fullPath));
header("X-Content-Type-Options: nosniff");

readfile($fullPath);
exit;
?>
