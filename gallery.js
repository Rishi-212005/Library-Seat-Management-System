document.addEventListener("DOMContentLoaded", () => {
  const galleryTabs = document.querySelectorAll(".gallery-tab")
  const galleryItems = document.querySelectorAll(".gallery-item")

  // Gallery filtering
  galleryTabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      const category = tab.dataset.category

      // Update active tab
      galleryTabs.forEach((t) => t.classList.remove("active"))
      tab.classList.add("active")

      // Filter gallery items
      galleryItems.forEach((item) => {
        if (category === "all" || item.dataset.category === category) {
          item.style.display = "block"
          setTimeout(() => {
            item.style.opacity = "1"
            item.style.transform = "scale(1)"
          }, 10)
        } else {
          item.style.opacity = "0"
          item.style.transform = "scale(0.8)"
          setTimeout(() => {
            item.style.display = "none"
          }, 300)
        }
      })
    })
  })

  // Initialize gallery items
  galleryItems.forEach((item) => {
    item.style.transition = "opacity 0.3s ease, transform 0.3s ease"
    item.style.opacity = "1"
    item.style.transform = "scale(1)"
  })

  // Video placeholder click handlers
  document.querySelectorAll(".video-placeholder").forEach((video) => {
    video.addEventListener("click", () => {
      const title = video.querySelector("h3").textContent
      alert(`Video: ${title}\n\nThis would open the video player in a real implementation.`)
    })
  })
})
