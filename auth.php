<?php

session_start();

require_once "db.php";


/* =========================================================
   REQUIRE PENDING AUTHENTICATION
========================================================= */

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
            "temporary_auth_code"
        ]
    ) ||

    !isset(
        $_SESSION[
            "temporary_auth_expires"
        ]
    )
) {

    header(
        "Location: login.php"
    );

    exit;
}


/* =========================================================
   AUTH INFORMATION
========================================================= */

$error = "";

$success = "";


$userId =
    (int) $_SESSION[
        "pending_auth_user_id"
    ];


$purpose =
    $_SESSION[
        "pending_auth_purpose"
    ]
    ?? "login";


$email =
    $_SESSION[
        "pending_auth_email"
    ]
    ?? "";


$role =
    $_SESSION[
        "pending_auth_role"
    ]
    ?? "student";


$temporaryCode =
    (string) $_SESSION[
        "temporary_auth_code"
    ];


$expires =
    (int) $_SESSION[
        "temporary_auth_expires"
    ];


/* =========================================================
   VALID PURPOSE
========================================================= */

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
            "temporary_auth_code"
        ],
        $_SESSION[
            "temporary_auth_expires"
        ]
    );


    header(
        "Location: login.php"
    );

    exit;
}


/* =========================================================
   PAGE TEXT
========================================================= */

if (
    $purpose === "registration"
) {

    $pageLabel =
        "ACCOUNT VERIFICATION";


    $pageTitle =
        "Verify Your Account";


    $pageDescription =
        "Enter the temporary verification code to finish creating your CVSWHO account.";


    $verifyButtonText =
        "Verify Account";


} else {

    $pageLabel =
        "LOGIN VERIFICATION";


    $pageTitle =
        "Verify Your Login";


    $pageDescription =
        "Your password was accepted. Enter the temporary verification code to complete your login.";


    $verifyButtonText =
        "Verify Login";
}


/* =========================================================
   EXPIRED CODE
========================================================= */

if (
    time() > $expires
) {

    $error =
        "Your verification code has expired. Generate a new code below.";
}


/* =========================================================
   VERIFY CODE
========================================================= */

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
            ]
            ?? ""
        );


    if (
        time() > $expires
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
            $temporaryCode,
            $enteredCode
        )
    ) {

        $error =
            "Incorrect verification code.";


    } else {


        /* =================================================
           REGISTRATION VERIFICATION
        ================================================= */

        if (
            $purpose === "registration"
        ) {

            $update =
                $conn->prepare("
                    UPDATE users

                    SET
                        email_verified = 1,
                        account_status = 'active'

                    WHERE id = ?
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

                    $update->close();


                    /* =====================================
                       REGISTRATION COMPLETE
                    ====================================== */

                    session_regenerate_id(
                        true
                    );


                    $_SESSION[
                        "user_id"
                    ] = $userId;


                    $_SESSION[
                        "user_role"
                    ] = $role;


                    $_SESSION[
                        "user_email"
                    ] = $email;


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
                        $role
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


        /* =================================================
           LOGIN VERIFICATION
        ================================================= */

        } else {


            /*
             * Before creating a logged-in session,
             * make sure the account is still active.
             */

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

                $accountStmt
                    ->bind_param(
                        "i",
                        $userId
                    );


                $accountStmt
                    ->execute();


                $account =
                    $accountStmt
                        ->get_result()
                        ->fetch_assoc();


                $accountStmt
                    ->close();


                if (!$account) {

                    $error =
                        "Account could not be found.";


                } elseif (
                    !(bool) $account[
                        "email_verified"
                    ]
                ) {

                    $error =
                        "This account is not verified.";


                } elseif (
                    $account[
                        "account_status"
                    ] !== "active"
                ) {

                    $error =
                        "This account is not currently active.";


                } else {


                    /* =====================================
                       LOGIN COMPLETE
                    ====================================== */

                    session_regenerate_id(
                        true
                    );


                    $_SESSION[
                        "user_id"
                    ] = $userId;


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


/* =========================================================
   GENERATE NEW CODE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST" &&

    isset(
        $_POST["resend_code"]
    )
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
    ] = $newCode;


    $_SESSION[
        "temporary_auth_expires"
    ] = time() + 600;


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


        <!-- =========================================
             BRAND
        ========================================== -->

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


        <!-- =========================================
             CONTENT
        ========================================== -->

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


            <p
                class="
                    verification-description
                "
            >

                <?= htmlspecialchars(
                    $pageDescription
                ) ?>

            </p>


            <!-- =====================================
                 TEMPORARY CODE

                 This matches the registration
                 behavior you currently use.
            ====================================== -->

            <div class="code-display">


                <span>
                    Your temporary code
                </span>


                <strong>

                    <?= htmlspecialchars(
                        $temporaryCode
                    ) ?>

                </strong>


                <small>
                    This code expires in
                    10 minutes.
                </small>


            </div>


            <!-- =====================================
                 ERROR
            ====================================== -->

            <?php if (
                $error !== ""
            ): ?>


                <div
                    class="
                        alert
                        error
                    "
                >

                    <?= htmlspecialchars(
                        $error
                    ) ?>

                </div>


            <?php endif; ?>


            <!-- =====================================
                 SUCCESS
            ====================================== -->

            <?php if (
                $success !== ""
            ): ?>


                <div
                    class="
                        alert
                        success
                    "
                >

                    <?= htmlspecialchars(
                        $success
                    ) ?>

                </div>


            <?php endif; ?>


            <!-- =====================================
                 VERIFY FORM
            ====================================== -->

            <form
                method="POST"
                action="auth.php"
            >


                <div class="input-group">


                    <label
                        for="
                            verification_code
                        "
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
                        $verifyButtonText
                    ) ?>

                    <span>
                        →
                    </span>

                </button>


            </form>


            <!-- =====================================
                 NEW CODE
            ====================================== -->

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


            <?php if (
                $purpose === "login"
            ): ?>


                <p
                    class="
                        verification-email
                    "
                    style="
                        margin-top: 12px;
                    "
                >

                    <a href="login.php">
                        ← Cancel login
                    </a>

                </p>


            <?php else: ?>


                <p
                    class="
                        verification-email
                    "
                    style="
                        margin-top: 12px;
                    "
                >

                    <a href="register.php">
                        ← Back to registration
                    </a>

                </p>


            <?php endif; ?>


        </div>


    </div>


</main>


</body>

</html>