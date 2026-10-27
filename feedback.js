const form = document.getElementById("feedbackForm");

form.addEventListener("submit", function (event) {
    event.preventDefault();

    let valid = true;

    document.querySelectorAll("small").forEach(function (error) {
        error.innerText = "";
    });

    const fullname = document.getElementById("fullname").value.trim();
    const enrollment = document.getElementById("enrollment").value.trim();
    const email = document.getElementById("email").value.trim();
    const department = document.getElementById("department").value.trim();
    const semester = document.getElementById("semester").value;
    const feedbackType = document.getElementById("feedback_type").value;
    const rating = document.getElementById("rating").value;
    const feedbackContent = document.getElementById("feedback_content").value.trim();

    const recommend = document.querySelector(
        'input[name="recommend"]:checked'
    );

    const fullnamePattern = /^[A-Za-z ]+$/;
    const enrollmentPattern = /^[0-9]{2}[A-Z]{2}[0-9]{3}$/;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const departmentPattern = /^[A-Za-z -]+$/;

    if (fullname === "") {
        document.getElementById("fullnameError").innerText =
            "Please enter your full name.";
        valid = false;
    } else if (!fullnamePattern.test(fullname)) {
        document.getElementById("fullnameError").innerText =
            "Name should contain only letters and spaces.";
        valid = false;
    }

    if (enrollment === "") {
        document.getElementById("enrollmentError").innerText =
            "Please enter your enrollment number.";
        valid = false;
    } else if (!enrollmentPattern.test(enrollment)) {
        document.getElementById("enrollmentError").innerText =
            "Example: 25CE040";
        valid = false;
    }

    if (email === "") {
        document.getElementById("emailError").innerText =
            "Please enter your email.";
        valid = false;
    } else if (!emailPattern.test(email)) {
        document.getElementById("emailError").innerText =
            "Please enter a valid email address.";
        valid = false;
    }

    if (department === "") {
        document.getElementById("departmentError").innerText =
            "Please enter your department.";
        valid = false;
    } else if (!departmentPattern.test(department)) {
        document.getElementById("departmentError").innerText =
            "Please enter a valid department.";
        valid = false;
    }

    if (semester === "") {
        document.getElementById("semesterError").innerText =
            "Please select your semester.";
        valid = false;
    }

    if (feedbackType === "") {
        document.getElementById("feedbackTypeError").innerText =
            "Please select feedback type.";
        valid = false;
    }

    if (rating === "") {
        document.getElementById("ratingError").innerText =
            "Please select a rating.";
        valid = false;
    }

    if (!recommend) {
        document.getElementById("recommendError").innerText =
            "Please select an option.";
        valid = false;
    }

    if (feedbackContent === "") {
        document.getElementById("feedbackError").innerText =
            "Please enter your feedback.";
        valid = false;
    } else if (feedbackContent.length < 10) {
        document.getElementById("feedbackError").innerText =
            "Feedback must be at least 10 characters.";
        valid = false;
    }

    if (valid) {
        alert("Feedback submitted successfully!");
        form.reset();
    }
});