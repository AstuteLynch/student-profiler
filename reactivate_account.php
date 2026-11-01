<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";


$error = "";

$success = "";


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
        "Unable to load your account.";

    $user =
        null;

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


/* INVALID */

if (!$user) {

    unset(
        $_SESSION[
            "reactivation_user_id"
        ],
        $_SESSION[
            "reactivation_email"
        ],
        $_SESSION[
            "reactivation_code_sent"
        ]
    );


    header(
        "Location: login.php"
    );


    exit;
}


/* ALREADY ACTIVE */

if (
    $user[
        "account_status"
    ] === "active"
) {

    unset(
        $_SESSION[
            "reactivation_user_id"
        ],
        $_SESSION[
            "reactivation_email"
        ],
        $_SESSION[
            "reactivation_code_sent"
        ]
    );


    header(
        "Location: login.php"
    );


    exit;
}


/* ONLY DEACTIVATED */

if (
    $user[
        "account_status"
    ] !== "deactivated"
) {

    $error =
        "This account cannot be reactivated from this page.";
}


/* VERIFIED EMAIL REQUIRED */

if (
    (int) $user[
        "email_verified"
    ] !== 1
) {

    $error =
        "This account has not completed email verification.";
}


/* SEND INITIAL OTP */

if (
    $error === "" &&
    !isset(
        $_SESSION[
            "reactivation_code_sent"
        ]
    )
) {

    try {

        $code =
            createAuthCode(
                $conn,
                $userId,
                "reactivation"
            );


        $emailSent =
            sendAuthCode(
                $user["email"],
                $code,
                "reactivation"
            );


        if (!$emailSent) {

            throw new RuntimeException(
                "Unable to send reactivation email."
            );
        }


        $_SESSION[
            "reactivation_code_sent"
        ] =
            true;


        $success =
            "A verification code has been sent to your email address.";

    } catch (
        Throwable $exception
    ) {

        $error =
            "Unable to send the account reactivation code.";
    }
}


/* VERIFY */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
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
                        "reactivation_code_sent"
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
                isset($update)
            ) {

                $update->close();
            }
        }
    }
}


/* RESEND */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
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


        $emailSent =
            sendAuthCode(
                $user["email"],
                $code,
                "reactivation"
            );


        if (!$emailSent) {

            throw new RuntimeException(
                "Unable to send reactivation email."
            );
        }


        $_SESSION[
            "reactivation_code_sent"
        ] =
            true;


        $success =
            "A new verification code has been sent to your email address.";


        $error =
            "";

    } catch (
        Throwable $exception
    ) {

        $error =
            "Unable to send a new verification code.";
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


        .reactivation-success {
            width: 100%;

            margin-bottom: 20px;

            padding:
                12px 14px;

            color: #18794e;

            background: #eaf7ef;

            border:
                1px solid #cee8d9;

            border-radius: 9px;

            font-size: 10px;

            line-height: 1.5;
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
                    code sent to your registered email
                    to restore access.
                </p>

            </div>


            <?php if (
                $success !== ""
            ): ?>

                <div class="reactivation-success">

                    <?= htmlspecialchars(
                        $success
                    ) ?>

                </div>

            <?php endif; ?>


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
                        Enter the 6-digit verification
                        code sent to your email.
                        The code expires after 10 minutes.
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
                    Send New Code
                </button>

            </form>


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
                    Your password was already verified.
                    A valid email verification code is
                    required before account access is restored.
                </p>

            </div>

        </div>


    </div>


</div>


</body>

</html>