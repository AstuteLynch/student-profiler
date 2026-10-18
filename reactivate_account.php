<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";


$error = "";
$success = "";


/* ALREADY LOGGED IN */

if (
    isset(
        $_SESSION["user_id"]
    )
) {

    $loggedInUserId =
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
            $loggedInUserId
        );


        $stmt->execute();


        $loggedInUser =
            $stmt
                ->get_result()
                ->fetch_assoc();


        $stmt->close();


        if (
            $loggedInUser &&
            $loggedInUser[
                "account_status"
            ] === "active" &&
            (int) $loggedInUser[
                "email_verified"
            ] === 1
        ) {

            if (
                $loggedInUser["role"]
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
}


/* REACTIVATION SESSION */

if (
    !isset(
        $_SESSION[
            "reactivation_user_id"
        ]
    ) ||
    !isset(
        $_SESSION[
            "reactivation_email"
        ]
    )
) {

    header(
        "Location: login.php"
    );

    exit;
}


$userId =
    (int) $_SESSION[
        "reactivation_user_id"
    ];


$email =
    strtolower(
        trim(
            $_SESSION[
                "reactivation_email"
            ]
        )
    );


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

        WHERE
            id = ?
            AND LOWER(email) = ?

        LIMIT 1
    ");


if (!$stmt) {

    $error =
        "Unable to load the account.";

} else {

    $stmt->bind_param(
        "is",
        $userId,
        $email
    );


    $stmt->execute();


    $user =
        $stmt
            ->get_result()
            ->fetch_assoc();


    $stmt->close();
}


/* INVALID ACCOUNT */

if (
    !isset($user) ||
    !$user
) {

    unset(
        $_SESSION[
            "reactivation_user_id"
        ],
        $_SESSION[
            "reactivation_email"
        ],
        $_SESSION[
            "reactivation_code_sent_user_id"
        ],
        $_SESSION[
            "dev_auth_code"
        ]
    );


    header(
        "Location: login.php"
    );

    exit;
}


/* ALREADY ACTIVE */

if (
    $user["account_status"]
    === "active"
) {

    unset(
        $_SESSION[
            "reactivation_user_id"
        ],
        $_SESSION[
            "reactivation_email"
        ],
        $_SESSION[
            "reactivation_code_sent_user_id"
        ],
        $_SESSION[
            "dev_auth_code"
        ]
    );


    header(
        "Location: login.php"
    );

    exit;
}


/* ONLY DEACTIVATED */

if (
    $user["account_status"]
    !== "deactivated"
) {

    $error =
        "This account cannot be reactivated from this page.";
}


/* VERIFIED ACCOUNT */

if (
    (int) $user[
        "email_verified"
    ] !== 1
) {

    $error =
        "This account has not completed email verification.";
}


/* SEND FIRST CODE */

if (
    $error === "" &&
    (
        !isset(
            $_SESSION[
                "reactivation_code_sent_user_id"
            ]
        ) ||
        (int) $_SESSION[
            "reactivation_code_sent_user_id"
        ] !== $userId
    )
) {

    try {

        $code =
            createAuthCode(
                $conn,
                $userId,
                "reactivation"
            );


        /*
            Email delivery may fail locally
            before PHPMailer is configured.
        */

        sendAuthCode(
            $user["email"],
            $code,
            "reactivation"
        );


        $_SESSION[
            "reactivation_code_sent_user_id"
        ] =
            $userId;


        /*
            DEVELOPMENT ONLY

            Remove this when PHPMailer
            is fully configured.
        */

        $_SESSION[
            "dev_auth_code"
        ] =
            $code;


        $success =
            "A reactivation verification code has been generated.";

    } catch (Throwable $exception) {

        $error =
            "Unable to generate the reactivation code.";
    }
}


/* VERIFY CODE */

if (
    $_SERVER["REQUEST_METHOD"]
        === "POST" &&
    isset(
        $_POST["verify_code"]
    )
) {

    $verificationCode =
        trim(
            $_POST[
                "verification_code"
            ] ?? ""
        );


    if (
        !preg_match(
            '/^\d{6}$/',
            $verificationCode
        )
    ) {

        $error =
            "Please enter the 6-digit verification code.";

    } elseif (
        !verifyAuthCode(
            $conn,
            $userId,
            "reactivation",
            $verificationCode
        )
    ) {

        $error =
            "The verification code is incorrect, expired, or has reached the maximum number of attempts.";

    } else {

        /* REACTIVATE ACCOUNT */

        $update =
            $conn->prepare("
                UPDATE users

                SET
                    account_status = 'active',
                    last_login_at = NOW()

                WHERE
                    id = ?
                    AND account_status = 'deactivated'

                LIMIT 1
            ");


        if (!$update) {

            $error =
                "Unable to reactivate your account.";

        } else {

            $update->bind_param(
                "i",
                $userId
            );


            if (
                !$update->execute()
            ) {

                $error =
                    "Unable to reactivate your account.";

            } elseif (
                $update->affected_rows < 1
            ) {

                $error =
                    "The account could not be reactivated.";

            } else {

                $update->close();


                /*
                    Since the user already entered
                    the correct password in login.php
                    before arriving here and has now
                    passed OTP verification, log them in.
                */

                session_regenerate_id(
                    true
                );


                $_SESSION[
                    "user_id"
                ] =
                    $userId;


                $_SESSION[
                    "user_role"
                ] =
                    $user["role"];


                $_SESSION[
                    "role"
                ] =
                    $user["role"];


                $_SESSION[
                    "user_email"
                ] =
                    $user["email"];


                unset(
                    $_SESSION[
                        "reactivation_user_id"
                    ],
                    $_SESSION[
                        "reactivation_email"
                    ],
                    $_SESSION[
                        "reactivation_code_sent_user_id"
                    ],
                    $_SESSION[
                        "dev_auth_code"
                    ]
                );


                if (
                    $user["role"]
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


            if (
                isset($update) &&
                $update instanceof mysqli_stmt
            ) {

                $update->close();
            }
        }
    }
}


/* RESEND CODE */

if (
    $_SERVER["REQUEST_METHOD"]
        === "POST" &&
    isset(
        $_POST["resend_code"]
    )
) {

    try {

        $code =
            createAuthCode(
                $conn,
                $userId,
                "reactivation"
            );


        sendAuthCode(
            $user["email"],
            $code,
            "reactivation"
        );


        $_SESSION[
            "dev_auth_code"
        ] =
            $code;


        $_SESSION[
            "reactivation_code_sent_user_id"
        ] =
            $userId;


        $success =
            "A new reactivation verification code has been generated.";


        $error = "";

    } catch (Throwable $exception) {

        $error =
            "Unable to generate a new verification code.";
    }
}


/* DEVELOPMENT CODE */

$devAuthCode =
    $_SESSION[
        "dev_auth_code"
    ] ?? "";

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
        Reactivate Account | CVSWHO
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


    <!-- SAME DESIGN AS LOGIN -->

    <link
        rel="stylesheet"
        href="login.css"
    >


    <style>

        .reactivation-icon {
            width: 58px;
            height: 58px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin:
                0 auto 20px;

            color: #006b3f;

            background: #e6f3eb;

            border-radius: 50%;

            font-size: 25px;
            font-weight: 800;
        }


        .development-code {
            width: 100%;

            margin-bottom: 20px;

            padding:
                15px 17px;

            text-align: center;

            background:
                #eff8f3;

            border:
                1px solid
                #cbe5d7;

            border-radius: 9px;
        }


        .development-code span {
            display: block;

            margin-bottom: 7px;

            color: #68766f;

            font-size: 9px;

            font-weight: 700;

            letter-spacing: .6px;

            text-transform:
                uppercase;
        }


        .development-code strong {
            display: block;

            color: #006b3f;

            font-size: 25px;

            font-weight: 800;

            letter-spacing: 7px;
        }


        .development-code small {
            display: block;

            margin-top: 7px;

            color: #68766f;

            font-size: 8px;

            line-height: 1.5;
        }


        .alert-success {
            width: 100%;

            margin-bottom: 20px;

            padding:
                12px 14px;

            color: #18794e;

            background: #eaf7ef;

            border:
                1px solid
                #cee8d9;

            border-radius: 9px;

            font-size: 10px;

            line-height: 1.5;
        }


        .otp-input {
            width: 100%;

            height: 52px;

            padding:
                0 15px;

            color: #1d2b24;

            background: #fbfdfc;

            border:
                1px solid #dce7e0;

            border-radius: 9px;

            outline: none;

            font-family:
                "Inter",
                sans-serif;

            font-size: 18px;

            font-weight: 700;

            letter-spacing: 8px;

            text-align: center;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        .otp-input:focus {
            border-color: #88bea3;

            background: white;

            box-shadow:
                0 0 0 4px
                rgba(
                    0,
                    107,
                    63,
                    .07
                );
        }


        .resend-form {
            margin-top: 13px;

            text-align: center;
        }


        .resend-button {
            padding: 7px 10px;

            color: #006b3f;

            background: transparent;

            border: none;

            border-radius: 7px;

            font-family:
                "Inter",
                sans-serif;

            font-size: 10px;

            font-weight: 700;

            cursor: pointer;
        }


        .resend-button:hover {
            background: #e6f3eb;
        }


        .account-email {
            margin-top: 17px;

            color: #68766f;

            text-align: center;

            font-size: 9px;

            line-height: 1.5;
        }


        .account-email strong {
            color: #1d2b24;
        }

    </style>

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


            <div class="reactivation-icon">
                ↻
            </div>


            <div class="auth-header">

                <span class="section-label">
                    ACCOUNT REACTIVATION
                </span>


                <h1>
                    Reactivate your account
                </h1>


                <p>
                    Your CVSWHO account is currently
                    deactivated. Enter the verification
                    code to restore access to your account.
                </p>

            </div>


            <!-- DEVELOPMENT OTP -->

            <?php if (
                $devAuthCode !== ""
            ): ?>

                <div class="development-code">

                    <span>
                        Development Verification Code
                    </span>


                    <strong>

                        <?= htmlspecialchars(
                            $devAuthCode
                        ) ?>

                    </strong>


                    <small>
                        For local testing only.
                        Remove this when PHPMailer
                        email delivery is enabled.
                    </small>

                </div>

            <?php endif; ?>


            <!-- SUCCESS -->

            <?php if (
                $success !== ""
            ): ?>

                <div class="alert-success">

                    <?= htmlspecialchars(
                        $success
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- ERROR -->

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


            <!-- VERIFICATION FORM -->

            <?php if (
                $error === "" ||
                $user[
                    "account_status"
                ] === "deactivated"
            ): ?>

                <form
                    method="POST"
                    action="reactivate_account.php"
                    class="auth-form"
                >


                    <div class="form-group">

                        <label
                            for="verification_code"
                        >
                            Verification Code
                        </label>


                        <input
                            type="text"
                            id="verification_code"
                            name="verification_code"
                            class="otp-input"
                            placeholder="000000"
                            maxlength="6"
                            minlength="6"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            pattern="[0-9]{6}"
                            required
                        >

                        <span class="input-help">
                            Enter the 6-digit code generated
                            for your account reactivation.
                        </span>

                    </div>


                    <button
                        type="submit"
                        name="verify_code"
                        class="auth-button"
                    >

                        Reactivate Account

                        <span class="button-arrow">
                            →
                        </span>

                    </button>

                </form>


                <!-- RESEND -->

                <form
                    method="POST"
                    action="reactivate_account.php"
                    class="resend-form"
                >

                    <button
                        type="submit"
                        name="resend_code"
                        class="resend-button"
                    >
                        Generate New Code
                    </button>

                </form>

            <?php endif; ?>


            <p class="account-email">

                Account:

                <strong>

                    <?= htmlspecialchars(
                        $user["email"]
                    ) ?>

                </strong>

            </p>


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
                    Secure Account Reactivation
                </strong>


                <p>
                    Your password was verified before
                    this reactivation request and a
                    temporary verification code is required
                    before access is restored.
                </p>

            </div>

        </div>


    </div>


</div>


</body>

</html>