<?php

session_start();

require_once "db.php";


/* PENDING AUTH */

if (
    !isset(
        $_SESSION[
            "pending_auth_user_id"
        ],
        $_SESSION[
            "pending_auth_purpose"
        ],
        $_SESSION[
            "temporary_auth_code"
        ],
        $_SESSION[
            "temporary_auth_expires"
        ]
    )
) {

    header("Location: login.php");
    exit;
}


$error = "";

$success = "";


$userId =
    (int) $_SESSION[
        "pending_auth_user_id"
    ];


$purpose =
    $_SESSION[
        "pending_auth_purpose"
    ] ?? "";


$email =
    $_SESSION[
        "pending_auth_email"
    ] ?? "";


$role =
    $_SESSION[
        "pending_auth_role"
    ] ?? "student";


$temporaryCode =
    (string) $_SESSION[
        "temporary_auth_code"
    ];


$expires =
    (int) $_SESSION[
        "temporary_auth_expires"
    ];


/* PURPOSE */

$allowedPurposes = [

    "registration",
    "login",
    "reactivation"
];


if (
    !in_array(
        $purpose,
        $allowedPurposes,
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
            "temporary_auth_code"
        ],
        $_SESSION[
            "temporary_auth_expires"
        ]
    );


    header("Location: login.php");
    exit;
}


/* PAGE TEXT */

if (
    $purpose === "registration"
) {

    $pageLabel =
        "ACCOUNT VERIFICATION";


    $pageTitle =
        "Verify Your Account";


    $pageDescription =
        "Enter the temporary verification code to finish creating your CVSWHO account.";


    $buttonText =
        "Verify Account";

} elseif (
    $purpose === "reactivation"
) {

    $pageLabel =
        "ACCOUNT REACTIVATION";


    $pageTitle =
        "Verify Reactivation";


    $pageDescription =
        "Enter the temporary verification code to reactivate your CVSWHO account.";


    $buttonText =
        "Reactivate Account";

} else {

    $pageLabel =
        "LOGIN VERIFICATION";


    $pageTitle =
        "Verify Your Login";


    $pageDescription =
        "Enter the temporary verification code to complete your login.";


    $buttonText =
        "Verify Login";
}


/* VERIFY */

if (
    $_SERVER["REQUEST_METHOD"]
        === "POST" &&
    isset($_POST["verify_code"])
) {

    $enteredCode =
        trim(
            $_POST[
                "verification_code"
            ] ?? ""
        );


    if (
        time() >
        (int) $_SESSION[
            "temporary_auth_expires"
        ]
    ) {

        $error =
            "Your verification code has expired. Generate a new code below.";

    } elseif (
        !preg_match(
            '/^\d{6}$/',
            $enteredCode
        )
    ) {

        $error =
            "Please enter the 6-digit verification code.";

    } elseif (
        !hash_equals(
            (string) $_SESSION[
                "temporary_auth_code"
            ],
            $enteredCode
        )
    ) {

        $error =
            "Incorrect verification code.";

    } else {

        /* REGISTRATION */

        if (
            $purpose === "registration"
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


            if (!$stmt) {

                $error =
                    "Unable to verify your account.";

            } else {

                $stmt->bind_param(
                    "i",
                    $userId
                );


                if (
                    $stmt->execute()
                ) {

                    $stmt->close();


                    session_regenerate_id(
                        true
                    );


                    $_SESSION["user_id"] =
                        $userId;


                    $_SESSION["user_role"] =
                        $role;


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
                            "temporary_auth_code"
                        ],
                        $_SESSION[
                            "temporary_auth_expires"
                        ]
                    );


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

                } else {

                    $error =
                        "Unable to verify your account.";


                    $stmt->close();
                }
            }
        }


        /* REACTIVATION */

        elseif (
            $purpose === "reactivation"
        ) {

            $accountStmt =
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


            if (!$accountStmt) {

                $error =
                    "Unable to reactivate your account.";

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
                        "Account could not be found.";

                } elseif (
                    (int) $account[
                        "email_verified"
                    ] !== 1
                ) {

                    $error =
                        "This account is not verified.";

                } elseif (
                    $account[
                        "account_status"
                    ] !== "deactivated"
                ) {

                    $error =
                        "This account cannot be reactivated from its current status.";

                } else {

                    $reactivateStmt =
                        $conn->prepare("
                            UPDATE users

                            SET
                                account_status =
                                    'active',

                                last_login_at =
                                    NOW()

                            WHERE
                                id = ?

                                AND
                                account_status =
                                    'deactivated'

                            LIMIT 1
                        ");


                    if (!$reactivateStmt) {

                        $error =
                            "Unable to reactivate your account.";

                    } else {

                        $reactivateStmt
                            ->bind_param(
                                "i",
                                $userId
                            );


                        if (
                            $reactivateStmt
                                ->execute()
                        ) {

                            $reactivateStmt
                                ->close();


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
                                $account[
                                    "role"
                                ];


                            $_SESSION[
                                "user_email"
                            ] =
                                $account[
                                    "email"
                                ];


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
                                    "reactivation_user_id"
                                ],
                                $_SESSION[
                                    "reactivation_email"
                                ]
                            );


                            if (
                                $account[
                                    "role"
                                ]
                                ===
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

                        } else {

                            $error =
                                "Unable to reactivate your account.";


                            $reactivateStmt
                                ->close();
                        }
                    }
                }
            }
        }


        /* LOGIN */

        else {

            $accountStmt =
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


            if (!$accountStmt) {

                $error =
                    "Unable to complete login.";

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
                        "Account could not be found.";

                } elseif (
                    (int) $account[
                        "email_verified"
                    ] !== 1
                ) {

                    $error =
                        "This account is not verified.";

                } elseif (
                    $account[
                        "account_status"
                    ] !== "active"
                ) {

                    $error =
                        "This account is no longer active.";

                } else {

                    $loginStmt =
                        $conn->prepare("
                            UPDATE users

                            SET last_login_at = NOW()

                            WHERE id = ?

                            LIMIT 1
                        ");


                    if ($loginStmt) {

                        $loginStmt->bind_param(
                            "i",
                            $userId
                        );


                        $loginStmt->execute();


                        $loginStmt->close();
                    }


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
                        $account[
                            "role"
                        ];


                    $_SESSION[
                        "user_email"
                    ] =
                        $account[
                            "email"
                        ];


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
}


/* NEW CODE */

if (
    $_SERVER["REQUEST_METHOD"]
        === "POST" &&
    isset($_POST["resend_code"])
) {

    $newCode =
        str_pad(
            (string) random_int(
                0,
                999999
            ),
            6,
            "0",
            STR_PAD_LEFT
        );


    $_SESSION[
        "temporary_auth_code"
    ] =
        $newCode;


    $_SESSION[
        "temporary_auth_expires"
    ] =
        time() + 600;


    $temporaryCode =
        $newCode;


    $expires =
        (int) $_SESSION[
            "temporary_auth_expires"
        ];


    $error = "";


    $success =
        "A new verification code has been generated.";
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


</head>


<body>


<main class="auth-page">


    <div class="verification-card">


        <!-- BRAND -->

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
                        Student Profile Platform
                    </span>


                </div>


            </a>


        </div>


        <!-- VERIFICATION -->

        <div class="verification-content">


            <div class="verification-icon">
                ✓
            </div>


            <span class="verification-label">

                <?= htmlspecialchars(
                    $pageLabel
                ) ?>

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


            <!-- TEMPORARY CODE -->

            <div class="code-display">


                <span>
                    Your temporary code
                </span>


                <strong>

                    <?= htmlspecialchars(
                        $_SESSION[
                            "temporary_auth_code"
                        ]
                    ) ?>

                </strong>


                <small>
                    This code expires in
                    10 minutes.
                </small>


            </div>


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


            <!-- OTP -->

            <form
                method="POST"
                action="auth.php"
            >


                <div class="input-group">


                    <label
                        for="verification_code"
                    >
                        Enter Verification Code
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

                    <?= htmlspecialchars(
                        $buttonText
                    ) ?>

                    <span>
                        →
                    </span>

                </button>


            </form>


            <!-- RESEND -->

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