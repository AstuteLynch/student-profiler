<?php

function isCvsuEmail(string $email): bool
{
    return (bool) preg_match(
        '/^[a-zA-Z]+\.[a-zA-Z]+@cvsu\.edu\.ph$/',
        $email
    );
}

function generateAuthCode(): string
{
    return str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
}

function createAuthCode(mysqli $conn, int $userId, string $purpose): string
{
    $code = generateAuthCode();
    $codeHash = password_hash($code, PASSWORD_DEFAULT);
    $expiresAt = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    $invalidate = $conn->prepare("
        UPDATE authentication_codes
        SET used_at = NOW()
        WHERE user_id = ?
        AND purpose = ?
        AND used_at IS NULL
    ");

    $invalidate->bind_param("is", $userId, $purpose);
    $invalidate->execute();

    $stmt = $conn->prepare("
        INSERT INTO authentication_codes
        (user_id, code_hash, purpose, expires_at)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isss",
        $userId,
        $codeHash,
        $purpose,
        $expiresAt
    );

    $stmt->execute();

    return $code;
}

function verifyAuthCode(mysqli $conn, int $userId, string $purpose, string $code): bool
{
    $stmt = $conn->prepare("
        SELECT id, code_hash, expires_at, attempts
        FROM authentication_codes
        WHERE user_id = ?
        AND purpose = ?
        AND used_at IS NULL
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->bind_param("is", $userId, $purpose);
    $stmt->execute();

    $result = $stmt->get_result();
    $authCode = $result->fetch_assoc();

    if (!$authCode) {
        return false;
    }

    if (strtotime($authCode["expires_at"]) < time()) {
        return false;
    }

    if ((int) $authCode["attempts"] >= 5) {
        return false;
    }

    if (!password_verify($code, $authCode["code_hash"])) {
        $attempt = $conn->prepare("
            UPDATE authentication_codes
            SET attempts = attempts + 1
            WHERE id = ?
        ");

        $attempt->bind_param("i", $authCode["id"]);
        $attempt->execute();

        return false;
    }

    $used = $conn->prepare("
        UPDATE authentication_codes
        SET used_at = NOW()
        WHERE id = ?
    ");

    $used->bind_param("i", $authCode["id"]);
    $used->execute();

    return true;
}

function sendAuthCode(string $email, string $code, string $purpose): bool
{
    if ($purpose === "registration") {
        $subject = "CVSWHO Email Verification Code";
        $message = "Your CVSWHO verification code is: " . $code . "\n\nThis code will expire in 10 minutes.";
    } else {
        $subject = "CVSWHO Login Verification Code";
        $message = "Your CVSWHO login verification code is: " . $code . "\n\nThis code will expire in 10 minutes.";
    }

    $headers = "From: CVSWHO <no-reply@cvsu.edu.ph>\r\n";
    $headers .= "Reply-To: no-reply@cvsu.edu.ph\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return mail($email, $subject, $message, $headers);
}

function requireLogin(): void
{
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }
}

function requireAdmin(): void
{
    if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "administrator") {
        header("Location: login.php");
        exit;
    }
}