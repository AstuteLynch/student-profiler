<?php

/* ADMIN AUTH */

function requireAdministrator(mysqli $conn): array
{
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }

    $adminUserId = (int) $_SESSION["user_id"];

    $stmt = $conn->prepare("
        SELECT
            u.id,
            u.email,
            u.role,
            u.account_status,
            u.email_verified,
            a.first_name,
            a.last_name,
            a.permissions
        FROM users u
        LEFT JOIN administrators a
            ON a.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    $stmt->bind_param("i", $adminUserId);
    $stmt->execute();

    $admin = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (
        !$admin ||
        ($admin["role"] ?? "") !== "administrator" ||
        ($admin["account_status"] ?? "") !== "active" ||
        (int)($admin["email_verified"] ?? 0) !== 1
    ) {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    return $admin;
}


/* CSRF */

function getAdminCsrfToken(): string
{
    if (empty($_SESSION["admin_csrf"])) {
        $_SESSION["admin_csrf"] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION["admin_csrf"];
}


function verifyAdminCsrfToken(?string $token): bool
{
    if (
        empty($_SESSION["admin_csrf"]) ||
        !$token
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION["admin_csrf"],
        $token
    );
}


/* EXPIRED TRASH */

function purgeExpiredStudentAccounts(mysqli $conn): void
{
    $stmt = $conn->prepare("
        DELETE u
        FROM users u
        INNER JOIN deleted_student_accounts d
            ON d.user_id = u.id
        WHERE
            u.role = 'student'
            AND d.purge_at <= NOW()
    ");

    if ($stmt) {
        $stmt->execute();
        $stmt->close();
    }
}


/* MOVE STUDENT TO TRASH */

function moveStudentToTrash(
    mysqli $conn,
    int $studentUserId,
    int $adminUserId
): bool {

    if ($studentUserId <= 0) {
        return false;
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            SELECT
                role,
                account_status
            FROM users
            WHERE id = ?
            LIMIT 1
            FOR UPDATE
        ");

        if (!$stmt) {
            throw new Exception(
                "Unable to locate student account."
            );
        }

        $stmt->bind_param(
            "i",
            $studentUserId
        );

        $stmt->execute();

        $student =
            $stmt
                ->get_result()
                ->fetch_assoc();

        $stmt->close();

        if (
            !$student ||
            $student["role"] !== "student"
        ) {
            throw new Exception(
                "Invalid student account."
            );
        }

        $previousStatus =
            $student["account_status"];

        $stmt = $conn->prepare("
            INSERT INTO deleted_student_accounts (
                user_id,
                deleted_by_admin_user_id,
                previous_account_status,
                deleted_at,
                purge_at
            )
            VALUES (
                ?,
                ?,
                ?,
                NOW(),
                DATE_ADD(NOW(), INTERVAL 30 DAY)
            )

            ON DUPLICATE KEY UPDATE
                deleted_by_admin_user_id =
                    VALUES(deleted_by_admin_user_id),

                previous_account_status =
                    VALUES(previous_account_status),

                deleted_at =
                    NOW(),

                purge_at =
                    DATE_ADD(NOW(), INTERVAL 30 DAY)
        ");

        if (!$stmt) {
            throw new Exception(
                "Unable to create trash record."
            );
        }

        $stmt->bind_param(
            "iis",
            $studentUserId,
            $adminUserId,
            $previousStatus
        );

        if (!$stmt->execute()) {
            throw new Exception(
                "Unable to move student to trash."
            );
        }

        $stmt->close();

        $stmt = $conn->prepare("
            UPDATE users
            SET account_status = 'archived'
            WHERE
                id = ?
                AND role = 'student'
        ");

        if (!$stmt) {
            throw new Exception(
                "Unable to disable student account."
            );
        }

        $stmt->bind_param(
            "i",
            $studentUserId
        );

        if (!$stmt->execute()) {
            throw new Exception(
                "Unable to disable student account."
            );
        }

        $stmt->close();

        $conn->commit();

        return true;

    } catch (Throwable $e) {
        $conn->rollback();

        return false;
    }
}


/* RESTORE STUDENT */

function restoreStudentFromTrash(
    mysqli $conn,
    int $studentUserId
): bool {

    if ($studentUserId <= 0) {
        return false;
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            SELECT
                previous_account_status
            FROM deleted_student_accounts
            WHERE user_id = ?
            LIMIT 1
            FOR UPDATE
        ");

        if (!$stmt) {
            throw new Exception(
                "Unable to find trash record."
            );
        }

        $stmt->bind_param(
            "i",
            $studentUserId
        );

        $stmt->execute();

        $trash =
            $stmt
                ->get_result()
                ->fetch_assoc();

        $stmt->close();

        if (!$trash) {
            throw new Exception(
                "Student is not in trash."
            );
        }

        $restoreStatus =
            $trash["previous_account_status"];

        $allowedStatuses = [
            "pending",
            "active",
            "archived",
            "deactivated",
            "suspended"
        ];

        if (
            !in_array(
                $restoreStatus,
                $allowedStatuses,
                true
            )
        ) {
            $restoreStatus = "active";
        }

        $stmt = $conn->prepare("
            UPDATE users
            SET account_status = ?
            WHERE
                id = ?
                AND role = 'student'
        ");

        if (!$stmt) {
            throw new Exception(
                "Unable to restore account."
            );
        }

        $stmt->bind_param(
            "si",
            $restoreStatus,
            $studentUserId
        );

        if (!$stmt->execute()) {
            throw new Exception(
                "Unable to restore account."
            );
        }

        $stmt->close();

        $stmt = $conn->prepare("
            DELETE FROM deleted_student_accounts
            WHERE user_id = ?
        ");

        if (!$stmt) {
            throw new Exception(
                "Unable to clear trash record."
            );
        }

        $stmt->bind_param(
            "i",
            $studentUserId
        );

        if (!$stmt->execute()) {
            throw new Exception(
                "Unable to clear trash record."
            );
        }

        $stmt->close();

        $conn->commit();

        return true;

    } catch (Throwable $e) {
        $conn->rollback();

        return false;
    }
}


/* PERMANENT DELETE */

function permanentlyDeleteStudent(
    mysqli $conn,
    int $studentUserId
): bool {

    if ($studentUserId <= 0) {
        return false;
    }

    $stmt = $conn->prepare("
        SELECT d.user_id
        FROM deleted_student_accounts d
        INNER JOIN users u
            ON u.id = d.user_id
        WHERE
            d.user_id = ?
            AND u.role = 'student'
        LIMIT 1
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "i",
        $studentUserId
    );

    $stmt->execute();

    $trash =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    if (!$trash) {
        return false;
    }

    $stmt = $conn->prepare("
        DELETE FROM users
        WHERE
            id = ?
            AND role = 'student'
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "i",
        $studentUserId
    );

    $success =
        $stmt->execute();

    $stmt->close();

    return $success;
}