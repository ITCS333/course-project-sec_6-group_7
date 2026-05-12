document.addEventListener("DOMContentLoaded", function () {
    const resourceId = new URLSearchParams(window.location.search).get("id");
    const resourceTitle = document.getElementById("resource-title");
    const resourceDescription = document.getElementById("resource-description");
    const resourceLink = document.getElementById("resource-link");
    const commentList = document.getElementById("comment-list");
    const commentForm = document.getElementById("comment-form");
    const newCommentText = document.getElementById("new-comment");

    async function loadResourceDetails() {
        try {
            const response = await fetch(`./api/index.php?id=${resourceId}`);
            const data = await response.json();

            if (data.success) {
                const resource = data.data;
                resourceTitle.textContent = resource.title;
                resourceDescription.textContent = resource.description;
                resourceLink.href = resource.link;
                resourceLink.textContent = "Access Resource Material";
                loadComments(resource.id);
            } else {
                console.error("Failed to load resource details");
            }
        } catch (error) {
            console.error("Error fetching resource details:", error);
        }
    }

    async function loadComments(resourceId) {
        try {
            const response = await fetch(`./api/index.php?resource_id=${resourceId}&action=comments`);
            const data = await response.json();

            if (data.success) {
                commentList.innerHTML = "";  // Clear existing comments
                data.data.forEach((comment) => {
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
        } catch (error) {
            console.error("Error fetching comments:", error);
        }
    }

    async function handleCommentSubmit(event) {
        event.preventDefault();

        const commentText = newCommentText.value.trim();
        if (!commentText) return;

        try {
            const response = await fetch("./api/index.php?action=comment", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    resource_id: resourceId,
                    author: "Student",
                    text: commentText,
                }),
            });

            const data = await response.json();

            if (data.success) {
                loadComments(resourceId);  // Reload comments
                newCommentText.value = "";  // Clear textarea
            } else {
                console.error("Failed to post comment");
            }
        } catch (error) {
            console.error("Error posting comment:", error);
        }
    }

    commentForm.addEventListener("submit", handleCommentSubmit);

    loadResourceDetails();
});
