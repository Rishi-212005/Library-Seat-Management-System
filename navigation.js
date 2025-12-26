document.addEventListener("DOMContentLoaded", () => {
  const navToggle = document.getElementById("navToggle")
  const navMenu = document.getElementById("navMenu")

  // Mobile navigation toggle
  if (navToggle && navMenu) {
    navToggle.addEventListener("click", () => {
      navMenu.classList.toggle("active")
      navToggle.classList.toggle("active")
    })

    // Close menu when clicking on a link
    document.querySelectorAll(".nav-link").forEach((link) => {
      link.addEventListener("click", () => {
        navMenu.classList.remove("active")
        navToggle.classList.remove("active")
      })
    })

    // Close menu when clicking outside
    document.addEventListener("click", (e) => {
      if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
        navMenu.classList.remove("active")
        navToggle.classList.remove("active")
      }
    })
  }

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault()
      const target = document.querySelector(this.getAttribute("href"))
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        })
      }
    })
  })
})
