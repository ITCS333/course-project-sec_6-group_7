/*
  Requirement: Add client-side validation to the login form.
*/

//Element Selections

const loginForm = document.getElementById("login-form");

const emailInput = document.getElementById("email");

const passwordInput = document.getElementById("password");

const messageContainer = document.getElementById("message-container");


//Functions

function displayMessage(message, type) {

  messageContainer.textContent = message;

  messageContainer.className = type;

}


function isValidEmail(email) {

  const regex = /\S+@\S+\.\S+/;

  return regex.test(email);

}


function isValidPassword(password) {

  return password.length >= 8;

}


function handleLogin(event) {

  event.preventDefault();

  const email = emailInput.value.trim();

  const password = passwordInput.value.trim();


  // Email validation
  if (!isValidEmail(email)) {

    displayMessage("Invalid email format.", "error");

    return;

  }


  // Password validation
  if (!isValidPassword(password)) {

    displayMessage("Password must be at least 8 characters.", "error");

    return;

  }
  if (typeof fetch === "undefined") return;
  // Login request
  fetch("./api/index.php", {

    method: "POST",

    headers: {
      "Content-Type": "application/json"
    },

    body: JSON.stringify({
      email,
      password
    })

  })

  .then(async response => {

    const result = await response.json();

    console.log(result);

    // Wrong email/password
    if (!response.ok || !result.success) {

      displayMessage("Invalid email or password.", "error");

      return;

    }

    // Successful login
    displayMessage("Login successful!", "success");

    // Optional: clear fields
    emailInput.value = "";

    passwordInput.value = "";

localStorage.clear();
localStorage.setItem("user_id", result.user.id);
localStorage.setItem("is_admin", String(result.user.is_admin));

if (result.user.is_admin == 1) {
  window.location.href = "../admin/manage_users.html";
} else {
  window.location.href = "../resources/list.html";
}

  })

  .catch(error => {

    console.error(error);

    displayMessage("Unable to connect. Please try again.", "error");

  });

}


function setupLoginForm() {

  if (loginForm) {

    loginForm.addEventListener("submit", handleLogin);

  }

}

setupLoginForm();