<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";


requireAdmin($conn);


$adminId =
    (int) $_SESSION["user_id"];


/* LOGOUT */

if (isset($_GET["logout"])) {

    logoutUser(
        "login.php"
    );
}


/* ADMIN */

$stmt =
    $conn->prepare("
        SELECT
            email,
            last_login_at,
            created_at

        FROM users

        WHERE id = ?

        LIMIT 1
    ");


$stmt->bind_param(
    "i",
    $adminId
);


$stmt->execute();


$administrator =
    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();


/* SUMMARY */

$summary = [

    "total" => 0,
    "active" => 0,
    "pending" => 0,
    "suspended" => 0,
    "archived" => 0,
    "deactivated" => 0
];


$stmt =
    $conn->prepare("
        SELECT

            COUNT(*) AS total,

            SUM(
                account_status = 'active'
            ) AS active,

            SUM(
                account_status = 'pending'
            ) AS pending,

            SUM(
                account_status = 'suspended'
            ) AS suspended,

            SUM(
                account_status = 'archived'
            ) AS archived,

            SUM(
                account_status = 'deactivated'
            ) AS deactivated

        FROM users

        WHERE role = 'student'
    ");


$stmt->execute();


$row =
    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();


if ($row) {

    foreach (
        $summary
        as $key => $value
    ) {

        $summary[$key] =
            (int) (
                $row[$key]
                ?? 0
            );
    }
}


/* RECENT STUDENTS */

$recentStudents = [];


$stmt =
    $conn->prepare("
        SELECT

            u.id,
            u.email,
            u.account_status,
            u.created_at,

            sp.first_name,
            sp.last_name,
            sp.student_id,
            sp.program

        FROM users u

        LEFT JOIN student_profiles sp
            ON sp.user_id = u.id

        WHERE u.role = 'student'

        ORDER BY u.created_at DESC

        LIMIT 6
    ");


$stmt->execute();


$result =
    $stmt->get_result();


while (
    $student =
        $result->fetch_assoc()
) {

    $recentStudents[] =
        $student;
}


$stmt->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Administrator Dashboard | CVSWHO
    </title>

    <style>

        :root {
            --primary: #006b3f;
            --deep: #004d2a;
            --dark: #003d24;
            --light: #e6f3eb;
            --background: #f5f8f6;
            --border: #dce7e0;
            --text: #1d2b24;
            --muted: #68766f;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            background: var(--background);
            color: var(--text);
            font-family: Inter, Arial, sans-serif;
        }

        a {
            text-decoration: none;
        }

        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
        }

        .nav-container {
            width: min(1180px, calc(100% - 40px));
            min-height: 72px;
            margin: auto;
            display: flex;
            align-items: center;
            gap: 35px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: auto;
        }

        .brand-mark {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            color: white;
            background: var(--primary);
            font-weight: 800;
        }

        .brand-text span {
            display: block;
        }

        .brand-name {
            color: var(--dark);
            font-size: 12px;
            font-weight: 800;
        }

        .brand-subtitle {
            margin-top: 2px;
            color: var(--muted);
            font-size: 8px;
        }

        .desktop-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-link {
            color: var(--muted);
            font-size: 9px;
            font-weight: 700;
        }

        .nav-link.active {
            color: var(--primary);
        }

        .logout-button {
            padding: 9px 13px;
            color: var(--primary);
            border: 1px solid var(--border);
            border-radius: 7px;
            font-size: 9px;
            font-weight: 700;
        }

        .page {
            width: min(1120px, calc(100% - 40px));
            margin: 38px auto 60px;
        }

        .eyebrow {
            color: var(--primary);
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .page-header h1 {
            margin-top: 7px;
            color: var(--dark);
            font-size: 30px;
        }

        .page-header p {
            margin-top: 8px;
            color: var(--muted);
            font-size: 10px;
        }

        .summary-grid {
            margin-top: 25px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .summary-card,
        .content-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .summary-card {
            padding: 20px;
        }

        .summary-card span {
            color: var(--muted);
            font-size: 8px;
            font-weight: 700;
        }

        .summary-card strong {
            display: block;
            margin-top: 8px;
            color: var(--primary);
            font-size: 25px;
        }

        .content-card {
            margin-top: 20px;
            padding: 22px;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 15px;
        }

        .section-heading h2 {
            color: var(--dark);
            font-size: 16px;
        }

        .primary-button {
            padding: 9px 13px;
            color: white;
            background: var(--primary);
            border-radius: 7px;
            font-size: 9px;
            font-weight: 700;
        }

        .student-list {
            border-top: 1px solid var(--border);
        }

        .student-row {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 3px;
            border-bottom: 1px solid var(--border);
        }

        .student-row-content {
            flex: 1;
        }

        .student-row h3 {
            color: var(--dark);
            font-size: 10px;
        }

        .student-row p {
            margin-top: 4px;
            color: var(--muted);
            font-size: 8px;
        }

        .status {
            padding: 5px 8px;
            background: var(--light);
            color: var(--primary);
            border-radius: 20px;
            font-size: 7px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .view-button {
            color: var(--primary);
            font-size: 8px;
            font-weight: 700;
        }

        @media (max-width: 800px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .desktop-nav {
                display: none;
            }
        }

        @media (max-width: 500px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>

<body>


<header class="navbar">

    <div class="nav-container">

        <a
            href="admin_dashboard.php"
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
                    Administrator Portal
                </span>

            </div>

        </a>


        <nav class="desktop-nav">

            <a
                href="admin_dashboard.php"
                class="nav-link active"
            >
                Dashboard
            </a>

            <a
                href="student_records.php"
                class="nav-link"
            >
                Student Records
            </a>

            <a
                href="admin_settings.php"
                class="nav-link"
            >
                Settings
            </a>

        </nav>


        <a
            href="admin_dashboard.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>

    </div>

</header>


<main class="page">


    <section class="page-header">

        <span class="eyebrow">
            ADMINISTRATION
        </span>

        <h1>
            Administrator Dashboard
        </h1>

        <p>
            Manage student accounts and monitor
            CVSWHO student records.
        </p>

    </section>


    <section class="summary-grid">

        <div class="summary-card">
            <span>TOTAL STUDENTS</span>
            <strong><?= $summary["total"] ?></strong>
        </div>

        <div class="summary-card">
            <span>ACTIVE STUDENTS</span>
            <strong><?= $summary["active"] ?></strong>
        </div>

        <div class="summary-card">
            <span>PENDING ACCOUNTS</span>
            <strong><?= $summary["pending"] ?></strong>
        </div>

        <div class="summary-card">
            <span>RESTRICTED / INACTIVE</span>

            <strong>
                <?= $summary["suspended"] +
                    $summary["archived"] +
                    $summary["deactivated"]
                ?>
            </strong>
        </div>

    </section>


    <section class="content-card">


        <div class="section-heading">

            <div>

                <span class="eyebrow">
                    RECENT ACCOUNTS
                </span>

                <h2>
                    Recently Registered Students
                </h2>

            </div>

            <a
                href="student_records.php"
                class="primary-button"
            >
                View All Students
            </a>

        </div>


        <div class="student-list">

            <?php foreach (
                $recentStudents
                as $student
            ): ?>

                <?php

                $name =
                    trim(
                        ($student["first_name"] ?? "") .
                        " " .
                        ($student["last_name"] ?? "")
                    );


                if ($name === "") {

                    $name =
                        $student["email"];
                }

                ?>


                <div class="student-row">

                    <div class="student-row-content">

                        <h3>
                            <?= htmlspecialchars(
                                $name
                            ) ?>
                        </h3>

                        <p>

                            <?= htmlspecialchars(
                                $student[
                                    "student_id"
                                ] ?? "No student ID"
                            ) ?>

                            •

                            <?= htmlspecialchars(
                                $student[
                                    "program"
                                ] ?? "Program not added"
                            ) ?>

                        </p>

                    </div>

                    <span class="status">

                        <?= htmlspecialchars(
                            $student[
                                "account_status"
                            ]
                        ) ?>

                    </span>

                    <a
                        href="admin_student_profile.php?id=<?= (int) $student["id"] ?>"
                        class="view-button"
                    >
                        Open Profile →
                    </a>

                </div>

            <?php endforeach; ?>

        </div>


    </section>


</main>


</body>

</html>