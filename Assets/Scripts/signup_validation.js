
document.getElementById('signupForm').addEventListener('submit', function (event) {
    const name = document.getElementById('name').value;
    const mobile = document.getElementById('mobile').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    // Name validation
    const nameRegex = /^[a-zA-Z\s]+$/;
    if (!nameRegex.test(name)) {
        toastr.options = {
            "progressBar": true,
            "closeButton": true,
        }
        toastr.error("Name cannot contain numbers or special symbols.");
        event.preventDefault();
        return;
    }

    // Mobile number validation
    const mobileRegex = /^\d{5,8}$/;
    if (!mobileRegex.test(mobile)) {
        toastr.options = {
            "progressBar": true,
            "closeButton": true,
            // Other toastr options you want to add (if any)
        };
        toastr.error("Mobile number should be 5 to 8 digits long.");
        event.preventDefault(); // Prevent form submission if mobile number is invalid
        return;
    }

    // Password validation
    const passwordRegex = /^(?=.*[A-Z])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,20}$/;
    if (!passwordRegex.test(password)) {
        toastr.options = {
            "progressBar": true,
            "closeButton": true,
        }
        toastr.error("Password must be 8-20 characters long, include at least one capital letter, and one special character.");
        // alert("");
        event.preventDefault();
        return;
    }

    // Confirm password validation
    if (password !== confirmPassword) {
        toastr.options = {
            "progressBar": true,
            "closeButton": true,
        }
        toastr.error("Passwords do not match.");
        event.preventDefault();
        return;
    }
});
