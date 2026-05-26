<?php
// view/client/signup.php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . "/../../config/database.php";
include_once __DIR__ . "/../../config/app.php";

// ===== FIXED REDIRECTION LOGIC =====
if (isset($_SESSION['user_id'])) {
    // Define redirect paths
    $redirectMap = [
        'admin'          => BASE . '/view/admin/dashboard.php',
        'nutritionniste' => BASE . '/view/nutritionniste/dashboard.php',
        'usine'          => BASE . '/view/usine/dashboard.php',
        'livreur'        => BASE . '/view/livreur/dashboard.php',
        'client'         => BASE . '/view/client/home.php',
    ];
    
    $role = $_SESSION['role'] ?? 'client';
    $redirectTo = $redirectMap[$role] ?? BASE . '/view/client/home.php';
    
    // Check if we're NOT already on the target page
    $currentScript = basename($_SERVER['SCRIPT_NAME']);
    $targetScript = basename($redirectTo);
    
    // Add debugging
    error_log("Signup.php - Current: $currentScript, Target: $targetScript");
    error_log("Signup.php - Role: $role, Redirect to: $redirectTo");
    
    if ($currentScript !== $targetScript) {
        error_log("Signup.php - Redirecting to: $redirectTo");
        header("Location: " . $redirectTo);
        exit();
    } else {
        error_log("Signup.php - Already on target page, no redirect");
    }
}

// If not logged in, continue with signup form
// ... rest of your signup.php code ...

// Séparer les messages d'erreur par type
$signin_error = isset($_SESSION['signin_error']) ? $_SESSION['signin_error'] : null;
$signup_error = isset($_SESSION['signup_error']) ? $_SESSION['signup_error'] : null;
$signup_success = isset($_SESSION['signup_success']) ? $_SESSION['signup_success'] : null;

// Nettoyer les sessions après récupération
unset($_SESSION['signin_error']);
unset($_SESSION['signup_error']);
unset($_SESSION['signup_success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
  <title>Benna | Connexion & Inscription</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=EB+Garamond:wght@400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?= BASE ?>/view/client/assets/signup.css"/>

</head>
<body>


<!-- Flèche de retour -->
<a href="<?= BASE ?>/index.php" class="back-arrow">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 12H5M12 19l-7-7 7-7"/>
    </svg>
    <span>Retour à l'accueil</span>
</a> 

<!-- Particles background -->
<div class="hero-particles" aria-hidden="true">
  <div class="hparticle p1"></div>
  <div class="hparticle p2"></div>
  <div class="hparticle p3"></div>
  <div class="hparticle p4"></div>
  <div class="hparticle p5"></div>
  <div class="hparticle p6"></div>
</div>

<!-- Dark Mode Toggle -->
<div class="toggle-container">
  <div class="toggle-wrap">
    <input type="checkbox" id="darkmode-toggle" class="toggle-input">
    <label for="darkmode-toggle" class="toggle-track">
      <div class="toggle-thumb"></div>
    </label>
  </div>
</div>

<div class="wrapper">
  <div class="container" id="container">
    
    <!-- SIGN UP FORM -->
    <div class="form-container sign-up-container">
      <form method="POST" action="<?= BASE ?>/controller/auth_controller.php?action=register" id="signupForm">
        <h1>Create Account</h1>
        <div class="bar"></div>
        <div class="socials">
          <a href="#">G</a>
          <a href="#">f</a>
          <a href="#">in</a>
          <a href="#">𝕏</a>
        </div>
        <div class="or">or use your email for registration</div>
        
        <?php if ($signup_error): ?>
          <div class="message-box error"><?= htmlspecialchars($signup_error) ?></div>
        <?php endif; ?>
        <?php if ($signup_success): ?>
          <div class="message-box success"><?= htmlspecialchars($signup_success) ?></div>
        <?php endif; ?>
        
        <div class="field">
          <span>👤</span>
          <input type="text" id="signupName" name="user_name" placeholder="Full Name" required>
        </div>
        <div class="field">
          <span>📧</span>
          <input type="email" id="signupEmail" name="email" placeholder="Email Address" required>
        </div>
        <div class="field">
          <span>📞</span>
          <input type="text" name="telephone" placeholder="Phone Number">
        </div>
        <div class="field">
          <span>📍</span>
          <input type="text" name="adresse" placeholder="Delivery Address">
        </div>
        <div class="field">
          <input type="password" id="signupPassword" name="password" placeholder="Password (min 8 characters)" required>
          <span class="password-toggle" onclick="togglePassword('signupPassword', this)">👁️</span>
        </div>
        <div class="password-strength">
          <div class="strength-bar" id="strengthBar"></div>
        </div>
        <div class="strength-text" id="strengthText"></div>
        <div class="field">
          <span>✓</span>
          <input type="password" id="signupConfirmPassword" placeholder="Confirm Password" required>
        </div>
<button type="submit" class="btn-submit1" id="signupBtn" style="width:auto;min-width:200px;min-height:46px;height:auto;line-height:1.4;padding:12px 48px;overflow:visible;display:block;margin:8px auto 0;background:linear-gradient(145deg,var(--green),var(--green-dark));border:none;border-radius:100px;color:white;font-family:var(--font-display);font-size:0.8rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;box-shadow:0 4px 18px rgba(0,117,75,0.3);">Sign Up</button>      </form>
    </div>

    <!-- SIGN IN FORM -->
    <div class="form-container sign-in-container">
      <form method="POST" action="<?= BASE ?>/controller/auth_controller.php?action=login" id="signinForm">
        <h1>Sign In</h1>
        <div class="bar"></div>
        <div class="socials">
          <a href="#">G</a>
          <a href="#">f</a>
          <a href="#">in</a>
          <a href="#">𝕏</a>
        </div>
        <div class="or">or use your email password</div>
        
        <?php if ($signin_error): ?>
          <div class="message-box error"><?= htmlspecialchars($signin_error) ?></div>
        <?php endif; ?>
        
        <div class="field">
          <span>📧</span>
          <input type="email" id="signinEmail" name="email" placeholder="Email" required>
        </div>
        <div class="field">
          <input type="password" id="signinPassword" name="password" placeholder="Password" required>
          <span class="password-toggle" onclick="togglePassword('signinPassword', this)">👁️</span>
        </div>
        <a href="#" class="forgot" id="forgotPassword">Forgot your password?</a>
        <button type="submit" class="btn-submit" id="signinBtn">Sign In</button>
        
       
      </form>
    </div>

    <!-- OVERLAY -->
    <div class="overlay-container">
      <div class="overlay">
        <div class="overlay-panel overlay-left">
          <h1>Welcome Back!</h1>
          <p>Enter your personal details to use all of site features</p>
          <button class="btn-ghost" id="signInBtn">Sign In</button>
        </div>
        <div class="overlay-panel overlay-right">
          <h1>Hello Friend!</h1>
          <p>Register with your personal details to use all of site features</p>
          <button class="btn-ghost" id="signUpBtn" style="width: 100%;">Sign Up</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Dark Mode Toggle
  const toggleCheckbox = document.getElementById('darkmode-toggle');
  
  if (localStorage.getItem('darkMode') === 'enabled') {
    document.body.classList.add('dark');
    toggleCheckbox.checked = true;
  }
  
  toggleCheckbox.addEventListener('change', function() {
    if (this.checked) {
      document.body.classList.add('dark');
      localStorage.setItem('darkMode', 'enabled');
    } else {
      document.body.classList.remove('dark');
      localStorage.setItem('darkMode', 'disabled');
    }
  });

  // Toast notification function
  function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  }

  // Password strength checker
  function checkPasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    const bar = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    
    if (!bar) return;
    
    if (password.length === 0) {
      bar.style.width = '0%';
      text.textContent = '';
      return;
    }
    
    switch(strength) {
      case 1:
        bar.style.width = '25%';
        bar.style.background = 'var(--red)';
        text.textContent = 'Weak';
        break;
      case 2:
        bar.style.width = '50%';
        bar.style.background = 'var(--gold)';
        text.textContent = 'Medium';
        break;
      case 3:
        bar.style.width = '75%';
        bar.style.background = '#52b788';
        text.textContent = 'Good';
        break;
      case 4:
        bar.style.width = '100%';
        bar.style.background = 'var(--green)';
        text.textContent = 'Strong';
        break;
    }
  }

  // Toggle password visibility
  window.togglePassword = function(inputId, element) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
      input.type = 'text';
      element.textContent = '🙈';
    } else {
      input.type = 'password';
      element.textContent = '👁️';
    }
  }

  // Validate email format
  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  // Signup form handler
  const signupForm = document.getElementById('signupForm');
  const signupPassword = document.getElementById('signupPassword');
  const signupConfirm = document.getElementById('signupConfirmPassword');
  const signupBtn = document.getElementById('signupBtn');

  signupPassword.addEventListener('input', (e) => {
    checkPasswordStrength(e.target.value);
  });

  signupForm.addEventListener('submit', (e) => {
    const name = document.getElementById('signupName').value.trim();
    const email = document.getElementById('signupEmail').value.trim();
    const password = signupPassword.value;
    const confirm = signupConfirm.value;
    
    if (!name || !email || !password || !confirm) {
      e.preventDefault();
      showToast('Please fill in all fields', 'error');
      return false;
    }
    
    if (!isValidEmail(email)) {
      e.preventDefault();
      showToast('Please enter a valid email address', 'error');
      return false;
    }
    
    if (password.length < 8) {
      e.preventDefault();
      showToast('Password must be at least 8 characters', 'error');
      return false;
    }
    
    if (password !== confirm) {
      e.preventDefault();
      showToast('Passwords do not match', 'error');
      return false;
    }
    
    signupBtn.classList.add('loading');
    signupBtn.disabled = true;
    signupBtn.textContent = '';
    
    return true;
  });

  // Signin form handler
  const signinForm = document.getElementById('signinForm');
  const signinBtn = document.getElementById('signinBtn');

  signinForm.addEventListener('submit', (e) => {
    const email = document.getElementById('signinEmail').value.trim();
    const password = document.getElementById('signinPassword').value;
    
    if (!email || !password) {
      e.preventDefault();
      showToast('Please fill in all fields', 'error');
      return false;
    }
    
    if (!isValidEmail(email)) {
      e.preventDefault();
      showToast('Please enter a valid email address', 'error');
      return false;
    }
    
    signinBtn.classList.add('loading');
    signinBtn.disabled = true;
    signinBtn.textContent = '';
    
    return true;
  });

  // Forgot password handler
  document.getElementById('forgotPassword').addEventListener('click', (e) => {
    e.preventDefault();
    showToast('Password reset link sent to your email!', 'info');
  });

  // Card transition
  const container = document.getElementById('container');
  const signUpBtn = document.getElementById('signUpBtn');
  const signInBtn = document.getElementById('signInBtn');

  signUpBtn.addEventListener('click', () => {
    container.classList.add('active');
  });

  signInBtn.addEventListener('click', () => {
    container.classList.remove('active');
  });

  // Afficher automatiquement le bon panneau s'il y a une erreur
  <?php if ($signin_error): ?>
  container.classList.remove('active');
  <?php elseif ($signup_error || $signup_success): ?>
  container.classList.add('active');
  <?php endif; ?>

  // Social icons handler
  document.querySelectorAll('.socials a').forEach(icon => {
    icon.addEventListener('click', (e) => {
      e.preventDefault();
      const platform = icon.innerText;
      showToast(`🔗 ${platform} login coming soon!`, 'info');
    });
  });

  // Real-time validation for signup email
  const signupEmail = document.getElementById('signupEmail');
  signupEmail.addEventListener('blur', () => {
    if (signupEmail.value && !isValidEmail(signupEmail.value)) {
      signupEmail.classList.add('error');
      showToast('Please enter a valid email', 'error');
    } else {
      signupEmail.classList.remove('error');
    }
  });

  signupEmail.addEventListener('input', () => {
    signupEmail.classList.remove('error');
  });

  // Real-time password match validation
  signupConfirm.addEventListener('input', () => {
    if (signupConfirm.value && signupConfirm.value !== signupPassword.value) {
      signupConfirm.classList.add('error');
    } else {
      signupConfirm.classList.remove('error');
    }
  });

  // Save form data to localStorage (auto-save)
  const signupFields = ['signupName', 'signupEmail'];
  signupFields.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    const saved = localStorage.getItem(fieldId);
    if (saved) field.value = saved;
    
    field.addEventListener('input', () => {
      localStorage.setItem(fieldId, field.value);
    });
  });

  // Load animation
  window.addEventListener('load', () => {
    const wrapper = document.querySelector('.wrapper');
    wrapper.style.opacity = '0';
    wrapper.style.transform = 'translateY(30px)';
    setTimeout(() => {
      wrapper.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
      wrapper.style.opacity = '1';
      wrapper.style.transform = 'translateY(0)';
    }, 100);
  });
</script>

</body>
</html>