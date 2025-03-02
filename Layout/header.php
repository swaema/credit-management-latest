<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>SafeFund Demo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap 5 CSS -->
  <link 
    rel="stylesheet" 
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
  />

  <!-- Bootstrap Icons (for eye icon) -->
  <link 
    rel="stylesheet" 
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
  />

  <!-- Tailwind CSS (optional if you need Tailwind utilities) -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/tailwindcss@3.2.7/dist/tailwind.min.css"
  />

  <!-- Include Bootstrap CSS (example CDN) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Include Bootstrap Icons (important for the eye icon!) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

</head>
<style>
    .space-y-2 {
      margin-top: 8px;
      margin-bottom: 8px;
    }

    .block {
      display: block;
    }

    .text-sm {
      font-size: 14px;
    }

    .font-medium {
      font-weight: 500;
    }

    .text-gray-700 {
      color: #374151;
    }

    .relative {
      position: relative;
    }

    .w-full {
      width: 100%;
    }

    .px-4 {
      padding-left: 16px;
      padding-right: 16px;
    }

    .py-2 {
      padding-top: 8px;
      padding-bottom: 8px;
    }

    .rounded-lg {
      border-radius: 8px;
    }

    .border {
      border-width: 1px;
    }

    .border-gray-200 {
      border-color: #e5e7eb;
    }

    .focus\:border-blue-500:focus {
      border-color: #3b82f6;
    }

    .focus\:ring-2:focus {
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }

    .absolute {
      position: absolute;
    }

    .right-3 {
      right: 12px;
    }

    .top-1\/2 {
      top: 50%;
    }

    .transform {
      transform: translateY(-50%);
    }

    .text-gray-600 {
      color: #6b7280;
    }

    .hover\:text-gray-800:hover {
      color: #374151;
    }

    /* Eye icon styles */
    .eye-icon {
      width: 20px;
      height: 20px;
      fill: currentColor;
    }
</style>
<body>

<!-- Main Header -->
<header class="main-menu shadow-sm">
  <div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between">
      <!-- Logo -->
      <a href="index.php" class="d-flex align-items-center text-dark text-decoration-none">
        <img 
          class="main-logo" 
          src="Assets/Images/logo.png" 
          alt="SafeFund Logo" 
          style="max-height: 40px;"
        >
      </a>

      <!-- Navigation Links -->
      <ul class="nav col-12 col-lg-auto mb-2 mb-lg-0 mx-lg-auto">
        <li class="nav-item mx-2">
          <a href="Borrow.php" class="nav-link px-2 custom-nav-link">Borrow</a>
        </li>
        <li class="nav-item mx-2">
          <a href="Lender.php" class="nav-link px-2 custom-nav-link">Lend</a>
        </li>
        <li class="nav-item mx-2">
          <a href="loanCalculator.php" class="nav-link px-2 custom-nav-link">Loan Calculator</a>
        </li>
        <li class="nav-item mx-2">
          <a href="login.php" class="nav-link px-2 custom-nav-link">Sign In</a>
        </li>
      </ul>

      <!-- Search Bar -->
      <form class="d-flex position-relative" style="max-width: 250px;">
          <input 
            type="search" 
            class="search-input" 
            placeholder="Search..."
          >
          <svg 
              class="search-icon" 
              viewBox="0 0 24 24" 
              width="16" 
              height="16"
              fill="none" 
              stroke="currentColor"
          >
          <path 
            d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" 
            stroke-width="2" 
            stroke-linecap="round" 
            stroke-linejoin="round"
          />
        </svg>
      </form>
    </div>
  </div>
</header>

<!-- Signup Modal -->
<div 
  class="modal fade" 
  id="staticBackdrop" 
  data-bs-backdrop="static" 
  data-bs-keyboard="false" 
  tabindex="-1" 
  aria-labelledby="staticBackdropLabel" 
  aria-hidden="true"
>
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="staticBackdropLabel">Welcome to SafeFund</h5>
        <button 
          type="button" 
          class="btn-close" 
          data-bs-dismiss="modal" 
          aria-label="Close"
        ></button>
      </div>
      <div class="modal-body">
        <form 
          id="signupForm" 
          action="signup.php" 
          method="post" 
          enctype="multipart/form-data"
        >
          <!-- Form Fields -->
          <div class="row g-3">
            <!-- Full Name -->
            <div class="col-md-6">
              <label for="name" class="form-label">Full Name</label>
              <input 
                type="text" 
                class="form-control" 
                id="name" 
                name="name" 
                placeholder="Enter your full name"
                pattern="^[a-zA-Z\s]+$" 
                title="Name cannot contain numbers or special symbols." 
                required 
                autocomplete="off"
              >
            </div>

            <!-- Email Address -->
            <div class="col-md-6">
              <label for="email" class="form-label">Email Address</label>
              <input 
                type="email" 
                class="form-control" 
                id="email" 
                name="email" 
                placeholder="Enter your email" 
                required 
                autocomplete="off"
              >
            </div>

            <!-- Password -->
            <div class="col-md-6">
              <label for="user_pass" class="form-label">Password</label>
              <div class="input-group">
                <input
                  type="password"
                  class="form-control"
                  id="user_pass"
                  name="user_pass"
                  placeholder="Enter your password"
                  pattern="^(?=.*[A-Z])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,20}$"
                  title="Password must be 8-20 characters long, include at least one capital letter, and one special character."
                  required
                  autocomplete="off"
                >
                <button
                  type="button"
                  class="btn btn-outline-secondary"
                  id="pass_toggle_btn"
                  onclick="togglePassword()"
                >
                  <svg class="eye-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5C7 5 2.73 8.11 1 12.5C2.73 16.89 7 20 12 20C17 20 21.27 16.89 23 12.5C21.27 8.11 17 5 12 5ZM12 17.5C9.24 17.5 7 15.26 7 12.5C7 9.74 9.24 7.5 12 7.5C14.76 7.5 17 9.74 17 12.5C17 15.26 14.76 17.5 12 17.5ZM12 9.5C10.34 9.5 9 10.84 9 12.5C9 14.16 10.34 15.5 12 15.5C13.66 15.5 15 14.16 15 12.5C15 10.84 13.66 9.5 12 9.5Z" fill="currentColor"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Confirm Password -->
            <div class="col-md-6">
              <label for="confirmPassword" class="form-label">Confirm Password</label>
              <input 
                type="password" 
                class="form-control" 
                id="confirmPassword" 
                name="confirmPassword" 
                placeholder="Confirm your password" 
                required 
                autocomplete="off"
              >
            </div>

            <!-- Mobile Number -->
            <div class="col-md-6">
              <label for="mobile" class="form-label">Mobile Number</label>
              <input 
                type="text" 
                class="form-control" 
                id="mobile" 
                name="mobile" 
                placeholder="Enter your mobile number"
                pattern="\d{5,8}" 
                title="Mobile number should be 5 to 8 digits long." 
                required 
                autocomplete="off"
              >
            </div>

            <!-- Role Selection -->
            <div class="col-md-6">
              <label for="role" class="form-label">Sign Up As</label>
              <select 
                class="form-select" 
                id="role" 
                name="role" 
                required
              >
                <option value="borrower">Borrower</option>
                <option value="lender">Lender</option>
              </select>
            </div>

            <!-- Address -->
            <div class="col-12">
              <label for="address" class="form-label">Address</label>
              <textarea 
                class="form-control" 
                id="address" 
                name="address" 
                placeholder="Enter your address" 
                required 
                autocomplete="off"
                rows="3"
              ></textarea>
            </div>

            <!-- File Uploads -->
            <div class="col-md-6">
              <label for="profile_image" class="form-label">Profile Image</label>
              <input 
                type="file" 
                class="form-control" 
                id="profile_image" 
                name="profile_image"
                accept=".png,.jpg,.jpeg,.gif" 
                required
              >
            </div>

            <div class="col-md-6">
              <label for="nic" class="form-label">NIC (Front and Back)</label>
              <input 
                type="file" 
                class="form-control" 
                id="nic" 
                name="nic[]" 
                multiple 
                required
              >
            </div>

            <div class="col-md-6">
              <label for="utility_bills" class="form-label">Utility Bills (At least 2)</label>
              <input 
                type="file" 
                class="form-control" 
                id="utility_bills" 
                name="utility_bills[]" 
                multiple 
                required
              >
            </div>

            <div class="col-md-6">
              <label for="salary_statements" class="form-label">Salary Statements (Last 6 Months)</label>
              <input 
                type="file" 
                class="form-control" 
                id="salary_statements" 
                name="salary_statements[]" 
                multiple 
                required
              >
            </div>
          </div>

          <!-- Form Actions -->
          <div class="d-flex justify-content-between align-items-center mt-4">
            <button 
              type="submit" 
              name="SignUp" 
              class="btn btn-primary"
            >
              Sign Up
            </button>
            <a href="login.php" class="text-primary">Already Registered?</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS (including Popper) -->
<script 
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>

<script>
    function togglePassword() {
      const passwordInput = document.getElementById('user_pass');
      const toggleButton = document.getElementById('pass_toggle_btn');
      const eyeIcon = toggleButton.querySelector('svg');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = '<path d="M12 5C7 5 2.73 8.11 1 12.5C2.73 16.89 7 20 12 20C17 20 21.27 16.89 23 12.5C21.27 8.11 17 5 12 5ZM12 17.5C9.24 17.5 7 15.26 7 12.5C7 9.74 9.24 7.5 12 7.5C14.76 7.5 17 9.74 17 12.5C17 15.26 14.76 17.5 12 17.5ZM12 9.5C10.34 9.5 9 10.84 9 12.5C9 14.16 10.34 15.5 12 15.5C13.66 15.5 15 14.16 15 12.5C15 10.84 13.66 9.5 12 9.5Z" fill="currentColor"/>';
      } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = '<path d="M12 7c3.31 0 6 2.69 6 6 0 .46-.05.91-.15 1.34l2.11 2.11c.81-1.05 1.45-2.25 1.85-3.54C20.27 8.11 16 5 12 5c-1.04 0-2.05.17-3 .46v2.07c.95-.27 1.96-.43 3-.43zm-8.9-1.9l2.91 2.91C4.9 9.16 4.01 10.7 3.19 12.5c1.73 4.39 6 7.5 11 7.5.95 0 1.87-.13 2.75-.37l2.91 2.91 1.41-1.41L4.51 3.69 3.1 5.1zm5.95 5.95l1.41 1.41c.03.15.04.31.04.47 0 1.66-1.34 3-3 3-.16 0-.32-.01-.47-.04l-1.41-1.41c.4-.45.64-1.03.64-1.67 0-.64-.24-1.22-.64-1.67zm3.9-1.17l2.79 2.79c.14-.7.21-1.42.21-2.17 0-1.66-1.34-3-3-3-.75 0-1.47.07-2.17.21z" fill="currentColor"/>';
      }
    }
</script>

</body>
</html>
