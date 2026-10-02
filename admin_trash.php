<?php

session_start();

require_once "db.php";
require_once "admin_student_functions.php";

/* ADMIN */

$admin =
    requireAdministrator($conn);

$csrfToken =
    getAdminCsrfToken();

purgeExpiredStudentAccounts($conn);


/* MESSAGE */

$error = "";
$success = "";

if (isset($_GET["restored"])) {
    $success =
        "Student account restored successfully.";
}

if (isset($_GET["deleted"])) {
    $success =
        "Student account permanently deleted.";
}


/* ACTION */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !verifyAdminCsrfToken(
            $_POST["csrf_token"] ?? ""
        )
    ) {
        $error =
            "Your session expired. Please refresh the page.";

    } else {
        $action =
            $_POST["action"] ?? "";

        $studentUserId =
            (int) (
                $_POST[
                    "student_user_id"
                ] ?? 0
            );

        if ($action === "restore") {

            if (
                restoreStudentFromTrash(
                    $conn,
                    $studentUserId
                )
            ) {
                header(
                    "Location: admin_trash.php?restored=1"
                );
                exit;
            }

            $error =
                "Unable to restore the student account.";

        } elseif (
            $action === "delete_permanently"
        ) {

            if (
                permanentlyDeleteStudent(
                    $conn,
                    $studentUserId
                )
            ) {
                header(
                    "Location: admin_trash.php?deleted=1"
                );
                exit;
            }

            $error =
                "Unable to permanently delete the student account.";
        }
    }
}


/* TRASH */

$stmt =
    $conn->prepare("
        SELECT
            u.id AS user_id,
            u.email,

            sp.student_id,
            sp.first_name,
            sp.middle_name,
            sp.last_name,
            sp.program,
            sp.year_level,

            d.previous_account_status,
            d.deleted_at,
            d.purge_at,

            TIMESTAMPDIFF(
                DAY,
                NOW(),
                d.purge_at
            ) AS days_remaining

        FROM deleted_student_accounts d

        INNER JOIN users u
            ON u.id = d.user_id

        LEFT JOIN student_profiles sp
            ON sp.user_id = u.id

        WHERE
            u.role = 'student'

        ORDER BY
            d.purge_at ASC
    ");

if (!$stmt) {
    die(
        "Unable to load Trash."
    );
}

$stmt->execute();

$deletedStudents =
    $stmt
        ->get_result();

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
        Student Trash | CVSWHO
    </title>

    <link
        rel="stylesheet"
        href="student_records.css"
    >

    <style>
        .message {
            margin-bottom: 18px;
            padding: 12px 15px;
            border-radius: 9px;
            font-size: 10px;
        }

        .message.success {
            color: #18794e;
            background: #eaf7ef;
            border: 1px solid #cee8d9;
        }

        .message.error {
            color: #a23939;
            background: #fff0f0;
            border: 1px solid #efcccc;
        }

        .trash-notice {
            margin-bottom: 20px;
            padding: 15px;
            color: #68582e;
            background: #fff9e8;
            border: 1px solid #eadba9;
            border-radius: 9px;
            font-size: 10px;
            line-height: 1.7;
        }

        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .restore-button,
        .delete-button {
            padding: 8px 11px;
            border-radius: 7px;
            font-size: 8px;
            font-weight: 700;
            cursor: pointer;
        }

        .restore-button {
            color: #006b3f;
            background: #eff8f3;
            border: 1px solid #b8d9c8;
        }

        .delete-button {
            color: #a23939;
            background: #fff0f0;
            border: 1px solid #e8bcbc;
        }

        .delete-countdown {
            color: #a06023;
            font-size: 9px;
            font-weight: 700;
        }

        .empty-trash {
            padding: 45px 20px;
            color: #68766f;
            text-align: center;
        }

        .back-button {
            display: inline-flex;
            padding: 10px 15px;
            color: #006b3f;
            background: #eff8f3;
            border: 1px solid #cfe5d8;
            border-radius: 8px;
            font-size: 9px;
            font-weight: 700;
            text-decoration: none;
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
                class="nav-link"
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
                href="admin_trash.php"
                class="nav-link active"
            >
                Trash
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


<main class="page">

    <section class="page-header">

        <div>

            <span class="eyebrow">
                ADMINISTRATION
            </span>

            <h1>
                Student Trash
            </h1>

            <p>
                Restore removed student accounts or permanently delete them.
            </p>

        </div>

        <a
            href="student_records.php"
            class="back-button"
        >
            ← Student Records
        </a>

    </section>


    <div class="trash-notice">

        Student accounts remain in Trash for
        <strong>30 days</strong>.

        During this period they cannot use their
        account and their public student profile
        is hidden.

        After 30 days, the account is permanently
        deleted automatically.

    </div>


    <?php if ($success !== ""): ?>

        <div class="message success">

            <?= htmlspecialchars(
                $success
            ) ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="message error">

            <?= htmlspecialchars(
                $error
            ) ?>

        </div>

    <?php endif; ?>


    <section class="records-card">

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Student
                        </th>

                        <th>
                            Student ID
                        </th>

                        <th>
                            Program
                        </th>

                        <th>
                            Removed
                        </th>

                        <th>
                            Permanent Deletion
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (
                    $deletedStudents->num_rows === 0
                ): ?>

                    <tr>

                        <td
                            colspan="6"
                            class="empty-trash"
                        >
                            Trash is empty.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php while (
                        $student =
                            $deletedStudents
                                ->fetch_assoc()
                    ): ?>

                        <?php

                        $nameParts = [
                            trim(
                                $student[
                                    "first_name"
                                ] ?? ""
                            ),
                            trim(
                                $student[
                                    "middle_name"
                                ] ?? ""
                            ),
                            trim(
                                $student[
                                    "last_name"
                                ] ?? ""
                            )
                        ];

                        $nameParts =
                            array_filter(
                                $nameParts
                            );

                        $studentName =
                            trim(
                                implode(
                                    " ",
                                    $nameParts
                                )
                            );

                        if ($studentName === "") {
                            $studentName =
                                $student["email"];
                        }

                        $daysRemaining =
                            max(
                                0,
                                (int) $student[
                                    "days_remaining"
                                ]
                            );

                        ?>

                        <tr>

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $studentName
                                    ) ?>

                                </strong>

                                <br>

                                <small>

                                    <?= htmlspecialchars(
                                        $student["email"]
                                    ) ?>

                                </small>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $student[
                                        "student_id"
                                    ] ?? "Not added"
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $student[
                                        "program"
                                    ] ?? "Not added"
                                ) ?>

                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    date(
                                        "M d, Y",
                                        strtotime(
                                            $student[
                                                "deleted_at"
                                            ]
                                        )
                                    )
                                ) ?>

                            </td>

                            <td>

                                <span class="delete-countdown">

                                    <?= $daysRemaining ?>

                                    day<?= $daysRemaining === 1
                                        ? ""
                                        : "s" ?>
                                    remaining

                                </span>

                                <br>

                                <small>

                                    <?= htmlspecialchars(
                                        date(
                                            "M d, Y",
                                            strtotime(
                                                $student[
                                                    "purge_at"
                                                ]
                                            )
                                        )
                                    ) ?>

                                </small>

                            </td>

                            <td>

                                <div class="action-group">

                                    <form
                                        method="POST"
                                        action="admin_trash.php"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars(
                                                $csrfToken
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="restore"
                                        >

                                        <input
                                            type="hidden"
                                            name="student_user_id"
                                            value="<?= (int) $student["user_id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="restore-button"
                                        >
                                            Restore
                                        </button>

                                    </form>


                                    <form
                                        method="POST"
                                        action="admin_trash.php"
                                        onsubmit="return confirm('Permanently delete this student account? This cannot be undone.');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars(
                                                $csrfToken
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete_permanently"
                                        >

                                        <input
                                            type="hidden"
                                            name="student_user_id"
                                            value="<?= (int) $student["user_id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="delete-button"
                                        >
                                            Delete Permanently
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>


<footer class="footer">

    <p>
        CVSWHO
    </p>

    <span>
        Administrator Portal
    </span>

</footer>

</body>
</html>

<?php

$stmt->close();

?>