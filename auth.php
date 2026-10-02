<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";


/* AUTH SESSION */

if (
    !isset(
        $_SESSION[
            "pending_auth_user_id"
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
    ] ?? "login";


$email =
    $_SESSION[
        "pending_auth_email"
    ] ?? "";


$role =
    $_SESSION[
        "pending_auth_role"
    ] ?? "student";


$error = "";

$success = "";


/* VERIFY */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["verify_code"])
) {

    $code =
        trim(
            $_POST[
                "verification_code"
            ] ?? ""
        );


    if (
        !preg_match(
            '/^\d{6}$/',
            $code
        )
    ) {

        $error =
            "Please enter the 6-digit verification code.";

    } else {

        $verified =
            false;


        /* LEGACY REGISTRATION */

        if (
            isset(
                $_SESSION[
                    "temporary_auth_code"
                ],
                $_SESSION[
                    "temporary_auth_expires"
                ]
            )
        ) {

            if (
                time() <=
                (int) $_SESSION[
                    "temporary_auth_expires"
                ] &&
                hash_equals(
                    (string) $_SESSION[
                        "temporary_auth_code"
                    ],
                    $code
                )
            ) {

                $verified =
                    true;
            }

        } else {

            $verified =
                verifyAuthCode(
                    $conn,
                    $userId,
                    $purpose,
                    $code
                );
        }


        if (!$verified) {

            $error =
                "The verification code is incorrect or expired.";

        } else {

            /* REGISTRATION */

            if (
                $purpose ===
                "registration"
            ) {

                $stmt =
                    $conn->prepare("
                        UPDATE users

                        SET
                            email_verified = 1,
                            account_status = 'active',
                            last_login_at = NOW()

                        WHERE id = ?

                        LIMIT 1
                    ");


                $stmt->bind_param(
                    "i",
                    $userId
                );


                $stmt->execute();

                $stmt->close();
            }


            /* REACTIVATION */

            if (
                $purpose ===
                "reactivation"
            ) {

                $stmt =
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


                $stmt->bind_param(
                    "i",
                    $userId
                );


                $stmt->execute();

                $stmt->close();
            }


            /* ACCOUNT CHECK */

            $stmt =
                $conn->prepare("
                    SELECT

                        email,
                        role,
                        account_status,
                        email_verified

                    FROM users

                    WHERE id = ?

                    LIMIT 1
                ");


            $stmt->bind_param(
                "i",
                $userId
            );


            $stmt->execute();


            $account =
                $stmt
                    ->get_result()
                    ->fetch_assoc();


            $stmt->close();


            if (
                !$account ||
                $account[
                    "account_status"
                ] !== "active" ||
                (int) $account[
                    "email_verified"
                ] !== 1
            ) {

                $error =
                    "Your account cannot currently be accessed.";

            } else {

                $update =
                    $conn->prepare("
                        UPDATE users

                        SET last_login_at = NOW()

                        WHERE id = ?

                        LIMIT 1
                    ");


                if ($update) {

                    $update->bind_param(
                        "i",
                        $userId
                    );

                    $update->execute();

                    $update->close();
                }


                session_regenerate_id(
                    true
                );


                $_SESSION["user_id"] =
                    $userId;


                $_SESSION["user_role"] =
                    $account["role"];


                $_SESSION["role"] =
                    $account["role"];


                $_SESSION["user_email"] =
                    $account["email"];


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
                    ],
                    $_SESSION[
                        "dev_auth_code"
                    ]
                );


                if (
                    $account["role"]
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


/* RESEND */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["resend_code"])
) {

    try {

        $newCode =
            createAuthCode(
                $conn,
                $userId,
                $purpose
            );


        sendAuthCode(
            $email,
            $newCode,
            $purpose
        );


        $_SESSION[
            "dev_auth_code"
        ] =
            $newCode;


        $success =
            "A new verification code has been generated.";

    } catch (Throwable $exception) {

        $error =
            "Unable to generate another verification code.";
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
        Verification | CVSWHO
    </title>

    <link
        rel="stylesheet"
        href="auth.css"
    >

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
                Verify Your Login
            </h1>


            <p class="verification-description">
                Enter the verification code
                generated for your account.
            </p>


            <?php if (
                isset(
                    $_SESSION[
                        "dev_auth_code"
                    ]
                )
            ): ?>

                <div class="code-display">

                    <span>
                        Development verification code
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $_SESSION[
                                "dev_auth_code"
                            ]
                        ) ?>

                    </strong>

                    <small>
                        Remove this display when
                        email delivery is ready for production.
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

                    <label for="verification_code">
                        Verification Code
                    </label>

                    <input
                        type="text"
                        id="verification_code"
                        name="verification_code"
                        placeholder="000000"
                        maxlength="6"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        autocomplete="one-time-code"
                        required
                    >

                </div>

                <button
                    type="submit"
                    name="verify_code"
                    class="verify-button"
                >
                    Verify
                    <span>→</span>
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