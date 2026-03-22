(function () {
  const apiBase = 'api';
  const $authShell = $('#authShell');

  function setMessage(text, type) {
    const $message = $('#message');
    $message.removeClass('error success').addClass(type).text(text);
  }

  function showRegisterMode() {
    $authShell.removeClass('is-login').addClass('is-register');
    setMessage('', '');
  }

  function showLoginMode() {
    $authShell.removeClass('is-register').addClass('is-login');
    setMessage('', '');
  }

  function saveSession(payload) {
    localStorage.setItem('auth_token', payload.token);
    localStorage.setItem('session_expires_at', String(payload.expires_at));
    localStorage.setItem('user_email', payload.user.email);
  }

  function hasValidLocalSession() {
    const token = localStorage.getItem('auth_token');
    const expiresAt = Number(localStorage.getItem('session_expires_at') || '0');
    return Boolean(token) && Date.now() < expiresAt;
  }

  if (hasValidLocalSession()) {
    window.location.href = 'dashboard.html';
  }

  $('#showLoginBtn').on('click', function () {
    showLoginMode();
  });

  $('#showRegisterBtn').on('click', function () {
    showRegisterMode();
  });

  $('#registerBtn').on('click', function () {
    const fullName = $('#registerName').val().trim();
    const email = $('#registerEmail').val().trim();
    const password = $('#registerPassword').val();

    if (!fullName || !email || !password) {
      setMessage('Please enter name, email, and password.', 'error');
      return;
    }

    if (password.length < 6) {
      setMessage('Password must be at least 6 characters.', 'error');
      return;
    }

    $.ajax({
      url: apiBase + '/auth/register.php',
      method: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({ full_name: fullName, email, password })
    })
      .done(function () {
        setMessage('Registration successful. Please login.', 'success');
        $('#loginEmail').val(email);
        $('#loginPassword').val('');
        showLoginMode();
      })
      .fail(function (xhr) {
        const err = xhr.responseJSON?.error || 'Registration failed. Try again.';
        setMessage(err, 'error');
      });
  });

  $('#loginBtn').on('click', function () {
    const email = $('#loginEmail').val().trim();
    const password = $('#loginPassword').val();

    if (!email || !password) {
      setMessage('Please enter both email and password.', 'error');
      return;
    }

    $.ajax({
      url: apiBase + '/auth/login.php',
      method: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({ email, password })
    })
      .done(function (response) {
        saveSession(response.data);
        setMessage('Login successful. Redirecting...', 'success');
        setTimeout(function () {
          window.location.href = 'dashboard.html';
        }, 500);
      })
      .fail(function (xhr) {
        const err = xhr.responseJSON?.error || 'Login failed. Try again.';
        setMessage(err, 'error');
      });
  });
})();
