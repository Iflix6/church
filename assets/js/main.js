document.addEventListener("DOMContentLoaded", () => {
  // Mobile menu toggle
  const menuToggle = document.createElement("button")
  menuToggle.className = "menu-toggle"
  menuToggle.innerHTML = "☰ Menu"
  menuToggle.style.display = "none"

  const nav = document.querySelector("nav")

  if (nav) {
    // Only add mobile menu functionality if nav exists
    const header = document.querySelector("header")
    header.insertBefore(menuToggle, nav)

    menuToggle.addEventListener("click", () => {
      nav.classList.toggle("active")
    })

    // Check screen size and adjust menu
    function checkScreenSize() {
      if (window.innerWidth <= 768) {
        menuToggle.style.display = "block"
        nav.classList.remove("active")
      } else {
        menuToggle.style.display = "none"
        nav.classList.remove("active")
      }
    }

    // Initial check
    checkScreenSize()

    // Listen for window resize
    window.addEventListener("resize", checkScreenSize)
  }

  // Add active class to current page in navigation
  const currentPage = window.location.pathname
  const navLinks = document.querySelectorAll("nav a")

  navLinks.forEach((link) => {
    if (link.getAttribute("href") === currentPage) {
      link.classList.add("active")
    }
  })

  // Form validation
  const forms = document.querySelectorAll("form")

  forms.forEach((form) => {
    form.addEventListener("submit", (event) => {
      let valid = true
      const requiredInputs = form.querySelectorAll("[required]")

      requiredInputs.forEach((input) => {
        if (!input.value.trim()) {
          valid = false
          input.classList.add("error")
        } else {
          input.classList.remove("error")
        }
      })

      if (!valid) {
        event.preventDefault()
        alert("Please fill in all required fields.")
      }
    })
  })

  // Auto-hide success messages after 5 seconds
  const successMessages = document.querySelectorAll(".success-message")

  successMessages.forEach((message) => {
    setTimeout(() => {
      message.style.opacity = "0"
      setTimeout(() => {
        message.style.display = "none"
      }, 500)
    }, 5000)
  })
})
