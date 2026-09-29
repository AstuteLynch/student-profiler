const nav = document.querySelector('.desktop-nav');
const navLinks = document.querySelectorAll('.nav-link');
const sections = document.querySelectorAll('main section[id]');

function moveUnderline(link) {
    if (!nav || !link) return;

    nav.style.setProperty('--underline-left', `${link.offsetLeft}px`);
    nav.style.setProperty('--underline-width', `${link.offsetWidth}px`);
}

function setActiveLink(id) {
    navLinks.forEach(link => {
        const isActive = link.getAttribute('href') === `#${id}`;
        link.classList.toggle('active', isActive);

        if (isActive) {
            moveUnderline(link);
        }
    });
}

navLinks.forEach(link => {
    link.addEventListener('click', () => {
        setActiveLink(link.getAttribute('href').substring(1));
    });
});

window.addEventListener('scroll', () => {
    let currentSection = 'home';

    sections.forEach(section => {
        const sectionTop = section.offsetTop - 150;

        if (window.scrollY >= sectionTop) {
            currentSection = section.id;
        }
    });

    setActiveLink(currentSection);
});

window.addEventListener('resize', () => {
    const activeLink = document.querySelector('.nav-link.active');
    moveUnderline(activeLink);
});

moveUnderline(document.querySelector('.nav-link.active'));