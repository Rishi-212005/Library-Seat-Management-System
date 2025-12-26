document.addEventListener("DOMContentLoaded", () => {
  // Check if librarian is logged in
  if (!sessionStorage.getItem("librarianLoggedIn")) {
    window.location.href = "librarian-login-final.html"
    return
  }

  // Load dashboard statistics
  loadDashboardStats()

  // Refresh stats every 30 seconds
  setInterval(loadDashboardStats, 30000)
})

function loadDashboardStats() {
  // Load statistics from the API
  Promise.all([
    fetch("manage-students-api.php?action=get_statistics")
      .then((r) => r.json())
      .catch(() => ({ success: false })),
    fetch("librarian-get-seats.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "date=" + new Date().toISOString().split("T")[0],
    })
      .then((r) => r.json())
      .catch(() => ({ success: false })),
  ])
    .then(([studentStats, seatStats]) => {
      // Update student statistics
      if (studentStats.success) {
        document.getElementById("totalStudents").textContent = studentStats.statistics.total || 0
      }

      // Update booking statistics
      if (seatStats.success) {
        document.getElementById("todayBookings").textContent = seatStats.stats.total || 0
        document.getElementById("activeBookings").textContent = seatStats.stats.booked || 0

        // Calculate attendance rate
        const total = seatStats.stats.total || 0
        const attended = seatStats.stats.attended || 0
        const rate = total > 0 ? Math.round((attended / total) * 100) : 0
        document.getElementById("attendanceRate").textContent = rate + "%"
      }
    })
    .catch((error) => {
      console.error("Error loading dashboard stats:", error)
    })
}

function logout() {
  if (confirm("Are you sure you want to logout?")) {
    sessionStorage.removeItem("librarianLoggedIn")
    sessionStorage.removeItem("librarianUsername")
    sessionStorage.removeItem("librarianLoginTime")
    window.location.href = "index.html"
  }
}
