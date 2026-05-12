/*
  Requirement: Make the "Manage Resources" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add id="resources-tbody" to the <tbody> element
     inside your resources-table. This id is required by this script.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the resources loaded from the API.
let resources = [];

// --- Element Selections ---
// TODO: Select the resource form ('#resource-form').
const resourceForm = document.getElementById('resource-form');


// TODO: Select the resources table body ('#resources-tbody').
const resourcesTbody = document.getElementById('resources-tbody');

// --- Functions ---

/**
 * TODO: Implement the createResourceRow function.
 * It takes one resource object { id, title, description, link }.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the title.
 * 2. A <td> for the description.
 * 3. A <td> for the link.
 * 4. A <td> containing two buttons:
 *    - An "Edit" button with class="edit-btn" and data-id="${id}".
 *    - A "Delete" button with class="delete-btn" and data-id="${id}".
 */
function createResourceRow(resource) {
    const row = document.createElement('tr');

  // Title cell
  const titleCell = document.createElement('td');
  titleCell.textContent = resource.title;

  // Description cell
  const descriptionCell = document.createElement('td');
  descriptionCell.textContent = resource.description;

  // Link cell
  const linkCell = document.createElement('td');
  const link = document.createElement('a');
  link.href = resource.link;
  link.textContent = 'Visit';
  linkCell.appendChild(link);

  // Actions cell with Edit and Delete buttons
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

  row.appendChild(titleCell);
  row.appendChild(descriptionCell);
  row.appendChild(linkCell);
  row.appendChild(actionsCell);

  return row;
  
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the resources table body ('#resources-tbody').
 * 2. Loop through the global `resources` array.
 * 3. For each resource, call `createResourceRow()` and
 *    append the returned <tr> to the table body.
 */
function renderTable() {
    // Clear the table body
  resourcesTbody.innerHTML = '';

  // Loop through the resources array and add each row
  resources.forEach(resource => {
    const row = createResourceRow(resource);
    resourcesTbody.appendChild(row);
  });
}

/**
 * TODO: Implement the handleAddResource function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title (id="resource-title"),
 *    description (id="resource-description"), and
 *    link (id="resource-link") inputs.
 * 3. Use `fetch()` to POST the new resource to the API:
 *    - URL: './api/index.php'
 *    - Method: POST
 *    - Headers: { 'Content-Type': 'application/json' }
 *    - Body: JSON.stringify({ title, description, link })
 * 4. The API returns { success: true, id: <new id> }.
 *    Add the new resource object (including the id returned by the API)
 *    to the global `resources` array.
 * 5. Call `renderTable()` to refresh the list.
 * 6. Reset the form.
 */
function handleAddResource(event) {
 event.preventDefault();

  // Get form values
  const title = document.getElementById('resource-title').value;
  const description = document.getElementById('resource-description').value;
  const link = document.getElementById('resource-link').value;

  // Send the data to the API
  fetch('./api/index.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ title, description, link }),
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Add the new resource to the resources array
        resources.push({ id: data.id, title, description, link });

        // Refresh the table
        renderTable();

        // Reset the form
        resourceForm.reset();
      }
    })
    .catch(error => console.error('Error:', error));}

/**
 * TODO: Implement the handleTableClick function.
 * This handles click events on the table body using event delegation.
 * It should:
 *
 * If the clicked element has class "delete-btn":
 * 1. Get the resource id from the button's data-id attribute.
 * 2. Use `fetch()` to DELETE the resource via the API:
 *    - URL: `./api/index.php?id=${id}`
 *    - Method: DELETE
 * 3. On success, remove the resource from the global `resources` array
 *    by filtering out the entry with the matching id.
 * 4. Call `renderTable()` to refresh the list.
 *
 * If the clicked element has class "edit-btn":
 * 1. Get the resource id from the button's data-id attribute.
 * 2. Find the matching resource in the global `resources` array.
 * 3. Populate the form fields (id="resource-title", id="resource-description",
 *    id="resource-link") with the resource's current values so the admin
 *    can edit them.
 * 4. Change the submit button (id="add-resource") text to "Update Resource"
 *    to indicate edit mode.
 * 5. On form submit, use `fetch()` to PUT the updated resource to the API:
 *    - URL: './api/index.php'
 *    - Method: PUT
 *    - Headers: { 'Content-Type': 'application/json' }
 *    - Body: JSON.stringify({ id, title, description, link })
 * 6. On success, update the matching resource in the global `resources` array.
 * 7. Call `renderTable()` and reset the form back to "Add" mode,
 *    restoring the submit button text to "Add Resource".
 */
function handleTableClick(event) {
  const button = event.target;

  if (button.classList.contains('delete-btn')) {
    const id = button.getAttribute('data-id');

    fetch(`./api/index.php?id=${id}`, {
      method: 'DELETE',
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Remove the resource from the array
          resources = resources.filter(resource => resource.id !== parseInt(id));

          // Refresh the table
          renderTable();
        }
      })
      .catch(error => console.error('Error:', error));
  }

  if (button.classList.contains('edit-btn')) {
    const id = button.getAttribute('data-id');
    const resource = resources.find(resource => resource.id === parseInt(id));

    // Populate the form with the resource details
    document.getElementById('resource-title').value = resource.title;
    document.getElementById('resource-description').value = resource.description;
    document.getElementById('resource-link').value = resource.link;

    // Change the submit button to "Update Resource"
    const submitButton = document.getElementById('add-resource-btn');
    submitButton.textContent = 'Update Resource';

    // Change the form submit behavior to update the resource
    resourceForm.removeEventListener('submit', handleAddResource);
    resourceForm.addEventListener('submit', (e) => handleEditResource(e, resource));
  }
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function must be 'async'.
 * It should:
 * 1. Use `fetch()` to GET all resources from the API:
 *    - URL: './api/index.php'
 *    - The API returns { success: true, data: [...] }
 * 2. Store the resources array (from `data`) in the global `resources` variable.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to the resource form (id="resource-form"),
 *    calling `handleAddResource`.
 * 5. Add the 'click' event listener to the table body (id="resources-tbody"),
 *    calling `handleTableClick`.
 */
async function loadAndInitialize() {
 try {
    const response = await fetch('./api/index.php');
    const data = await response.json();

    if (data.success) {
      resources = data.data;

      // Render the table with initial data
      renderTable();

      // Add event listeners
      resourceForm.addEventListener('submit', handleAddResource);
      resourcesTbody.addEventListener('click', handleTableClick);
    }
  } catch (error) {
    console.error('Error loading resources:', error);
  }}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
