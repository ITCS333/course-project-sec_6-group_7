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
 * Render resource details on the page
 */
function renderResourceDetails(resource) {
  document.getElementById("resource-title").textContent = resource.title;
  document.getElementById("resource-description").textContent = resource.description;
  document.getElementById("resource-link").href = resource.link;
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
 
  // Load resource details
  const resourceResponse = await fetch(`./api/index.php?id=${resourceId}`);
  const resourceData = await resourceResponse.json();
  if (resourceData.success) {
    renderResourceDetails(resourceData.data);
  }
 
  // Load comments
  const commentsResponse = await fetch(`./api/index.php?resource_id=${resourceId}&action=comments`);
  const commentsData = await commentsResponse.json();
  if (commentsData.success) {
    renderComments(commentsData.data);
  }
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
 * Attach event listeners on DOM ready
 */
document.addEventListener("DOMContentLoaded", () => {
  initializePage();
  const form = document.getElementById("comment-form");
  if (form) {
    form.addEventListener("submit", handleAddComment);
  }
});