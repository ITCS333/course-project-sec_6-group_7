/*
  Requirement: Handle CRUD operations for managing course resources.

  Instructions:
  - This file should handle the creation, reading, updating, and deletion of course resources.
  - Dynamically populate the "Existing Resources" table and handle resource management actions (Add, Edit, Delete).
*/

// --- Global Variables ---
let resources = [];  // This will hold the resources loaded from the API.
const resourceForm = document.getElementById('resource-form');
const resourcesTbody = document.getElementById('resources-tbody');

// --- Functions ---

/**
 * Create a table row for a resource.
 * It takes one resource object { id, title, description, link }.
 * It returns an <tr> element.
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
  link.textContent = 'Visit';
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
 * This function will clear the current table and populate it with the resources array.
 */
function renderTable() {
  resourcesTbody.innerHTML = '';  // Clear existing content
  resources.forEach(resource => {
    const row = createResourceRow(resource);
    resourcesTbody.appendChild(row);
  });
}

/**
 * Fetch resources from the API and populate the table
 * This function is called when the page loads.
 */
async function loadResources() {
  try {
    const response = await fetch('./api/index.php');  // Fetch data from the API
    const data = await response.json();

    if (data.success) {
      resources = data.data; // Store the resources
      renderTable(); // Call renderTable() to populate the table
    } else {
      console.error('Failed to load resources');
    }
  } catch (error) {
    console.error('Error fetching resources:', error);
  }
}

/**
 * Handle the form submission for adding a new resource.
 * It will send the resource data to the API and update the resources list.
 */
async function handleAddResource(event) {
  event.preventDefault();

  const title = document.getElementById('new-resource-title').value;
  const description = document.getElementById('resource-description').value;
  const link = document.getElementById('resource-link').value;

  const response = await fetch('./api/index.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ title, description, link }),
  });

  const data = await response.json();
  if (data.success) {
    resources.push({ id: data.id, title, description, link });
    renderTable();
    resourceForm.reset();
  } else {
    console.error('Failed to add resource');
  }
}

/**
 * Handle the click event for editing or deleting a resource.
 * This function listens for clicks on the Edit and Delete buttons.
 */
async function handleTableClick(event) {
  const button = event.target;
  const id = button.getAttribute('data-id');

  if (button.classList.contains('delete-btn')) {
    await handleDeleteResource(id);
  }

  if (button.classList.contains('edit-btn')) {
    const resource = resources.find(r => r.id === id);
    document.getElementById('new-resource-title').value = resource.title;
    document.getElementById('resource-description').value = resource.description;
    document.getElementById('resource-link').value = resource.link;

    const submitButton = document.getElementById('add-resource-btn');
    submitButton.textContent = 'Update Resource';

    resourceForm.removeEventListener('submit', handleAddResource);
    resourceForm.addEventListener('submit', (e) => handleEditResource(e, resource));
  }
}

/**
 * Handle deleting a resource.
 * It will send a DELETE request to the API and remove the resource from the resources array.
 */
async function handleDeleteResource(id) {
  const response = await fetch(`./api/index.php?id=${id}`, { method: 'DELETE' });

  const data = await response.json();
  if (data.success) {
    resources = resources.filter(resource => resource.id !== parseInt(id));  // Remove deleted resource
    renderTable();
  } else {
    console.error('Failed to delete resource');
  }
}

/**
 * Handle editing a resource.
 * It will send a PUT request to the API to update the resource and update the resources list.
 */
async function handleEditResource(event, resource) {
  event.preventDefault();

  const title = document.getElementById('new-resource-title').value;
  const description = document.getElementById('resource-description').value;
  const link = document.getElementById('resource-link').value;

  const response = await fetch('./api/index.php', {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ id: resource.id, title, description, link }),
  });

  const data = await response.json();
  if (data.success) {
    const updatedResource = { id: resource.id, title, description, link };
    resources = resources.map(r => (r.id === resource.id ? updatedResource : r));
    renderTable();
    resourceForm.reset();
    const submitButton = document.getElementById('add-resource-btn');
    submitButton.textContent = 'Add Resource';
    resourceForm.removeEventListener('submit', handleEditResource);
    resourceForm.addEventListener('submit', handleAddResource);
  } else {
    console.error('Failed to update resource');
  }
}

// --- Initial Page Load ---
loadResources();

// --- Event Listeners ---
resourceForm.addEventListener('submit', handleAddResource);
resourcesTbody.addEventListener('click', handleTableClick);
