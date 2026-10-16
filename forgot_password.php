<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";

$error = "";


/* ALREADY LOGGED IN */

if (isset($_SESSION["user_id"])) {

    $role =
        $_SESSION["user_role"]
        ?? $_SESSION["role"]
        ?? "student";

    if ($role === "administrator") {

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


/* REQUEST RESET */

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


    if ($email === "") {

        $error =
            "Please enter your email address.";

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
                    role,
                    account_status,
                    email_verified
                FROM users
                WHERE LOWER(email) = ?
                LIMIT 1
            ");


        if (!$stmt) {

            $error =
                "Unable to process your request right now.";

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


            if (!$user) {

                $error =
                    "No CVSWHO account was found using that email address.";

            } elseif (
                !in_array(
                    $user["role"],
                    [
                        "student",
                        "administrator"
                    ],
                    true
                )
            ) {

                $error =
                    "This account cannot reset its password here.";

            } elseif (
                $user["role"] === "student" &&
                !isCvsuEmail(
                    $user["email"]
                )
            ) {

                $error =
                    "Student accounts must use a valid CvSU email address.";

            } elseif (
                $user["account_status"] === "archived"
            ) {

                $error =
                    "This account is currently unavailable.";

            } elseif (
                $user["account_status"] === "suspended"
            ) {

                $error =
                    "This account is currently suspended.";

            } elseif (
                $user["account_status"] === "pending"
            ) {

                $error =
                    "This account has not completed registration.";

            } elseif (
                (int) $user["email_verified"] !== 1
            ) {

                $error =
                    "This email address has not been verified.";

            } else {

                try {

                    $userId =
                        (int) $user["id"];


                    $code =
                        createAuthCode(
                            $conn,
                            $userId,
                            "password_reset"
                        );


                    sendAuthCode(
                        $user["email"],
                        $code,
                        "password_reset"
                    );


                    $_SESSION[
                        "pending_auth_user_id"
                    ] =
                        $userId;


                    $_SESSION[
                        "pending_auth_purpose"
                    ] =
                        "password_reset";


                    $_SESSION[
                        "pending_auth_email"
                    ] =
                        $user["email"];


                    $_SESSION[
                        "pending_auth_role"
                    ] =
                        $user["role"];


                    /* DEVELOPMENT ONLY */

                    $_SESSION[
                        "dev_auth_code"
                    ] =
                        $code;


                    unset(
                        $_SESSION[
                            "password_reset_verified"
                        ]
                    );


                    header(
                        "Location: auth.php"
                    );

                    exit;

                } catch (Throwable $exception) {

                    $error =
                        "Unable to generate your password reset verification code.";
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
        Forgot Password | CVSWHO
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
                    ACCOUNT RECOVERY
                </span>

                <h1>
                    Forgot your password?
                </h1>

                <p>
                    Enter the email address connected to your CVSWHO account.
                    We will verify your account before allowing you to create
                    a new password.
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


            <form
                method="POST"
                action="forgot_password.php"
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
                        Students use their CvSU school email.
                        Administrators use their assigned administrator email.
                    </span>

                </div>


                <button
                    type="submit"
                    class="auth-button"
                >

                    Continue

                    <span class="button-arrow">
                        →
                    </span>

                </button>


            </form>


            <div class="auth-divider">

                <span>
                    ACCOUNT RECOVERY
                </span>

            </div>


            <div class="auth-bottom">

                <p>
                    Remember your password?

                    <a href="login.php">
                        Sign in
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


        <div class="auth-security">

            <div class="security-icon">
                ✓
            </div>

            <div>

                <strong>
                    Secure Password Recovery
                </strong>

                <p>
                    Your account must be verified using a temporary
                    verification code before your password can be changed.
                </p>

            </div>

        </div>


    </div>


</div>


</body>

</html>