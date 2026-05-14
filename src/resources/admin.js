
let resources = [];

const resourceForm = document.getElementById("resource-form");

const resourcesTbody = document.getElementById("resources-tbody");

let editingId = null;

function createResourceRow(resource) {
  const tr = document.createElement("tr");

  const titleTd = document.createElement("td");
  titleTd.textContent = resource.title;

  const descriptionTd = document.createElement("td");
  descriptionTd.textContent = resource.description;

const linkTd = document.createElement("td");

const linkAnchor = document.createElement("a");
linkAnchor.href = resource.link;
linkAnchor.textContent = resource.link;
linkAnchor.target = "_blank";

linkTd.appendChild(linkAnchor);

  const actionsTd = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.className = "edit-btn";
  editBtn.dataset.id = resource.id;

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.className = "delete-btn";
  deleteBtn.dataset.id = resource.id;

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(titleTd);
  tr.appendChild(descriptionTd);
  tr.appendChild(linkTd);
  tr.appendChild(actionsTd);

  return tr;
}

function renderTable(data = resources) {
  resourcesTbody.innerHTML = "";

  data.forEach(resource => {
    const row = createResourceRow(resource);
    resourcesTbody.appendChild(row);
  });
}

async function handleAddResource(event) {
  event.preventDefault();

  const title = document.getElementById("resource-title").value;
  const description = document.getElementById("resource-description").value;
  const link = document.getElementById("resource-link").value;

  if (editingId) {

    const response = await fetch("./api/index.php", {
      method: "PUT",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        id: editingId,
        title,
        description,
        link
      })
    });

    const result = await response.json();

    if (result.success) {
      resources = resources.map(resource => {
        if (resource.id == editingId) {
          return {
            id: editingId,
            title,
            description,
            link
          };
        }

        return resource;
      });

      renderTable();

      resourceForm.reset();

      document.getElementById("add-resource").textContent = "Add Resource";

      editingId = null;
    }

  } else {

    const response = await fetch("./api/index.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        title,
        description,
        link
      })
    });

    const result = await response.json();

    if (result.success) {
      resources.push({
        id: result.id,
        title,
        description,
        link
      });

      renderTable();

      resourceForm.reset();
    }
  }
}

async function handleTableClick(event) {
  const target = event.target;
  const id = target.dataset.id;

  if (target.classList.contains("delete-btn")) {

    const response = await fetch(`./api/index.php?id=${id}`, {
      method: "DELETE"
    });

    const result = await response.json();

    if (result.success) {
      resources = resources.filter(resource => resource.id != id);
      renderTable();
    }
  }

  if (target.classList.contains("edit-btn")) {

    const resource = resources.find(resource => resource.id == id);

    document.getElementById("resource-title").value = resource.title;
    document.getElementById("resource-description").value = resource.description;
    document.getElementById("resource-link").value = resource.link;

    document.getElementById("add-resource").textContent = "Update Resource";

    editingId = id;
  }
}

async function loadAndInitialize() {

  const response = await fetch("./api/index.php");
  const result = await response.json();

  if (result.success) {
    resources = result.data;
  }

  renderTable();

  resourceForm.addEventListener("submit", handleAddResource);

  resourcesTbody.addEventListener("click", handleTableClick);
}

loadAndInitialize();