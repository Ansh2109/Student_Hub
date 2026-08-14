const menuToggle = document.getElementById("menuToggle");
const mainNav = document.getElementById("mainNav");

if (menuToggle && mainNav) {

    menuToggle.addEventListener("click", function () {

        mainNav.classList.toggle("open");

        const isOpen =
            mainNav.classList.contains("open");

        menuToggle.setAttribute(
            "aria-label",
            isOpen
                ? "Close navigation menu"
                : "Open navigation menu"
        );

    });

}


/* =================================================
   2. LIGHT / DARK THEME
================================================= */

const themeToggle =
    document.getElementById("themeToggle");


function updateThemeButton() {

    if (!themeToggle) {
        return;
    }

    if (document.body.classList.contains("dark-theme")) {

        themeToggle.textContent =
            "☀️ Light Mode";

    } else {

        themeToggle.textContent =
            "🌙 Dark Mode";

    }

}


const savedTheme =
    localStorage.getItem("studentHubTheme");


if (savedTheme === "dark") {

    document.body.classList.add("dark-theme");

}


updateThemeButton();


if (themeToggle) {

    themeToggle.addEventListener("click", function () {

        document.body.classList.toggle("dark-theme");


        const isDark =
            document.body.classList.contains("dark-theme");


        localStorage.setItem(
            "studentHubTheme",
            isDark ? "dark" : "light"
        );


        updateThemeButton();

    });

}


/* =================================================
   3. NOTIFICATION BANNER
================================================= */

const notification =
    document.getElementById("notification");

const closeNotification =
    document.getElementById("closeNotification");


if (notification && closeNotification) {

    closeNotification.addEventListener(
        "click",
        function () {

            notification.style.display = "none";

        }
    );

}


/* =================================================
   4. MODAL POPUP
================================================= */

const eventModal =
    document.getElementById("eventModal");

const openModal =
    document.getElementById("openModal");

const closeModal =
    document.getElementById("closeModal");

const modalCloseButton =
    document.getElementById("modalCloseButton");

const modalTitle =
    document.getElementById("modalTitle");

const modalText =
    document.getElementById("modalText");


function showModal(title, text) {

    if (!eventModal) {
        return;
    }


    if (modalTitle) {
        modalTitle.textContent = title;
    }


    if (modalText) {
        modalText.textContent = text;
    }


    eventModal.classList.add("show");

}


function hideModal() {

    if (eventModal) {

        eventModal.classList.remove("show");

    }

}


if (openModal) {

    openModal.addEventListener(
        "click",
        function () {

            showModal(
                "About StudentHub",
                "StudentHub helps students manage academic activities, events, profiles and campus communication."
            );

        }
    );

}


if (closeModal) {

    closeModal.addEventListener(
        "click",
        hideModal
    );

}


if (modalCloseButton) {

    modalCloseButton.addEventListener(
        "click",
        hideModal
    );

}


if (eventModal) {

    eventModal.addEventListener(
        "click",
        function (event) {

            if (event.target === eventModal) {

                hideModal();

            }

        }
    );

}


/* =================================================
   EVENT PAGE MODAL BUTTONS
================================================= */

const eventButtons =
    document.querySelectorAll(
        ".event-details-btn"
    );


eventButtons.forEach(function (button) {

    button.addEventListener(
        "click",
        function () {

            const eventName =
                button.dataset.event;


            showModal(
                eventName,
                "You can register for this event through the StudentHub Events section."
            );

        }
    );

});


/* =================================================
   5. CONTENT SLIDER
================================================= */

const slides =
    document.querySelectorAll(".slide");

const nextSlide =
    document.getElementById("nextSlide");

const previousSlide =
    document.getElementById("previousSlide");


let currentSlide = 0;


function showSlide(index) {

    if (slides.length === 0) {
        return;
    }


    slides.forEach(function (slide) {

        slide.classList.remove("active");

    });


    slides[index].classList.add("active");

}


if (slides.length > 0) {

    showSlide(currentSlide);


    if (nextSlide) {

        nextSlide.addEventListener(
            "click",
            function () {

                currentSlide++;


                if (
                    currentSlide >=
                    slides.length
                ) {

                    currentSlide = 0;

                }


                showSlide(currentSlide);

            }
        );

    }


    if (previousSlide) {

        previousSlide.addEventListener(
            "click",
            function () {

                currentSlide--;


                if (currentSlide < 0) {

                    currentSlide =
                        slides.length - 1;

                }


                showSlide(currentSlide);

            }
        );

    }

}


/* =================================================
   6. COLLAPSIBLE FAQ
================================================= */

const faqQuestions =
    document.querySelectorAll(
        ".faq-question"
    );


faqQuestions.forEach(function (question) {

    question.addEventListener(
        "click",
        function () {

            const answer =
                question.nextElementSibling;

            const icon =
                question.querySelector("span");


            answer.classList.toggle("open");


            const isOpen =
                answer.classList.contains("open");


            if (icon) {

                icon.textContent =
                    isOpen ? "−" : "+";

            }

        }
    );

});


/* =================================================
   7. REGISTRATION FORM
================================================= */

const registerForm =
    document.getElementById("registerForm");

const registerMessage =
    document.getElementById("registerMessage");


if (registerForm) {

    registerForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            const fullname =
                document.getElementById("fullname").value;

            const email =
                document.getElementById("email").value;

            const phone =
                document.getElementById("phone").value;

            const password =
                document.getElementById("password").value;

            const confirmPassword =
                document.getElementById(
                    "confirmPassword"
                ).value;


            if (password !== confirmPassword) {

                registerMessage.textContent =
                    "Passwords do not match.";

                registerMessage.className =
                    "error-message show";

                return;

            }


            const student = {

                fullname: fullname,
                email: email,
                phone: phone,
                password: password

            };


            localStorage.setItem(
                "studentHubUser",
                JSON.stringify(student)
            );


            registerMessage.textContent =
                "Registration successful! Redirecting to login...";


            registerMessage.className =
                "success-message show";


            registerForm.reset();


            setTimeout(function () {

                window.location.href =
                    "login.html";

            }, 1500);

        }
    );

}


/* =================================================
   8. LOGIN FORM
================================================= */

const loginForm =
    document.getElementById("loginForm");

const loginMessage =
    document.getElementById("loginMessage");


if (loginForm) {

    loginForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            const loginEmail =
                document.getElementById(
                    "loginEmail"
                ).value;


            const loginPassword =
                document.getElementById(
                    "loginPassword"
                ).value;


            const storedUser =
                localStorage.getItem(
                    "studentHubUser"
                );


            if (!storedUser) {

                loginMessage.textContent =
                    "No account found. Please register first.";

                loginMessage.className =
                    "error-message show";

                return;

            }


            const user =
                JSON.parse(storedUser);


            if (
                loginEmail === user.email &&
                loginPassword === user.password
            ) {

                localStorage.setItem(
                    "studentHubLoggedIn",
                    "true"
                );


                loginMessage.textContent =
                    "Login successful! Redirecting to dashboard...";


                loginMessage.className =
                    "success-message show";


                setTimeout(function () {

                    window.location.href =
                        "dashboard.html";

                }, 1000);

            } else {

                loginMessage.textContent =
                    "Invalid email or password.";

                loginMessage.className =
                    "error-message show";

            }

        }
    );

}


/* =================================================
   9. CONTACT FORM
================================================= */

const contactForm =
    document.getElementById("contactForm");

const contactMessage =
    document.getElementById("contactMessage");


if (contactForm) {

    contactForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            contactMessage.textContent =
                "Your message has been sent successfully!";


            contactMessage.className =
                "success-message show";


            contactForm.reset();

        }
    );

}


/* =================================================
   10. FEEDBACK FORM
================================================= */

const feedbackForm =
    document.getElementById("feedbackForm");

const feedbackMessage =
    document.getElementById("feedbackMessage");


if (feedbackForm) {

    feedbackForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            feedbackMessage.textContent =
                "Thank you! Your feedback has been submitted successfully.";


            feedbackMessage.className =
                "success-message show";


            feedbackForm.reset();

        }
    );

}
/* =================================================
   11. DASHBOARD LOGIN PROTECTION
================================================= */

const currentPage = window.location.pathname;

if (currentPage.includes("dashboard.html")) {

    const loggedIn =
        localStorage.getItem("studentHubLoggedIn");

    if (loggedIn !== "true") {

        window.location.href = "login.html";

    }
}

const logoutButton =
    document.getElementById("logoutButton");

if (logoutButton) {

    logoutButton.addEventListener("click", function () {

        localStorage.removeItem("studentHubLoggedIn");

        window.location.href = "login.html";

    });

}