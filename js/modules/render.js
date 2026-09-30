// Render Events

export function renderEvents(events, container) {
    container.innerHTML = "";

    events.forEach(function (event) {
        const card = document.createElement("article");

        card.className = "data-card";

        card.innerHTML = `
            <h3>${event.title}</h3>
            <p><strong>Category:</strong> ${event.category}</p>
            <p><strong>Department:</strong> ${event.department}</p>
            <p><strong>Date:</strong> ${event.date}</p>
            <p><strong>Venue:</strong> ${event.venue}</p>
            <p>${event.description}</p>
        `;

        container.appendChild(card);
    });
}


// Render Students

export function renderStudents(students, container) {
    container.innerHTML = "";

    students.forEach(function (student) {
        const card = document.createElement("article");

        card.className = "data-card";

        card.innerHTML = `
            <h3>${student.name}</h3>
            <p><strong>Course:</strong> ${student.course}</p>
            <p><strong>Year:</strong> ${student.year}</p>
            <p><strong>Department:</strong> ${student.department}</p>
            <p><strong>City:</strong> ${student.city}</p>
        `;

        container.appendChild(card);
    });
}


// Render FAQs

export function renderFAQs(faqs, container) {
    container.innerHTML = "";

    faqs.forEach(function (faq) {
        const card = document.createElement("article");

        card.className = "data-card";

        card.innerHTML = `
            <h3>${faq.question}</h3>
            <p><strong>Category:</strong> ${faq.category}</p>
            <p>${faq.answer}</p>
        `;

        container.appendChild(card);
    });
}