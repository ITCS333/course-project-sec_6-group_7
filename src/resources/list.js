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
  link.href = `details.html?id=${resource.id}`;  // Fixed the href to match the expected format
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
    const commentArticle = createCommentArticle(comment);
    commentList.appendChild(commentArticle);
  });
}

/**
 * Handle the form submission for adding a new comment
 */
async function handleAddComment(event) {
  event.preventDefault();
  const commentText = document.getElementById("new-comment").value.trim();
  if (commentText === "") return;  // Do nothing if the textarea is empty

  const resourceId = new URLSearchParams(window.location.search).get("id");
  const response = await fetch("./api/index.php?action=comment", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      resource_id: resourceId,
      author: "Student", // Hardcoded author for simplicity
      text: commentText,
    }),
  });

  const data = await response.json();
  if (data.success) {
    renderComments([data.comment]);
    document.getElementById("new-comment").value = ""; // Clear the comment box
  } else {
    console.error("Failed to add comment");
  }
}

/**
 * Create an article for each comment
 */
function createCommentArticle(comment) {
  const article = document.createElement("article");

  const commentText = document.createElement("p");
  commentText.textContent = comment.text;
  article.appendChild(commentText);

  const footer = document.createElement("footer");
  footer.textContent = `Posted by: ${comment.author}`;
  article.appendChild(footer);

  return article;
}

/**
 * Attach event listener to the comment form
 */
document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("comment-form").addEventListener("submit", handleAddComment);
});
