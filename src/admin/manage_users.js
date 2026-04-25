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

  const nameTd = document.createElement("td");
  nameTd.textContent = user.name;

  const emailTd = document.createElement("td");
  emailTd.textContent = user.email;

  const adminTd = document.createElement("td");
  adminTd.textContent = user.is_admin == 1 ? "Yes" : "No";

  const actionsTd = document.createElement("td");

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.className = "delete-btn";
  deleteBtn.dataset.id = user.id;

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

function handleAddUser(event) {
  event.preventDefault();

  const name = document.getElementById("user-name").value.trim();
  const email = document.getElementById("user-email").value.trim();
  const password = document.getElementById("default-password").value;
  const is_admin = document.getElementById("is-admin").value;

  if (!name || !email || !password) return;

  const newUser = {
    id: Date.now(),
    name,
    email,
    is_admin
  };

  users.push(newUser);
  renderTable(users);

  addUserForm.reset();
}

function handleTableClick(event) {
  if (event.target.classList.contains("delete-btn")) {
    const id = event.target.dataset.id;

    users = users.filter(u => u.id != id);
    renderTable(users);
  }
}

function handleSearch() {
  const term = searchInput.value.toLowerCase();

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

  users.sort((a, b) => {
    if (key === "is_admin") {
      return a[key] - b[key];
    }
    return a[key].localeCompare(b[key]);
  });

  renderTable(users);
}

function setup() {
  addUserForm.addEventListener("submit", handleAddUser);
  userTableBody.addEventListener("click", handleTableClick);
  searchInput.addEventListener("input", handleSearch);

  tableHeaders.forEach(th => {
    th.addEventListener("click", handleSort);
  });
}

// --- Initial Page Load ---
setup();