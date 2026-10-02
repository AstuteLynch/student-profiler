<?php

session_start();

require_once "db.php";


$error = "";

$showReactivation = false;


/* ALREADY LOGGED IN */

if (isset($_SESSION["user_id"])) {

    $loggedUserId =
        (int) $_SESSION["user_id"];


    $stmt =
        $conn->prepare("
            SELECT
                role,
                account_status

            FROM users

            WHERE id = ?

            LIMIT 1
        ");


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $loggedUserId
        );


        $stmt->execute();


        $loggedUser =
            $stmt
                ->get_result()
                ->fetch_assoc();


        $stmt->close();


        if (
            $loggedUser &&
            $loggedUser["account_status"]
                === "active"
        ) {

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
    }


    unset(
        $_SESSION["user_id"],
        $_SESSION["user_role"],
        $_SESSION["user_email"]
    );
}


/* REACTIVATION CONFIRMATION */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "")
        === "confirm_reactivation"
) {

    $reactivationUserId =
        (int) (
            $_SESSION[
                "reactivation_user_id"
            ] ?? 0
        );


    if ($reactivationUserId <= 0) {

        $error =
            "Your reactivation session expired. Please sign in again.";

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

                WHERE id = ?

                LIMIT 1
            ");


        if (!$stmt) {

            $error =
                "Unable to process account reactivation.";

        } else {

            $stmt->bind_param(
                "i",
                $reactivationUserId
            );


            $stmt->execute();


            $user =
                $stmt
                    ->get_result()
                    ->fetch_assoc();


            $stmt->close();


            if (!$user) {

                $error =
                    "Account could not be found.";

            } elseif (
                $user["account_status"]
                !== "deactivated"
            ) {

                $error =
                    "This account is no longer deactivated.";

            } elseif (
                (int) $user[
                    "email_verified"
                ] !== 1
            ) {

                $error =
                    "This account is not verified.";

            } else {

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


                $_SESSION[
                    "pending_auth_user_id"
                ] =
                    (int) $user["id"];


                $_SESSION[
                    "pending_auth_purpose"
                ] =
                    "reactivation";


                $_SESSION[
                    "pending_auth_email"
                ] =
                    $user["email"];


                $_SESSION[
                    "pending_auth_role"
                ] =
                    $user["role"];


                $_SESSION[
                    "temporary_auth_code"
                ] =
                    $code;


                $_SESSION[
                    "temporary_auth_expires"
                ] =
                    time() + 600;


                unset(
                    $_SESSION[
                        "reactivation_user_id"
                    ],
                    $_SESSION[
                        "reactivation_email"
                    ]
                );


                header(
                    "Location: auth.php"
                );

                exit;
            }
        }
    }
}


/* CANCEL REACTIVATION */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "")
        === "cancel_reactivation"
) {

    unset(
        $_SESSION[
            "reactivation_user_id"
        ],
        $_SESSION[
            "reactivation_email"
        ]
    );


    header(
        "Location: login.php"
    );

    exit;
}


/* LOGIN */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    (
        !isset($_POST["action"]) ||
        $_POST["action"] === "login"
    )
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
                (int) $user[
                    "email_verified"
                ] !== 1
            ) {

                $error =
                    "This account has not been verified yet.";

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


                $showReactivation =
                    true;

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
                !== "active"
            ) {

                $error =
                    "This account is not currently active.";

            } else {

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


                $_SESSION[
                    "temporary_auth_code"
                ] =
                    $code;


                $_SESSION[
                    "temporary_auth_expires"
                ] =
                    time() + 600;


                header(
                    "Location: auth.php"
                );

                exit;
            }
        }
    }
}


/* REACTIVATION STATE */

if (
    !$showReactivation &&
    isset(
        $_SESSION[
            "reactivation_user_id"
        ]
    )
) {

    $showReactivation =
        true;
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


    <style>

        .reactivation-box {

            margin-bottom: 20px;

            padding: 20px;

            background:
                #f3f9f6;

            border:
                1px solid #cfe5d8;

            border-radius: 12px;
        }


        .reactivation-box h2 {

            color:
                #003d24;

            font-size: 16px;
        }


        .reactivation-box p {

            margin-top: 8px;

            color:
                #68766f;

            font-size: 10px;

            line-height: 1.7;
        }


        .reactivation-account {

            margin-top: 12px;

            padding: 11px;

            color:
                #006b3f;

            background:
                #e6f3eb;

            border-radius: 8px;

            font-size: 9px;

            font-weight: 700;

            word-break:
                break-word;
        }


        .reactivation-actions {

            display: flex;

            gap: 9px;

            margin-top: 16px;
        }


        .reactivate-button {

            flex: 1;

            padding: 11px 14px;

            color: white;

            background:
                #006b3f;

            border:
                1px solid #006b3f;

            border-radius: 8px;

            font-family: inherit;

            font-size: 10px;

            font-weight: 700;

            cursor: pointer;
        }


        .reactivate-button:hover {

            background:
                #004d2a;
        }


        .cancel-reactivation {

            flex: 1;

            padding: 11px 14px;

            color:
                #68766f;

            background:
                white;

            border:
                1px solid #dce7e0;

            border-radius: 8px;

            font-family: inherit;

            font-size: 10px;

            font-weight: 700;

            cursor: pointer;
        }

    </style>


</head>


<body>


<div class="page-wrapper">


    <div class="auth-container">


        <div class="auth-card">


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
                            Student Profile Platform
                        </span>


                    </div>


                </a>


            </div>


            <?php if ($showReactivation): ?>


                <!-- REACTIVATION -->

                <div class="auth-header">


                    <h1>
                        Account Deactivated
                    </h1>


                    <p>
                        This account was previously
                        deactivated.
                    </p>


                </div>


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


                <div class="reactivation-box">


                    <h2>
                        Reactivate your account?
                    </h2>


                    <p>

                        You can restore access to
                        your CVSWHO account now.

                        Your profile and saved
                        records will remain intact.

                        You will need to verify
                        your account using an OTP
                        before reactivation is
                        completed.

                    </p>


                    <div class="reactivation-account">

                        <?= htmlspecialchars(
                            $_SESSION[
                                "reactivation_email"
                            ] ?? ""
                        ) ?>

                    </div>


                    <div class="reactivation-actions">


                        <form
                            method="POST"
                            action="login.php"
                            style="flex: 1;"
                        >


                            <input
                                type="hidden"
                                name="action"
                                value="confirm_reactivation"
                            >


                            <button
                                type="submit"
                                class="reactivate-button"
                            >
                                Reactivate Account
                            </button>


                        </form>


                        <form
                            method="POST"
                            action="login.php"
                            style="flex: 1;"
                        >


                            <input
                                type="hidden"
                                name="action"
                                value="cancel_reactivation"
                            >


                            <button
                                type="submit"
                                class="cancel-reactivation"
                            >
                                Cancel
                            </button>


                        </form>


                    </div>


                </div>


            <?php else: ?>


                <!-- HEADER -->

                <div class="auth-header">


                    <h1>
                        Welcome back
                    </h1>


                    <p>
                        Sign in to continue
                        managing your student profile.
                    </p>


                </div>


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


                <!-- LOGIN -->

                <form
                    method="POST"
                    action="login.php"
                    class="auth-form"
                >


                    <input
                        type="hidden"
                        name="action"
                        value="login"
                    >


                    <div class="form-group">


                        <label for="email">
                            CvSU Student Email
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
                        Login
                    </button>


                </form>


                <!-- BOTTOM -->

                <div class="auth-bottom">


                    <p>

                        Don't have an account?

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


            <?php endif; ?>


        </div>


    </div>


</div>


</body>

</html>