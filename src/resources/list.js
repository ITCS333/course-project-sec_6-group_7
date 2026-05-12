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
  link.href = "details.html?id=" + resource.id;
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
 * Initialize the page when the DOM is ready
 */
document.addEventListener("DOMContentLoaded", () => {
  loadResources();
});
