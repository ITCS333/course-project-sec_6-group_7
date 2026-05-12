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
 * Attach event listener to the comment form
 */
document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("comment-form").addEventListener("submit", handleAddComment);
});
