const form = document.getElementById("loginForm");

form.addEventListener("submit", function(event) {

    event.preventDefault();

    let valid = true;

    document.getElementById("emailError").innerText = "";
    document.getElementById("passwordError").innerText = "";

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (email === "") {
        document.getElementById("emailError").innerText =
            "Please enter your email.";
        valid = false;
    }
    else if (!emailPattern.test(email)) {
        document.getElementById("emailError").innerText =
            "Please enter a valid email address.";
        valid = false;
    }

    if (password === "") {
        document.getElementById("passwordError").innerText =
            "Please enter your password.";
        valid = false;
    }
    else if (password.length < 8) {
        document.getElementById("passwordError").innerText =
            "Password must be at least 8 characters.";
        valid = false;
    }

    if (valid) {
        alert("Login successful!");
        form.submit();
    }

});