document.addEventListener("DOMContentLoaded", () => {
  // Check if librarian is logged in
  if (!sessionStorage.getItem("librarianLoggedIn")) {
    window.location.href = "librarian-login-final.html"
    return
  }

  let currentFilter = "all"
  let currentRejectStudentId = null
  let studentsData = []

  // Initialize page
  loadStudents()
  updateStatistics()

  // Filter tab event listeners
  document.querySelectorAll(".filter-tab").forEach((tab) => {
    tab.addEventListener("click", function () {
      // Update active tab
      document.querySelectorAll(".filter-tab").forEach((t) => t.classList.remove("active"))
      this.classList.add("active")

      // Update filter and reload students
      currentFilter = this.dataset.status
      updateStudentsTitle()
      displayFilteredStudents()
    })
  })

  // Logout functionality
  document.getElementById("logoutBtn").addEventListener("click", (e) => {
    e.preventDefault()
    if (confirm("Are you sure you want to logout?")) {
      sessionStorage.removeItem("librarianLoggedIn")
      sessionStorage.removeItem("librarianUsername")
      sessionStorage.removeItem("librarianLoginTime")
      window.location.href = "index.html"
    }
  })

  function updateStudentsTitle() {
    const titles = {
      all: "All Students",
      pending: "Pending Review",
      approved: "Approved Students",
      rejected: "Rejected Students",
    }
    document.getElementById("studentsTitle").textContent = titles[currentFilter] || "Students"
  }

  function loadStudents() {
    showLoading()

    fetch(`manage-students-api.php?action=get_students&status=all`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          studentsData = data.students
          displayFilteredStudents()
        } else {
          showError("Error loading students: " + (data.error || "Unknown error"))
        }
      })
      .catch((error) => {
        console.error("Error loading students:", error)
        showError("Failed to load students. Please check your connection and try again.")
      })
  }

  function displayFilteredStudents() {
    const filteredStudents =
      currentFilter === "all" ? studentsData : studentsData.filter((student) => student.status === currentFilter)

    displayStudents(filteredStudents)
  }

  function showLoading() {
    document.getElementById("studentsList").innerHTML = `
      <div class="loading">
        <div class="loading-spinner"></div>
        <p>Loading students...</p>
      </div>
    `
  }

  function showError(message) {
    document.getElementById("studentsList").innerHTML = `
      <div class="no-students">
        <div class="no-students-icon">⚠️</div>
        <h3>Error Loading Students</h3>
        <p>${message}</p>
        <button class="btn btn-secondary" onclick="loadStudents()" style="margin-top: 1rem;">
          Try Again
        </button>
      </div>
    `
  }

  function displayStudents(students) {
    const studentsList = document.getElementById("studentsList")

    if (students.length === 0) {
      const filterText = currentFilter === "all" ? "" : currentFilter
      studentsList.innerHTML = `
        <div class="no-students">
          <div class="no-students-icon">👥</div>
          <h3>No ${filterText} students found</h3>
          <p>There are currently no students in this category.</p>
        </div>
      `
      return
    }

    const studentsHtml = students.map((student) => createStudentCard(student)).join("")
    studentsList.innerHTML = studentsHtml
  }

  function createStudentCard(student) {
    const initials = student.student_name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase()
    const statusClass = `status-${student.status}`
    const registrationDate = new Date(student.registration_date).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    })
    const updatedDate = student.updated_at
      ? new Date(student.updated_at).toLocaleDateString("en-US", {
          year: "numeric",
          month: "short",
          day: "numeric",
        })
      : null

    return `
      <div class="student-card">
        <div class="student-summary" onclick="toggleStudentDetails(${student.id})">
          <div class="student-basic-info">
            <div class="student-avatar">${initials}</div>
            <div class="student-name-info">
              <h4>${escapeHtml(student.student_name)}</h4>
              <div class="student-roll">Roll: ${escapeHtml(student.roll_number)}</div>
            </div>
          </div>
          <div class="student-status-badge ${statusClass}">
            ${student.status}
          </div>
        </div>
        
        <div class="student-details" id="details-${student.id}">
          <div class="details-grid">
            <div class="detail-section">
              <h5>Personal Information</h5>
              <div class="detail-item">
                <span class="detail-label">Full Name</span>
                <span class="detail-value">${escapeHtml(student.student_name)}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Roll Number</span>
                <span class="detail-value">${escapeHtml(student.roll_number)}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Course</span>
                <span class="detail-value">${escapeHtml(student.course || "N/A")}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Email</span>
                <span class="detail-value">${escapeHtml(student.email)}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Phone</span>
                <span class="detail-value">${escapeHtml(student.phone)}</span>
              </div>
            </div>
            
            <div class="detail-section">
              <h5>Registration Details</h5>
              <div class="detail-item">
                <span class="detail-label">Status</span>
                <span class="detail-value">
                  <span class="student-status-badge ${statusClass}">${student.status}</span>
                </span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Registered</span>
                <span class="detail-value">${registrationDate}</span>
              </div>
              ${
                updatedDate
                  ? `
                <div class="detail-item">
                  <span class="detail-label">Last Updated</span>
                  <span class="detail-value">${updatedDate}</span>
                </div>
              `
                  : ""
              }
            </div>
          </div>
          
          ${
            student.passport_photo || student.id_card_photo
              ? `
            <div class="photos-section">
              <h5>Uploaded Documents</h5>
              <div class="photos-grid">
                ${
                  student.passport_photo
                    ? `
                  <div class="photo-container">
                    <img src="${student.passport_photo}" alt="Passport Photo" onclick="openImageModal('${student.passport_photo}')">
                    <div class="photo-label">Passport Photo</div>
                  </div>
                `
                    : ""
                }
                ${
                  student.id_card_photo
                    ? `
                  <div class="photo-container">
                    <img src="${student.id_card_photo}" alt="ID Card" onclick="openImageModal('${student.id_card_photo}')">
                    <div class="photo-label">ID Card</div>
                  </div>
                `
                    : ""
                }
              </div>
            </div>
          `
              : ""
          }
          
          ${
            student.status === "pending"
              ? `
            <div class="actions-section">
              <button class="btn btn-approve" onclick="approveStudent(${student.id})">
                ✓ Approve Student
              </button>
              <button class="btn btn-reject" onclick="openRejectModal(${student.id}, '${escapeHtml(student.student_name)}')">
                ✗ Reject Student
              </button>
            </div>
          `
              : ""
          }
          
          ${
            student.status === "rejected" && student.issue && student.issue !== "No issue"
              ? `
            <div class="rejection-reason">
              <h6>Rejection Reason:</h6>
              <p>${escapeHtml(student.issue)}</p>
            </div>
          `
              : ""
          }
        </div>
      </div>
    `
  }

  function updateStatistics() {
    fetch("manage-students-api.php?action=get_statistics")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const stats = data.statistics
          document.getElementById("totalStudents").textContent = stats.total || 0
          document.getElementById("pendingStudents").textContent = stats.pending || 0
          document.getElementById("approvedStudents").textContent = stats.approved || 0
          document.getElementById("rejectedStudents").textContent = stats.rejected || 0
        }
      })
      .catch((error) => {
        console.error("Error loading statistics:", error)
      })
  }

  function escapeHtml(text) {
    if (!text) return ""
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    }
    return text.toString().replace(/[&<>"']/g, (m) => map[m])
  }

  // Global functions
  window.toggleStudentDetails = (studentId) => {
    const details = document.getElementById(`details-${studentId}`)
    if (details.classList.contains("show")) {
      details.classList.remove("show")
      setTimeout(() => {
        details.style.display = "none"
      }, 300)
    } else {
      details.style.display = "block"
      setTimeout(() => {
        details.classList.add("show")
      }, 10)
    }
  }

  window.approveStudent = (studentId) => {
    if (confirm("Are you sure you want to approve this student?")) {
      updateStudentStatusAPI(studentId, "approved", "No issue")
    }
  }

  window.openRejectModal = (studentId, studentName) => {
    currentRejectStudentId = studentId
    document.getElementById("rejectStudentName").textContent = studentName
    document.getElementById("rejectReason").value = ""
    document.getElementById("rejectModal").style.display = "block"
  }

  window.closeRejectModal = () => {
    document.getElementById("rejectModal").style.display = "none"
    currentRejectStudentId = null
  }

  window.confirmReject = () => {
    const reason = document.getElementById("rejectReason").value.trim()

    if (!reason) {
      alert("Please provide a reason for rejection.")
      return
    }

    if (currentRejectStudentId) {
      updateStudentStatusAPI(currentRejectStudentId, "rejected", reason)
      window.closeRejectModal()
    }
  }

  window.openImageModal = (imageSrc) => {
    document.getElementById("modalImage").src = imageSrc
    document.getElementById("imageModal").style.display = "block"
  }

  window.closeImageModal = () => {
    document.getElementById("imageModal").style.display = "none"
  }

  function updateStudentStatusAPI(studentId, status, issue) {
    const data = {
      student_id: studentId,
      status: status,
      issue: issue,
    }

    fetch("manage-students-api.php?action=update_status", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const action = status === "approved" ? "approved" : "rejected"
          alert(`Student ${action} successfully!`)

          // Reload students and update statistics
          loadStudents()
          updateStatistics()
        } else {
          alert(`Error: ${data.error}`)
        }
      })
      .catch((error) => {
        console.error("Error updating student status:", error)
        alert("Error updating student status. Please try again.")
      })
  }

  // Close modals when clicking outside
  window.onclick = (event) => {
    const rejectModal = document.getElementById("rejectModal")
    if (event.target === rejectModal) {
      window.closeRejectModal()
    }
  }

  // Refresh data every 30 seconds
  setInterval(() => {
    updateStatistics()
  }, 30000)
})
