const nav = document.querySelector(".desktop-nav");
const navLinks = document.querySelectorAll(".nav-link");
const sections = document.querySelectorAll("main section[id]");


/* UNDERLINE */

function moveUnderline(link) {

    if (!nav || !link) {
        return;
    }

    nav.style.setProperty(
        "--underline-left",
        `${link.offsetLeft}px`
    );

    nav.style.setProperty(
        "--underline-width",
        `${link.offsetWidth}px`
    );
}


/* PAGE NAME */

function getCurrentPage() {

    const pathname =
        window.location.pathname;

    const page =
        pathname.substring(
            pathname.lastIndexOf("/") + 1
        );

    return page || "index.php";
}


/* PAGE NAVIGATION */

function setActivePageLink() {

    const currentPage =
        getCurrentPage();

    let matchedLink = null;


    navLinks.forEach(link => {

        const href =
            link.getAttribute("href") || "";


        if (
            href === "" ||
            href.startsWith("#") ||
            href.startsWith("http://") ||
            href.startsWith("https://")
        ) {

            return;
        }


        const cleanHref =
            href.split("?")[0]
                .split("#")[0];


        const isActive =
            cleanHref === currentPage;


        link.classList.toggle(
            "active",
            isActive
        );


        if (isActive) {

            matchedLink = link;
        }
    });


    if (matchedLink) {

        moveUnderline(
            matchedLink
        );
    }
}


/* LANDING PAGE */

function setActiveSection(id) {

    navLinks.forEach(link => {

        const href =
            link.getAttribute("href") || "";


        if (!href.startsWith("#")) {
            return;
        }


        const isActive =
            href === `#${id}`;


        link.classList.toggle(
            "active",
            isActive
        );


        if (isActive) {

            moveUnderline(
                link
            );
        }
    });
}


/* DETECT NAV TYPE */

const hasSectionNavigation =
    Array.from(navLinks).some(
        link => {

            const href =
                link.getAttribute("href")
                || "";

            return href.startsWith("#");
        }
    );


/* LANDING PAGE BEHAVIOR */

if (
    hasSectionNavigation &&
    sections.length > 0
) {

    navLinks.forEach(link => {

        const href =
            link.getAttribute("href")
            || "";


        if (!href.startsWith("#")) {
            return;
        }


        link.addEventListener(
            "click",
            function () {

                const id =
                    href.substring(1);


                if (id !== "") {

                    setActiveSection(
                        id
                    );
                }
            }
        );
    });


    window.addEventListener(
        "scroll",
        function () {

            let currentSection =
                sections.length > 0
                    ? sections[0].id
                    : "";


            sections.forEach(section => {

                const sectionTop =
                    section.offsetTop - 150;


                if (
                    window.scrollY >=
                    sectionTop
                ) {

                    currentSection =
                        section.id;
                }
            });


            if (
                currentSection !== ""
            ) {

                setActiveSection(
                    currentSection
                );
            }
        }
    );


    const currentHash =
        window.location.hash
            .substring(1);


    if (currentHash !== "") {

        setActiveSection(
            currentHash
        );

    } else if (
        sections.length > 0
    ) {

        setActiveSection(
            sections[0].id
        );
    }


/* PHP PAGE BEHAVIOR */

} else {

    setActivePageLink();
}


/* RESIZE */

window.addEventListener(
    "resize",
    function () {

        const activeLink =
            document.querySelector(
                ".desktop-nav .nav-link.active"
            );


        moveUnderline(
            activeLink
        );
    }
);


/* INITIAL UNDERLINE */

window.addEventListener(
    "load",
    function () {

        const activeLink =
            document.querySelector(
                ".desktop-nav .nav-link.active"
            );


        moveUnderline(
            activeLink
        );
    }
);