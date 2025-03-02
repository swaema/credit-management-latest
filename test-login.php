<?php
// Minimal test file: No sessions, no includes, just a form and the toggle script.

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Test Login</title>
  <!-- Bootstrap CSS -->
  <link 
    rel="stylesheet" 
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
  />
  
  <!-- Bootstrap Icons -->
  <link 
    rel="stylesheet" 
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
  />

  <style>
    /* Force the icon to be positioned and clickable */
    #password-toggle {
      position: absolute !important;
      right: 10px !important;
      top: 50% !important;
      transform: translateY(-50%) !important;
      cursor: pointer !important;
    }
    /* Provide space for the icon in the input */
    #test-password {
      padding-right: 2.5rem !important;
    }
  </style>
</head>
<body>

<div class="container" style="margin-top:50px;">
  <div class="row justify-content-center">
    <div class="col-md-4">
      
      <h4 class="text-center mb-4">Test Login</h4>
      
      <form>
        <!-- Email -->
        <div class="mb-3">
          <label for="test-email" class="form-label">Email address</label>
          <input 
            type="email" 
            class="form-control" 
            id="test-email" 
            placeholder="Enter your email"
          >
        </div>
        
        <!-- Password + Eye Icon -->
        <div class="mb-3 position-relative">
          <label for="test-password" class="form-label">Password</label>
          <input 
            type="password" 
            class="form-control" 
            id="test-password" 
            placeholder="Enter your password"
          >
          <i 
            class="bi bi-eye-fill position-absolute"
            id="password-toggle"
          ></i>
        </div>
        
        <!-- Submit button (dummy) -->
        <button type="submit" class="btn btn-primary w-100">
          Login
        </button>
      </form>
      
    </div>
  </div>
</div>

<!-- Bootstrap JS bundle -->
<script 
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js">
</script>

<script>
// This must run AFTER the elements exist.
document.addEventListener('DOMContentLoaded', function() {
  const passwordInput = document.getElementById('test-password');
  const toggleIcon    = document.getElementById('password-toggle');
  
  if (!passwordInput || !toggleIcon) {
    console.error('Could not find the password or toggle icon element.');
    return;
  }

  toggleIcon.addEventListener('click', function() {
    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
      // Switch icon classes
      toggleIcon.classList.remove('bi-eye-fill');
      toggleIcon.classList.add('bi-eye-slash-fill');
    } else {
      passwordInput.type = 'password';
      // Switch back
      toggleIcon.classList.remove('bi-eye-slash-fill');
      toggleIcon.classList.add('bi-eye-fill');
    }
  });
});
</script>

</body>
</html>
