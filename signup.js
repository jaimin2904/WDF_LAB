const form = document.getElementById("signupForm");

form.addEventListener("submit", function(event) {

    event.preventDefault();

    let valid = true;

    document.getElementById("usernameError").innerText = "";
    document.getElementById("emailError").innerText = "";
    document.getElementById("passwordError").innerText = "";

    const username = document.getElementById("username").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    const namePattern = /^[A-Za-z ]+$/;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (username === "") {
        document.getElementById("usernameError").innerText =
            "Please enter your name.";
        valid = false;
    }
    else if (!namePattern.test(username)) {
        document.getElementById("usernameError").innerText =
            "Only alphabets and spaces are allowed.";
        valid = false;
    }

    if (email === "") {
        document.getElementById("emailError").innerText =
            "Please enter your email.";
        valid = false;
    }
    else if (!emailPattern.test(email)) {
        document.getElementById("emailError").innerText =
            "Please enter a valid email.";
        valid = false;
    }

    if (password === "") {
        document.getElementById("passwordError").innerText =
            "Please enter your password.";
        valid = false;
    }
    else if (password.length < 8) {
        document.getElementById("passwordError").innerText =
            "Password must contain at least 8 characters.";
        valid = false;
    }

    if (valid) {
        alert("Signup successful!");

        form.submit();
    }

});