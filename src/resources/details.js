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
    // Create and render the new comment directly
    const commentArticle = createCommentArticle(data.comment);
    document.getElementById("comment-list").appendChild(commentArticle);
    document.getElementById("new-comment").value = ""; // Clear the comment box
  } else {
    console.error("Failed to add comment");
  }
}

/**
 * Create a comment article element (needed here too for handleAddComment)
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
