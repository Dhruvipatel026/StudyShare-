
<?php
session_start();
require_once "db.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

/* Homepage statistics */

$approvedCount = 0;
$contributorCount = 0;
$totalDownloads = 0;

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM resources WHERE is_approved = 1"
);

if ($result) {
    $approvedCount = $result->fetch_assoc()["total"];
}

$result = $conn->query(
    "SELECT COUNT(DISTINCT user_id) AS total
     FROM resources
     WHERE is_approved = 1"
);

if ($result) {
    $contributorCount = $result->fetch_assoc()["total"];
}

$result = $conn->query(
    "SELECT COALESCE(SUM(downloads), 0) AS total
     FROM resources
     WHERE is_approved = 1"
);

if ($result) {
    $totalDownloads = $result->fetch_assoc()["total"];
}

/* Search and filters */

$search = trim($_GET["search"] ?? "");
$semester = (int)($_GET["semester"] ?? 0);
$type = trim($_GET["type"] ?? "");

$allowedTypes = [
    "Notes",
    "Question Paper",
    "Practical File",
    "Assignment"
];

$sql = "SELECT id, title, description, semester, subject,
               unit_name, resource_type, file_name, file_path,
               downloads, average_rating, rating_count
        FROM resources
        WHERE is_approved = 1";

if ($search !== "") {
    $safeSearch = $conn->real_escape_string($search);

    $sql .= " AND (
        title LIKE '%$safeSearch%'
        OR subject LIKE '%$safeSearch%'
        OR unit_name LIKE '%$safeSearch%'
        OR description LIKE '%$safeSearch%'
    )";
}

if ($semester >= 1 && $semester <= 8) {
    $sql .= " AND semester = $semester";
}

if (in_array($type, $allowedTypes, true)) {
    $safeType = $conn->real_escape_string($type);
    $sql .= " AND resource_type = '$safeType'";
}

$sql .= " ORDER BY created_at DESC";

$resources = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>StudyShare | CE Academic Resource Portal</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fa;
    color: #26364b;
}

header {
    background: #19365c;
    color: white;
    padding: 18px 7%;
}

.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 18px;
}

.logo {
    font-size: 27px;
    font-weight: bold;
    letter-spacing: -0.5px;
}

.logo span {
    color: #a9c9ed;
}

nav {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
}

nav a {
    color: white;
    text-decoration: none;
    font-size: 14px;
}

nav a:hover {
    color: #c6ddf7;
}

.nav-button {
    background: white;
    color: #19365c;
    padding: 10px 15px;
    border-radius: 5px;
    font-weight: bold;
}

.hero {
    background: #e8eff7;
    padding: 58px 7% 48px;
}

.hero-content {
    max-width: 900px;
    margin: auto;
}

.tag {
    display: inline-block;
    color: #315c8a;
    background: white;
    border: 1px solid #d2dfed;
    border-radius: 4px;
    padding: 7px 10px;
    font-size: 12px;
    font-weight: bold;
}

.hero h1 {
    max-width: 700px;
    color: #19365c;
    font-size: 42px;
    line-height: 1.2;
    margin: 20px 0 15px;
}

.hero p {
    max-width: 650px;
    color: #52657c;
    line-height: 1.8;
    font-size: 16px;
}

.hero-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 25px;
}

.button {
    display: inline-block;
    padding: 12px 18px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
}

.primary {
    background: #19365c;
    color: white;
}

.secondary {
    border: 1px solid #aabbd0;
    color: #19365c;
    background: white;
}

.statistics {
    max-width: 1000px;
    margin: -1px auto 0;
    padding: 0 18px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.stat {
    background: white;
    border: 1px solid #e0e6ee;
    padding: 23px;
    border-radius: 6px;
    text-align: center;
}

.stat h2 {
    color: #19365c;
    font-size: 29px;
    margin: 0 0 8px;
}

.stat p {
    color: #64748b;
    margin: 0;
    font-size: 13px;
}

main {
    max-width: 1100px;
    margin: 45px auto;
    padding: 0 18px;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.section-heading h2 {
    color: #19365c;
    margin: 0;
    font-size: 26px;
}

.section-heading p {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 0;
}

.search-box {
    background: white;
    border: 1px solid #e0e6ee;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 25px;
}

.filter-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 12px;
}

input, select {
    width: 100%;
    min-width: 0;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 5px;
    font-size: 14px;
    background: white;
}

.search-button {
    border: none;
    border-radius: 5px;
    background: #19365c;
    color: white;
    padding: 12px 18px;
    cursor: pointer;
    font-size: 14px;
}

.clear-link {
    display: inline-block;
    margin-top: 13px;
    color: #315c8a;
    text-decoration: none;
    font-size: 13px;
}

.resource-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.resource-card {
    background: white;
    border: 1px solid #e0e6ee;
    border-radius: 7px;
    padding: 23px;
}

.resource-type {
    display: inline-block;
    background: #eaf1f9;
    color: #315c8a;
    padding: 6px 9px;
    border-radius: 4px;
    font-size: 12px;
}

.resource-card h3 {
    color: #19365c;
    margin: 15px 0 10px;
    font-size: 20px;
    overflow-wrap: anywhere;
}

.resource-card p {
    color: #64748b;
    line-height: 1.6;
    font-size: 14px;
    overflow-wrap: anywhere;
}

.resource-details {
    border-top: 1px solid #edf0f4;
    margin-top: 16px;
    padding-top: 14px;
    color: #64748b;
    font-size: 13px;
    line-height: 1.9;
}

.download-link {
    display: inline-block;
    margin-top: 16px;
    color: #19365c;
    font-weight: bold;
    font-size: 14px;
    text-decoration: none;
}

.download-link:hover {
    text-decoration: underline;
}

.empty-state {
    background: white;
    border: 1px solid #e0e6ee;
    border-radius: 7px;
    text-align: center;
    padding: 45px 20px;
}

.empty-state h3 {
    color: #19365c;
    margin-top: 0;
}

.empty-state p {
    color: #64748b;
    line-height: 1.6;
}

.contribute {
    background: #19365c;
    color: white;
    padding: 35px;
    border-radius: 7px;
    margin-top: 45px;
}

.contribute h2 {
    margin-top: 0;
}

.contribute p {
    color: #d6e2f0;
    line-height: 1.7;
}

.contribute a {
    display: inline-block;
    margin-top: 8px;
    background: white;
    color: #19365c;
    padding: 11px 16px;
    border-radius: 5px;
    font-size: 14px;
    font-weight: bold;
    text-decoration: none;
}

footer {
    background: white;
    border-top: 1px solid #e0e6ee;
    padding: 25px 7%;
    color: #64748b;
    font-size: 13px;
    line-height: 1.8;
    text-align: center;
}

footer a {
    color: #315c8a;
    text-decoration: none;
}

@media (max-width: 700px) {
    .navbar {
        align-items: flex-start;
    }

    nav {
        gap: 14px;
    }

    .hero h1 {
        font-size: 33px;
    }

    .statistics {
        grid-template-columns: 1fr;
    }

    .filter-row {
        grid-template-columns: 1fr;
    }

    .resource-grid {
        grid-template-columns: 1fr;
    }

    .contribute {
        padding: 25px;
    }
}
</style>
</head>

<body>

<header>
    <div class="navbar">
        <div class="logo">Study<span>Share</span></div>

        <nav>
            <a href="index.php">Home</a>
            <a href="#resources">Resources</a>
            <a href="#contribute">Contribute</a>
            <a href="request.php">Request Notes</a>
            <?php if (isset($_SESSION["user_id"])): ?>
                <a href="upload.php">Upload Resource</a>

                <?php if (($_SESSION["user_role"] ?? "") === "admin"): ?>
                    <a href="admin.php">Admin Panel</a>
                <?php endif; ?>

                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a class="nav-button" href="register.php">Join StudyShare</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="hero">
    <div class="hero-content">
        <span class="tag">COMPUTER ENGINEERING RESOURCE PORTAL</span>

        <h1>Your study materials, organised in one place.</h1>

        <p>
            Find semester-wise notes, previous question papers,
            practical files and assignments shared by fellow students.
            Learn together and make exam preparation easier.
        </p>

        <div class="hero-buttons">
            <a class="button primary" href="#resources">Explore Resources</a>

            <?php if (isset($_SESSION["user_id"])): ?>
                <a class="button secondary" href="upload.php">Share a Resource</a>
            <?php else: ?>
                <a class="button secondary" href="register.php">Become a Contributor</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="statistics">
    <div class="stat">
        <h2><?php echo (int)$approvedCount; ?>+</h2>
        <p>Approved Resources</p>
    </div>

    <div class="stat">
        <h2><?php echo (int)$contributorCount; ?>+</h2>
        <p>Student Contributors</p>
    </div>

    <div class="stat">
        <h2><?php echo (int)$totalDownloads; ?>+</h2>
        <p>Total Downloads</p>
    </div>
</section>

<main>
    <section id="resources">
        <div class="section-heading">
            <div>
                <h2>Study Resources</h2>
                <p>Browse materials by keyword, semester or resource type.</p>
            </div>
        </div>

        <div class="search-box">
            <form method="GET" action="index.php">
                <div class="filter-row">
                    <input
                        type="search"
                        name="search"
                        placeholder="Search notes, subjects or units..."
                        value="<?php echo e($search); ?>"
                    >

                    <select name="semester">
                        <option value="0">All Semesters</option>

                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>"
                                <?php echo $semester === $i ? "selected" : ""; ?>>
                                Semester <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <select name="type">
                        <option value="">All Types</option>

                        <?php foreach ($allowedTypes as $allowedType): ?>
                            <option value="<?php echo e($allowedType); ?>"
                                <?php echo $type === $allowedType ? "selected" : ""; ?>>
                                <?php echo e($allowedType); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button class="search-button" type="submit">Search</button>
                </div>

                <a class="clear-link" href="index.php">Clear search and filters</a>
            </form>
        </div>

        <?php if ($resources && $resources->num_rows > 0): ?>
            <div class="resource-grid">
                <?php while ($resource = $resources->fetch_assoc()): ?>
                    <article class="resource-card">
                        <span class="resource-type">
                            <?php echo e($resource["resource_type"]); ?>
                        </span>

                        <h3><?php echo e($resource["title"]); ?></h3>

                        <p>
                            <?php
                            echo e(
                                $resource["description"] !== ""
                                ? $resource["description"]
                                : "Study material shared with the StudyShare community."
                            );
                            ?>
                        </p>

                        <div class="resource-details">
                            <strong>Subject:</strong>
                            <?php echo e($resource["subject"]); ?><br>

                            <strong>Semester:</strong>
                            <?php echo (int)$resource["semester"]; ?><br>

                            <strong>Unit:</strong>
                            <?php echo e($resource["unit_name"]); ?><br>

                            <strong>Rating:</strong>
                            <?php
                            if ((int)$resource["rating_count"] > 0) {
                                echo number_format((float)$resource["average_rating"], 1);
                                echo " / 5 (" . (int)$resource["rating_count"] . " ratings)";
                            } else {
                                echo "Not rated yet";
                            }
                            ?><br>

                            <strong>Downloads:</strong>
                            <?php echo (int)$resource["downloads"]; ?>
                        </div>
		
 	       <form method="POST" action="rate.php" style="margin-top:15px;">

    <input type="hidden"
           name="resource_id"
           value="<?php echo (int)$resource["id"]; ?>">

    <label style="font-size:13px; color:#64748b;">
        Rate this resource
    </label>

    <div style="margin-top:8px;">
        <?php for ($star = 1; $star <= 5; $star++): ?>

            <button
                type="submit"
                name="rating"
                value="<?php echo $star; ?>"
                title="Give <?php echo $star; ?> star<?php echo $star > 1 ? 's' : ''; ?>"
                style="
                    border:1px solid #d5dde7;
                    background:white;
                    color:#f2b01e;
                    padding:5px 8px;
                    border-radius:4px;
                    cursor:pointer;
                    font-size:16px;
                "
            >★</button>

        <?php endfor; ?>
    </div>

</form>
                        <?php
                        $filePath = $resource["file_path"];
                        $safeFilePath = str_replace("\\", "/", $filePath);
                        $safeFilePath = ltrim($safeFilePath, "/");
                        $fileUrl = "";

                        if (
                            strpos($safeFilePath, "uploads/") === 0 &&
                            strpos($safeFilePath, "..") === false
                        ) {
                            $fileUrl = $safeFilePath;
                        }
                        ?>

                        <?php if ($fileUrl !== ""): ?>
                        
<a class="download-link"
   href="download.php?id=<?php echo (int)$resource['id']; ?>">
    Open PDF &rarr;
</a>

<a class="download-link"
   href="download.php?id=<?php echo (int)$resource['id']; ?>&download=1"
   style="margin-left: 12px;">
    Download PDF &darr;
</a>

                        <?php else: ?>
                            <p>The resource file link is unavailable.</p>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>No resources found</h3>
                <p>
                    There are no approved resources matching your search yet.
                    Try another filter or share a resource with your classmates.
                </p>

                <?php if (isset($_SESSION["user_id"])): ?>
                    <a class="button primary" href="upload.php">Upload a Resource</a>
                <?php else: ?>
                    <a class="button primary" href="register.php">Join StudyShare</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="contribute" id="contribute">
        <h2>Help your classmates learn better.</h2>
        <p>
            Have useful notes, a previous question paper or a practical file?
            Share your academic resources and help build a better study
            library for Computer Engineering students.
        </p>

        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="upload.php">Upload a Resource</a>
        <?php else: ?>
            <a href="register.php">Create Your Account</a>
        <?php endif; ?>
    </section>
</main>

<footer>
    <strong>StudyShare</strong> — Computer Engineering Academic Resource Portal
    <br>
    Learn together. Share knowledge. Prepare better.
    <br>
    <a href="index.php">Home</a> |
    <a href="login.php">Login</a> |
    <a href="register.php">Register</a>
</footer>

</body>
</html>
