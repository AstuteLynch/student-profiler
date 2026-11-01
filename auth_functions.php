<?php

require_once __DIR__ .
    "/config/brevo_config.php";


/* CVSU EMAIL */

function isCvsuEmail(
    string $email
): bool {

    return (bool) preg_match(
        '/^[a-zA-Z]+\.[a-zA-Z]+@cvsu\.edu\.ph$/',
        trim($email)
    );
}


/* AUTH PURPOSE */

function isValidAuthPurpose(
    string $purpose
): bool {

    return in_array(
        $purpose,
        [
            "registration",
            "login",
            "email_change",
            "password_reset",
            "reactivation"
        ],
        true
    );
}


/* GENERATE OTP */

function generateAuthCode(): string
{
    return str_pad(
        (string) random_int(
            0,
            999999
        ),
        6,
        "0",
        STR_PAD_LEFT
    );
}


/* CREATE OTP */

function createAuthCode(
    mysqli $conn,
    int $userId,
    string $purpose
): string {

    if (
        !isValidAuthPurpose(
            $purpose
        )
    ) {

        throw new RuntimeException(
            "Invalid authentication purpose."
        );
    }


    $code =
        generateAuthCode();


    $codeHash =
        password_hash(
            $code,
            PASSWORD_DEFAULT
        );


    /* INVALIDATE OLD OTP */

    $invalidate =
        $conn->prepare("
            UPDATE authentication_codes

            SET used_at = NOW()

            WHERE
                user_id = ?
                AND purpose = ?
                AND used_at IS NULL
        ");


    if (!$invalidate) {

        throw new RuntimeException(
            "Unable to invalidate previous authentication codes."
        );
    }


    $invalidate->bind_param(
        "is",
        $userId,
        $purpose
    );


    $invalidate->execute();

    $invalidate->close();


    /* SAVE HASHED OTP */

    $stmt =
        $conn->prepare("
            INSERT INTO authentication_codes
            (
                user_id,
                code_hash,
                purpose,
                expires_at
            )

            VALUES
            (
                ?,
                ?,
                ?,
                DATE_ADD(
                    NOW(),
                    INTERVAL 10 MINUTE
                )
            )
        ");


    if (!$stmt) {

        throw new RuntimeException(
            "Unable to create authentication code."
        );
    }


    $stmt->bind_param(
        "iss",
        $userId,
        $codeHash,
        $purpose
    );


    if (!$stmt->execute()) {

        $stmt->close();


        throw new RuntimeException(
            "Unable to create authentication code."
        );
    }


    $stmt->close();


    return $code;
}


/* VERIFY OTP */

function verifyAuthCode(
    mysqli $conn,
    int $userId,
    string $purpose,
    string $code
): bool {

    if (
        !isValidAuthPurpose(
            $purpose
        )
    ) {

        return false;
    }


    $stmt =
        $conn->prepare("
            SELECT
                id,
                code_hash,
                attempts

            FROM authentication_codes

            WHERE
                user_id = ?
                AND purpose = ?
                AND used_at IS NULL
                AND expires_at > NOW()

            ORDER BY id DESC

            LIMIT 1
        ");


    if (!$stmt) {

        return false;
    }


    $stmt->bind_param(
        "is",
        $userId,
        $purpose
    );


    $stmt->execute();


    $authCode =
        $stmt
            ->get_result()
            ->fetch_assoc();


    $stmt->close();


    if (!$authCode) {

        return false;
    }


    if (
        (int) $authCode[
            "attempts"
        ] >= 5
    ) {

        return false;
    }


    /* WRONG OTP */

    if (
        !password_verify(
            $code,
            $authCode[
                "code_hash"
            ]
        )
    ) {

        $attempt =
            $conn->prepare("
                UPDATE authentication_codes

                SET
                    attempts =
                    attempts + 1

                WHERE id = ?
            ");


        if ($attempt) {

            $authenticationCodeId =
                (int) $authCode[
                    "id"
                ];


            $attempt->bind_param(
                "i",
                $authenticationCodeId
            );


            $attempt->execute();

            $attempt->close();
        }


        return false;
    }


    /* MARK OTP AS USED */

    $used =
        $conn->prepare("
            UPDATE authentication_codes

            SET used_at = NOW()

            WHERE id = ?
        ");


    if (!$used) {

        return false;
    }


    $authenticationCodeId =
        (int) $authCode[
            "id"
        ];


    $used->bind_param(
        "i",
        $authenticationCodeId
    );


    $used->execute();

    $used->close();


    return true;
}


/* SEND OTP THROUGH BREVO */

function sendAuthCode(
    string $email,
    string $code,
    string $purpose
): bool {

    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        return false;
    }


    if (
        !isValidAuthPurpose(
            $purpose
        )
    ) {

        return false;
    }


    switch ($purpose) {

        case "registration":

            $subject =
                "Verify Your CVSWHO Account";

            $heading =
                "Account Verification";

            $description =
                "Use the verification code below to complete your CVSWHO account registration.";

            break;


        case "login":

            $subject =
                "CVSWHO Login Verification Code";

            $heading =
                "Login Verification";

            $description =
                "Use the verification code below to complete your CVSWHO login.";

            break;


        case "password_reset":

            $subject =
                "CVSWHO Password Reset Code";

            $heading =
                "Password Reset";

            $description =
                "Use the verification code below to continue resetting your CVSWHO password.";

            break;


        case "reactivation":

            $subject =
                "CVSWHO Account Reactivation Code";

            $heading =
                "Account Reactivation";

            $description =
                "Use the verification code below to reactivate your CVSWHO account.";

            break;


        case "email_change":

            $subject =
                "CVSWHO Email Verification Code";

            $heading =
                "Email Verification";

            $description =
                "Use the verification code below to confirm your CVSWHO email address.";

            break;


        default:

            return false;
    }


    $safeCode =
        htmlspecialchars(
            $code,
            ENT_QUOTES,
            "UTF-8"
        );


    $safeHeading =
        htmlspecialchars(
            $heading,
            ENT_QUOTES,
            "UTF-8"
        );


    $safeDescription =
        htmlspecialchars(
            $description,
            ENT_QUOTES,
            "UTF-8"
        );


    $htmlContent = '
    <!DOCTYPE html>

    <html>

    <head>
        <meta charset="UTF-8">
    </head>

    <body
        style="
            margin:0;
            padding:0;
            background:#f4f8f6;
            font-family:Arial,sans-serif;
            color:#1d2b24;
        "
    >

        <div
            style="
                max-width:520px;
                margin:40px auto;
                padding:0 20px;
            "
        >

            <div
                style="
                    background:#ffffff;
                    border:1px solid #dce7e0;
                    border-radius:14px;
                    overflow:hidden;
                "
            >

                <div
                    style="
                        padding:22px 28px;
                        border-bottom:1px solid #e5ece8;
                    "
                >

                    <strong
                        style="
                            color:#006b3f;
                            font-size:18px;
                        "
                    >
                        CVSWHO
                    </strong>


                    <div
                        style="
                            margin-top:3px;
                            color:#68766f;
                            font-size:11px;
                        "
                    >
                        Student Profile Management
                    </div>

                </div>


                <div
                    style="
                        padding:35px 28px;
                        text-align:center;
                    "
                >

                    <div
                        style="
                            color:#006b3f;
                            font-size:11px;
                            font-weight:bold;
                            letter-spacing:1px;
                        "
                    >
                        ' . $safeHeading . '
                    </div>


                    <h1
                        style="
                            margin:12px 0;
                            color:#003d24;
                            font-size:25px;
                        "
                    >
                        Verification Code
                    </h1>


                    <p
                        style="
                            color:#68766f;
                            font-size:13px;
                            line-height:1.6;
                        "
                    >
                        ' . $safeDescription . '
                    </p>


                    <div
                        style="
                            margin:28px 0;
                            padding:20px;
                            background:#eaf5ef;
                            border-radius:10px;
                        "
                    >

                        <div
                            style="
                                color:#006b3f;
                                font-size:30px;
                                font-weight:bold;
                                letter-spacing:8px;
                            "
                        >
                            ' . $safeCode . '
                        </div>

                    </div>


                    <p
                        style="
                            color:#68766f;
                            font-size:11px;
                            line-height:1.6;
                        "
                    >
                        This verification code expires
                        in 10 minutes.
                    </p>


                    <p
                        style="
                            color:#68766f;
                            font-size:11px;
                            line-height:1.6;
                        "
                    >
                        Never share this verification code
                        with anyone.
                    </p>


                    <p
                        style="
                            color:#68766f;
                            font-size:11px;
                            line-height:1.6;
                        "
                    >
                        If you did not request this code,
                        you can safely ignore this email.
                    </p>


                    <p
                        style="
                            margin-top:22px;
                            padding-top:18px;
                            border-top:1px solid #e5ece8;
                            color:#89968f;
                            font-size:10px;
                            line-height:1.6;
                        "
                    >
                        This is an automated message
                        from CVSWHO.
                        Please do not reply to this email.
                        This address is not monitored.
                    </p>

                </div>

            </div>


            <p
                style="
                    margin-top:16px;
                    text-align:center;
                    color:#89968f;
                    font-size:10px;
                "
            >
                CVSWHO Account Security
            </p>

        </div>

    </body>

    </html>
    ';


    $payload = [

        "sender" => [

            "name" =>
                BREVO_SENDER_NAME,

            "email" =>
                BREVO_SENDER_EMAIL

        ],


        "to" => [

            [

                "email" =>
                    $email

            ]

        ],


        "replyTo" => [

            "name" =>
                BREVO_REPLY_TO_NAME,

            "email" =>
                BREVO_REPLY_TO_EMAIL

        ],


        "subject" =>
            $subject,


        "htmlContent" =>
            $htmlContent,


        "tags" => [

            "cvswho-otp",

            $purpose

        ]
    ];


    $json =
        json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        );


    if ($json === false) {

        error_log(
            "Brevo JSON error: " .
            json_last_error_msg()
        );


        return false;
    }


    if (
        !function_exists(
            "curl_init"
        )
    ) {

        error_log(
            "PHP cURL extension is disabled."
        );


        return false;
    }


    $curl =
        curl_init(
            "https://api.brevo.com/v3/smtp/email"
        );


    if ($curl === false) {

        return false;
    }


    curl_setopt_array(
        $curl,
        [

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_POST =>
                true,

            CURLOPT_HTTPHEADER =>
                [

                    "accept: application/json",

                    "api-key: " .
                        BREVO_API_KEY,

                    "content-type: application/json"

                ],

            CURLOPT_POSTFIELDS =>
                $json,

            CURLOPT_CONNECTTIMEOUT =>
                10,

            CURLOPT_TIMEOUT =>
                20

        ]
    );


    $response =
        curl_exec(
            $curl
        );


    $httpCode =
        (int) curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );


    $curlError =
        curl_error(
            $curl
        );


    curl_close(
        $curl
    );


    if (
        $response === false ||
        $httpCode < 200 ||
        $httpCode >= 300
    ) {

        error_log(
            "Brevo email failed. HTTP: " .
            $httpCode .
            " | CURL: " .
            $curlError .
            " | RESPONSE: " .
            (string) $response
        );


        return false;
    }


    return true;
}


/* SESSION ROLE */

function getSessionRole(): string
{
    $role =
        $_SESSION[
            "user_role"
        ]
        ??
        $_SESSION[
            "role"
        ]
        ??
        "";


    return strtolower(
        trim(
            (string) $role
        )
    );
}


/* REQUIRE LOGIN */

function requireLogin(
    ?mysqli $conn = null
): void {

    if (
        !isset(
            $_SESSION[
                "user_id"
            ]
        )
    ) {

        header(
            "Location: login.php"
        );


        exit;
    }


    if ($conn === null) {

        return;
    }


    $userId =
        (int) $_SESSION[
            "user_id"
        ];


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

        logoutUser(
            "login.php"
        );
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
        $user[
            "account_status"
        ] !== "active" ||
        (int) $user[
            "email_verified"
        ] !== 1
    ) {

        logoutUser(
            "login.php"
        );
    }


    $_SESSION[
        "user_role"
    ] =
        $user[
            "role"
        ];


    $_SESSION[
        "role"
    ] =
        $user[
            "role"
        ];


    $_SESSION[
        "user_email"
    ] =
        $user[
            "email"
        ];
}


/* REQUIRE ADMIN */

function requireAdmin(
    ?mysqli $conn = null
): void {

    if (
        !isset(
            $_SESSION[
                "user_id"
            ]
        )
    ) {

        header(
            "Location: login.php"
        );


        exit;
    }


    if ($conn === null) {

        if (
            getSessionRole()
            !== "administrator"
        ) {

            header(
                "Location: login.php"
            );


            exit;
        }


        return;
    }


    $userId =
        (int) $_SESSION[
            "user_id"
        ];


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

        logoutUser(
            "login.php"
        );
    }


    $stmt->bind_param(
        "i",
        $userId
    );


    $stmt->execute();


    $administrator =
        $stmt
            ->get_result()
            ->fetch_assoc();


    $stmt->close();


    if (
        !$administrator ||
        $administrator[
            "role"
        ] !== "administrator" ||
        $administrator[
            "account_status"
        ] !== "active" ||
        (int) $administrator[
            "email_verified"
        ] !== 1
    ) {

        logoutUser(
            "login.php"
        );
    }


    $_SESSION[
        "user_role"
    ] =
        "administrator";


    $_SESSION[
        "role"
    ] =
        "administrator";


    $_SESSION[
        "user_email"
    ] =
        $administrator[
            "email"
        ];
}


/* LOGOUT */

function logoutUser(
    string $redirect =
        "login.php"
): void {

    $_SESSION = [];


    if (
        ini_get(
            "session.use_cookies"
        )
    ) {

        $params =
            session_get_cookie_params();


        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params[
                "path"
            ],
            $params[
                "domain"
            ],
            $params[
                "secure"
            ],
            $params[
                "httponly"
            ]
        );
    }


    session_destroy();


    header(
        "Location: " .
        $redirect
    );


    exit;
}