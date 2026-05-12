document.addEventListener("DOMContentLoaded", function () {
    const resourceListSection = document.getElementById("resource-list-section");

    async function loadResources() {
        try {
            const response = await fetch("./api/index.php");
            const data = await response.json();

            if (data.success) {
                data.data.forEach((resource) => {
                    const resourceArticle = document.createElement("article");

                    const title = document.createElement("h3");
                    title.textContent = resource.title;
                    resourceArticle.appendChild(title);

                    const description = document.createElement("p");
                    description.textContent = resource.description;
                    resourceArticle.appendChild(description);

                    const link = document.createElement("a");
                    link.href = resource.link;
                    link.textContent = "View Resource & Discussion";
                    resourceArticle.appendChild(link);

                    resourceListSection.appendChild(resourceArticle);
                });
            } else {
                console.error("Failed to load resources");
            }
        } catch (error) {
            console.error("Error fetching resources:", error);
        }
    }

    loadResources();
});
