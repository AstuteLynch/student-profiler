<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";

$error = "";


/* LOGGED IN */

if (isset($_SESSION["user_id"])) {

    $userId =
        (int) $_SESSION["user_id"];


    $stmt =
        $conn->prepare("
            SELECT
                role,
                account_status,
                email_verified
            FROM users
            WHERE id = ?
            LIMIT 1
        ");


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $userId
        );


        $stmt->execute();


        $user =
            $stmt
                ->get_result()
                ->fetch_assoc();


        $stmt->close();


        if (
            $user &&
            $user["account_status"] === "active" &&
            (int) $user["email_verified"] === 1
        ) {

            if (
                $user["role"] ===
                "administrator"
            ) {

                header(
                    "Location: admin_dashboard.php"
                );

            } else {

                header(
                    "Location: student_dashboard.php"
                );
            }


            exit;
        }
    }


    unset(
        $_SESSION["user_id"],
        $_SESSION["user_role"],
        $_SESSION["role"],
        $_SESSION["user_email"]
    );
}


/* LOGIN */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $email =
        strtolower(
            trim(
                $_POST["email"]
                ?? ""
            )
        );


    $password =
        $_POST["password"]
        ?? "";


    if (
        $email === "" ||
        $password === ""
    ) {

        $error =
            "Please enter your email and password.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    } else {

        $stmt =
            $conn->prepare("
                SELECT
                    id,
                    email,
                    password_hash,
                    role,
                    account_status,
                    email_verified
                FROM users
                WHERE LOWER(email) = ?
                LIMIT 1
            ");


        if (!$stmt) {

            $error =
                "Unable to process your login right now.";

        } else {

            $stmt->bind_param(
                "s",
                $email
            );


            $stmt->execute();


            $user =
                $stmt
                    ->get_result()
                    ->fetch_assoc();


            $stmt->close();


            if (
                !$user ||
                !password_verify(
                    $password,
                    $user["password_hash"]
                )
            ) {

                $error =
                    "Invalid email or password.";

            } elseif (
                $user["role"] === "student" &&
                !isCvsuEmail(
                    $user["email"]
                )
            ) {

                $error =
                    "Student accounts must use a valid CvSU email address.";

            } elseif (
                $user["account_status"]
                === "suspended"
            ) {

                $error =
                    "This account is currently suspended.";

            } elseif (
                $user["account_status"]
                === "archived"
            ) {

                $error =
                    "This account is currently archived.";

            } elseif (
                $user["account_status"]
                === "pending"
            ) {

                $error =
                    "This account is still pending verification.";

            } elseif (
                $user["account_status"]
                === "deactivated"
            ) {

                $_SESSION[
                    "reactivation_user_id"
                ] =
                    (int) $user["id"];


                $_SESSION[
                    "reactivation_email"
                ] =
                    $user["email"];


                header(
                    "Location: reactivate_account.php"
                );

                exit;

            } elseif (
                $user["account_status"]
                !== "active"
            ) {

                $error =
                    "This account is not currently active.";

            } elseif (
                (int) $user[
                    "email_verified"
                ] !== 1
            ) {

                $error =
                    "This account has not been verified.";

            } else {

                try {

                    $code =
                        createAuthCode(
                            $conn,
                            (int) $user["id"],
                            "login"
                        );


                    /*
                        Email sending can fail locally
                        before PHPMailer is configured.

                        We still generate the OTP
                        for local development testing.
                    */

                    sendAuthCode(
                        $user["email"],
                        $code,
                        "login"
                    );


                    $_SESSION[
                        "pending_auth_user_id"
                    ] =
                        (int) $user["id"];


                    $_SESSION[
                        "pending_auth_purpose"
                    ] =
                        "login";


                    $_SESSION[
                        "pending_auth_email"
                    ] =
                        $user["email"];


                    $_SESSION[
                        "pending_auth_role"
                    ] =
                        $user["role"];


                    /*
                        DEVELOPMENT ONLY

                        auth.php displays this OTP
                        while PHPMailer is not yet configured.
                    */

                    $_SESSION[
                        "dev_auth_code"
                    ] =
                        $code;


                    header(
                        "Location: auth.php"
                    );

                    exit;

                } catch (Throwable $exception) {

                    $error =
                        "Unable to generate your verification code.";
                }
            }
        }
    }
}

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
        Login | CVSWHO
    </title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="login.css"
    >

</head>

<body>


<div class="page-wrapper">


    <div class="auth-container">


        <!-- BRAND -->

        <div class="auth-brand">

            <a
                href="index.php"
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

        </div>


        <!-- CARD -->

        <div class="auth-card">


            <div class="auth-header">

                <span class="section-label">
                    ACCOUNT ACCESS
                </span>

                <h1>
                    Welcome back
                </h1>

                <p>
                    Sign in to continue to your CVSWHO account.
                </p>

            </div>


            <?php if (
                $error !== ""
            ): ?>

                <div class="alert alert-error">

                    <span class="alert-icon">
                        !
                    </span>

                    <span>

                        <?= htmlspecialchars(
                            $error
                        ) ?>

                    </span>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form
                method="POST"
                action="login.php"
                class="auth-form"
            >


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            @
                        </span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email address"
                            value="<?= htmlspecialchars(
                                $_POST["email"]
                                ?? ""
                            ) ?>"
                            autocomplete="email"
                            required
                        >

                    </div>

                    <span class="input-help">
                        CvSU students use their school email.
                        Administrators use their assigned administrator email.
                    </span>

                </div>


                <div class="form-group">

                    <div class="label-row">

                        <label for="password">
                            Password
                        </label>

                    </div>


                    <div class="input-wrapper">

                        <span class="input-icon">
                            •
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Show password"
                        >
                            Show
                        </button>

                    </div>

                </div>


                <button
                    type="submit"
                    class="auth-button"
                >

                    Sign In

                    <span class="button-arrow">
                        →
                    </span>

                </button>


            </form>


            <!-- DIVIDER -->

            <div class="auth-divider">

                <span>
                    STUDENT ACCESS
                </span>

            </div>


            <!-- REGISTER -->

            <div class="auth-bottom">

                <p>
                    Don't have a student account?

                    <a href="register.php">
                        Create an account
                    </a>
                </p>

                <a
                    href="index.php"
                    class="back-link"
                >
                    ← Back to home
                </a>

            </div>


        </div>


        <!-- SECURITY -->

        <div class="auth-security">

            <div class="security-icon">
                ✓
            </div>

            <div>

                <strong>
                    Secure CVSWHO Access
                </strong>

                <p>
                    Student and administrator accounts
                    use the same secure login and are
                    automatically directed to the correct portal.
                </p>

            </div>

        </div>


        <p class="login-note">
            Administrator accounts cannot be created
            through student registration.
        </p>


    </div>


</div>


<script>

    const passwordInput =
        document.getElementById(
            "password"
        );


    const passwordToggle =
        document.getElementById(
            "passwordToggle"
        );


    passwordToggle.addEventListener(
        "click",
        function () {

            const showing =
                passwordInput.type ===
                "text";


            passwordInput.type =
                showing
                    ? "password"
                    : "text";


            passwordToggle.textContent =
                showing
                    ? "Show"
                    : "Hide";


            passwordToggle.setAttribute(
                "aria-label",
                showing
                    ? "Show password"
                    : "Hide password"
            );
        }
    );

</script>


</body>

</html>