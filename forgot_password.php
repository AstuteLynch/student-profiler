<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";


$error = "";


/* ALREADY LOGGED IN */

if (
    isset(
        $_SESSION["user_id"]
    )
) {

    $role =
        getSessionRole();


    if (
        $role ===
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


    if (
        $email === ""
    ) {

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
                "Unable to process the request.";

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
                    "No CVSWHO account was found for that email address.";

            } elseif (
                (int) $user[
                    "email_verified"
                ] !== 1
            ) {

                $error =
                    "This email address has not been verified.";

            } elseif (
                in_array(
                    $user[
                        "account_status"
                    ],
                    [
                        "pending",
                        "archived",
                        "suspended"
                    ],
                    true
                )
            ) {

                $error =
                    "This account cannot currently reset its password.";

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


                    $emailSent =
                        sendAuthCode(
                            $user["email"],
                            $code,
                            "password_reset"
                        );


                    if (!$emailSent) {

                        throw new RuntimeException(
                            "Unable to send password reset email."
                        );
                    }


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


                    header(
                        "Location: auth.php"
                    );


                    exit;

                } catch (
                    Throwable $exception
                ) {

                    $error =
                        "Unable to send the password reset verification code. Please try again.";
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
                    Enter the email address connected
                    to your CVSWHO account.
                    A verification code will be sent
                    to your email.
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

                </div>


                <button
                    type="submit"
                    class="auth-button"
                >

                    Send Verification Code

                    <span class="button-arrow">
                        →
                    </span>

                </button>


            </form>


            <div class="auth-divider">

                <span>
                    ACCOUNT ACCESS
                </span>

            </div>


            <div class="auth-bottom">

                <a
                    href="login.php"
                    class="back-link"
                >
                    ← Back to login
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
                    Secure Password Recovery
                </strong>


                <p>
                    Your password can only be reset
                    after the verification code sent
                    to your registered email is confirmed.
                </p>

            </div>

        </div>


    </div>


</div>


</body>

</html>