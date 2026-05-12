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
    document.getElementById("new-comment").value = ""; // Clear the comment box after posting
  } else {
    console.error("Failed to add comment");
  }
}
