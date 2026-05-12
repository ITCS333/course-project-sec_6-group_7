/**
 * Create a comment article element
 */
function createCommentArticle(comment) {
  const article = document.createElement("article");
  
  const commentText = document.createElement("p");
  commentText.textContent = comment.text;
  article.appendChild(commentText);
  
  const footer = document.createElement("footer");
  footer.textContent = "Posted by: " + comment.author;
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
    // Create and append the new comment
    const commentArticle = createCommentArticle(data.comment);
    document.getElementById("comment-list").appendChild(commentArticle);
    document.getElementById("new-comment").value = ""; // Clear the comment box
  } else {
    console.error("Failed to add comment");
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
 * Initialize the page (load resource details and comments)
 */
async function initializePage() {
  const resourceId = getResourceIdFromURL();
  if (!resourceId) {
    console.error("Resource ID not found in the URL.");
    return;
  }

  // Load resource details and comments
  const resourceResponse = await fetch("./api/index.php?id=" + resourceId);
  const resourceData = await resourceResponse.json();
  if (resourceData.success) {
    renderResourceDetails(resourceData.data);
  }

  const commentsResponse = await fetch("./api/index.php?resource_id=" + resourceId + "&action=comments");
  const commentsData = await commentsResponse.json();
  if (commentsData.success) {
    renderComments(commentsData.data);
  }
}

/**
 * Attach event listener to the comment form
 */
document.addEventListener("DOMContentLoaded", () => {
  initializePage();
  document.getElementById("comment-form").addEventListener("submit", handleAddComment);
});
