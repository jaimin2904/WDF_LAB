let events = [];
let filteredEvents = [];
let currentPage = 1;
const eventsPerPage = 5;

const container = document.getElementById("eventsContainer");
const loading = document.getElementById("loading");
const error = document.getElementById("error");
const searchEvent = document.getElementById("searchEvent");
const categoryFilter = document.getElementById("categoryFilter");
const sortEvent = document.getElementById("sortEvent");
const previousBtn = document.getElementById("previousBtn");
const nextBtn = document.getElementById("nextBtn");
const pageNumber = document.getElementById("pageNumber");

fetch("data/events.json")
    .then(response => {
        if (!response.ok) {
            throw new Error("Unable to load events.");
        }

        return response.json();
    })
    .then(data => {
        events = data;
        filteredEvents = [...events];

        loading.innerText = "";

        displayEvents();
    })
    .catch(errorMessage => {
        loading.innerText = "";
        error.innerText = "Error loading events.";
        console.log(errorMessage);
    });

function displayEvents() {

    container.innerHTML = "";

    const start = (currentPage - 1) * eventsPerPage;
    const end = start + eventsPerPage;

    const pageEvents = filteredEvents.slice(start, end);

    if (pageEvents.length === 0) {
        container.innerHTML = "<p align='center'>No events found.</p>";
        updatePagination();
        return;
    }

    pageEvents.forEach(event => {

        const div = document.createElement("div");

        div.className = "card";

        div.innerHTML = `
            <h3>${event.name}</h3>
            <p>Date: ${event.date}</p>
            <p>Location: ${event.location}</p>
            <p>Category: ${event.category}</p>
        `;

        container.appendChild(div);
    });

    updatePagination();
}

function applyFilters() {

    const searchText = searchEvent.value.toLowerCase();
    const category = categoryFilter.value;

    filteredEvents = events.filter(event => {

        const matchesSearch =
            event.name.toLowerCase().includes(searchText) ||
            event.location.toLowerCase().includes(searchText);

        const matchesCategory =
            category === "all" ||
            event.category === category;

        return matchesSearch && matchesCategory;
    });

    applySorting();

    currentPage = 1;

    displayEvents();
}

function applySorting() {

    const sortValue = sortEvent.value;

    if (sortValue === "nameAsc") {
        filteredEvents.sort((a, b) =>
            a.name.localeCompare(b.name)
        );
    }

    else if (sortValue === "nameDesc") {
        filteredEvents.sort((a, b) =>
            b.name.localeCompare(a.name)
        );
    }

    else if (sortValue === "dateAsc") {
        filteredEvents.sort((a, b) =>
            new Date(a.date) - new Date(b.date)
        );
    }

    else if (sortValue === "dateDesc") {
        filteredEvents.sort((a, b) =>
            new Date(b.date) - new Date(a.date)
        );
    }
}

searchEvent.addEventListener("input", function() {
    applyFilters();
});

categoryFilter.addEventListener("change", function() {
    applyFilters();
});

sortEvent.addEventListener("change", function() {
    applyFilters();
});

previousBtn.addEventListener("click", function() {

    if (currentPage > 1) {
        currentPage--;
        displayEvents();
    }

});

nextBtn.addEventListener("click", function() {

    const totalPages = Math.ceil(
        filteredEvents.length / eventsPerPage
    );

    if (currentPage < totalPages) {
        currentPage++;
        displayEvents();
    }

});

function updatePagination() {

    const totalPages = Math.ceil(
        filteredEvents.length / eventsPerPage
    );

    pageNumber.innerText =
        "Page " + currentPage + " of " + (totalPages || 1);

    previousBtn.disabled = currentPage === 1;

    nextBtn.disabled =
        currentPage >= totalPages || totalPages === 0;
}