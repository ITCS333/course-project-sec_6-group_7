/*
  Requirement: Handle CRUD operations for managing course resources.
*/

// --- Global Variables ---
let resources = [];
const resourceForm = document.getElementById('resource-form');
const resourcesTbody = document.getElementById('resources-tbody');

// --- Functions ---

/**
 * Create a table row for a resource.
 * It takes one resource object { id, title, description, link }.
 * It returns a <tr> element.
 */
function createResourceRow(resource) {
  const row = document.createElement('tr');

  const titleCell = document.createElement('td');
  titleCell.textContent = resource.title;
  row.appendChild(titleCell);

  const descriptionCell = document.createElement('td');
  descriptionCell.textContent = resource.description;
  row.appendChild(descriptionCell);

  const linkCell = document.createElement('td');
  const link = document.createElement('a');
  link.href = resource.link;
  link.textContent = resource.link;  // Show the URL as text so tests can find it
  linkCell.appendChild(link);
  row.appendChild(linkCell);

  const actionsCell = document.createElement('td');
  const editButton = document.createElement('button');
  editButton.textContent = 'Edit';
  editButton.classList.add('edit-btn');
  editButton.setAttribute('data-id', resource.id);

  const deleteButton = document.createElement('button');
  deleteButton.textContent = 'Delete';
  deleteButton.classList.add('delete-btn');
  deleteButton.setAttribute('data-id', resource.id);

  actionsCell.appendChild(editButton);
  actionsCell.appendChild(deleteButton);
  row.appendChild(actionsCell);

  return row;
}

/**
 * Render the resources into the table.
 */
function renderTable() {
  resourcesTbody.innerHTML = '';  // Clear existing content
  resources.forEach(function(resource) {
    const row = createResourceRow(resource);
    resourcesTbody.appendChild(row);
  });
}

/**
 * Fetch resources from the API and populate the table.
 */
async function loadResources() {
  try {
    const response = await fetch('./api/index.php');
    const data = await response.json();

    if (data.success) {
      resources = data.data;
      renderTable();
    } else {
      console.error('Failed to load resources');
    }
  } catch (error) {
    console.error('Error fetching resources:', error);
  }
}

/**
 * Handle the form submission for adding a new resource.
 */
async function handleAddResource(event) {
  event.preventDefault();

  const title = document.getElementById('resource-title').value;
  const description = document.getElementById('resource-description').value;
  const link = document.getElementById('resource-link').value;

  const response = await fetch('./api/index.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ title: title, description: description, link: link }),
  });

  const data = await response.json();
  if (data.success) {
    resources.push({ id: data.id, title: title, description: description, link: link });
    renderTable();
    resourceForm.reset();
  } else {
    console.error('Failed to add resource');
  }
}

/**
 * Handle the click event for editing or deleting a resource.
 */
async function handleTableClick(event) {
  const button = event.target;
  const id = button.getAttribute('data-id');

  if (button.classList.contains('delete-btn')) {
    await handleDeleteResource(id);
  }

  if (button.classList.contains('edit-btn')) {
    const resource = resources.find(function(r) { return r.id == id; });
    document.getElementById('resource-title').value = resource.title;
    document.getElementById('resource-description').value = resource.description;
    document.getElementById('resource-link').value = resource.link;

    const submitButton = document.getElementById('add-resource');
    submitButton.textContent = 'Update Resource';

    resourceForm.removeEventListener('submit', handleAddResource);
    resourceForm.addEventListener('submit', function(e) { handleEditResource(e, resource); });
  }
}

/**
 * Handle deleting a resource.
 */
async function handleDeleteResource(id) {
  const response = await fetch('./api/index.php?id=' + id, { method: 'DELETE' });

  const data = await response.json();
  if (data.success) {
    resources = resources.filter(function(resource) { return resource.id != id; });
    renderTable();
  } else {
    console.error('Failed to delete resource');
  }
}

/**
 * Handle editing a resource.
 */
async function handleEditResource(event, resource) {
  event.preventDefault();

  const title = document.getElementById('resource-title').value;
  const description = document.getElementById('resource-description').value;
  const link = document.getElementById('resource-link').value;

  const response = await fetch('./api/index.php', {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ id: resource.id, title: title, description: description, link: link }),
  });

  const data = await response.json();
  if (data.success) {
    const updatedResource = { id: resource.id, title: title, description: description, link: link };
    resources = resources.map(function(r) { return r.id == resource.id ? updatedResource : r; });
    renderTable();
    resourceForm.reset();
    const submitButton = document.getElementById('add-resource');
    submitButton.textContent = 'Add Resource';
    resourceForm.removeEventListener('submit', function(e) { handleEditResource(e, resource); });
    resourceForm.addEventListener('submit', handleAddResource);
  } else {
    console.error('Failed to update resource');
  }
}

/**
 * Load and initialize the admin page
 */
async function loadAndInitialize() {
  await loadResources();
  resourceForm.addEventListener('submit', handleAddResource);
  resourcesTbody.addEventListener('click', handleTableClick);
}

// --- Initial Page Load ---
loadAndInitialize();
