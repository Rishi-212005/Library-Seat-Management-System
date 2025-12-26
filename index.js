document.addEventListener("DOMContentLoaded", () => {
  // Check if student is already logged in
  const currentUser = JSON.parse(localStorage.getItem("currentUser"))
  if (currentUser) {
    // Add a notification that user is already logged in
    const container = document.querySelector(".container")
    const notification = document.createElement("div")
    notification.className = "notification"
    notification.innerHTML = `
      <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; text-align: center;">
        You are logged in as ${currentUser.studentName}. 
        <a href="seat-booking.html" style="color: #155724; text-decoration: underline;">Continue to Seat Booking</a> or 
        <a href="#" id="logoutBtn" style="color: #155724; text-decoration: underline;">Logout</a>
      </div>
    `
    container.insertBefore(notification, container.firstChild)

    // Add logout functionality
    document.getElementById("logoutBtn").addEventListener("click", (e) => {
      e.preventDefault()
      localStorage.removeItem("currentUser")
      window.location.reload()
    })
  }

  // Check if librarian is already logged in
  if (localStorage.getItem("librarianSession")) {
    // Add a notification that librarian is already logged in
    const container = document.querySelector(".container")
    const notification = document.createElement("div")
    notification.className = "notification"
    notification.innerHTML = `
      <div style="background: #cce5ff; color: #004085; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; text-align: center;">
        You are logged in as Librarian. 
        <a href="librarian-dashboard.html" style="color: #004085; text-decoration: underline;">Continue to Dashboard</a> or 
        <a href="#" id="librarianLogoutBtn" style="color: #004085; text-decoration: underline;">Logout</a>
      </div>
    `
    container.insertBefore(notification, container.firstChild)

    // Add logout functionality
    document.getElementById("librarianLogoutBtn").addEventListener("click", (e) => {
      e.preventDefault()
      localStorage.removeItem("librarianSession")
      window.location.reload()
    })
  }
})
