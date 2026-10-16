<?php

session_start();

require_once "db.php";

$error = "";
$success = "";


/* RESET AUTH */

if (
    empty(
        $_SESSION[
            "password_reset_verified"
        ]
    ) ||
    empty(
        $_SESSION[
            "password_reset_user_id"
        ]
    ) ||
    empty(
        $_SESSION[
            "password_reset_email"
        ]
    )
) {

    header(
        "Location: forgot_password.php"
    );

    exit;
}


$userId =
    (int) $_SESSION[
        "password_reset_user_id"
    ];


$email =
    $_SESSION[
        "password_reset_email"
    ];


/* LOAD ACCOUNT */

$stmt =
    $conn->prepare("
        SELECT
            id,
            email,
            role,
            account_status,
            email_verified
        FROM users
        WHERE id = ?
        LIMIT 1
    ");


if (!$stmt) {

    header(
        "Location: forgot_password.php"
    );

    exit;
}


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
    !$user ||
    strtolower(
        $user["email"]
    ) !== strtolower(
        $email
    ) ||
    (int) $user[
        "email_verified"
    ] !== 1
) {

    unset(
        $_SESSION[
            "password_reset_verified"
        ],
        $_SESSION[
            "password_reset_user_id"
        ],
        $_SESSION[
            "password_reset_email"
        ]
    );


    header(
        "Location: forgot_password.php"
    );

    exit;
}


/* RESET PASSWORD */

if (
    $_SERVER["REQUEST_METHOD"]
        === "POST"
) {

    $password =
        $_POST["password"]
        ?? "";


    $confirmPassword =
        $_POST["confirm_password"]
        ?? "";


    if (
        $password === "" ||
        $confirmPassword === ""
    ) {

        $error =
            "Please enter and confirm your new password.";

    } elseif (
        strlen(
            $password
        ) < 8
    ) {

        $error =
            "Password must be at least 8 characters.";

    } elseif (
        !preg_match(
            '/[A-Z]/',
            $password
        )
    ) {

        $error =
            "Password must contain at least one uppercase letter.";

    } elseif (
        !preg_match(
            '/[a-z]/',
            $password
        )
    ) {

        $error =
            "Password must contain at least one lowercase letter.";

    } elseif (
        !preg_match(
            '/[0-9]/',
            $password
        )
    ) {

        $error =
            "Password must contain at least one number.";

    } elseif (
        $password !==
        $confirmPassword
    ) {

        $error =
            "Passwords do not match.";

    } else {

        $passwordHash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        $update =
            $conn->prepare("
                UPDATE users
                SET password_hash = ?
                WHERE id = ?
            ");


        if (!$update) {

            $error =
                "Unable to update your password.";

        } else {

            $update->bind_param(
                "si",
                $passwordHash,
                $userId
            );


            if (
                $update->execute()
            ) {

                $update->close();


                unset(
                    $_SESSION[
                        "password_reset_verified"
                    ],
                    $_SESSION[
                        "password_reset_user_id"
                    ],
                    $_SESSION[
                        "password_reset_email"
                    ]
                );


                $_SESSION[
                    "password_reset_success"
                ] =
                    true;


                header(
                    "Location: reset_password.php?success=1"
                );


                exit;

            } else {

                $update->close();


                $error =
                    "Unable to update your password.";
            }
        }
    }
}


/* SUCCESS PAGE */

if (
    isset(
        $_GET["success"]
    ) &&
    !empty(
        $_SESSION[
            "password_reset_success"
        ]
    )
) {

    $success =
        "Your password has been changed successfully.";


    unset(
        $_SESSION[
            "password_reset_success"
        ]
    );
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
        Reset Password | CVSWHO
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

    <style>

        .alert-success {
            width: 100%;

            margin-bottom: 21px;

            padding: 12px 14px;

            color: #17643d;

            background: #edf8f2;

            border: 1px solid #cbe5d7;

            border-radius: 9px;

            font-size: 11px;

            line-height: 1.5;
        }

    </style>

</head>

<body>


<div class="page-wrapper">


    <div class="auth-container">


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


        <div class="auth-card">


            <div class="auth-header">

                <span class="section-label">
                    PASSWORD RECOVERY
                </span>

                <h1>
                    Create a new password
                </h1>

                <p>
                    Choose a secure new password for your CVSWHO account.
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


            <?php if (
                $success !== ""
            ): ?>

                <div class="alert-success">

                    <?= htmlspecialchars(
                        $success
                    ) ?>

                </div>


                <div class="auth-bottom">

                    <p>
                        You can now sign in using your new password.
                    </p>

                    <a
                        href="login.php"
                        class="auth-button"
                    >
                        Sign In
                    </a>

                </div>


            <?php else: ?>


                <form
                    method="POST"
                    action="reset_password.php"
                    class="auth-form"
                >


                    <div class="form-group">

                        <label for="password">
                            New Password
                        </label>


                        <div class="input-wrapper">

                            <span class="input-icon">
                                •
                            </span>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your new password"
                                autocomplete="new-password"
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


                        <span class="input-help">
                            At least 8 characters with uppercase,
                            lowercase, and a number.
                        </span>

                    </div>


                    <div class="form-group">

                        <label for="confirm_password">
                            Confirm New Password
                        </label>


                        <div class="input-wrapper">

                            <span class="input-icon">
                                •
                            </span>

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Confirm your new password"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                id="confirmPasswordToggle"
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

                        Change Password

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

                    <a
                        href="login.php"
                        class="back-link"
                    >
                        ← Back to login
                    </a>

                </div>


            <?php endif; ?>


        </div>


        <div class="auth-security">

            <div class="security-icon">
                ✓
            </div>

            <div>

                <strong>
                    Secure Password Update
                </strong>

                <p>
                    Your identity was verified using a temporary
                    verification code before this password reset.
                </p>

            </div>

        </div>


    </div>


</div>


<script>

    function setupPasswordToggle(
        inputId,
        toggleId
    ) {

        const input =
            document.getElementById(
                inputId
            );


        const toggle =
            document.getElementById(
                toggleId
            );


        if (
            !input ||
            !toggle
        ) {

            return;
        }


        toggle.addEventListener(
            "click",
            function () {

                const showing =
                    input.type ===
                    "text";


                input.type =
                    showing
                        ? "password"
                        : "text";


                toggle.textContent =
                    showing
                        ? "Show"
                        : "Hide";


                toggle.setAttribute(
                    "aria-label",
                    showing
                        ? "Show password"
                        : "Hide password"
                );
            }
        );
    }


    setupPasswordToggle(
        "password",
        "passwordToggle"
    );


    setupPasswordToggle(
        "confirm_password",
        "confirmPasswordToggle"
    );

</script>


</body>

</html>