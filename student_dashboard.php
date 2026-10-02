<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

if (isset($_GET["logout"])) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: login.php");
    exit;
}


$stmt = $conn->prepare("
    SELECT
        u.email,
        u.account_status,
        sp.first_name,
        sp.middle_name,
        sp.last_name,
        sp.student_id,
        sp.phone,
        sp.birthdate,
        sp.gender,
        sp.address,
        sp.program,
        sp.year_level,
        sp.section,
        sp.college,
        sp.campus,
        sp.profile_photo,
        sp.about_me
    FROM users u
    LEFT JOIN student_profiles sp
        ON sp.user_id = u.id
    WHERE u.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    session_destroy();
    header("Location: login.php");
    exit;
}


$firstName = trim($student["first_name"] ?? "");
$middleName = trim($student["middle_name"] ?? "");
$lastName = trim($student["last_name"] ?? "");

$studentName = trim(
    $firstName . " " . $lastName
);

if ($studentName === "") {
    $studentName = "Student";
}


$avatarInitial = strtoupper(
    substr(
        $firstName !== "" ? $firstName : $studentName,
        0,
        1
    )
);


$program = trim($student["program"] ?? "");
$yearLevel = trim($student["year_level"] ?? "");
$section = trim($student["section"] ?? "");
$college = trim($student["college"] ?? "");
$studentId = trim($student["student_id"] ?? "");
$phone = trim($student["phone"] ?? "");
$birthdate = trim($student["birthdate"] ?? "");
$gender = trim($student["gender"] ?? "");
$address = trim($student["address"] ?? "");
$campus = trim($student["campus"] ?? "");
$aboutMe = trim($student["about_me"] ?? "");
$profilePhoto = trim($student["profile_photo"] ?? "");


$completionFields = [
    $firstName,
    $lastName,
    $studentId,
    $program,
    $yearLevel,
    $section,
    $college,
    $phone,
    $birthdate,
    $gender,
    $address,
    $aboutMe
];

$completedFields = 0;
$totalFields = count($completionFields);

foreach ($completionFields as $field) {
    if ($field !== "") {
        $completedFields++;
    }
}


$accomplishmentCountStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM accomplishments
    WHERE user_id = ?
");

$accomplishmentCountStmt->bind_param(
    "i",
    $userId
);

$accomplishmentCountStmt->execute();

$accomplishmentCount = (int) (
    $accomplishmentCountStmt
        ->get_result()
        ->fetch_assoc()["total"] ?? 0
);


$hobbyCount = 0;

$hobbyTableCheck = $conn->query("
    SHOW TABLES LIKE 'hobbies_interests'
");

if ($hobbyTableCheck && $hobbyTableCheck->num_rows > 0) {

    $hobbyStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM hobbies_interests
        WHERE user_id = ?
    ");

    $hobbyStmt->bind_param(
        "i",
        $userId
    );

    $hobbyStmt->execute();

    $hobbyCount = (int) (
        $hobbyStmt
            ->get_result()
            ->fetch_assoc()["total"] ?? 0
    );
}


$organizationCount = 0;

$organizationTableCheck = $conn->query("
    SHOW TABLES LIKE 'organizations_activities'
");

if (
    $organizationTableCheck &&
    $organizationTableCheck->num_rows > 0
) {

    $organizationStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM organizations_activities
        WHERE user_id = ?
    ");

    $organizationStmt->bind_param(
        "i",
        $userId
    );

    $organizationStmt->execute();

    $organizationCount = (int) (
        $organizationStmt
            ->get_result()
            ->fetch_assoc()["total"] ?? 0
    );
}


if ($accomplishmentCount > 0) {
    $completedFields++;
}

$totalFields++;

if ($hobbyCount > 0) {
    $completedFields++;
}

$totalFields++;

if ($organizationCount > 0) {
    $completedFields++;
}

$totalFields++;


$profileCompletion = (int) round(
    ($completedFields / $totalFields) * 100
);

$profileCompletion = max(
    0,
    min(100, $profileCompletion)
);


$privacyVisibility = "Private";

$privacyStmt = $conn->prepare("
    SELECT profile_visibility
    FROM privacy_settings
    WHERE user_id = ?
    LIMIT 1
");

$privacyStmt->bind_param(
    "i",
    $userId
);

$privacyStmt->execute();

$privacy = $privacyStmt
    ->get_result()
    ->fetch_assoc();

if ($privacy) {

    $visibilityValue = $privacy["profile_visibility"] ?? "private";

    if ($visibilityValue === "public") {
        $privacyVisibility = "Public";
    } elseif ($visibilityValue === "school") {
        $privacyVisibility = "School Only";
    } else {
        $privacyVisibility = "Private";
    }
}


$recentAccomplishments = [];

$recentStmt = $conn->prepare("
    SELECT
        title,
        category,
        date_achieved
    FROM accomplishments
    WHERE user_id = ?
    ORDER BY date_achieved DESC, id DESC
    LIMIT 5
");

$recentStmt->bind_param(
    "i",
    $userId
);

$recentStmt->execute();

$recentResult = $recentStmt->get_result();

while ($row = $recentResult->fetch_assoc()) {

    $recentAccomplishments[] = $row;
}


$profileStatus = $student["account_status"] ?? "pending";

if ($profileStatus === "active") {
    $accountStatus = "Active";
} elseif ($profileStatus === "pending") {
    $accountStatus = "Pending";
} elseif ($profileStatus === "suspended") {
    $accountStatus = "Suspended";
} else {
    $accountStatus = ucfirst($profileStatus);
}


$programDisplay = $program !== ""
    ? $program
    : "Program not added";

$yearDisplay = $yearLevel !== ""
    ? $yearLevel
    : "Year level not added";

$studentIdDisplay = $studentId !== ""
    ? $studentId
    : "Student ID not added";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard | CVSWHO</title>

    <link
        rel="stylesheet"
        href="student_dashboard.css"
    >

</head>

<body>

<header class="navbar">

    <div class="nav-container">

        <a
            href="student_dashboard.php"
            class="brand"
        >

            <div class="brand-mark">
                C
            </div>

            <div class="brand-text">

                <span class="brand-name">
                    CVSWHO
                </span>

                <span class="brand-subtitle">
                    Student Profile Management
                </span>

            </div>

        </a>


        <nav class="desktop-nav">

            <a
                href="student_dashboard.php"
                class="nav-link active"
            >
                Dashboard
            </a>

            <a
                href="student_profile.php"
                class="nav-link"
            >
                Profile
            </a>

            <a
                href="accomplishments.php"
                class="nav-link"
            >
                Accomplishments
            </a>

            <a
                href="settings.php"
                class="nav-link"
            >
                Settings
            </a>

        </nav>


        <a
            href="student_dashboard.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>

    </div>

</header>


<main class="dashboard">


    <section class="welcome-section">

        <div>

            <span class="eyebrow">
                STUDENT DASHBOARD
            </span>

            <h1>
                Welcome, <?= htmlspecialchars($studentName) ?>!
            </h1>

            <p>
                Manage your student profile, accomplishments, and
                activities in one place.
            </p>

        </div>


        <a
            href="student_profile.php"
            class="primary-button"
        >
            View Profile
        </a>

    </section>


    <section class="completion-card">

        <div class="completion-content">

            <div>

                <span class="section-label">
                    PROFILE COMPLETION
                </span>

                <h2>
                    <?= $profileCompletion ?>% complete
                </h2>

                <p>

                    <?php if ($profileCompletion >= 100): ?>

                        Your profile is complete. You can continue adding
                        accomplishments, hobbies, and activities.

                    <?php elseif ($profileCompletion > 0): ?>

                        Keep your profile updated by adding your personal
                        information, academic background, accomplishments,
                        hobbies, and activities.

                    <?php else: ?>

                        Start building your student profile by adding
                        your personal and academic information.

                    <?php endif; ?>

                </p>

            </div>


            <div
                class="progress-circle"
                style="--progress: <?= $profileCompletion ?>%;"
            >

                <div class="progress-circle-inner">

                    <strong>
                        <?= $profileCompletion ?>%
                    </strong>

                    <span>
                        Complete
                    </span>

                </div>

            </div>

        </div>


        <div class="progress-bar">

            <div
                class="progress-fill"
                style="width: <?= $profileCompletion ?>%;"
            ></div>

        </div>

    </section>


    <section class="overview-grid">


        <div class="overview-card profile-overview">

            <div class="card-header">

                <div>

                    <span class="section-label">
                        PROFILE OVERVIEW
                    </span>

                    <h2>
                        Your Profile
                    </h2>

                </div>

                <span class="status-badge">
                    <?= htmlspecialchars($accountStatus) ?>
                </span>

            </div>


            <div class="profile-details">

                <?php if ($profilePhoto !== ""): ?>

                    <img
                        src="<?= htmlspecialchars($profilePhoto) ?>"
                        alt="Profile photo"
                        class="avatar avatar-image"
                    >

                <?php else: ?>

                    <div class="avatar">
                        <?= htmlspecialchars($avatarInitial) ?>
                    </div>

                <?php endif; ?>


                <div>

                    <h3>
                        <?= htmlspecialchars($studentName) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($programDisplay) ?>
                    </p>

                    <span>
                        <?= htmlspecialchars($yearDisplay) ?>
                    </span>

                </div>

            </div>


            <div class="profile-meta">

                <div>

                    <span>
                        Student ID
                    </span>

                    <strong>
                        <?= htmlspecialchars($studentIdDisplay) ?>
                    </strong>

                </div>

                <?php if ($section !== ""): ?>

                    <div>

                        <span>
                            Section
                        </span>

                        <strong>
                            <?= htmlspecialchars($section) ?>
                        </strong>

                    </div>

                <?php endif; ?>

            </div>


            <a
                href="student_profile.php"
                class="text-link"
            >
                View full profile →
            </a>

        </div>


        <div class="overview-card visibility-card">

            <div class="card-header">

                <div>

                    <span class="section-label">
                        PROFILE VISIBILITY
                    </span>

                    <h2>
                        <?= htmlspecialchars($privacyVisibility) ?>
                    </h2>

                </div>

                <div class="visibility-icon">
                    ✓
                </div>

            </div>


            <p>

                <?php if ($privacyVisibility === "Public"): ?>

                    Your profile is currently visible publicly according
                    to your privacy settings.

                <?php elseif ($privacyVisibility === "School Only"): ?>

                    Your profile is currently visible to authorized
                    users within the school.

                <?php else: ?>

                    Your profile is currently private.

                <?php endif; ?>

            </p>


            <a
                href="settings.php"
                class="text-link"
            >
                Manage privacy settings →
            </a>

        </div>

    </section>


    <section class="quick-actions">

        <div class="section-heading">

            <div>

                <span class="section-label">
                    QUICK ACTIONS
                </span>

                <h2>
                    What would you like to do?
                </h2>

            </div>

        </div>


        <div class="action-grid">


            <a
                href="student_profile.php"
                class="action-card"
            >

                <div class="action-icon">
                    01
                </div>

                <div>

                    <h3>
                        View Profile
                    </h3>

                    <p>
                        Review your student information.
                    </p>

                </div>

                <span class="action-arrow">
                    →
                </span>

            </a>


            <a
                href="student_profile.php?edit=1"
                class="action-card"
            >

                <div class="action-icon">
                    02
                </div>

                <div>

                    <h3>
                        Edit Profile
                    </h3>

                    <p>
                        Update your personal and academic details.
                    </p>

                </div>

                <span class="action-arrow">
                    →
                </span>

            </a>


            <a
                href="accomplishments.php?action=add"
                class="action-card"
            >

                <div class="action-icon">
                    03
                </div>

                <div>

                    <h3>
                        Add Achievement
                    </h3>

                    <p>
                        Add a new accomplishment or certificate.
                    </p>

                </div>

                <span class="action-arrow">
                    →
                </span>

            </a>

        </div>

    </section>


    <section class="accomplishments-section">

        <div class="section-heading">

            <div>

                <span class="section-label">
                    RECENT ACCOMPLISHMENTS
                </span>

                <h2>
                    Your latest achievements
                </h2>

            </div>


            <a
                href="accomplishments.php"
                class="text-link"
            >
                View all →
            </a>

        </div>


        <div class="accomplishment-list">

            <?php if (count($recentAccomplishments) > 0): ?>

                <?php foreach ($recentAccomplishments as $accomplishment): ?>

                    <div class="accomplishment-item">

                        <div class="achievement-icon">
                            ✓
                        </div>


                        <div class="achievement-info">

                            <h3>
                                <?= htmlspecialchars(
                                    $accomplishment["title"]
                                ) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars(
                                    $accomplishment["category"]
                                ) ?>
                            </p>

                        </div>


                        <span class="achievement-date">

                            <?php

                            if (
                                !empty(
                                    $accomplishment["date_achieved"]
                                )
                            ) {

                                echo htmlspecialchars(
                                    date(
                                        "M d, Y",
                                        strtotime(
                                            $accomplishment["date_achieved"]
                                        )
                                    )
                                );

                            } else {

                                echo "Date not added";

                            }

                            ?>

                        </span>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-accomplishments">

                    <div class="empty-achievement-icon">
                        +
                    </div>

                    <div>

                        <h3>
                            No accomplishments yet
                        </h3>

                        <p>
                            Your latest accomplishments will appear here
                            after you add them.
                        </p>

                    </div>

                    <a
                        href="accomplishments.php?action=add"
                        class="empty-action"
                    >
                        Add accomplishment
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>


<footer class="footer">

    <p>
        StudentProfiler
    </p>

    <span>
        Manage your student profile with ease.
    </span>

</footer>


</body>
</html>