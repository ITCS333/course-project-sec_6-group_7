/**
 * Create an article for each resource
 */
function createResourceArticle(resource) {
  const article = document.createElement("article");

  const title = document.createElement("h3");
  title.textContent = resource.title;
  article.appendChild(title);

  const description = document.createElement("p");
  description.textContent = resource.description;
  article.appendChild(description);

  const link = document.createElement("a");
  link.href = resource.link;
  link.textContent = "View Resource & Discussion";
  article.appendChild(link);

  return article;
}

/**
 * Load resources and display them in the list
 */
async function loadResources() {
  try {
    const response = await fetch("./api/index.php");
    const data = await response.json();

    if (data.success) {
      const resourceListSection = document.getElementById("resource-list-section");
      resourceListSection.innerHTML = "";  // Clear existing content

      data.data.forEach((resource) => {
        const article = createResourceArticle(resource);
        resourceListSection.appendChild(article);
      });
    } else {
      console.error("Failed to load resources");
    }
  } catch (error) {
    console.error("Error fetching resources:", error);
  }
}

/**
 * Fetch the resource ID from the URL query string
 */
function getResourceIdFromURL() {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get("id");
}

/**
 * Initialize the page (load resource details and comments)
 */
async function initializePage() {
  const resourceId = getResourceIdFromURL();
  if (!resourceId) {
    console.error("Resource ID not found in the URL.");
    return;
  }

  // Load resource details and comments
  const resourceResponse = await fetch(`./api/index.php?id=${resourceId}`);
  const resourceData = await resourceResponse.json();
  if (resourceData.success) {
    renderResourceDetails(resourceData.data);
  }

  const commentsResponse = await fetch(`./api/index.php?resource_id=${resourceId}&action=comments`);
  const commentsData = await commentsResponse.json();
  if (commentsData.success) {
    renderComments(commentsData.data);
  }
}

/**
 * Render resource details on the page
 */
function renderResourceDetails(resource) {
  document.getElementById("resource-title").textContent = resource.title;
  document.getElementById("resource-description").textContent = resource.description;
  document.getElementById("resource-link").href = resource.link;
}

/**
 * Render comments for a resource
 */
function renderComments(comments) {
  const commentList = document.getElementById("comment-list");
  commentList.innerHTML = ""; // Clear existing comments

  comments.forEach((comment) => {
    const commentArticle = document.createElement("article");
    const commentText = document.createElement("p");
    commentText.textContent = comment.text;
    commentArticle.appendChild(commentText);

    const footer = document.createElement("footer");
    footer.textContent = `Posted by: ${comment.author}`;
    commentArticle.appendChild(footer);

    commentList.appendChild(commentArticle);
  });
}

/**
 * Initialize the page when the DOM is ready
 */
document.addEventListener("DOMContentLoaded", () => {
  initializePage();
});
