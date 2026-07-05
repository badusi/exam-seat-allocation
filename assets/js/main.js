// Main JavaScript functionality
class ExamSeatApp {
  constructor() {
    this.init()
  }

  init() {
    this.setupEventListeners()
    this.initializeTabs()
    this.initializeModals()
    this.setupFormValidation()
  }

  setupEventListeners() {
    // Form submissions
    document.addEventListener("submit", (e) => {
      if (e.target.classList.contains("ajax-form")) {
        e.preventDefault()
        this.handleAjaxForm(e.target)
      }
    })

    // Button clicks
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("btn-logout")) {
        this.handleLogout(e.target)
      }

      if (e.target.classList.contains("btn-print")) {
        window.print()
      }

      if (e.target.classList.contains("modal-trigger")) {
        this.openModal(e.target.dataset.modal)
      }

      if (e.target.classList.contains("modal-close")) {
        this.closeModal()
      }

      if (e.target.classList.contains("tab-trigger")) {
        this.switchTab(e.target)
      }
    })
  }

  async handleAjaxForm(form) {
    const formData = new FormData(form)
    const submitBtn = form.querySelector('button[type="submit"]')
    const originalText = submitBtn.textContent

    submitBtn.disabled = true
    submitBtn.innerHTML = '<span class="loading"></span> Loading...'

    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: formData,
      })

      const result = await response.json()

     if (result.success) {
        this.showAlert("success", "Operation completed successfully!")

        if (form.classList.contains("login-form")) {
          const role = result.data?.role?.toLowerCase()

          if (role === "admin") {
            window.location.href = "/exam-seat-allocation/admin/dashboard.php"
          } else if (role === "student") {
            window.location.href = "/exam-seat-allocation/student/dashboard.php"
          } else {
            this.showAlert("error", "Unknown user role")
          }

        } else if (form.classList.contains("seat-allocation-form")) {
          this.displaySeatAllocation(result.data)
        }

      }
       else {
        this.showAlert("error", result.error || "An error occurred")
      }
    } catch (error) {
      console.error("Form submission error:", error)
      this.showAlert("error", "Network error occurred")
    } finally {
      submitBtn.disabled = false
      submitBtn.textContent = originalText
    }
  }

  async handleLogout(button) {
    const userType = button.dataset.userType || "student"

    try {
      const response = await fetch(`/auth/${userType}/logout.php`, {
        method: "POST",
      })

      if (response.ok) {
        window.location.href = "/"
      }
    } catch (error) {
      console.error("Logout error:", error)
    }
  }

  showAlert(type, message) {
    document.querySelectorAll(".alert").forEach((alert) => alert.remove())

    const alert = document.createElement("div")
    alert.className = `alert alert-${type} fade-in`
    alert.innerHTML = `
      <span class="icon">${this.getAlertIcon(type)}</span>
      <span>${message}</span>
    `

    const main = document.querySelector("main") || document.body
    main.insertBefore(alert, main.firstChild)

    setTimeout(() => {
      alert?.remove()
    }, 5000)
  }

  getAlertIcon(type) {
    const icons = {
      success: "✓",
      error: "✗",
      warning: "⚠",
      info: "ℹ",
    }
    return icons[type] || "ℹ"
  }

  initializeTabs() {
    const tabTriggers = document.querySelectorAll(".tab-trigger")
    if (tabTriggers.length > 0) {
      this.switchTab(tabTriggers[0])
    }
  }

  switchTab(trigger) {
    const tabList = trigger.closest(".tab-list")
    const tabsContainer = tabList.closest(".tabs")
    const targetTab = trigger.dataset.tab

    tabList.querySelectorAll(".tab-trigger").forEach((t) => t.classList.remove("active"))
    trigger.classList.add("active")

    tabsContainer.querySelectorAll(".tab-content").forEach((content) => {
      content.classList.remove("active")
    })

    const targetContent = tabsContainer.querySelector(`[data-tab-content="${targetTab}"]`)
    if (targetContent) targetContent.classList.add("active")
  }

  initializeModals() {
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("modal")) {
        this.closeModal()
      }
    })

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        this.closeModal()
      }
    })
  }

  openModal(modalId) {
    const modal = document.getElementById(modalId)
    if (modal) {
      modal.style.display = "flex"
      document.body.style.overflow = "hidden"
    }
  }

  closeModal() {
    document.querySelectorAll(".modal").forEach((modal) => {
      modal.style.display = "none"
    })
    document.body.style.overflow = ""
  }

  setupFormValidation() {
    const forms = document.querySelectorAll("form")
    forms.forEach((form) => {
      const inputs = form.querySelectorAll("input[required], select[required]")
      inputs.forEach((input) => {
        input.addEventListener("blur", () => this.validateField(input))
        input.addEventListener("input", () => this.clearFieldError(input))
      })
    })
  }

  validateField(field) {
    const value = field.value.trim()
    let isValid = true
    let errorMessage = ""

    if (field.hasAttribute("required") && !value) {
      isValid = false
      errorMessage = "This field is required"
    } else if (field.type === "email" && !this.isValidEmail(value)) {
      isValid = false
      errorMessage = "Please enter a valid email address"
    } else if (field.name === "matricNumber" && !this.isValidMatricNumber(value)) {
      isValid = false
      errorMessage = "Please enter a valid matric number (e.g., CS2021010001)"
    }

    if (!isValid) {
      this.showFieldError(field, errorMessage)
    } else {
      this.clearFieldError(field)
    }

    return isValid
  }

  showFieldError(field, message) {
    this.clearFieldError(field)

    field.classList.add("error")
    const errorDiv = document.createElement("div")
    errorDiv.className = "field-error text-sm text-red-600 mt-1"
    errorDiv.textContent = message

    field.parentNode.appendChild(errorDiv)
  }

  clearFieldError(field) {
    field.classList.remove("error")
    const errorDiv = field.parentNode.querySelector(".field-error")
    if (errorDiv) errorDiv.remove()
  }

  isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
  }

  // isValidMatricNumber(matric) {
  //   return /^[A-Z]{2,4}\/\d{4}\/\d{3}$/.test(matric.toUpperCase())
  // }

isValidMatricNumber(matric) {
  return /^[A-Z]{2}\d{10}$/.test(matric.toUpperCase());
}


  displaySeatAllocation(allocation) {
    const container = document.getElementById("seat-allocation-result")
    if (!container) return

    container.innerHTML = `
      <div class="card fade-in">
        <div class="card-header text-center">
          <div class="icon-xl mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center">
            <span class="text-emerald-600">✓</span>
          </div>
          <h3 class="text-emerald-600">Seat Allocated Successfully!</h3>
          <p class="text-gray-600">Your examination seat has been assigned. Please save these details.</p>
        </div>
        <div class="card-content space-y-6">
          <div class="grid gap-4">
            <div class="flex justify-between items-center p-4 bg-emerald-50 rounded-lg border border-emerald-200">
              <span class="font-medium text-emerald-700">Seat Number:</span>
              <div class="text-2xl font-bold text-emerald-600">#${allocation.seat_number}</div>
            </div>

            <div class="p-4 bg-teal-50 rounded-lg border border-teal-200">
              <div class="flex items-center space-x-2 mb-2">
                <span class="icon text-teal-600">📍</span>
                <span class="font-medium text-teal-700">Examination Venue</span>
              </div>
              <div class="text-lg font-semibold text-teal-900">${allocation.hall_name}</div>
              <div class="text-teal-700">${allocation.venue}</div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-2 mb-2">
                  <span class="icon text-gray-600">📅</span>
                  <span class="font-medium text-gray-700">Allocated On</span>
                </div>
                <div class="text-gray-900">${new Date(allocation.allocated_at).toLocaleDateString()}</div>
              </div>

              <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-2 mb-2">
                  <span class="icon text-gray-600">🕒</span>
                  <span class="font-medium text-gray-700">Time</span>
                </div>
                <div class="text-gray-900">${new Date(allocation.allocated_at).toLocaleTimeString()}</div>
              </div>
            </div>
          </div>

          <div class="alert alert-info">
            <span class="icon">ℹ</span>
            <div>
              <strong>Important:</strong> Please arrive at the examination venue 30 minutes before the scheduled time. Bring this seat allocation details and your student ID card.
            </div>
          </div>

          <button type="button" class="btn btn-outline w-full btn-print">
            Print Seat Details
          </button>
        </div>
      </div>
    `

    const form = document.getElementById("seat-allocation-form")
    if (form) form.style.display = "none"
  }

  async refreshData() {
    window.location.reload()
  }

  formatDate(dateString) {
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    })
  }

  formatTime(dateString) {
    return new Date(dateString).toLocaleTimeString("en-US", {
      hour: "2-digit",
      minute: "2-digit",
    })
  }
}

// Initialize the app
document.addEventListener("DOMContentLoaded", () => {
  const appInstance = new ExamSeatApp()
  window.ExamSeatAppInstance = appInstance
})

// Utility functions
function showLoading(element) {
  element.disabled = true
  element.innerHTML = '<span class="loading"></span> Loading...'
}

function hideLoading(element, originalText) {
  element.disabled = false
  element.textContent = originalText
}

function copyToClipboard(text) {
  navigator.clipboard
    .writeText(text)
    .then(() => {
      if (window.ExamSeatAppInstance) {
        window.ExamSeatAppInstance.showAlert("success", "Copied to clipboard!")
      }
    })
    .catch(() => {
      if (window.ExamSeatAppInstance) {
        window.ExamSeatAppInstance.showAlert("error", "Failed to copy to clipboard")
      }
    })
}



document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.ajax-form.seat-allocation-form');
    const resultBox = document.getElementById('seat-allocation-result');

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = "Processing...";

            fetch(form.action, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.textContent = "Get My Systematic Seat Assignment";

                if (data.success) {
                    // Reload the page to show the new seat
                    location.reload();
                } else {
                    resultBox.innerHTML = `<div class="alert alert-error">${data.message || 'An error occurred.'}</div>`;
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.textContent = "Get My Systematic Seat Assignment";
                resultBox.innerHTML = `<div class="alert alert-error">Request failed. Please try again.</div>`;
                console.error(error);
            });
        });
    }
});



