// document.addEventListener("DOMContentLoaded", () => {
//   let selectedSeat = null
//   let currentSeatStatuses = {}

//   // Set default date to today
//   const today = new Date().toISOString().split("T")[0]
//   document.getElementById("booking_date").value = today
//   document.getElementById("booking_date").min = today

//   // Event listeners
//   document.getElementById("booking_date").addEventListener("change", updateSeatAvailability)
//   document.getElementById("start_time").addEventListener("change", updateSeatAvailability)
//   document.getElementById("duration").addEventListener("change", updateSeatAvailability)
//   document.getElementById("refreshBtn").addEventListener("click", updateSeatAvailability)
//   document.getElementById("bookSeatBtn").addEventListener("click", handleBookSeat)

//   // Floor tab switching
//   document.querySelectorAll(".floor-tab").forEach((tab) => {
//     tab.addEventListener("click", function () {
//       switchFloor(this.dataset.floor)
//     })
//   })

//   // Seat click handlers
//   document.querySelectorAll(".seat, .computer-seat").forEach((seat) => {
//     seat.addEventListener("click", function () {
//       if (this.classList.contains("available")) {
//         selectSeat(this)
//       }
//     })
//   })

//   // Initialize seat availability on page load
//   updateSeatAvailability()

//   // Auto-refresh seat status every 30 seconds
//   setInterval(() => {
//     if (document.getElementById("booking_date").value) {
//       console.log("Auto-refreshing seat status...")
//       updateSeatAvailability()
//     }
//   }, 30000)

//   function selectSeat(seatElement) {
//     // Remove previous selection
//     document.querySelectorAll(".seat.selected, .computer-seat.selected").forEach((seat) => {
//       seat.classList.remove("selected")
//     })

//     // Select new seat
//     seatElement.classList.add("selected")
//     selectedSeat = {
//       id: seatElement.dataset.seatId,
//       isComputer: seatElement.dataset.isComputer === "true",
//     }

//     // Update display
//     const seatType = selectedSeat.isComputer ? "Computer Station" : "Study Seat"
//     document.getElementById("selectedSeatInfo").innerHTML = `
//             <strong>Selected:</strong> ${selectedSeat.id} (${seatType})
//         `

//     updateBookButton()
//   }

//   function updateSeatAvailability() {
//     const date = document.getElementById("booking_date").value
//     const startTime = document.getElementById("start_time").value
//     const duration = document.getElementById("duration").value

//     console.log("=== UPDATING SEAT AVAILABILITY ===")
//     console.log("Date:", date, "Start Time:", startTime, "Duration:", duration)

//     if (!date) {
//       console.log("No date selected")
//       showStatusMessage("Please select a date to see seat availability.", "info")
//       resetAllSeatsToAvailable()
//       updateDebugInfo("No date selected")
//       return
//     }

//     // Show loading state
//     showStatusMessage("Loading seat availability...", "info")
//     document.querySelector(".floor-section").classList.add("loading")

//     // Fetch seat data from database
//     fetchSeatData(date, startTime || 9, duration || 1)
//   }

//   function fetchSeatData(date, startTime, duration) {
//     console.log("Fetching seat data for:", { date, startTime, duration })

//     const requestData = {
//       date: date,
//       start_time: Number.parseInt(startTime),
//       duration: Number.parseInt(duration),
//     }

//     fetch(`get-seat-status.php?t=${Date.now()}`, {
//       method: "POST",
//       headers: {
//         "Content-Type": "application/json",
//         "Cache-Control": "no-cache",
//         Pragma: "no-cache",
//       },
//       body: JSON.stringify(requestData),
//     })
//       .then((response) => {
//         console.log("Response status:", response.status)
//         if (!response.ok) {
//           throw new Error(`HTTP error! status: ${response.status}`)
//         }
//         return response.json()
//       })
//       .then((data) => {
//         console.log("=== SERVER RESPONSE ===")
//         console.log("Full response:", data)

//         // Remove loading state
//         document.querySelector(".floor-section").classList.remove("loading")

//         if (data.success) {
//           currentSeatStatuses = data.seat_statuses || {}
//           updateSeatDisplay(data.seat_statuses || {})
//           updateDebugInfo(data)

//           const availableCount = Object.values(data.seat_statuses).filter((status) => status === "available").length
//           const bookedCount = Object.values(data.seat_statuses).filter((status) => status === "booked").length
//           const attendedCount = Object.values(data.seat_statuses).filter((status) => status === "attended").length

//           showStatusMessage(
//             `Seat status updated! Available: ${availableCount}, Booked: ${bookedCount}, Attended: ${attendedCount}`,
//             "success",
//           )
//         } else {
//           console.error("Server error:", data.error)
//           resetAllSeatsToAvailable()
//           updateDebugInfo("Error: " + data.error)
//           showStatusMessage("Error loading seat data: " + data.error, "error")
//         }
//       })
//       .catch((error) => {
//         console.error("Fetch error:", error)
//         document.querySelector(".floor-section").classList.remove("loading")
//         resetAllSeatsToAvailable()
//         updateDebugInfo("Network error: " + error.message)
//         showStatusMessage("Network error: Unable to load seat data. Please check your connection.", "error")
//       })
//   }

//   function updateSeatDisplay(seatStatuses) {
//     console.log("=== UPDATING SEAT DISPLAY ===")
//     console.log("Seat statuses:", seatStatuses)

//     let updatedCount = 0

//     document.querySelectorAll(".seat, .computer-seat").forEach((seat) => {
//       const seatId = seat.dataset.seatId
//       const oldClasses = seat.className

//       // Reset all status classes
//       seat.classList.remove("booked", "attended", "selected", "available")

//       // Apply status from database
//       const status = seatStatuses[seatId] || "available"
//       seat.classList.add(status)

//       const newClasses = seat.className
//       if (oldClasses !== newClasses) {
//         console.log(`Seat ${seatId}: ${oldClasses} → ${newClasses}`)
//         updatedCount++
//       }
//     })

//     console.log(`Updated ${updatedCount} seats with database status`)

//     // Clear selection if selected seat is now unavailable
//     if (selectedSeat && seatStatuses[selectedSeat.id] && seatStatuses[selectedSeat.id] !== "available") {
//       console.log(`Clearing selection for seat ${selectedSeat.id} - now ${seatStatuses[selectedSeat.id]}`)
//       clearSeatSelection()
//     }
//   }

//   function resetAllSeatsToAvailable() {
//     document.querySelectorAll(".seat, .computer-seat").forEach((seat) => {
//       seat.classList.remove("booked", "attended", "selected")
//       seat.classList.add("available")
//     })
//     currentSeatStatuses = {}
//   }

//   function clearSeatSelection() {
//     selectedSeat = null
//     document.querySelectorAll(".seat.selected, .computer-seat.selected").forEach((seat) => {
//       seat.classList.remove("selected")
//     })
//     document.getElementById("selectedSeatInfo").innerHTML = "No seat selected"
//     updateBookButton()
//   }

//   function updateDebugInfo(data) {
//     const debugContent = document.getElementById("debugInfo")

//     if (typeof data === "string") {
//       debugContent.textContent = data
//       return
//     }

//     if (data && data.success) {
//       let debugText = `Database Query Results:\n`
//       debugText += `Date: ${data.date}\n`
//       debugText += `Time Range: ${data.start_time}:00 - ${data.start_time + data.duration}:00\n`
//       debugText += `Total Bookings Found: ${data.total_bookings}\n`
//       debugText += `Conflicting Seats: ${data.conflicting_seats.length}\n\n`

//       if (data.booking_details && data.booking_details.length > 0) {
//         debugText += `Booking Details:\n`
//         data.booking_details.forEach((booking) => {
//           debugText += `- Seat ${booking.seat_id}: ${booking.status} (${booking.time_slot}) - Student ${booking.student_id}\n`
//           debugText += `  Booking Code: ${booking.booking_code}\n`
//           if (booking.attendance_code) {
//             debugText += `  Attendance Code: ${booking.attendance_code}\n`
//           }
//           debugText += `\n`
//         })
//       } else {
//         debugText += `No bookings found for the selected time slot.\n`
//       }

//       if (data.debug) {
//         debugText += `\nDebug Info:\n`
//         debugText += `SQL Query: ${data.debug.sql_query}\n`
//         debugText += `Query Parameters: ${JSON.stringify(data.debug.query_params, null, 2)}\n`
//       }

//       debugContent.textContent = debugText
//     } else {
//       debugContent.textContent = data || "No debug data available"
//     }
//   }

//   function showStatusMessage(message, type) {
//     const statusDiv = document.getElementById("statusMessage")
//     statusDiv.textContent = message
//     statusDiv.className = `status-message ${type}`
//     statusDiv.style.display = "block"

//     // Auto-hide success messages after 5 seconds
//     if (type === "success") {
//       setTimeout(() => {
//         statusDiv.style.display = "none"
//       }, 5000)
//     }
//   }

//   function updateBookButton() {
//     const date = document.getElementById("booking_date").value
//     const startTime = document.getElementById("start_time").value
//     const duration = document.getElementById("duration").value
//     const bookBtn = document.getElementById("bookSeatBtn")

//     bookBtn.disabled = !(date && startTime && duration && selectedSeat)
//   }

//   function handleBookSeat() {
//     if (!selectedSeat) {
//       showStatusMessage("Please select a seat first.", "error")
//       return
//     }

//     const date = document.getElementById("booking_date").value
//     const startTime = document.getElementById("start_time").value
//     const duration = document.getElementById("duration").value

//     if (!date || !startTime || !duration) {
//       showStatusMessage("Please fill in all booking details.", "error")
//       return
//     }

//     // Generate booking codes
//     const bookingCode = Math.random().toString(36).substr(2, 8).toUpperCase()
//     const attendanceCode = Math.random().toString(36).substr(2, 12).toUpperCase()

//     // Show booking confirmation
//     const seatType = selectedSeat.isComputer ? "Computer Station" : "Study Seat"
//     const startHour = Number.parseInt(startTime)
//     const endHour = startHour + Number.parseInt(duration)

//     const confirmMessage =
//       `🎉 Seat booking confirmed!\n\n` +
//       `Seat: ${selectedSeat.id} (${seatType})\n` +
//       `Date: ${new Date(date).toLocaleDateString()}\n` +
//       `Time: ${formatTime(startHour)} - ${formatTime(endHour)}\n` +
//       `Duration: ${duration} hour(s)\n\n` +
//       `Booking Code: ${bookingCode}\n` +
//       `Attendance Code: ${attendanceCode}\n\n` +
//       `Please provide the attendance code to the librarian when you arrive.`

//     alert(confirmMessage)

//     // Update seat status to booked
//     const seatElement = document.querySelector(`[data-seat-id="${selectedSeat.id}"]`)
//     if (seatElement) {
//       seatElement.classList.remove("available", "selected")
//       seatElement.classList.add("booked")
//     }

//     // Clear selection and form
//     clearSeatSelection()
//     document.getElementById("start_time").value = ""
//     document.getElementById("duration").value = ""

//     showStatusMessage("Seat booked successfully! Check your email for confirmation.", "success")
//   }

//   function switchFloor(floor) {
//     // Update tabs
//     document.querySelectorAll(".floor-tab").forEach((tab) => {
//       tab.classList.remove("active")
//     })
//     document.querySelector(`[data-floor="${floor}"]`).classList.add("active")

//     // Update content
//     document.querySelectorAll(".floor-content").forEach((content) => {
//       content.classList.remove("active")
//     })
//     document.getElementById(`${floor}Floor`).classList.add("active")

//     // Clear selection when switching floors
//     if (selectedSeat) {
//       clearSeatSelection()
//     }
//   }

//   function formatTime(hour) {
//     if (hour <= 12) {
//       return hour === 12 ? "12:00 PM" : `${hour}:00 AM`
//     } else {
//       return `${hour - 12}:00 PM`
//     }
//   }

//   // Update book button state when form fields change
//   document.getElementById("booking_date").addEventListener("change", updateBookButton)
//   document.getElementById("start_time").addEventListener("change", updateBookButton)
//   document.getElementById("duration").addEventListener("change", updateBookButton)
// })
