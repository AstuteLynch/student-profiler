<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";

/* PENDING AUTH */

if (
    !isset(
        $_SESSION[
            "pending_auth_user_id"
        ]
    ) ||
    !isset(
        $_SESSION[
            "pending_auth_purpose"
        ]
    ) ||
    !isset(
        $_SESSION[
            "pending_auth_email"
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
        "pending_auth_user_id"
    ];

$purpose =
    $_SESSION[
        "pending_auth_purpose"
    ];

$email =
    $_SESSION[
        "pending_auth_email"
    ];

$role =
    $_SESSION[
        "pending_auth_role"
    ] ?? "student";

$devAuthCode =
    $_SESSION[
        "dev_auth_code"
    ] ?? "";

$error = "";
$success = "";

/* VALID PURPOSE */

if (
    !in_array(
        $purpose,
        [
            "registration",
            "login"
        ],
        true
    )
) {
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
            "dev_auth_code"
        ]
    );

    header(
        "Location: login.php"
    );

    exit;
}

/* VERIFY */

if (
    $_SERVER["REQUEST_METHOD"]
        === "POST" &&
    isset(
        $_POST["verify_code"]
    )
) {

    $enteredCode =
        trim(
            $_POST[
                "verification_code"
            ] ?? ""
        );

    if (
        !preg_match(
            '/^\d{6}$/',
            $enteredCode
        )
    ) {
        $error =
            "Please enter the 6-digit verification code.";

    } elseif (
        !verifyAuthCode(
            $conn,
            $userId,
            $purpose,
            $enteredCode
        )
    ) {
        $error =
            "The verification code is incorrect, expired, or has reached the maximum number of attempts.";

    } else {

        /* REGISTRATION */

        if (
            $purpose ===
            "registration"
        ) {

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
                    "Unable to verify your account.";

            } else {

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
                    ($user["role"] ?? "")
                    !== "student"
                ) {
                    $error =
                        "Unable to verify your account.";

                } else {

                    $update =
                        $conn->prepare("
                            UPDATE users
                            SET
                                email_verified = 1,
                                account_status = 'active'
                            WHERE
                                id = ?
                                AND role = 'student'
                        ");

                    if (!$update) {
                        $error =
                            "Unable to verify your account.";

                    } else {

                        $update->bind_param(
                            "i",
                            $userId
                        );

                        if (
                            !$update->execute()
                        ) {
                            $error =
                                "Unable to verify your account.";

                        } else {

                            session_regenerate_id(
                                true
                            );

                            $_SESSION["user_id"] =
                                $userId;

                            $_SESSION["user_role"] =
                                "student";

                            $_SESSION["role"] =
                                "student";

                            $_SESSION["user_email"] =
                                $email;

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
                                    "dev_auth_code"
                                ]
                            );

                            header(
                                "Location: student_dashboard.php"
                            );

                            exit;
                        }

                        $update->close();
                    }
                }
            }

        /* LOGIN */

        } elseif (
            $purpose === "login"
        ) {

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
                    "Unable to complete login.";

            } else {

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

                if (!$user) {

                    $error =
                        "Account not found.";

                } elseif (
                    !in_array(
                        $user["role"] ?? "",
                        [
                            "student",
                            "administrator"
                        ],
                        true
                    )
                ) {

                    $error =
                        "This account cannot login here.";

                } elseif (
                    $user[
                        "account_status"
                    ] !== "active"
                ) {

                    $error =
                        "This account is no longer active.";

                } elseif (
                    (int) $user[
                        "email_verified"
                    ] !== 1
                ) {

                    $error =
                        "This email has not been verified.";

                } else {

                    session_regenerate_id(
                        true
                    );

                    $_SESSION["user_id"] =
                        (int) $user["id"];

                    $_SESSION["user_role"] =
                        $user["role"];

                    $_SESSION["role"] =
                        $user["role"];

                    $_SESSION["user_email"] =
                        $user["email"];

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
            }
        }
    }
}

/* GENERATE NEW CODE */

if (
    $_SERVER["REQUEST_METHOD"]
        === "POST" &&
    isset(
        $_POST["resend_code"]
    )
) {

    $accountStmt =
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

    if (!$accountStmt) {

        $error =
            "Unable to generate a new verification code.";

    } else {

        $accountStmt->bind_param(
            "i",
            $userId
        );

        $accountStmt->execute();

        $account =
            $accountStmt
                ->get_result()
                ->fetch_assoc();

        $accountStmt->close();

        if (!$account) {

            $error =
                "Account not found.";

        } elseif (
            $purpose === "login" &&
            (
                $account[
                    "account_status"
                ] !== "active" ||
                (int) $account[
                    "email_verified"
                ] !== 1
            )
        ) {

            $error =
                "This account cannot currently login.";

        } elseif (
            $purpose ===
            "registration" &&
            ($account["role"] ?? "")
            !== "student"
        ) {

            $error =
                "Invalid registration account.";

        } else {

            $code =
                createAuthCode(
                    $conn,
                    $userId,
                    $purpose
                );

            /* DEVELOPMENT ONLY */

            $_SESSION[
                "dev_auth_code"
            ] = $code;

            $devAuthCode =
                $code;

            $success =
                "A new verification code has been generated.";
        }
    }
}

/* DISPLAY */

if (
    $purpose ===
    "registration"
) {

    $pageTitle =
        "Verify Your Account";

    $pageDescription =
        "Enter the verification code generated for your account.";

    $buttonText =
        "Verify Account";

} else {

    $pageTitle =
        "Verify Your Login";

    $pageDescription =
        "Enter the verification code generated for your account.";

    $buttonText =
        "Verify";
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
        Verification | CVSWHO
    </title>

    <link
        rel="stylesheet"
        href="auth.css"
    >

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

    <style>

        .development-code {
            margin: 22px 0;
            padding: 16px 18px;

            text-align: center;

            background:
                #eff8f3;

            border:
                1px solid #cbe5d7;

            border-radius: 10px;
        }

        .development-code span {
            display: block;

            margin-bottom: 8px;

            color:
                #68766f;

            font-size: 9px;
            font-weight: 700;

            letter-spacing: .7px;

            text-transform: uppercase;
        }

        .development-code strong {
            display: block;

            color:
                #006b3f;

            font-size: 27px;
            font-weight: 800;

            letter-spacing: 8px;
        }

        .development-code small {
            display: block;

            margin-top: 8px;

            color:
                #68766f;

            font-size: 8px;
            line-height: 1.5;
        }

    </style>

</head>

<body>

<main class="auth-page">

    <div class="verification-card">

        <div class="verification-brand">

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
                        Account Verification
                    </span>

                </div>

            </a>

        </div>


        <div class="verification-content">

            <div class="verification-icon">
                ✓
            </div>

            <span class="verification-label">
                ACCOUNT VERIFICATION
            </span>

            <h1>

                <?= htmlspecialchars(
                    $pageTitle
                ) ?>

            </h1>

            <p class="verification-description">

                <?= htmlspecialchars(
                    $pageDescription
                ) ?>

            </p>


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
                        Local testing only. This will be removed when PHPMailer email delivery is enabled.
                    </small>

                </div>

            <?php else: ?>

                <div class="development-code">

                    <span>
                        Development Verification Code
                    </span>

                    <strong>
                        ------
                    </strong>

                    <small>
                        No development code exists in this session. Go back and login or register again.
                    </small>

                </div>

            <?php endif; ?>


            <?php if (
                $error !== ""
            ): ?>

                <div class="alert error">

                    <?= htmlspecialchars(
                        $error
                    ) ?>

                </div>

            <?php endif; ?>


            <?php if (
                $success !== ""
            ): ?>

                <div class="alert success">

                    <?= htmlspecialchars(
                        $success
                    ) ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action="auth.php"
            >

                <div class="input-group">

                    <label
                        for="verification_code"
                    >
                        Verification Code
                    </label>

                    <input
                        type="text"
                        id="verification_code"
                        name="verification_code"
                        placeholder="000000"
                        maxlength="6"
                        minlength="6"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        pattern="[0-9]{6}"
                        required
                    >

                </div>


                <button
                    type="submit"
                    name="verify_code"
                    class="verify-button"
                >

                    <?= htmlspecialchars(
                        $buttonText
                    ) ?>

                    <span>
                        →
                    </span>

                </button>

            </form>


            <form
                method="POST"
                action="auth.php"
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


            <p class="verification-email">

                Account:

                <strong>

                    <?= htmlspecialchars(
                        $email
                    ) ?>

                </strong>

            </p>

        </div>

    </div>

</main>

</body>

</html>