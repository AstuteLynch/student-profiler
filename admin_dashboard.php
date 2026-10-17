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

    <link
        rel="stylesheet"
        href="admin_dashboard.css"
    >

</head>

<body>


<!-- NAVIGATION -->

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
            href="logout.php"
            class="logout-button"
        >
            Log out
        </a>

    </div>

</header>


<!-- PAGE -->

<main class="page">


    <!-- HEADER -->

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


    <!-- SUMMARY -->

    <section class="summary-grid">

        <div class="summary-card">

            <span>
                TOTAL STUDENTS
            </span>

            <strong>
                <?= $summary["total"] ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                ACTIVE STUDENTS
            </span>

            <strong>
                <?= $summary["active"] ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                PENDING ACCOUNTS
            </span>

            <strong>
                <?= $summary["pending"] ?>
            </strong>

        </div>


        <div class="summary-card">

            <span>
                RESTRICTED / INACTIVE
            </span>

            <strong>

                <?= $summary["suspended"] +
                    $summary["archived"] +
                    $summary["deactivated"]
                ?>

            </strong>

        </div>

    </section>


    <!-- RECENT STUDENTS -->

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