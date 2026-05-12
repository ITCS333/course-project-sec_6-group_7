
const resourceListSection = document.getElementById("resource-list-section");
function createResourceArticle(resource) {
  const article = document.createElement("article");

  const title = document.createElement("h3");
  title.textContent = resource.title;

  const description = document.createElement("p");
  description.textContent = resource.description;

  const link = document.createElement("a");
  link.href = `details.html?id=${resource.id}`;
  link.textContent = "View Resource & Discussion";

  article.appendChild(title);
  article.appendChild(description);
  article.appendChild(link);

  return article;
}
async function loadResources() {
  try {
    const response = await fetch("./api/index.php");
    const result = await response.json();

    resourceListSection.innerHTML = "";

    result.data.forEach(resource => {
      const article = createResourceArticle(resource);
      resourceListSection.appendChild(article);
    });

  } catch (error) {
    console.error(error);
  }
}
loadResources();