// --- Global Data Store ---
let users = [];

// --- Element Selections ---
const userTableBody = document.getElementById("user-table-body");
const addUserForm = document.getElementById("add-user-form");
const changePasswordForm = document.getElementById("password-form");
const searchInput = document.getElementById("search-input");
const tableHeaders = document.querySelectorAll("#user-table thead th");

// --- Functions ---

function createUserRow(user) {
  const tr = document.createElement("tr");

  tr.innerHTML = `
    <td>${user.name}</td>
    <td>${user.email}</td>
    <td>${user.is_admin == 1 ? "Yes" : "No"}</td>
    <td>
      <button class="edit-btn" data-id="${user.id}">Edit</button>
      <button class="delete-btn" data-id="${user.id}">Delete</button>
    </td>
  `;

  return tr;
}

function renderTable(userArray) {
  userTableBody.innerHTML = "";
  userArray.forEach(u => userTableBody.appendChild(createUserRow(u)));
}

// ---------------- PASSWORD ----------------

function handleChangePassword(e) {
  e.preventDefault();

  const current = document.getElementById("current-password").value;
  const newPass = document.getElementById("new-password").value;
  const confirm = document.getElementById("confirm-password").value;

  if (newPass !== confirm) {
    alert("Passwords do not match");
    return;
  }

  if (newPass.length < 8) {
    alert("Password must be at least 8 characters");
    return;
  }

  document.getElementById("current-password").value = "";
  document.getElementById("new-password").value = "";
  document.getElementById("confirm-password").value = "";
}

// ---------------- ADD USER ----------------

function handleAddUser(e) {
  e.preventDefault();

  const name = document.getElementById("user-name").value.trim();
  const email = document.getElementById("user-email").value.trim();
  const password = document.getElementById("default-password").value;
  const is_admin = document.getElementById("is-admin").value;

  if (!name || !email || !password) {
    alert("All required fields must be filled");
    return;
  }

  fetch("api/index.php", {
    method: "POST",
    body: JSON.stringify({ name, email, password, is_admin })
  });

  addUserForm.reset();
}

// ---------------- DELETE ----------------

function handleTableClick(e) {
  if (e.target.classList.contains("delete-btn")) {
    const id = e.target.dataset.id;

    if (!confirm("Are you sure?")) return;

    fetch(`api/index.php?id=${id}`, {
      method: "DELETE"
    });
  }
}

// ---------------- SEARCH ----------------

function handleSearch(e) {
  const term = e.target.value.toLowerCase();

  const filtered = users.filter(u =>
    u.name.toLowerCase().includes(term) ||
    u.email.toLowerCase().includes(term)
  );

  renderTable(filtered);
}

// ---------------- SORT ----------------

function handleSort(e) {
  const th = e.currentTarget;
  const index = th.cellIndex;
  const map = ["name", "email", "is_admin"];
  const key = map[index];

  if (!key) return;

  const dir = th.dataset.sortDir === "asc" ? "desc" : "asc";
  th.dataset.sortDir = dir;

  users.sort((a, b) => {
    if (key === "is_admin") return dir === "asc" ? a[key] - b[key] : b[key] - a[key];
    return dir === "asc"
      ? a[key].localeCompare(b[key])
      : b[key].localeCompare(a[key]);
  });

  renderTable(users);
}

// ---------------- LOAD ----------------

async function loadUsersAndInitialize() {
  if (loadUsersAndInitialize._listenersAttached) return;

  const res = await fetch("api/index.php");
  const data = await res.json();

  if (data.success) {
    users = data.data;
    renderTable(users);
  }

  changePasswordForm.addEventListener("submit", handleChangePassword);
  addUserForm.addEventListener("submit", handleAddUser);
  userTableBody.addEventListener("click", handleTableClick);
  searchInput.addEventListener("input", handleSearch);

  tableHeaders.forEach(th => th.addEventListener("click", handleSort));

  loadUsersAndInitialize._listenersAttached = true;
}

// --- Initial Page Load ---
loadUsersAndInitialize();