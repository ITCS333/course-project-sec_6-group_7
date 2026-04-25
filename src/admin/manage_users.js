// --- Global Data Store ---
let users = [];

// --- Element Selections ---

// TODO: Select the user table body element with id="user-table-body".
const userTableBody = document.getElementById("user-table-body");

// TODO: Select the "Add User" form with id="add-user-form".
const addUserForm = document.getElementById("add-user-form");

// TODO: Select the "Change Password" form with id="password-form".
const changePasswordForm = document.getElementById("password-form");

// TODO: Select the search input field with id="search-input".
const searchInput = document.getElementById("search-input");

// TODO: Select all table header (th) elements inside the thead of id="user-table".
const tableHeaders = document.querySelectorAll("#user-table thead th");


// --- Functions ---

function createUserRow(user) {
  const tr = document.createElement("tr");

  const nameTd = document.createElement("td");
  nameTd.textContent = user.name;

  const emailTd = document.createElement("td");
  emailTd.textContent = user.email;

  const adminTd = document.createElement("td");
  adminTd.textContent = user.is_admin == 1 ? "Yes" : "No";

  const actionsTd = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.className = "edit-btn";
  editBtn.dataset.id = user.id;

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.className = "delete-btn";
  deleteBtn.dataset.id = user.id;

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(nameTd);
  tr.appendChild(emailTd);
  tr.appendChild(adminTd);
  tr.appendChild(actionsTd);

  return tr;
}

function renderTable(userArray) {
  userTableBody.innerHTML = "";

  userArray.forEach(user => {
    const row = createUserRow(user);
    userTableBody.appendChild(row);
  });
}

function handleChangePassword(event) {
  event.preventDefault();

  const current = document.getElementById("current-password").value;
  const newPass = document.getElementById("new-password").value;
  const confirm = document.getElementById("confirm-password").value;

  if (newPass !== confirm) {
    alert("Passwords do not match.");
    return;
  }

  if (newPass.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  fetch("../api/index.php?action=change_password", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      id: 1, // simple assumption (admin)
      current_password: current,
      new_password: newPass
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("Password updated successfully!");
      changePasswordForm.reset();
    } else {
      alert(data.message);
    }
  });
}

function handleAddUser(event) {
  event.preventDefault();

  const name = document.getElementById("user-name").value.trim();
  const email = document.getElementById("user-email").value.trim();
  const password = document.getElementById("default-password").value;
  const is_admin = document.getElementById("is-admin").value;

  if (!name || !email || !password) {
    alert("Please fill out all required fields.");
    return;
  }

  if (password.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  fetch("../api/index.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      name,
      email,
      password,
      is_admin
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      loadUsersAndInitialize();
      addUserForm.reset();
    } else {
      alert(data.message);
    }
  });
}

function handleTableClick(event) {
  const target = event.target;

  if (target.classList.contains("delete-btn")) {
    const id = target.dataset.id;

    fetch("../api/index.php?id=" + id, {
      method: "DELETE"
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        users = users.filter(u => u.id != id);
        renderTable(users);
      } else {
        alert(data.message);
      }
    });
  }
}

function handleSearch(event) {
  const term = searchInput.value.toLowerCase();

  if (!term) {
    renderTable(users);
    return;
  }

  const filtered = users.filter(u =>
    u.name.toLowerCase().includes(term) ||
    u.email.toLowerCase().includes(term)
  );

  renderTable(filtered);
}

function handleSort(event) {
  const index = event.currentTarget.cellIndex;

  const map = ["name", "email", "is_admin"];
  const key = map[index];

  if (!key) return;

  let dir = event.currentTarget.dataset.sortDir || "asc";
  dir = dir === "asc" ? "desc" : "asc";
  event.currentTarget.dataset.sortDir = dir;

  users.sort((a, b) => {
    if (key === "is_admin") {
      return dir === "asc" ? a[key] - b[key] : b[key] - a[key];
    } else {
      return dir === "asc"
        ? a[key].localeCompare(b[key])
        : b[key].localeCompare(a[key]);
    }
  });

  renderTable(users);
}

async function loadUsersAndInitialize() {
  try {
    const res = await fetch("../api/index.php");

    if (!res.ok) {
      alert("Failed to load users");
      return;
    }

    const data = await res.json();

    users = data.data;
    renderTable(users);

    changePasswordForm.addEventListener("submit", handleChangePassword);
    addUserForm.addEventListener("submit", handleAddUser);
    userTableBody.addEventListener("click", handleTableClick);
    searchInput.addEventListener("input", handleSearch);

    tableHeaders.forEach(th => {
      th.addEventListener("click", handleSort);
    });

  } catch (err) {
    alert("Error loading users");
  }
}

// --- Initial Page Load ---
loadUsersAndInitialize();