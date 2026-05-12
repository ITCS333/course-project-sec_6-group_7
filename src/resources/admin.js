/*
  Handle CRUD operations for resources (Admin)
*/

// Fetch the resources from the API
async function loadResources() {
  const response = await fetch("./api/index.php");
  const data = await response.json();
  if (data.success) {
    const tbody = document.getElementById("resources-tbody");
    tbody.innerHTML = "";  // Clear existing table rows

    data.data.forEach((resource) => {
      const row = createResourceRow(resource);
      tbody.appendChild(row);
    });
  }
}

// Create a table row for each resource
function createResourceRow(resource) {
  const row = document.createElement("tr");

  const titleCell = document.createElement("td");
  titleCell.textContent = resource.title;
  row.appendChild(titleCell);

  const descriptionCell = document.createElement("td");
  descriptionCell.textContent = resource.description;
  row.appendChild(descriptionCell);

  const linkCell = document.createElement("td");
  const link = document.createElement("a");
  link.href = resource.link;
  link.textContent = "Visit";
  linkCell.appendChild(link);
  row.appendChild(linkCell);

  const actionsCell = document.createElement("td");
  const editButton = document.createElement("button");
  editButton.textContent = "Edit";
  editButton.setAttribute("data-id", resource.id);

  const deleteButton = document.createElement("button");
  deleteButton.textContent = "Delete";
  deleteButton.setAttribute("data-id", resource.id);

  actionsCell.appendChild(editButton);
  actionsCell.appendChild(deleteButton);
  row.appendChild(actionsCell);

  return row;
}

// Handle the form submission for adding a resource
async function handleAddResource(event) {
  event.preventDefault();

  const title = document.getElementById("resource-title").value;
  const description = document.getElementById("resource-description").value;
  const link = document.getElementById("resource-link").value;

  const response = await fetch("./api/index.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ title, description, link }),
  });

  const data = await response.json();
  if (data.success) {
    loadResources();  // Reload resources after adding a new one
    document.getElementById("resource-form").reset();
  } else {
    console.error("Failed to add resource");
  }
}

// Attach event listeners to the form
document.addEventListener("DOMContentLoaded", () => {
  document
    .getElementById("resource-form")
    .addEventListener("submit", handleAddResource);

  loadResources();
});
