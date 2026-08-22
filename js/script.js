const menuToggle =
    document.getElementById("menuToggle");

const mainNav =
    document.getElementById("mainNav");

if (menuToggle && mainNav) {

    menuToggle.addEventListener(
        "click",
        function () {

            mainNav.classList.toggle("open");

            const isOpen =
                mainNav.classList.contains("open");

            menuToggle.setAttribute(
                "aria-label",
                isOpen
                    ? "Close navigation menu"
                    : "Open navigation menu"
            );

        }
    );

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

    if (
        document.body.classList.contains("dark-theme")
    ) {

        themeToggle.textContent =
            "☀️ Light Mode";

    } else {

        themeToggle.textContent =
            "🌙 Dark Mode";

    }

}

if (themeToggle) {

    themeToggle.addEventListener(
        "click",
        function () {

            document.body.classList.toggle(
                "dark-theme"
            );

            updateThemeButton();

        }
    );

}


/* =================================================
   3. NOTIFICATION
================================================= */

const notification =
    document.getElementById("notification");

const closeNotification =
    document.getElementById("closeNotification");

if (
    notification &&
    closeNotification
) {

    closeNotification.addEventListener(
        "click",
        function () {

            notification.style.display =
                "none";

        }
    );

}


/* =================================================
   4. MODAL
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

            if (
                event.target === eventModal
            ) {

                hideModal();

            }

        }
    );

}


/* =================================================
   5. EVENT MODAL BUTTONS
================================================= */

const eventButtons =
    document.querySelectorAll(
        ".event-details-btn"
    );

eventButtons.forEach(
    function (button) {

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

    }
);


/* =================================================
   6. CONTENT SLIDER
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

    slides.forEach(
        function (slide) {

            slide.classList.remove("active");

        }
    );

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
                    currentSlide >= slides.length
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
   7. FAQ
================================================= */

const faqQuestions =
    document.querySelectorAll(
        ".faq-question"
    );

faqQuestions.forEach(
    function (question) {

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

    }
);


/* =================================================
   8. PHONE INPUT
   Practical 5
================================================= */

const phoneInput =
    document.getElementById("phone");

if (phoneInput) {

    phoneInput.addEventListener(
        "input",
        function () {

            /* Allow only numbers */

            this.value =
                this.value.replace(
                    /[^0-9]/g,
                    ""
                );

            /* Maximum 10 digits */

            if (this.value.length > 10) {

                this.value =
                    this.value.slice(
                        0,
                        10
                    );

            }

        }
    );

}


/* =================================================
   9. REGISTRATION FORM
================================================= */

const registerForm =
    document.getElementById("registerForm");

if (registerForm) {

    registerForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            /* ---------- Get Values ---------- */

            const fullname =
                document.getElementById(
                    "fullname"
                ).value.trim();

            const email =
                document.getElementById(
                    "email"
                ).value.trim();

            const phone =
                document.getElementById(
                    "phone"
                ).value.trim();

            const dob =
                document.getElementById(
                    "dob"
                ).value;

            const password =
                document.getElementById(
                    "password"
                ).value;

            const confirmPassword =
                document.getElementById(
                    "confirmPassword"
                ).value;

            const course =
                document.getElementById(
                    "course"
                ).value;

            const year =
                document.getElementById(
                    "year"
                ).value;

            const terms =
                document.getElementById(
                    "terms"
                ).checked;


            /* ---------- Regex ---------- */

            const namePattern =
                /^[A-Za-z ]+$/;

            const emailPattern =
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            const phonePattern =
                /^[0-9]{10}$/;

            const passwordPattern =
                /^(?=.*[A-Za-z])(?=.*\d).{6,}$/;


            /* ---------- Error Elements ---------- */

            const nameError =
                document.getElementById(
                    "nameError"
                );

            const emailError =
                document.getElementById(
                    "emailError"
                );

            const phoneError =
                document.getElementById(
                    "phoneError"
                );

            const passwordError =
                document.getElementById(
                    "passwordError"
                );

            const confirmPasswordError =
                document.getElementById(
                    "confirmPasswordError"
                );

            const courseError =
                document.getElementById(
                    "courseError"
                );

            const yearError =
                document.getElementById(
                    "yearError"
                );

            const genderError =
                document.getElementById(
                    "genderError"
                );

            const termsError =
                document.getElementById(
                    "termsError"
                );


            /* ---------- Clear Old Errors ---------- */

            nameError.textContent = "";
            emailError.textContent = "";
            phoneError.textContent = "";
            passwordError.textContent = "";
            confirmPasswordError.textContent = "";
            courseError.textContent = "";
            yearError.textContent = "";
            genderError.textContent = "";
            termsError.textContent = "";


            let isValid = true;


            /* ---------- Name ---------- */

            if (fullname === "") {

                nameError.textContent =
                    "Name is required.";

                isValid = false;

            } else if (
                !namePattern.test(fullname)
            ) {

                nameError.textContent =
                    "Name should contain only letters and spaces.";

                isValid = false;

            }


            /* ---------- Email ---------- */

            if (email === "") {

                emailError.textContent =
                    "Email is required.";

                isValid = false;

            } else if (
                !emailPattern.test(email)
            ) {

                emailError.textContent =
                    "Please enter a valid email address.";

                isValid = false;

            }


            /* ---------- Mobile ---------- */

            if (phone === "") {

                phoneError.textContent =
                    "Mobile number is required.";

                isValid = false;

            } else if (
                !phonePattern.test(phone)
            ) {

                phoneError.textContent =
                    "Mobile number must contain exactly 10 digits.";

                isValid = false;

            }


            /* ---------- Date of Birth ---------- */

            if (dob === "") {

                isValid = false;

            }


            /* ---------- Password ---------- */

            if (password === "") {

                passwordError.textContent =
                    "Password is required.";

                isValid = false;

            } else if (
                !passwordPattern.test(password)
            ) {

                passwordError.textContent =
                    "Password must contain at least 6 characters, including a letter and a number.";

                isValid = false;

            }


            /* ---------- Confirm Password ---------- */

            if (confirmPassword === "") {

                confirmPasswordError.textContent =
                    "Please confirm your password.";

                isValid = false;

            } else if (
                password !== confirmPassword
            ) {

                confirmPasswordError.textContent =
                    "Passwords do not match.";

                isValid = false;

            }


            /* ---------- Course ---------- */

            if (course === "") {

                courseError.textContent =
                    "Please select a course.";

                isValid = false;

            }


            /* ---------- Year ---------- */

            if (year === "") {

                yearError.textContent =
                    "Please select your year.";

                isValid = false;

            }


            /* ---------- Gender ---------- */

            const gender =
                document.querySelector(
                    'input[name="gender"]:checked'
                );

            if (!gender) {

                genderError.textContent =
                    "Please select your gender.";

                isValid = false;

            }


            /* ---------- Terms ---------- */

            if (!terms) {

                termsError.textContent =
                    "You must accept the Terms and Conditions.";

                isValid = false;

            }


            /* ---------- Success ---------- */

            if (isValid) {

                const registerMessage =
                    document.getElementById(
                        "registerMessage"
                    );

                registerMessage.textContent =
                    "Registration successful! Redirecting to login page...";

                registerMessage.className =
                    "success-message show";

                registerForm.reset();

                setTimeout(
                    function () {

                        window.location.href =
                            "login.html";

                    },
                    1500
                );

            }

        }
    );

}


/* =================================================
   10. LOGIN FORM
   Simple frontend demo
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
                ).value.trim();

            const loginPassword =
                document.getElementById(
                    "loginPassword"
                ).value;


            if (
                loginEmail === "" ||
                loginPassword === ""
            ) {

                loginMessage.textContent =
                    "Please enter your email and password.";

                loginMessage.className =
                    "error-message show";

                return;

            }


            loginMessage.textContent =
                "Login successful! Redirecting to dashboard...";

            loginMessage.className =
                "success-message show";


            setTimeout(
                function () {

                    window.location.href =
                        "dashboard.html";

                },
                1000
            );

        }
    );

}


/* =================================================
   11. CONTACT FORM
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
   12. FEEDBACK FORM
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
   13. DASHBOARD
   No localStorage for now
================================================= */


/* =================================================
   14. LOGOUT
================================================= */

const logoutButton =
    document.getElementById(
        "logoutButton"
    );

if (logoutButton) {

    logoutButton.addEventListener(
        "click",
        function () {

            window.location.href =
                "login.html";

        }
    );

}