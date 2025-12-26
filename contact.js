document.addEventListener("DOMContentLoaded", () => {
  const contactForm = document.getElementById("contactForm")

  if (contactForm) {
    contactForm.addEventListener("submit", (e) => {
      e.preventDefault()

      // Get form data
      const formData = new FormData(contactForm)
      const data = {
        firstName: formData.get("firstName"),
        lastName: formData.get("lastName"),
        email: formData.get("email"),
        phone: formData.get("phone"),
        subject: formData.get("subject"),
        message: formData.get("message"),
        timestamp: new Date().toISOString(),
      }

      // Store in localStorage (in real implementation, this would be sent to server)
      const contacts = JSON.parse(localStorage.getItem("contactSubmissions") || "[]")
      contacts.push(data)
      localStorage.setItem("contactSubmissions", JSON.stringify(contacts))

      // Show success message
      alert(
        `Thank you for your message, ${data.firstName}!\n\nWe have received your inquiry about "${data.subject}" and will get back to you within 24 hours at ${data.email}.`,
      )

      // Reset form
      contactForm.reset()
    })
  }

  // Map button handlers
  document.querySelectorAll(".map-buttons .btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const action = btn.textContent.trim()
      if (action === "Get Directions") {
        alert("This would open directions to SRS Government Degree College in your maps app.")
      } else if (action === "View in Maps") {
        alert("This would open the college location in your default maps application.")
      }
    })
  })
})
