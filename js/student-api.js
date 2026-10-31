const apiRoot = document.getElementById("studentApi");

if (apiRoot) {
    const endpoint = apiRoot.dataset.endpoint;
    const csrfToken = apiRoot.dataset.csrf;
    const status = document.getElementById("apiStatus");
    const rows = document.getElementById("studentApiRows");
    const loading = document.getElementById("studentLoading");
    const search = document.getElementById("studentSearch");
    const department = document.getElementById("studentDepartment");
    const form = document.getElementById("studentApiForm");
    const pagination = document.getElementById("studentApiPagination");
    let currentPage = 1;
    let searchTimer;

    async function request(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": csrfToken,
                ...options.headers
            }
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.error || "The request could not be completed.");
        }
        return result;
    }

    function setStatus(message, isError = false) {
        status.textContent = message;
        status.className = isError ? "error-message show" : "success-message show";
    }

    function populateForm(student) {
        for (const field of ["id", "name", "email", "course", "year", "department", "city"]) {
            const element = form.elements.namedItem(field);
            element.value = student[field] ?? "";
        }
        document.getElementById("studentName").focus();
    }

    function addCell(row, value) {
        const cell = document.createElement("td");
        cell.textContent = value;
        row.appendChild(cell);
    }

    async function loadStudents(page = 1) {
        currentPage = page;
        loading.hidden = false;
        rows.replaceChildren();
        try {
            const params = new URLSearchParams({
                q: search.value.trim(),
                department: department.value,
                page: String(currentPage)
            });
            const result = await request(`${endpoint}?${params}`);
            loading.hidden = true;
            result.data.forEach((student) => {
                const row = document.createElement("tr");
                addCell(row, student.name);
                addCell(row, student.email);
                addCell(row, student.course);
                addCell(row, student.year);
                addCell(row, student.department);
                const actions = document.createElement("td");
                const edit = document.createElement("button");
                edit.type = "button";
                edit.textContent = "Edit";
                edit.addEventListener("click", () => populateForm(student));
                const remove = document.createElement("button");
                remove.type = "button";
                remove.textContent = "Delete";
                remove.addEventListener("click", async () => {
                    if (!window.confirm(`Delete ${student.name}?`)) {
                        return;
                    }
                    try {
                        const deleted = await request(endpoint, {
                            method: "DELETE",
                            body: JSON.stringify({id: student.id})
                        });
                        setStatus(deleted.message);
                        await loadStudents(currentPage);
                    } catch (error) {
                        setStatus(error.message, true);
                    }
                });
                actions.append(edit, remove);
                row.appendChild(actions);
                rows.appendChild(row);
            });
            pagination.replaceChildren();
            for (let number = 1; number <= result.pagination.pages; number++) {
                const button = document.createElement("button");
                button.type = "button";
                button.textContent = String(number);
                button.setAttribute("aria-current", number === currentPage ? "page" : "false");
                button.addEventListener("click", () => loadStudents(number));
                pagination.appendChild(button);
            }
            if (result.data.length === 0) {
                const row = document.createElement("tr");
                const cell = document.createElement("td");
                cell.colSpan = 6;
                cell.textContent = "No students match this search.";
                row.appendChild(cell);
                rows.appendChild(row);
            }
        } catch (error) {
            loading.hidden = true;
            setStatus(error.message, true);
        }
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        const payload = Object.fromEntries(new FormData(form).entries());
        const method = payload.id ? "PUT" : "POST";
        try {
            const result = await request(endpoint, {
                method,
                body: JSON.stringify(payload)
            });
            setStatus(result.message);
            form.reset();
            document.getElementById("studentId").value = "";
            await loadStudents(1);
        } catch (error) {
            setStatus(error.message, true);
        }
    });

    document.getElementById("cancelStudentEdit").addEventListener("click", () => form.reset());
    search.addEventListener("input", () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => loadStudents(1), 300);
    });
    department.addEventListener("change", () => loadStudents(1));
    loadStudents();
}
