<?php

session_start();

require_once "db.php";
require_once "auth_functions.php";


requireAdmin($conn);


/* STUDENT */

$studentUserId =
    (int) (
        $_GET["id"]
        ?? $_POST["student_id"]
        ?? 0
    );


if ($studentUserId <= 0) {

    header(
        "Location: student_records.php"
    );

    exit;
}


/* CSRF */

if (
    empty(
        $_SESSION[
            "admin_student_csrf"
        ]
    )
) {

    $_SESSION[
        "admin_student_csrf"
    ] =
        bin2hex(
            random_bytes(32)
        );
}


$csrfToken =
    $_SESSION[
        "admin_student_csrf"
    ];


$error = "";

$success = "";


/* STATUS ACTION */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "")
        === "change_status"
) {

    $submittedToken =
        $_POST["csrf_token"]
        ?? "";


    $newStatus =
        $_POST["account_status"]
        ?? "";


    $allowedStatuses = [

        "active",
        "suspended",
        "archived"
    ];


    if (
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        $error =
            "Your form session expired.";

    } elseif (
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $error =
            "Invalid account status.";

    } else {

        $check =
            $conn->prepare("
                SELECT
                    role,
                    email_verified

                FROM users

                WHERE id = ?

                LIMIT 1
            ");


        $check->bind_param(
            "i",
            $studentUserId
        );


        $check->execute();


        $target =
            $check
                ->get_result()
                ->fetch_assoc();


        $check->close();


        if (
            !$target ||
            $target["role"] !== "student"
        ) {

            $error =
                "Student account could not be found.";

        } elseif (
            $newStatus === "active" &&
            (int) $target[
                "email_verified"
            ] !== 1
        ) {

            $error =
                "An unverified student account cannot be activated.";

        } else {

            $update =
                $conn->prepare("
                    UPDATE users

                    SET account_status = ?

                    WHERE
                        id = ?
                        AND role = 'student'

                    LIMIT 1
                ");


            $update->bind_param(
                "si",
                $newStatus,
                $studentUserId
            );


            if ($update->execute()) {

                $success =
                    "Student account status updated successfully.";

            } else {

                $error =
                    "Unable to update the student account.";
            }


            $update->close();
        }
    }
}


/* PROFILE */

$stmt =
    $conn->prepare("
        SELECT

            u.email,
            u.account_status,
            u.email_verified,
            u.last_login_at,
            u.created_at,

            sp.student_id,
            sp.first_name,
            sp.middle_name,
            sp.last_name,
            sp.suffix,

            sp.phone,
            sp.birthdate,
            sp.gender,
            sp.address,

            sp.program,
            sp.year_level,
            sp.section,
            sp.college,
            sp.campus,

            sp.profile_photo,
            sp.about_me,

            fi.father_name,
            fi.mother_name,
            fi.guardian_name,
            fi.guardian_contact

        FROM users u

        LEFT JOIN student_profiles sp
            ON sp.user_id = u.id

        LEFT JOIN family_information fi
            ON fi.user_id = u.id

        WHERE
            u.id = ?
            AND u.role = 'student'

        LIMIT 1
    ");


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


if (!$student) {

    http_response_code(404);

    die(
        "Student record not found."
    );
}


/* NAME */

$nameParts = [];


foreach (
    [
        $student["first_name"] ?? "",
        $student["middle_name"] ?? "",
        $student["last_name"] ?? "",
        $student["suffix"] ?? ""
    ]
    as $part
) {

    $part =
        trim(
            $part
        );


    if ($part !== "") {

        $nameParts[] =
            $part;
    }
}


$fullName =
    implode(
        " ",
        $nameParts
    );


if ($fullName === "") {

    $fullName =
        "Student";
}


/* COUNTS */

$counts = [

    "accomplishments" => 0,
    "hobbies" => 0,
    "organizations" => 0
];


$stmt =
    $conn->prepare("
        SELECT

            (
                SELECT COUNT(*)
                FROM accomplishments
                WHERE user_id = ?
            ) AS accomplishments,

            (
                SELECT COUNT(*)
                FROM hobbies
                WHERE user_id = ?
            )
            +
            (
                SELECT COUNT(*)
                FROM interests
                WHERE user_id = ?
            ) AS hobbies,

            (
                SELECT COUNT(*)
                FROM organizations
                WHERE user_id = ?
            ) AS organizations
    ");


$stmt->bind_param(
    "iiii",
    $studentUserId,
    $studentUserId,
    $studentUserId,
    $studentUserId
);


$stmt->execute();


$countRow =
    $stmt
        ->get_result()
        ->fetch_assoc();


$stmt->close();


if ($countRow) {

    $counts["accomplishments"] =
        (int) $countRow[
            "accomplishments"
        ];


    $counts["hobbies"] =
        (int) $countRow[
            "hobbies"
        ];


    $counts["organizations"] =
        (int) $countRow[
            "organizations"
        ];
}


/* EDUCATION */

$education = [];


$stmt =
    $conn->prepare("
        SELECT
            education_level,
            school_name,
            start_year,
            end_year,
            achievements

        FROM education

        WHERE user_id = ?

        ORDER BY id ASC
    ");


$stmt->bind_param(
    "i",
    $studentUserId
);


$stmt->execute();


$result =
    $stmt->get_result();


while (
    $row =
        $result->fetch_assoc()
) {

    $education[] =
        $row;
}


$stmt->close();


function adminValue(
    ?string $value
): string {

    $value =
        trim(
            (string) $value
        );


    return
        $value !== ""
            ? $value
            : "Not provided";
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
        Student Record | CVSWHO
    </title>

    <link
        rel="stylesheet"
        href="admin_student_profile.css"
    >

    <style>

        .admin-message {
            margin-bottom: 18px;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 9px;
        }

        .admin-message.success {
            color: #18794e;
            background: #eaf7ef;
            border: 1px solid #cee8d9;
        }

        .admin-message.error {
            color: #a23939;
            background: #fff0f0;
            border: 1px solid #efcccc;
        }

        .admin-actions {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border: 1px solid #dce7e0;
            border-radius: 11px;
        }

        .admin-actions h2 {
            color: #003d24;
            font-size: 14px;
        }

        .admin-actions p {
            margin-top: 6px;
            color: #68766f;
            font-size: 9px;
        }

        .status-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }

        .status-action {
            padding: 9px 13px;
            border-radius: 7px;
            border: 1px solid #dce7e0;
            background: white;
            font: inherit;
            font-size: 8px;
            font-weight: 700;
            cursor: pointer;
        }

        .activate {
            color: #18794e;
        }

        .suspend {
            color: #a36e17;
        }

        .archive {
            color: #8e3c3c;
        }

        .about {
            white-space: pre-wrap;
        }

    </style>

</head>

<body>


<header class="navbar">

    <div class="nav-container">

        <a
            href="admin_dashboard.php"
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
                    Administrator Portal
                </span>

            </div>

        </a>


        <nav class="desktop-nav">

            <a
                href="admin_dashboard.php"
                class="nav-link"
            >
                Dashboard
            </a>

            <a
                href="student_records.php"
                class="nav-link active"
            >
                Student Records
            </a>

            <a
                href="admin_settings.php"
                class="nav-link"
            >
                Settings
            </a>

        </nav>


        <a
            href="admin_dashboard.php?logout=1"
            class="logout-button"
        >
            Log out
        </a>

    </div>

</header>


<main class="page">


    <section class="page-header">

        <div>

            <span class="eyebrow">
                STUDENT RECORD
            </span>

            <h1>
                Student Profile
            </h1>

            <p>
                Review student and account information.
            </p>

        </div>

        <a
            href="student_records.php"
            class="back-button"
        >
            ← Back to Records
        </a>

    </section>


    <?php if (
        $success !== ""
    ): ?>

        <div class="admin-message success">

            <?= htmlspecialchars(
                $success
            ) ?>

        </div>

    <?php endif; ?>


    <?php if (
        $error !== ""
    ): ?>

        <div class="admin-message error">

            <?= htmlspecialchars(
                $error
            ) ?>

        </div>

    <?php endif; ?>


    <section class="profile-header">

        <div class="profile-photo">

            <?php if (
                !empty(
                    $student[
                        "profile_photo"
                    ]
                )
            ): ?>

                <img
                    src="<?= htmlspecialchars(
                        $student[
                            "profile_photo"
                        ]
                    ) ?>"
                    alt=""
                    style="
                        width:100%;
                        height:100%;
                        object-fit:cover;
                        border-radius:inherit;
                    "
                >

            <?php else: ?>

                <?= htmlspecialchars(
                    strtoupper(
                        substr(
                            $student[
                                "first_name"
                            ] ?: "S",
                            0,
                            1
                        )
                    )
                ) ?>

            <?php endif; ?>

        </div>


        <div class="profile-info">

            <span class="status">

                <?= htmlspecialchars(
                    strtoupper(
                        $student[
                            "account_status"
                        ]
                    )
                ) ?>

            </span>

            <h2>
                <?= htmlspecialchars(
                    $fullName
                ) ?>
            </h2>

            <p>
                <?= htmlspecialchars(
                    adminValue(
                        $student[
                            "student_id"
                        ]
                    )
                ) ?>
            </p>

            <span>

                <?= htmlspecialchars(
                    adminValue(
                        $student[
                            "program"
                        ]
                    )
                ) ?>

            </span>

        </div>

    </section>


    <!-- ACCOUNT MANAGEMENT -->

    <section class="admin-actions">

        <h2>
            Account Management
        </h2>

        <p>
            Administrators may change account status.
            Student profile content remains controlled
            by the student.
        </p>


        <div class="status-actions">


            <?php if (
                $student[
                    "account_status"
                ] !== "active"
            ): ?>

                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $csrfToken
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="student_id"
                        value="<?= $studentUserId ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="change_status"
                    >

                    <input
                        type="hidden"
                        name="account_status"
                        value="active"
                    >

                    <button
                        type="submit"
                        class="status-action activate"
                    >
                        Activate Account
                    </button>

                </form>

            <?php endif; ?>


            <?php if (
                $student[
                    "account_status"
                ] !== "suspended"
            ): ?>

                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $csrfToken
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="student_id"
                        value="<?= $studentUserId ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="change_status"
                    >

                    <input
                        type="hidden"
                        name="account_status"
                        value="suspended"
                    >

                    <button
                        type="submit"
                        class="status-action suspend"
                    >
                        Suspend Account
                    </button>

                </form>

            <?php endif; ?>


            <?php if (
                $student[
                    "account_status"
                ] !== "archived"
            ): ?>

                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $csrfToken
                        ) ?>"
                    >

                    <input
                        type="hidden"
                        name="student_id"
                        value="<?= $studentUserId ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="change_status"
                    >

                    <input
                        type="hidden"
                        name="account_status"
                        value="archived"
                    >

                    <button
                        type="submit"
                        class="status-action archive"
                    >
                        Archive Account
                    </button>

                </form>

            <?php endif; ?>


        </div>

    </section>


    <div class="profile-grid">


        <!-- PERSONAL -->

        <section class="profile-card">

            <span class="section-label">
                PERSONAL INFORMATION
            </span>

            <h2>
                Personal Details
            </h2>

            <div class="info-list">

                <div>
                    <span>Email</span>
                    <strong>
                        <?= htmlspecialchars(
                            $student["email"]
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Phone</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "phone"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Birthdate</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "birthdate"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Gender</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "gender"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Address</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "address"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

            </div>

        </section>


        <!-- ACADEMIC -->

        <section class="profile-card">

            <span class="section-label">
                ACADEMIC INFORMATION
            </span>

            <h2>
                Academic Details
            </h2>

            <div class="info-list">

                <div>
                    <span>Program</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "program"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Year Level</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "year_level"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Section</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "section"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>College</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "college"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Campus</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "campus"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

            </div>

        </section>


        <!-- ACCOUNT -->

        <section class="profile-card">

            <span class="section-label">
                ACCOUNT INFORMATION
            </span>

            <h2>
                Account Details
            </h2>

            <div class="info-list">

                <div>
                    <span>Status</span>
                    <strong>
                        <?= htmlspecialchars(
                            ucfirst(
                                $student[
                                    "account_status"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Email Verified</span>
                    <strong>
                        <?= (int) $student[
                            "email_verified"
                        ] === 1
                            ? "Yes"
                            : "No"
                        ?>
                    </strong>
                </div>

                <div>
                    <span>Last Login</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "last_login_at"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Account Created</span>
                    <strong>
                        <?= htmlspecialchars(
                            $student[
                                "created_at"
                            ]
                        ) ?>
                    </strong>
                </div>

            </div>

        </section>


        <!-- FAMILY -->

        <section class="profile-card">

            <span class="section-label">
                FAMILY INFORMATION
            </span>

            <h2>
                Parent & Guardian
            </h2>

            <div class="info-list">

                <div>
                    <span>Father</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "father_name"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Mother</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "mother_name"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Guardian</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "guardian_name"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

                <div>
                    <span>Guardian Contact</span>
                    <strong>
                        <?= htmlspecialchars(
                            adminValue(
                                $student[
                                    "guardian_contact"
                                ]
                            )
                        ) ?>
                    </strong>
                </div>

            </div>

        </section>


        <!-- PROFILE -->

        <section class="profile-card full">

            <span class="section-label">
                PROFILE INFORMATION
            </span>

            <h2>
                About the Student
            </h2>

            <p class="about">

                <?= htmlspecialchars(
                    adminValue(
                        $student[
                            "about_me"
                        ]
                    )
                ) ?>

            </p>


            <div class="summary-grid">

                <div>
                    <span>Accomplishments</span>
                    <strong>
                        <?= $counts[
                            "accomplishments"
                        ] ?>
                    </strong>
                </div>

                <div>
                    <span>Hobbies & Interests</span>
                    <strong>
                        <?= $counts[
                            "hobbies"
                        ] ?>
                    </strong>
                </div>

                <div>
                    <span>Organizations</span>
                    <strong>
                        <?= $counts[
                            "organizations"
                        ] ?>
                    </strong>
                </div>

            </div>

        </section>


        <!-- EDUCATION -->

        <section class="profile-card full">

            <span class="section-label">
                EDUCATIONAL BACKGROUND
            </span>

            <h2>
                Education History
            </h2>


            <div class="info-list">

                <?php if (
                    count($education) > 0
                ): ?>

                    <?php foreach (
                        $education
                        as $record
                    ): ?>

                        <div>

                            <span>

                                <?= htmlspecialchars(
                                    strtoupper(
                                        str_replace(
                                            "_",
                                            " ",
                                            $record[
                                                "education_level"
                                            ]
                                        )
                                    )
                                ) ?>

                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    adminValue(
                                        $record[
                                            "school_name"
                                        ]
                                    )
                                ) ?>

                            </strong>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div>

                        <span>Education</span>

                        <strong>
                            No education records added.
                        </strong>

                    </div>

                <?php endif; ?>

            </div>

        </section>


    </div>


</main>


<footer class="footer">

    <p>
        CVSWHO
    </p>

    <span>
        Administrator Portal
    </span>

</footer>


</body>

</html>