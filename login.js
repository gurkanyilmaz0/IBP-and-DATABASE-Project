// ============================================================
// JS DOĞRULAMA (Client-side validation)
// ============================================================
document.getElementById('loginForm').addEventListener('submit', function(e) {
  let valid = true;

  const email    = document.getElementById('email');
  const password = document.getElementById('password');
  const emailErr = document.getElementById('emailErr');
  const passErr  = document.getElementById('passErr');

  // E-posta doğrulama
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email.value.trim())) {
    email.classList.add('input-error');
    emailErr.classList.add('visible');
    valid = false;
  } else {
    email.classList.remove('input-error');
    emailErr.classList.remove('visible');
  }

  // Şifre doğrulama (min 6 karakter)
  if (password.value.length < 6) {
    password.classList.add('input-error');
    passErr.classList.add('visible');
    valid = false;
  } else {
    password.classList.remove('input-error');
    passErr.classList.remove('visible');
  }

  if (!valid) e.preventDefault();
});

// Canlı temizleme
['email','password'].forEach(function(id) {
  document.getElementById(id).addEventListener('input', function() {
    this.classList.remove('input-error');
    document.getElementById(id === 'email' ? 'emailErr' : 'passErr')
            .classList.remove('visible');
  });
});
