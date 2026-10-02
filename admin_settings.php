<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";


requireAdmin($conn);


$adminId =
    (int) $_SESSION["user_id"];


$error = "";

$success = "";


/* CSRF */

if (
    empty(
        $_SESSION[
            "admin_settings_csrf"
        ]
    )
) {

    $_SESSION[
        "admin_settings_csrf"
    ] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION[
        "admin_settings_csrf"
    ];


/* LOGOUT */

if (isset($_GET["logout"])) {

    logoutUser(
        "login.php"
    );
}


/* CHANGE EMAIL */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "")
        === "change_email"
) {

    $token =
        $_POST["csrf_token"]
        ?? "";


    $newEmail =
        strtolower(
            trim(
                $_POST["new_email"]
                ?? ""
            )
        );


    $password =
        $_POST["current_password"]
        ?? "";


    if (
        !hash_equals(
            $csrfToken,
            $token
        )
    ) {

        $error =
            "Your form session expired.";

    } elseif (
        !filter_var(
            $newEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    } else {

        $stmt =
            $conn->prepare("
                SELECT
                    password_hash

                FROM users

                WHERE id = ?

                LIMIT 1
            ");


        $stmt->bind_param(
            "i",
            $adminId
        );


        $stmt->execute();


        $account =
            $stmt
                ->get_result()
                ->fetch_assoc();


        $stmt->close();


        if (
            !$account ||
            !password_verify(
                $password,
                $account[
                    "password_hash"
                ]
            )
        ) {

            $error =
                "Your current password is incorrect.";

        } else {

            $check =
                $conn->prepare("
                    SELECT id

                    FROM users

                    WHERE
                        LOWER(email) = ?
                        AND id != ?

                    LIMIT 1
                ");


            $check->bind_param(
                "si",
                $newEmail,
                $adminId
            );


            $check->execute();


            $exists =
                $check
                    ->get_result()
                    ->fetch_assoc();


            $check->close();


            if ($exists) {

                $error =
                    "That email address is already being used.";

            } else {

                $update =
                    $conn->prepare("
                        UPDATE users

                        SET
                            email = ?,
                            email_verified = 1

                        WHERE id = ?

                        LIMIT 1
                    ");


                $update->bind_param(
                    "si",
                    $newEmail,
                    $adminId
                );


                if ($update->execute()) {

                    $_SESSION[
                        "user_email"
                    ] =
                        $newEmail;


                    $success =
                        "Administrator email updated successfully.";

                } else {

                    $error =
                        "Unable to update your email.";
                }


                $update->close();
            }
        }
    }
}


/* CHANGE PASSWORD */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "")
        === "change_password"
) {

    $token =
        $_POST["csrf_token"]
        ?? "";


    $currentPassword =
        $_POST[
            "current_password"
        ] ?? "";


    $newPassword =
        $_POST[
            "new_password"
        ] ?? "";


    $confirmPassword =
        $_POST[
            "confirm_password"
        ] ?? "";


    if (
        !hash_equals(
            $csrfToken,
            $token
        )
    ) {

        $error =
            "Your form session expired.";

    } elseif (
        strlen(
            $newPassword
        ) < 8
    ) {

        $error =
            "Your new password must contain at least 8 characters.";

    } elseif (
        !preg_match(
            '/[A-Z]/',
            $newPassword
        ) ||
        !preg_match(
            '/[a-z]/',
            $newPassword
        ) ||
        !preg_match(
            '/[0-9]/',
            $newPassword
        )
    ) {

        $error =
            "Your password must contain uppercase, lowercase, and a number.";

    } elseif (
        $newPassword !==
        $confirmPassword
    ) {

        $error =
            "The new passwords do not match.";

    } else {

        $stmt =
            $conn->prepare("
                SELECT password_hash

                FROM users

                WHERE id = ?

                LIMIT 1
            ");


        $stmt->bind_param(
            "i",
            $adminId
        );


        $stmt->execute();


        $account =
            $stmt
                ->get_result()
                ->fetch_assoc();


        $stmt->close();


        if (
            !$account ||
            !password_verify(
                $currentPassword,
                $account[
                    "password_hash"
                ]
            )
        ) {

            $error =
                "Your current password is incorrect.";

        } else {

            $newHash =
                password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );


            $update =
                $conn->prepare("
                    UPDATE users

                    SET password_hash = ?

                    WHERE id = ?

                    LIMIT 1
                ");


            $update->bind_param(
                "si",
                $newHash,
                $adminId
            );


            if ($update->execute()) {

                session_regenerate_id(
                    true
                );


                $success =
                    "Administrator password updated successfully.";

            } else {

                $error =
                    "Unable to update your password.";
            }


            $update->close();
        }
    }
}


/* ADMIN */

$stmt =
    $conn->prepare("
        SELECT

            email,
            role,
            account_status,
            email_verified,
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
        Administrator Settings | CVSWHO
    </title>

    <link
        rel="stylesheet"
        href="admin_settings.css"
    >

    <style>

        .message {
            margin-bottom: 18px;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 9px;
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

        .settings-form {
            margin-top: 17px;
            display: grid;
            gap: 13px;
        }

        .settings-form label {
            display: block;
            margin-bottom: 6px;
            color: #003d24;
            font-size: 9px;
            font-weight: 700;
        }

        .settings-form input {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #dce7e0;
            border-radius: 8px;
            font: inherit;
            font-size: 9px;
        }

        .save-button {
            width: fit-content;
            padding: 10px 15px;
            color: white;
            background: #006b3f;
            border: none;
            border-radius: 7px;
            font: inherit;
            font-size: 9px;
            font-weight: 700;
            cursor: pointer;
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
                href="admin_settings.php"
                class="nav-link active"
            >
                Settings
            </a>

        </nav>


        <a
            href="admin_settings.php?logout=1"
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
            Administrator Settings
        </h1>

        <p>
            Manage your administrator account
            information and security.
        </p>

    </section>


    <?php if (
        $success !== ""
    ): ?>

        <div class="message success">

            <?= htmlspecialchars(
                $success
            ) ?>

        </div>

    <?php endif; ?>


    <?php if (
        $error !== ""
    ): ?>

        <div class="message error">

            <?= htmlspecialchars(
                $error
            ) ?>

        </div>

    <?php endif; ?>


    <!-- ACCOUNT -->

    <section class="settings-card">

        <span class="section-label">
            ADMINISTRATOR ACCOUNT
        </span>

        <h2>
            Account Information
        </h2>


        <div class="account-profile">

            <div class="profile-icon">
                A
            </div>

            <div>

                <h3>
                    Administrator
                </h3>

                <p>
                    <?= htmlspecialchars(
                        $administrator[
                            "email"
                        ]
                    ) ?>
                </p>

                <span>
                    System Administrator
                </span>

            </div>

        </div>


        <div class="information-grid">

            <div>

                <span>Email</span>

                <strong>
                    <?= htmlspecialchars(
                        $administrator[
                            "email"
                        ]
                    ) ?>
                </strong>

            </div>

            <div>

                <span>Role</span>

                <strong>
                    Administrator
                </strong>

            </div>

            <div>

                <span>Status</span>

                <strong class="active">
                    <?= htmlspecialchars(
                        ucfirst(
                            $administrator[
                                "account_status"
                            ]
                        )
                    ) ?>
                </strong>

            </div>

            <div>

                <span>Last Login</span>

                <strong>

                    <?= htmlspecialchars(
                        $administrator[
                            "last_login_at"
                        ] ?? "Not available"
                    ) ?>

                </strong>

            </div>

        </div>

    </section>


    <!-- EMAIL -->

    <section class="settings-card">

        <span class="section-label">
            EMAIL
        </span>

        <h2>
            Change Administrator Email
        </h2>

        <p>
            Administrator accounts may use
            standard email addresses and are not
            restricted to the CvSU student format.
        </p>


        <form
            method="POST"
            class="settings-form"
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
                value="change_email"
            >


            <div>

                <label for="new_email">
                    New Email Address
                </label>

                <input
                    type="email"
                    id="new_email"
                    name="new_email"
                    value="<?= htmlspecialchars(
                        $administrator[
                            "email"
                        ]
                    ) ?>"
                    required
                >

            </div>


            <div>

                <label for="email_password">
                    Current Password
                </label>

                <input
                    type="password"
                    id="email_password"
                    name="current_password"
                    required
                >

            </div>


            <button
                type="submit"
                class="save-button"
            >
                Update Email
            </button>

        </form>

    </section>


    <!-- PASSWORD -->

    <section class="settings-card">

        <span class="section-label">
            SECURITY
        </span>

        <h2>
            Change Password
        </h2>


        <form
            method="POST"
            class="settings-form"
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
                value="change_password"
            >


            <div>

                <label for="current_password">
                    Current Password
                </label>

                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    required
                >

            </div>


            <div>

                <label for="new_password">
                    New Password
                </label>

                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    required
                >

            </div>


            <div>

                <label for="confirm_password">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                >

            </div>


            <button
                type="submit"
                class="save-button"
            >
                Change Password
            </button>

        </form>

    </section>


    <!-- PERMISSIONS -->

    <section class="settings-card">

        <span class="section-label">
            ADMINISTRATOR PERMISSIONS
        </span>

        <h2>
            Account Permissions
        </h2>


        <div class="permission-list">

            <div class="permission">

                <div>

                    <h3>
                        View Student Records
                    </h3>

                    <p>
                        Review registered student information.
                    </p>

                </div>

                <span class="enabled">
                    Enabled
                </span>

            </div>


            <div class="permission">

                <div>

                    <h3>
                        Manage Student Account Status
                    </h3>

                    <p>
                        Activate, suspend, and archive
                        student accounts.
                    </p>

                </div>

                <span class="enabled">
                    Enabled
                </span>

            </div>


            <div class="permission">

                <div>

                    <h3>
                        Modify Student Content
                    </h3>

                    <p>
                        Editing student profile content
                        is reserved for the student.
                    </p>

                </div>

                <span>
                    Disabled
                </span>

            </div>

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