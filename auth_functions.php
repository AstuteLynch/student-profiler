
<?php

/* CVSU EMAIL */

function isCvsuEmail(string $email): bool
{
    return (bool) preg_match(
        '/^[a-zA-Z]+\.[a-zA-Z]+@cvsu\.edu\.ph$/',
        trim($email)
    );
}


/* AUTH CODE */

function generateAuthCode(): string
{
    return str_pad(
        (string) random_int(0, 999999),
        6,
        "0",
        STR_PAD_LEFT
    );
}


function createAuthCode(
    mysqli $conn,
    int $userId,
    string $purpose
): string {

    $code = generateAuthCode();

    $codeHash = password_hash(
        $code,
        PASSWORD_DEFAULT
    );

    $expiresAt = date(
        "Y-m-d H:i:s",
        strtotime("+10 minutes")
    );

    $invalidate = $conn->prepare("
        UPDATE authentication_codes
        SET used_at = NOW()
        WHERE
            user_id = ?
            AND purpose = ?
            AND used_at IS NULL
    ");

    if ($invalidate) {

        $invalidate->bind_param(
            "is",
            $userId,
            $purpose
        );

        $invalidate->execute();
        $invalidate->close();
    }

    $stmt = $conn->prepare("
        INSERT INTO authentication_codes
        (
            user_id,
            code_hash,
            purpose,
            expires_at
        )
        VALUES (?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new RuntimeException(
            "Unable to create authentication code."
        );
    }

    $stmt->bind_param(
        "isss",
        $userId,
        $codeHash,
        $purpose,
        $expiresAt
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


function verifyAuthCode(
    mysqli $conn,
    int $userId,
    string $purpose,
    string $code
): bool {

    $stmt = $conn->prepare("
        SELECT
            id,
            code_hash,
            expires_at,
            attempts
        FROM authentication_codes
        WHERE
            user_id = ?
            AND purpose = ?
            AND used_at IS NULL
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

    $authCode = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    if (!$authCode) {
        return false;
    }

    if (
        strtotime($authCode["expires_at"]) < time()
    ) {
        return false;
    }

    if (
        (int) $authCode["attempts"] >= 5
    ) {
        return false;
    }

    if (
        !password_verify(
            $code,
            $authCode["code_hash"]
        )
    ) {

        $attempt = $conn->prepare("
            UPDATE authentication_codes
            SET attempts = attempts + 1
            WHERE id = ?
        ");

        if ($attempt) {

            $authenticationCodeId =
                (int) $authCode["id"];

            $attempt->bind_param(
                "i",
                $authenticationCodeId
            );

            $attempt->execute();
            $attempt->close();
        }

        return false;
    }

    $used = $conn->prepare("
        UPDATE authentication_codes
        SET used_at = NOW()
        WHERE id = ?
    ");

    if ($used) {

        $authenticationCodeId =
            (int) $authCode["id"];

        $used->bind_param(
            "i",
            $authenticationCodeId
        );

        $used->execute();
        $used->close();
    }

    return true;
}


/* SEND CODE - LOCAL DEVELOPMENT */

function sendAuthCode(
    string $email,
    string $code,
    string $purpose
): bool {

    /*
       DEVELOPMENT OTP

       Uses the existing OTP system without SMTP.
       Only permitted when accessed locally.

       No changes to OTP generation, database
       validation, or verification are required.
    */

    $localAddresses = [
        "127.0.0.1",
        "::1"
    ];

    $clientAddress =
        $_SERVER["REMOTE_ADDR"] ?? "";

    if (
        !in_array(
            $clientAddress,
            $localAddresses,
            true
        )
    ) {
        return false;
    }

    if (
        session_status() !== PHP_SESSION_ACTIVE
    ) {
        return false;
    }

    // Store OTP for the existing verification page
    $_SESSION["dev_auth_code"] = $code;

    // Associate the OTP with its intended purpose
    $_SESSION["dev_auth_purpose"] = $purpose;

    // No SMTP connection is required
    return true;
}


/* SESSION ROLE */

function getSessionRole(): string
{
    $role =
        $_SESSION["user_role"]
        ?? $_SESSION["role"]
        ?? "";

    return strtolower(
        trim((string) $role)
    );
}


/* LOGIN */

function requireLogin(
    ?mysqli $conn = null
): void {

    if (
        !isset($_SESSION["user_id"])
    ) {

        header("Location: login.php");
        exit;
    }

    if ($conn === null) {
        return;
    }

    $userId =
        (int) $_SESSION["user_id"];

    $stmt = $conn->prepare("
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
        logoutUser("login.php");
    }

    $stmt->bind_param(
        "i",
        $userId
    );

    $stmt->execute();

    $user = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    if (
        !$user ||
        $user["account_status"] !== "active" ||
        (int) $user["email_verified"] !== 1
    ) {

        logoutUser("login.php");
    }

    $_SESSION["user_role"] =
        $user["role"];

    $_SESSION["role"] =
        $user["role"];

    $_SESSION["user_email"] =
        $user["email"];
}


/* ADMIN */

function requireAdmin(
    ?mysqli $conn = null
): void {

    if (
        !isset($_SESSION["user_id"])
    ) {

        header("Location: login.php");
        exit;
    }

    if ($conn === null) {

        if (
            getSessionRole() !== "administrator"
        ) {

            header("Location: login.php");
            exit;
        }

        return;
    }

    $userId =
        (int) $_SESSION["user_id"];

    $stmt = $conn->prepare("
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
        logoutUser("login.php");
    }

    $stmt->bind_param(
        "i",
        $userId
    );

    $stmt->execute();

    $administrator = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    if (
        !$administrator ||
        $administrator["role"] !== "administrator" ||
        $administrator["account_status"] !== "active" ||
        (int) $administrator["email_verified"] !== 1
    ) {

        logoutUser("login.php");
    }

    $_SESSION["user_role"] =
        "administrator";

    $_SESSION["role"] =
        "administrator";

    $_SESSION["user_email"] =
        $administrator["email"];
}


/* LOGOUT */

function logoutUser(
    string $redirect = "login.php"
): void {

    $_SESSION = [];

    if (
        ini_get("session.use_cookies")
    ) {

        $params =
            session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header(
        "Location: " . $redirect
    );

    exit;
}
