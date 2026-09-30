import { fetchData } from "./modules/data.js";

import {
    renderEvents,
    renderStudents,
    renderFAQs
} from "./modules/render.js";


// HTML Elements

const dataType = document.getElementById("dataType");
const searchInput = document.getElementById("searchInput");
const filterSelect = document.getElementById("filterSelect");
const sortSelect = document.getElementById("sortSelect");
const dataContainer = document.getElementById("dataContainer");
const loading = document.getElementById("loading");
const error = document.getElementById("error");
const resultCount = document.getElementById("resultCount");
const pagination = document.getElementById("pagination");


// Variables

let allData = [];
let currentData = [];
let currentPage = 1;

const recordsPerPage = 6;


// JSON files

const files = {
    events: "../data/events.json",
    students: "../data/students.json",
    faqs: "../data/faqs.json"
};


// Load JSON data

async function loadData() {
    loading.style.display = "block";
    error.textContent = "";
    dataContainer.innerHTML = "";
    resultCount.textContent = "";
    pagination.innerHTML = "";

    try {
        const file = files[dataType.value];

        allData = await fetchData(file);

        currentPage = 1;

        createFilterOptions();

        processData();

    } catch (err) {
        error.textContent = "Unable to load data.";
        console.error(err);

    } finally {
        loading.style.display = "none";
    }
}


// Create filter options

function createFilterOptions() {
    filterSelect.innerHTML =
        '<option value="all">All</option>';

    let values = [];

    if (dataType.value === "events") {
        values = allData.map(function (item) {
            return item.category;
        });
    }

    else if (dataType.value === "students") {
        values = allData.map(function (item) {
            return item.department;
        });
    }

    else if (dataType.value === "faqs") {
        values = allData.map(function (item) {
            return item.category;
        });
    }


    const uniqueValues = [...new Set(values)];

    uniqueValues.sort();

    uniqueValues.forEach(function (value) {
        const option = document.createElement("option");

        option.value = value;
        option.textContent = value;

        filterSelect.appendChild(option);
    });
}


// Search, Filter and Sort

function processData() {
    let data = [...allData];


    // Search

    const search = searchInput.value
        .toLowerCase()
        .trim();

    if (search !== "") {
        data = data.filter(function (item) {
            return JSON.stringify(item)
                .toLowerCase()
                .includes(search);
        });
    }


    // Filter

    const filter = filterSelect.value;

    if (filter !== "all") {

        data = data.filter(function (item) {

            if (dataType.value === "events") {
                return item.category === filter;
            }

            if (dataType.value === "students") {
                return item.department === filter;
            }

            if (dataType.value === "faqs") {
                return item.category === filter;
            }

            return true;
        });
    }


    // Sort

    const sort = sortSelect.value;

    if (sort === "az") {
        data.sort(function (a, b) {
            return getName(a).localeCompare(getName(b));
        });
    }

    if (sort === "za") {
        data.sort(function (a, b) {
            return getName(b).localeCompare(getName(a));
        });
    }


    currentData = data;

    currentPage = 1;

    renderCurrentPage();
}


// Get name for sorting

function getName(item) {

    if (dataType.value === "events") {
        return item.title;
    }

    if (dataType.value === "students") {
        return item.name;
    }

    if (dataType.value === "faqs") {
        return item.question;
    }

    return "";
}


// Render current page

function renderCurrentPage() {

    const start =
        (currentPage - 1) * recordsPerPage;

    const end =
        start + recordsPerPage;

    const pageData =
        currentData.slice(start, end);


    resultCount.textContent =
        "Showing " + currentData.length + " record(s)";


    if (dataType.value === "events") {
        renderEvents(
            pageData,
            dataContainer
        );
    }

    else if (dataType.value === "students") {
        renderStudents(
            pageData,
            dataContainer
        );
    }

    else if (dataType.value === "faqs") {
        renderFAQs(
            pageData,
            dataContainer
        );
    }


    createPagination();
}


// Create pagination buttons

function createPagination() {

    pagination.innerHTML = "";

    const totalPages =
        Math.ceil(
            currentData.length / recordsPerPage
        );


    if (totalPages <= 1) {
        return;
    }


    // Previous button

    const previous =
        document.createElement("button");

    previous.textContent = "Previous";

    previous.disabled =
        currentPage === 1;

    previous.addEventListener(
        "click",
        function () {

            if (currentPage > 1) {
                currentPage--;
                renderCurrentPage();
            }

        }
    );

    pagination.appendChild(previous);


    // Page number buttons

    for (
        let i = 1;
        i <= totalPages;
        i++
    ) {

        const button =
            document.createElement("button");

        button.textContent = i;

        if (i === currentPage) {
            button.classList.add("active-page");
        }

        button.addEventListener(
            "click",
            function () {
                currentPage = i;
                renderCurrentPage();
            }
        );

        pagination.appendChild(button);
    }


    // Next button

    const next =
        document.createElement("button");

    next.textContent = "Next";

    next.disabled =
        currentPage === totalPages;

    next.addEventListener(
        "click",
        function () {

            if (currentPage < totalPages) {
                currentPage++;
                renderCurrentPage();
            }

        }
    );

    pagination.appendChild(next);
}


// Event listeners

dataType.addEventListener(
    "change",
    loadData
);

searchInput.addEventListener(
    "input",
    processData
);

filterSelect.addEventListener(
    "change",
    processData
);

sortSelect.addEventListener(
    "change",
    processData
);


// Initial loading

loadData();