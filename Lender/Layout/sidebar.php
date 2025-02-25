<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 bg-dark p-0">
      <div class="sidebar-wrapper d-flex flex-column flex-shrink-0 pt-3 text-white bg-dark vh-100">
        <!-- Sidebar Header -->
        <a href="/" class="sidebar-brand d-flex align-items-center mb-3 px-3 text-white text-decoration-none">
          <i class="bi bi-person-workspace fs-4 me-2"></i>
          <span class="fs-4 fw-semibold">Lender Menu</span>
        </a>
        
        <hr class="sidebar-divider opacity-50 mx-3">
        
        <!-- Navigation Items -->
        <ul class="nav nav-pills flex-column mb-auto px-2">
          <!-- Dashboard -->
          <li class="nav-item mb-2">
            <a href="index.php" class="nav-link text-white hover-item">
              <i class="bi bi-speedometer2 me-2"></i>
              <span>Dashboard</span>
            </a>
          </li>
          
          <!-- Loan Management Section -->
          <li class="sidebar-heading mt-2 mb-2 px-3">
            <span class="text-muted text-uppercase fs-7 fw-bold">Loan Management</span>
          </li>
          
          <li class="nav-item mb-2">
            <a href="LoanApplications.php" class="nav-link text-white hover-item">
              <i class="bi bi-file-earmark-text me-2"></i>
              <span>Loan Applications</span>
            </a>
          </li>
          
          <li class="nav-item mb-2">
            <a href="ApprovedLoans.php" class="nav-link text-white hover-item">
              <i class="bi bi-check-circle me-2"></i>
              <span>Active Loans</span>
            </a>
          </li>
        </ul>
        
        <hr class="sidebar-divider opacity-50 mx-3 mt-auto">
        
        <!-- User Profile Dropdown -->
        <div class="dropdown px-3 mb-3">
          <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
            id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="profile-image-wrapper me-2">
              <img src="../<?php echo $_SESSION['user_image']; ?>" 
                   alt="<?php echo $_SESSION['user_name']; ?>"
                   onerror="this.onerror=null; this.src='../uploads/users/default/download.png';" 
                   class="rounded-circle">
            </div>
            <strong><?php echo $_SESSION['user_name']; ?></strong>
          </a>
          <ul class="dropdown-menu dropdown-menu-dark shadow" aria-labelledby="dropdownUser1">
            <li>
              <a class="dropdown-item py-2" href="profile.php">
                <i class="bi bi-person-circle me-2"></i>Profile
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item py-2" href="logout.php">
                <i class="bi bi-box-arrow-right me-2"></i>Sign out
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>

<style>
.sidebar-wrapper {
  width: 280px;
  transition: all 0.3s ease;
  box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}

.hover-item {
  border-radius: 8px;
  transition: all 0.2s ease;
  margin: 0 0.5rem;
  padding: 0.75rem 1rem;
}

.hover-item:hover {
  background-color: rgba(255, 255, 255, 0.1) !important;
  transform: translateX(5px);
}

.sidebar-heading {
  font-size: 0.75rem;
  letter-spacing: 0.5px;
}

.fs-7 {
  font-size: 0.75rem;
}

.profile-image-wrapper {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.profile-image-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.dropdown-menu {
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  margin-top: 10px;
}

.dropdown-item {
  transition: all 0.2s ease;
  border-radius: 4px;
  margin: 2px 4px;
}

.dropdown-item:hover {
  background-color: rgba(255, 255, 255, 0.1);
  transform: translateX(5px);
}

.sidebar-divider {
  height: 0;
  border-top: 1px solid rgba(255, 255, 255, 0.15);
}

/* Active state styling */
.nav-link.active {
  background-color: rgba(255, 255, 255, 0.15) !important;
}

/* Icon styling */
.bi {
  font-size: 1.1rem;
}

/* Brand section hover effect */
.sidebar-brand:hover {
  opacity: 0.9;
}
</style>