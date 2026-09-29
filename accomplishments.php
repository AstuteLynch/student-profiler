<?php

$accomplishments = [
    [
        "title" => "Academic Achievement",
        "category" => "Academic",
        "description" => "Recognized for academic performance and participation in academic activities.",
        "date" => "2025-06-15",
        "organization" => "Cavite State University",
        "document" => "Certificate of Recognition.pdf"
    ],
    [
        "title" => "Web Development Project",
        "category" => "Project",
        "description" => "Developed a student-focused web application as part of an academic project.",
        "date" => "2026-03-20",
        "organization" => "Cavite State University",
        "document" => "Project Certificate.pdf"
    ],
    [
        "title" => "Programming Competition",
        "category" => "Competition",
        "description" => "Participated in a programming competition and completed programming challenges.",
        "date" => "2025-11-08",
        "organization" => "Computer Science Department",
        "document" => "Competition Certificate.pdf"
    ],
    [
        "title" => "Leadership Participation",
        "category" => "Organization",
        "description" => "Participated in student organization activities and events.",
        "date" => "2025-09-10",
        "organization" => "Student Organization",
        "document" => "Certificate of Participation.pdf"
    ]
];

$categories = [
    "All",
    "Academic",
    "Competition",
    "Project",
    "Organization",
    "Certification",
    "Other"
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Accomplishments | StudentProfiler</title>

    <link rel="stylesheet" href="accomplishments.css">
</head>

<body>

    <header class="navbar">

        <div class="nav-container">

            <a href="student_dashboard.php" class="brand">

                <div class="brand-mark">
                    SP
                </div>

                <div class="brand-text">

                    <span class="brand-name">
                        StudentProfiler
                    </span>

                    <span class="brand-subtitle">
                        Student Profile Management
                    </span>

                </div>

            </a>

            <nav class="desktop-nav">

                <a href="student_dashboard.php" class="nav-link">
                    Dashboard
                </a>

                <a href="student_profile.php" class="nav-link">
                    Profile
                </a>

                <a href="accomplishments.php" class="nav-link active">
                    Accomplishments
                </a>

                <a href="settings.php" class="nav-link">
                    Settings
                </a>

            </nav>

            <a href="login.php" class="logout-button">
                Log out
            </a>

        </div>

    </header>


    <main class="accomplishments-page">

        <section class="page-header">

            <div>

                <span class="eyebrow">
                    MY PROFILE
                </span>

                <h1>
                    Accomplishments
                </h1>

                <p>
                    View and manage your achievements, certificates, projects, competitions, and other accomplishments.
                </p>

            </div>

            <a href="add_accomplishment.php" class="add-button">
                + Add Accomplishment
            </a>

        </section>


        <section class="summary-row">

            <div class="summary-card">

                <div class="summary-icon">
                    A
                </div>

                <div>

                    <span>
                        TOTAL ACCOMPLISHMENTS
                    </span>

                    <strong>
                        <?php echo count($accomplishments); ?>
                    </strong>

                </div>

            </div>


            <div class="summary-card">

                <div class="summary-icon">
                    C
                </div>

                <div>

                    <span>
                        CATEGORIES
                    </span>

                    <strong>
                        <?php echo count($categories) - 1; ?>
                    </strong>

                </div>

            </div>


            <div class="summary-card">

                <div class="summary-icon">
                    D
                </div>

                <div>

                    <span>
                        DOCUMENTS
                    </span>

                    <strong>
                        <?php echo count($accomplishments); ?>
                    </strong>

                </div>

            </div>

        </section>


        <section class="filter-card">

            <div class="filter-heading">

                <span class="section-label">
                    FILTER
                </span>

                <h2>
                    Browse Accomplishments
                </h2>

            </div>

            <div class="category-filters">

                <?php foreach ($categories as $index => $category): ?>

                    <button
                        type="button"
                        class="filter-button <?php echo $index === 0 ? 'active' : ''; ?>"
                        data-category="<?php echo htmlspecialchars($category); ?>"
                    >
                        <?php echo htmlspecialchars($category); ?>
                    </button>

                <?php endforeach; ?>

            </div>

        </section>


        <section class="accomplishments-section">

            <div class="section-top">

                <div>

                    <span class="section-label">
                        ACHIEVEMENTS
                    </span>

                    <h2>
                        Your Accomplishments
                    </h2>

                </div>

                <span class="result-count">
                    <?php echo count($accomplishments); ?> records
                </span>

            </div>


            <div class="accomplishment-list" id="accomplishmentList">

                <?php foreach ($accomplishments as $accomplishment): ?>

                    <article
                        class="accomplishment-card"
                        data-category="<?php echo htmlspecialchars($accomplishment["category"]); ?>"
                    >

                        <div class="accomplishment-icon">
                            ✓
                        </div>

                        <div class="accomplishment-content">

                            <div class="accomplishment-header">

                                <div>

                                    <span class="category-label">
                                        <?php echo htmlspecialchars($accomplishment["category"]); ?>
                                    </span>

                                    <h3>
                                        <?php echo htmlspecialchars($accomplishment["title"]); ?>
                                    </h3>

                                </div>

                                <span class="achievement-date">
                                    <?php echo date("M d, Y", strtotime($accomplishment["date"])); ?>
                                </span>

                            </div>

                            <p class="description">
                                <?php echo htmlspecialchars($accomplishment["description"]); ?>
                            </p>

                            <div class="accomplishment-footer">

                                <div class="organization">

                                    <span class="footer-label">
                                        ORGANIZATION
                                    </span>

                                    <span>
                                        <?php echo htmlspecialchars($accomplishment["organization"]); ?>
                                    </span>

                                </div>

                                <a
                                    href="#"
                                    class="certificate-button"
                                >
                                    View Certificate
                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


            <div class="empty-state" id="emptyState">

                <div class="empty-icon">
                    —
                </div>

                <h3>
                    No accomplishments found
                </h3>

                <p>
                    There are no accomplishments under this category.
                </p>

            </div>

        </section>

    </main>


    <footer class="footer">

        <p>
            StudentProfiler
        </p>

        <span>
            Manage your student profile with ease.
        </span>

    </footer>


    <script>

        const filterButtons = document.querySelectorAll(".filter-button");
        const accomplishmentCards = document.querySelectorAll(".accomplishment-card");
        const emptyState = document.getElementById("emptyState");

        filterButtons.forEach(button => {

            button.addEventListener("click", () => {

                filterButtons.forEach(item => {
                    item.classList.remove("active");
                });

                button.classList.add("active");

                const selectedCategory = button.dataset.category;

                let visibleCount = 0;

                accomplishmentCards.forEach(card => {

                    const cardCategory = card.dataset.category;

                    if (
                        selectedCategory === "All" ||
                        cardCategory === selectedCategory
                    ) {

                        card.style.display = "flex";
                        visibleCount++;

                    } else {

                        card.style.display = "none";

                    }

                });

                emptyState.style.display =
                    visibleCount === 0 ? "flex" : "none";

            });

        });

    </script>

</body>
</html>