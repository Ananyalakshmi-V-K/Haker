(function () {
  const apiBase = 'api';
  const habitTypes = ['gym', 'study', 'water'];
  const dailyTargets = { gym: 10, study: 12, water: 12 };
  let habitChart = null;
  let currentProfile = { name: '', email: '' };

  function clearSession() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('session_expires_at');
    localStorage.removeItem('user_email');
  }

  function getToken() {
    return localStorage.getItem('auth_token');
  }

  function requireSession() {
    const token = getToken();
    const expiresAt = Number(localStorage.getItem('session_expires_at') || '0');
    if (!token || Date.now() >= expiresAt) {
      clearSession();
      window.location.href = 'index.html';
      return false;
    }
    return true;
  }

  function setMessage(text, type) {
    const $message = $('#habitMessage');
    $message.removeClass('error success').addClass(type).text(text);
  }

  function setProfileMessage(text, type) {
    const $message = $('#profileMessage');
    $message.removeClass('error success').addClass(type).text(text);
  }

  function getInitials(name, email) {
    const cleanName = String(name || '').trim();
    if (cleanName) {
      const parts = cleanName.split(/\s+/).slice(0, 2);
      return parts.map(function (part) { return part[0]?.toUpperCase() || ''; }).join('') || 'U';
    }

    const cleanEmail = String(email || '').trim();
    return (cleanEmail[0] || 'U').toUpperCase();
  }

  function renderProfile(profile) {
    currentProfile = {
      name: String(profile.name || ''),
      email: String(profile.email || '')
    };

    $('#profileAvatarText').text(getInitials(currentProfile.name, currentProfile.email));
    $('#profileName').val(currentProfile.name || '-');
    $('#profileEmail').val(currentProfile.email || '-');
    $('#headerUserName').text(currentProfile.name || 'User');
    $('#headerUserEmail').text(currentProfile.email || 'email@example.com');
  }

  function buildLastNDates(days) {
    const labels = [];
    for (let index = days - 1; index >= 0; index -= 1) {
      const date = new Date();
      date.setDate(date.getDate() - index);
      labels.push(date.toISOString().split('T')[0]);
    }
    return labels;
  }

  function renderStreaks(streaks) {
    $('#streakGym').text(Number(streaks.gym || 0));
    $('#streakStudy').text(Number(streaks.study || 0));
    $('#streakWater').text(Number(streaks.water || 0));
  }

  function localDateString() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
  }

  function setRingProgress(selector, percent) {
    const safePercent = Math.max(0, Math.min(100, Number(percent || 0)));
    const angle = (safePercent / 100) * 360;
    $(selector).css('--progress-angle', angle.toFixed(2) + 'deg');
  }

  function renderDailyRingProgress(logs) {
    const today = localDateString();
    const todayValues = { gym: 0, study: 0, water: 0 };

    logs.forEach(function (entry) {
      if (entry.date === today && habitTypes.includes(entry.habit)) {
        todayValues[entry.habit] = Number(entry.value || 0);
      }
    });

    setRingProgress('#streakGym', (todayValues.gym / dailyTargets.gym) * 100);
    setRingProgress('#streakStudy', (todayValues.study / dailyTargets.study) * 100);
    setRingProgress('#streakWater', (todayValues.water / dailyTargets.water) * 100);
  }

  function setStreaksLoading() {
    ['#streakGym', '#streakStudy', '#streakWater'].forEach(function (selector) {
      $(selector).text('...').css('--progress-angle', '0deg');
    });
  }

  function setLogsLoading() {
    $('#logsTableBody').html('<tr><td colspan="3" class="text-muted">Loading logs...</td></tr>');
  }

  function renderLogsTable(logs) {
    if (!logs.length) {
      $('#logsTableBody').html('<tr><td colspan="3" class="text-muted">No logs found</td></tr>');
      return;
    }

    const rows = logs
      .slice()
      .sort(function (a, b) {
        if (a.date === b.date) {
          return a.habit.localeCompare(b.habit);
        }
        return a.date < b.date ? 1 : -1;
      })
      .map(function (entry) {
        return `<tr><td>${entry.date}</td><td class="text-capitalize">${entry.habit}</td><td>${entry.value}</td></tr>`;
      })
      .join('');

    $('#logsTableBody').html(rows);
  }

  function renderChart(logs) {
    const labels = buildLastNDates(14);
    const logMap = { gym: {}, study: {}, water: {} };

    logs.forEach(function (entry) {
      if (habitTypes.includes(entry.habit)) {
        logMap[entry.habit][entry.date] = Number(entry.value || 0);
      }
    });

    const datasets = [
      { label: 'Gym', habit: 'gym', borderColor: '#d4a017' },
      { label: 'Study', habit: 'study', borderColor: '#2fa39a' },
      { label: 'Water', habit: 'water', borderColor: '#f5f5f5' }
    ].map(function (series) {
      return {
        label: series.label,
        data: labels.map(function (label) {
          return logMap[series.habit][label] || 0;
        }),
        borderColor: series.borderColor,
        backgroundColor: series.borderColor,
        tension: 0.25,
        pointRadius: 2
      };
    });

    const context = document.getElementById('habitChart');
    if (!context) {
      return;
    }

    if (habitChart) {
      habitChart.destroy();
    }

    habitChart = new Chart(context, {
      type: 'line',
      data: { labels, datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        resizeDelay: 150,
        plugins: {
          legend: {
            labels: {
              color: '#f5f5f5'
            }
          }
        },
        scales: {
          x: {
            ticks: { color: '#b9b9b9' },
            grid: { color: '#2a2a2d' }
          },
          y: {
            beginAtZero: true,
            ticks: { color: '#b9b9b9' },
            grid: { color: '#2a2a2d' }
          }
        }
      }
    });
  }

  function loadSummary(refreshKey) {
    return $.ajax({
      url: apiBase + '/habits/summary.php?_t=' + encodeURIComponent(String(refreshKey || Date.now())),
      method: 'GET',
      cache: false,
      dataType: 'json',
      headers: { Authorization: 'Bearer ' + getToken() }
    }).done(function (response) {
      renderStreaks(response.data.streaks || {});
    });
  }

  function loadProfile() {
    return $.ajax({
      url: apiBase + '/profile/me.php?_t=' + encodeURIComponent(String(Date.now())),
      method: 'GET',
      cache: false,
      dataType: 'json',
      headers: { Authorization: 'Bearer ' + getToken() }
    }).done(function (response) {
      renderProfile((response.data && response.data.profile) || {});
    });
  }

  function loadLogs(refreshKey) {
    return $.ajax({
      url: apiBase + '/habits/logs.php?days=14&_t=' + encodeURIComponent(String(refreshKey || Date.now())),
      method: 'GET',
      cache: false,
      dataType: 'json',
      headers: { Authorization: 'Bearer ' + getToken() }
    }).done(function (response) {
      const logs = response.data.logs || [];
      renderLogsTable(logs);
      renderChart(logs);
      renderDailyRingProgress(logs);
    });
  }

  function refreshTracker(refreshKey) {
    setStreaksLoading();
    setLogsLoading();
    const key = refreshKey || Date.now();
    $.when(loadSummary(key), loadLogs(key)).fail(function () {
      renderStreaks({ gym: 0, study: 0, water: 0 });
      renderLogsTable([]);
      renderChart([]);
      setMessage('Dashboard data service is unavailable. Login is still active.', 'error');
    });
  }

  if (!requireSession()) {
    return;
  }

  $('#habitDate').val(new Date().toISOString().split('T')[0]);
  $.when(loadProfile()).fail(function () {
    clearSession();
    window.location.href = 'index.html';
  });
  refreshTracker(Date.now());

  $('#profileAvatarBtn').on('click', function () {
    $('#profileMenu').toggleClass('open');
  });

  $(document).on('click', function (event) {
    if (!$(event.target).closest('#profileMenu').length) {
      $('#profileMenu').removeClass('open');
    }
  });

  $('#menuDetailsBtn').on('click', function () {
    $('#profileDetailsCard').toggleClass('d-none');
    $('#profileMenu').removeClass('open');
  });

  $('#setPasswordBtn').on('click', function () {
    const $button = $('#setPasswordBtn');
    const password = String($('#newPassword').val() || '');

    if (password.length < 6) {
      setProfileMessage('Password must be at least 6 characters.', 'error');
      return;
    }

    $button.prop('disabled', true).text('Updating...');
    $.ajax({
      url: apiBase + '/profile/set-password.php',
      method: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      headers: { Authorization: 'Bearer ' + getToken() },
      data: JSON.stringify({ password: password })
    })
      .done(function () {
        $('#newPassword').val('');
        setProfileMessage('Password updated successfully.', 'success');
      })
      .fail(function (xhr) {
        const err = xhr.responseJSON?.error || 'Failed to update password.';
        setProfileMessage(err, 'error');
      })
      .always(function () {
        $button.prop('disabled', false).text('Set Password');
      });
  });

  $('#saveHabitBtn').on('click', function () {
    const $saveButton = $('#saveHabitBtn');
    const payload = {
      date: $('#habitDate').val(),
      habit: $('#habitType').val(),
      value: Number($('#habitValue').val() || 0)
    };

    if (!payload.date || !payload.habit) {
      setMessage('Date and habit are required.', 'error');
      return;
    }

    $saveButton.prop('disabled', true).text('Saving...');

    $.ajax({
      url: apiBase + '/habits/log.php',
      method: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      headers: { Authorization: 'Bearer ' + getToken() },
      data: JSON.stringify(payload)
    })
      .done(function () {
        setMessage('Habit log saved.', 'success');
        refreshTracker(Date.now());
      })
      .fail(function (xhr) {
        const err = xhr.responseJSON?.error || 'Failed to save habit log.';
        setMessage(err, 'error');
      })
      .always(function () {
        $saveButton.prop('disabled', false).text('Save Log');
      });
  });

  $('#menuLogoutBtn').on('click', function () {
    $.ajax({
      url: apiBase + '/auth/logout.php',
      method: 'POST',
      dataType: 'json',
      contentType: 'application/json',
      headers: { Authorization: 'Bearer ' + getToken() },
      data: JSON.stringify({})
    }).always(function () {
      clearSession();
      window.location.href = 'index.html';
    });
  });
})();
