<?php
require_once 'Classes/User.php';
require_once 'Classes/Database.php';
require_once 'Classes/Mail.php';
require_once 'Classes/otp.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$email = $_GET['email'] ?? $_POST['email'] ?? '';
$message = $_GET['s'] ?? '';
$expiry = OTPHandler::getOTPExpiryTime($email);

if ($expiry === null) {
    echo "OTP not found for this email.";
} else {
    $remaining = $expiry - time();
    
    if ($remaining <= 0) {
        $expiry = 0;
    } else {
       $expiry = $remaining;
    }
}


if (isset($_POST['verifyOTP'])) {
    $submitted_otp = implode('', $_POST['otp']);
    
    if (!isset($_SESSION['otp_cache'][$email])) {
        header("Location: otp.php?e=No OTP found for this email&email=$email");
        exit();
    }
    
    $created_at = $_SESSION['otp_cache'][$email]['created_at'];
    $expiry_time = 5 * 60;
    
    if ((time() - $created_at) > $expiry_time) {
        unset($_SESSION['otp_cache'][$email]);
        header("Location: otp.php?e=OTP has expired. Please request a new one&email=$email");
        exit();
    }
    
    $stored_otp = OTPHandler::getStoredOTP($email);
    
    if ($submitted_otp === $stored_otp) {
        OTPHandler::removeOTPFromSession($email);
        User::changeStatus($email);
        header("Location: login.php?s=Accout verified successfully.");
        exit();
    } else {
        $error = "Invalid OTP. Please try again";
    }
}

if (isset($_GET['resendOTP'])) {
    $otp = OTPHandler::generateOTP();
    if (OTPHandler::storeOTPInSession($email, $otp)) {
        sendOTPEmail($email, $otp);
    }
}

function sendOTPEmail($email, $otp)
{
    $check = Mail::SendOtp($email, $otp);
    if ($check == 1) {
        header("Location: otp.php?s=OTP Sent Successfully&email=" . urlencode($email));
    }
}

include_once('Layout/head.php');
include_once('Layout/header.php');
?>

<div class="container min-vh-100 d-flex align-items-center">
    <div class="row justify-content-center w-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5 otp-form bg-white p-4 rounded-3 shadow-lg" style="animation: formEntrance 0.5s ease-out">
            <form method="post" action="">
                <div class="text-center mb-4">
                    <h2 class="fw-bold mb-3 text-gradient">Email Verification</h2>
                    <p class="text-muted">
                        We've sent an OTP code to <br>
                        <span class="fw-bold text-primary"><?= htmlspecialchars($email) ?></span>
                    </p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger shake"><?= $error ?></div>
                <?php endif; ?>

                <div class="mb-4">
                    <div class="row g-2 justify-content-center">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="col-4 col-sm-3 col-md-2">
                                <input type="text"
                                       inputmode="numeric"
                                       pattern="[0-9]*"
                                       class="form-control form-control-lg text-center otp-input"
                                       name="otp[]"
                                       maxlength="1"
                                       required
                                       <?= $i > 0 ? 'disabled' : '' ?>
                                       data-index="<?= $i ?>">
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

                <div class="d-grid mb-3">
                    <button type="submit" name="verifyOTP" class="btn btn-primary btn-lg btn-hover">
                        Verify
                        <span class="btn-animate"></span>
                    </button>
                </div>
            </form>
            <form action="" method="get">
                <!-- Added hidden email input for the resend form -->
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <div class="text-center">
                    <p class="text-muted mb-0" id="timerText">OTP expires in <span id="countdown" class="fw-bold"></span></p>
                    <p class="mb-0 d-none text-danger fw-bold" id="expiredText">OTP expired</p>
                    <button type="submit" name="resendOTP" class="btn btn-link text-decoration-none d-none resend-btn" id="resendBtn">
                        Resend Code
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.otp-form {
    max-width: 500px;
    margin: 0 auto;
    border: 1px solid rgba(0,0,0,0.1);
}

@keyframes formEntrance {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes iconFloat {
    0% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0); }
}

.text-gradient {
    background: linear-gradient(45deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.otp-input {
    font-size: 1.5rem;
    height: 3.5rem;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: #f8f9fa;
    color: #2d3748;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.otp-input:focus {
    border-color: #667eea;
    background: #ffffff;
    box-shadow: 0 4px 6px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
    outline: none;
}

.otp-input:hover {
    border-color: #a3bffa;
}

.otp-input:disabled {
    background: #edf2f7;
    opacity: 0.7;
}

#countdown {
    color: #667eea;
    transition: color 0.3s ease;
}

.timer-pulse {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.shake {
    animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
}

@keyframes shake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px); }
    30%, 50%, 70% { transform: translateX(-3px); }
    40%, 60% { transform: translateX(3px); }
}

.btn-hover {
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.btn-animate {
    position: absolute;
    background: rgba(255,255,255,0.5);
    width: 50px;
    height: 100%;
    left: -75px;
    top: 0;
    transform: skewX(-45deg);
    transition: left 0.5s ease;
}

.btn-hover:hover .btn-animate {
    left: 150%;
}

.resend-btn {
    transition: all 0.3s ease;
}

.resend-btn:hover {
    transform: translateY(-1px);
    opacity: 0.9;
}

/* Hide number input arrows */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type=number] {
    -moz-appearance: textfield;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const inputs = document.querySelectorAll('.otp-input');
    const timerElement = document.getElementById('countdown');
    const expiredText = document.getElementById('expiredText');
    const timerText = document.getElementById('timerText');
    const resendBtn = document.getElementById('resendBtn');
    let timeLeft = <?php echo $expiry ?>;

    // OTP input handling
    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            // Allow only numbers
            e.target.value = e.target.value.replace(/\D/g,'');
            
            if (e.target.value.length === 1) {
                if (index < inputs.length - 1) {
                    inputs[index + 1].removeAttribute('disabled');
                    inputs[index + 1].focus();
                }
            }
            
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && e.target.value === '') {
                if (index > 0) {
                    inputs[index - 1].value = '';
                    inputs[index - 1].focus();
                }
            }
        });
    });

    // Timer functionality
    const updateTimer = () => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

        if (timeLeft <= 60) {
            timerElement.style.color = '#e53e3e';
            timerElement.classList.add('timer-pulse');
        } else if (timeLeft <= 150) {
            timerElement.style.color = '#dd6b20';
        }

        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            inputs.forEach(input => input.disabled = true);
            timerText.classList.add('d-none');
            expiredText.classList.remove('d-none');
            resendBtn.classList.remove('d-none');
        } else {
            timeLeft--;
        }
    };

    let timerInterval = setInterval(updateTimer, 1000);

    // Add shake animation on error
    <?php if ($error): ?>
        setTimeout(() => {
            document.querySelector('.alert-danger').classList.add('shake');
            inputs.forEach(input => {
                input.classList.add('shake');
                setTimeout(() => {
                    input.classList.remove('shake');
                    input.value = '';
                }, 400);
            });
            inputs[0].focus();
            inputs[0].removeAttribute('disabled');
        }, 100);
    <?php endif; ?>
});
</script>

<?php include_once('Layout/footer.php'); ?>
