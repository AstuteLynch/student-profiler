<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";


$error = "";


/* =========================================================
   ALREADY LOGGED IN
========================================================= */

if (isset($_SESSION["user_id"])) {

    $loggedUserId =
        (int) $_SESSION["user_id"];


    $roleStmt =
        $conn->prepare("
            SELECT role

            FROM users

            WHERE id = ?

            LIMIT 1
        ");


    if ($roleStmt) {

        $roleStmt->bind_param(
            "i",
            $loggedUserId
        );


        $roleStmt->execute();


        $loggedUser =
            $roleStmt
                ->get_result()
                ->fetch_assoc();


        $roleStmt->close();


        if (
            ($loggedUser["role"] ?? "")
            === "administrator"
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


    header(
        "Location: student_dashboard.php"
    );

    exit;
}


/* =========================================================
   LOGIN SUBMISSION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
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


    /* =====================================================
       BASIC VALIDATION
    ===================================================== */

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


        /* =================================================
           GET ACCOUNT
        ================================================= */

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

                WHERE email = ?

                LIMIT 1
            ");


        if (!$stmt) {

            $error =
                "Unable to process login right now.";

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


            /* =============================================
               INVALID EMAIL / PASSWORD

               Use one generic message so outsiders cannot
               determine whether a specific email exists.
            ============================================= */

            if (
                !$user ||
                !password_verify(
                    $password,
                    $user[
                        "password_hash"
                    ]
                )
            ) {

                $error =
                    "Invalid email or password.";


            /* =============================================
               ACCOUNT NOT VERIFIED
            ============================================= */

            } elseif (
                !(bool) $user[
                    "email_verified"
                ]
            ) {

                $error =
                    "This account has not been verified yet. Please complete registration verification first.";


            /* =============================================
               ACCOUNT STATUS
            ============================================= */

            } elseif (
                $user[
                    "account_status"
                ] !== "active"
            ) {

                if (
                    $user[
                        "account_status"
                    ] === "suspended"
                ) {

                    $error =
                        "This account is currently suspended.";

                } elseif (
                    $user[
                        "account_status"
                    ] === "deactivated"
                ) {

                    $error =
                        "This account is currently deactivated.";

                } elseif (
                    $user[
                        "account_status"
                    ] === "archived"
                ) {

                    $error =
                        "This account is currently archived.";

                } else {

                    $error =
                        "This account is not currently active.";
                }


            /* =============================================
               PASSWORD IS CORRECT

               DO NOT LOGIN YET.

               Generate the exact same temporary
               verification behavior as registration.
            ============================================= */

            } else {

                $userId =
                    (int) $user["id"];


                $role =
                    $user["role"]
                    ?? "student";


                /*
                 * Create six-digit temporary code.
                 *
                 * Same behavior as register.php.
                 */

                $code =
                    str_pad(
                        (string) random_int(
                            0,
                            999999
                        ),
                        6,
                        "0",
                        STR_PAD_LEFT
                    );


                /*
                 * Remove any older temporary
                 * authentication information first.
                 */

                unset(
                    $_SESSION[
                        "pending_auth_user_id"
                    ],
                    $_SESSION[
                        "pending_auth_purpose"
                    ],
                    $_SESSION[
                        "pending_auth_email"
                    ],
                    $_SESSION[
                        "pending_auth_role"
                    ],
                    $_SESSION[
                        "temporary_auth_code"
                    ],
                    $_SESSION[
                        "temporary_auth_expires"
                    ]
                );


                /*
                 * Store login verification in session.
                 */

                $_SESSION[
                    "pending_auth_user_id"
                ] = $userId;


                $_SESSION[
                    "pending_auth_purpose"
                ] = "login";


                $_SESSION[
                    "pending_auth_email"
                ] = $user["email"];


                $_SESSION[
                    "pending_auth_role"
                ] = $role;


                $_SESSION[
                    "temporary_auth_code"
                ] = $code;


                $_SESSION[
                    "temporary_auth_expires"
                ] = time() + 600;


                /*
                 * Password was correct,
                 * but the user is NOT authenticated yet.
                 *
                 * auth.php must verify the code first.
                 */

                header(
                    "Location: auth.php"
                );

                exit;
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


        <div class="auth-card">


            <!-- =========================================
                 BRAND
            ========================================== -->

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
                            Student Profile Platform
                        </span>


                    </div>


                </a>


            </div>


            <!-- =========================================
                 HEADER
            ========================================== -->

            <div class="auth-header">


                <h1>
                    Welcome back
                </h1>


                <p>
                    Sign in to continue managing
                    your student profile.
                </p>


            </div>


            <!-- =========================================
                 ERROR
            ========================================== -->

            <?php if (
                $error !== ""
            ): ?>


                <div
                    class="
                        alert
                        alert-error
                    "
                >

                    <?= htmlspecialchars(
                        $error
                    ) ?>

                </div>


            <?php endif; ?>


            <!-- =========================================
                 LOGIN FORM
            ========================================== -->

            <form
                method="POST"
                action=""
                class="auth-form"
            >


                <div class="form-group">


                    <label for="email">
                        Email Address
                    </label>


                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="firstname.lastname@cvsu.edu.ph"
                        value="<?= htmlspecialchars(
                            $_POST["email"]
                            ?? ""
                        ) ?>"
                        autocomplete="email"
                        required
                    >


                </div>


                <div class="form-group">


                    <label for="password">
                        Password
                    </label>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >


                </div>


                <button
                    type="submit"
                    class="auth-button"
                >
                    Continue
                </button>


            </form>


            <!-- =========================================
                 BOTTOM LINKS
            ========================================== -->

            <div class="auth-bottom">


                <p>

                    Don't have an account?

                    <a href="register.php">
                        Register
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


    </div>


</div>


</body>

</html>