// Medical Record System - JavaScript Functions

document.addEventListener("DOMContentLoaded", () => {
  // Initialize tooltips
  initializeTooltips()

  // Initialize form validation
  initializeFormValidation()

  // Initialize data tables
  initializeDataTables()

  // Initialize date pickers
  initializeDatePickers()
})

// Tooltip initialization
function initializeTooltips() {
  const tooltipElements = document.querySelectorAll("[data-tooltip]")
  tooltipElements.forEach((element) => {
    element.addEventListener("mouseenter", showTooltip)
    element.addEventListener("mouseleave", hideTooltip)
  })
}

function showTooltip(event) {
  const tooltip = document.createElement("div")
  tooltip.className = "tooltip"
  tooltip.textContent = event.target.getAttribute("data-tooltip")
  document.body.appendChild(tooltip)

  const rect = event.target.getBoundingClientRect()
  tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + "px"
  tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + "px"
}

function hideTooltip() {
  const tooltip = document.querySelector(".tooltip")
  if (tooltip) {
    tooltip.remove()
  }
}

// Form validation
function initializeFormValidation() {
  const forms = document.querySelectorAll("form")
  forms.forEach((form) => {
    form.addEventListener("submit", validateForm)
  })
}

function validateForm(event) {
  const form = event.target
  const requiredFields = form.querySelectorAll("[required]")
  let isValid = true

  requiredFields.forEach((field) => {
    if (!field.value.trim()) {
      showFieldError(field, "Field ini wajib diisi")
      isValid = false
    } else {
      clearFieldError(field)
    }
  })

  // Validate email fields
  const emailFields = form.querySelectorAll('input[type="email"]')
  emailFields.forEach((field) => {
    if (field.value && !isValidEmail(field.value)) {
      showFieldError(field, "Format email tidak valid")
      isValid = false
    }
  })

  // Validate phone fields
  const phoneFields = form.querySelectorAll('input[name*="hp"], input[name*="telepon"]')
  phoneFields.forEach((field) => {
    if (field.value && !isValidPhone(field.value)) {
      showFieldError(field, "Format nomor telepon tidak valid")
      isValid = false
    }
  })

  if (!isValid) {
    event.preventDefault()
  }
}

function showFieldError(field, message) {
  clearFieldError(field)

  const errorDiv = document.createElement("div")
  errorDiv.className = "field-error"
  errorDiv.textContent = message
  errorDiv.style.color = "#dc3545"
  errorDiv.style.fontSize = "0.875rem"
  errorDiv.style.marginTop = "5px"

  field.style.borderColor = "#dc3545"
  field.parentNode.appendChild(errorDiv)
}

function clearFieldError(field) {
  const existingError = field.parentNode.querySelector(".field-error")
  if (existingError) {
    existingError.remove()
  }
  field.style.borderColor = ""
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

function isValidPhone(phone) {
  const phoneRegex = /^[\d\-+$$$$\s]+$/
  return phoneRegex.test(phone) && phone.replace(/\D/g, "").length >= 10
}

// Data table enhancements
function initializeDataTables() {
  const tables = document.querySelectorAll(".data-table")
  tables.forEach((table) => {
    addTableSearch(table)
    addTableSort(table)
    addTablePagination(table)
  })
}

function addTableSearch(table) {
  const searchContainer = document.createElement("div")
  searchContainer.className = "table-search"
  searchContainer.style.marginBottom = "20px"

  const searchInput = document.createElement("input")
  searchInput.type = "text"
  searchInput.placeholder = "Cari data..."
  searchInput.className = "form-control"
  searchInput.style.maxWidth = "300px"

  searchInput.addEventListener("input", function () {
    filterTable(table, this.value)
  })

  searchContainer.appendChild(searchInput)
  table.parentNode.insertBefore(searchContainer, table)
}

function filterTable(table, searchTerm) {
  const rows = table.querySelectorAll("tbody tr")
  const term = searchTerm.toLowerCase()

  rows.forEach((row) => {
    const text = row.textContent.toLowerCase()
    row.style.display = text.includes(term) ? "" : "none"
  })
}

function addTableSort(table) {
  const headers = table.querySelectorAll("thead th")
  headers.forEach((header, index) => {
    header.style.cursor = "pointer"
    header.addEventListener("click", () => sortTable(table, index))

    // Add sort indicator
    const sortIcon = document.createElement("i")
    sortIcon.className = "fas fa-sort"
    sortIcon.style.marginLeft = "5px"
    sortIcon.style.opacity = "0.5"
    header.appendChild(sortIcon)
  })
}

function sortTable(table, columnIndex) {
  const tbody = table.querySelector("tbody")
  const rows = Array.from(tbody.querySelectorAll("tr"))
  const header = table.querySelectorAll("thead th")[columnIndex]
  const icon = header.querySelector("i")

  // Determine sort direction
  const isAscending = !header.classList.contains("sort-desc")

  // Reset all sort indicators
  table.querySelectorAll("thead th").forEach((th) => {
    th.classList.remove("sort-asc", "sort-desc")
    const thIcon = th.querySelector("i")
    if (thIcon) {
      thIcon.className = "fas fa-sort"
      thIcon.style.opacity = "0.5"
    }
  })

  // Set current sort indicator
  header.classList.add(isAscending ? "sort-asc" : "sort-desc")
  icon.className = isAscending ? "fas fa-sort-up" : "fas fa-sort-down"
  icon.style.opacity = "1"

  // Sort rows
  rows.sort((a, b) => {
    const aText = a.cells[columnIndex].textContent.trim()
    const bText = b.cells[columnIndex].textContent.trim()

    // Try to parse as numbers
    const aNum = Number.parseFloat(aText)
    const bNum = Number.parseFloat(bText)

    if (!isNaN(aNum) && !isNaN(bNum)) {
      return isAscending ? aNum - bNum : bNum - aNum
    }

    // Try to parse as dates
    const aDate = new Date(aText)
    const bDate = new Date(bText)

    if (!isNaN(aDate.getTime()) && !isNaN(bDate.getTime())) {
      return isAscending ? aDate - bDate : bDate - aDate
    }

    // Default string comparison
    return isAscending ? aText.localeCompare(bText) : bText.localeCompare(aText)
  })

  // Reorder rows in DOM
  rows.forEach((row) => tbody.appendChild(row))
}

function addTablePagination(table) {
  const rowsPerPage = 10
  const tbody = table.querySelector("tbody")
  const rows = Array.from(tbody.querySelectorAll("tr"))

  if (rows.length <= rowsPerPage) return

  let currentPage = 1
  const totalPages = Math.ceil(rows.length / rowsPerPage)

  // Create pagination container
  const paginationContainer = document.createElement("div")
  paginationContainer.className = "table-pagination"
  paginationContainer.style.marginTop = "20px"
  paginationContainer.style.textAlign = "center"

  function showPage(page) {
    const start = (page - 1) * rowsPerPage
    const end = start + rowsPerPage

    rows.forEach((row, index) => {
      row.style.display = index >= start && index < end ? "" : "none"
    })

    updatePaginationButtons()
  }

  function updatePaginationButtons() {
    paginationContainer.innerHTML = ""

    // Previous button
    const prevBtn = createPaginationButton("‹", currentPage > 1, () => {
      if (currentPage > 1) {
        currentPage--
        showPage(currentPage)
      }
    })
    paginationContainer.appendChild(prevBtn)

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
      const pageBtn = createPaginationButton(i, true, () => {
        currentPage = i
        showPage(currentPage)
      })

      if (i === currentPage) {
        pageBtn.style.backgroundColor = "#537D5D"
        pageBtn.style.color = "white"
      }

      paginationContainer.appendChild(pageBtn)
    }

    // Next button
    const nextBtn = createPaginationButton("›", currentPage < totalPages, () => {
      if (currentPage < totalPages) {
        currentPage++
        showPage(currentPage)
      }
    })
    paginationContainer.appendChild(nextBtn)
  }

  function createPaginationButton(text, enabled, onClick) {
    const button = document.createElement("button")
    button.textContent = text
    button.className = "pagination-btn"
    button.style.cssText = `
            margin: 0 2px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            cursor: ${enabled ? "pointer" : "not-allowed"};
            opacity: ${enabled ? "1" : "0.5"};
            border-radius: 4px;
        `

    if (enabled) {
      button.addEventListener("click", onClick)
    }

    return button
  }

  table.parentNode.appendChild(paginationContainer)
  showPage(1)
}

// Date picker initialization
function initializeDatePickers() {
  const dateInputs = document.querySelectorAll('input[type="date"]')
  dateInputs.forEach((input) => {
    // Set max date to today for birth dates
    if (input.name.includes("tgl_lhr")) {
      input.max = new Date().toISOString().split("T")[0]
    }

    // Set min date to today for appointment dates
    if (input.name.includes("tgl_kunjungan")) {
      input.min = new Date().toISOString().split("T")[0]
    }
  })
}

// Utility functions
function formatCurrency(amount) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
  }).format(amount)
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString("id-ID", {
    year: "numeric",
    month: "long",
    day: "numeric",
  })
}

function showNotification(message, type = "info") {
  const notification = document.createElement("div")
  notification.className = `notification notification-${type}`
  notification.textContent = message
  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        animation: slideInRight 0.3s ease;
    `

  const colors = {
    success: "#28a745",
    error: "#dc3545",
    warning: "#ffc107",
    info: "#17a2b8",
  }

  notification.style.backgroundColor = colors[type] || colors.info

  document.body.appendChild(notification)

  setTimeout(() => {
    notification.style.animation = "slideOutRight 0.3s ease"
    setTimeout(() => notification.remove(), 300)
  }, 3000)
}

// Add CSS for animations
const style = document.createElement("style")
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .tooltip {
        position: absolute;
        background: #333;
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 0.875rem;
        z-index: 1000;
        pointer-events: none;
    }
    
    .tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #333 transparent transparent transparent;
    }
`
document.head.appendChild(style)

// Export functions for global use
window.MedicalRecordSystem = {
  showNotification,
  formatCurrency,
  formatDate,
  validateForm,
  filterTable,
  sortTable,
}
