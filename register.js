
const form = document.getElementById("eventForm");


// When user clicks Submit
form.addEventListener("submit", function(event) {

    // Stop the form from submitting immediately
    event.preventDefault();


    // Get values from inputs
    const name = document.getElementById("username").value.trim();
    const studentId = document.getElementById("studentId").value.trim();
    const email = document.getElementById("email").value.trim();
    const phone = document.getElementById("phone").value.trim();
    const eventName = document.getElementById("event").value;


    // Get error elements
    const nameError = document.getElementById("nameError");
    const idError = document.getElementById("idError");
    const emailError = document.getElementById("emailError");
    const phoneError = document.getElementById("phoneError");
    const eventError = document.getElementById("eventError");


    // Clear previous errors
    nameError.innerText = "";
    idError.innerText = "";
    emailError.innerText = "";
    phoneError.innerText = "";
    eventError.innerText = "";


    // Assume form is valid
    let valid = true;


    // -------------------------
    // 1. NAME VALIDATION
    // -------------------------

    const namePattern = /^[A-Za-z ]+$/;

    if (name === "") {

        nameError.innerText = "Name is required.";
        valid = false;

    }
    else if (!namePattern.test(name)) {

        nameError.innerText =
            "Name can contain only alphabets and spaces.";

        valid = false;
    }


    // -------------------------
    // 2. STUDENT ID VALIDATION
    // -------------------------

    const idPattern = /^[0-9]{2}[A-Z]{2}[0-9]{3}$/;

    if (studentId === "") {

        idError.innerText = "Student ID is required.";
        valid = false;

    }
    else if (!idPattern.test(studentId)) {

        idError.innerText =
            "Invalid ID. Example: 25CE047";

        valid = false;
    }


    // -------------------------
    // 3. EMAIL VALIDATION
    // -------------------------

    const emailPattern =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (email === "") {

        emailError.innerText = "Email is required.";
        valid = false;

    }
    else if (!emailPattern.test(email)) {

        emailError.innerText =
            "Please enter a valid email address.";

        valid = false;
    }


   

    const phonePattern = /^[0-9]{10}$/;

    if (phone === "") {

        phoneError.innerText =
            "Phone number is required.";

        valid = false;

    }
    else if (!phonePattern.test(phone)) {

        phoneError.innerText =
            "Phone number must contain exactly 10 digits.";

        valid = false;
    }


    // -------------------------
    // 5. EVENT VALIDATION
    // -------------------------

    if (eventName === "") {

        eventError.innerText =
            "Please select an event.";

        valid = false;
    }


    // -------------------------
    // FINAL RESULT
    // -------------------------

    if (valid) {

        alert("Event participation successful!");

        // For now, don't actually submit anywhere.
        form.reset();

    }

});

