// Payroll App JavaScript - Intentionally vulnerable for penetration testing

// Global variables (exposed for testing)
window.currentUser = null;
window.apiEndpoint = '../api/';

// Initialize app
document.addEventListener('DOMContentLoaded', function () {
  initializeAnimations();
  loadUserData();
});

// Animation functions
function initializeAnimations() {
  // Smooth scroll animations
  const cards = document.querySelectorAll('.transform');

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate-fadeInUp');
      }
    });
  });

  cards.forEach(card => {
    observer.observe(card);
  });
}

// Load user data (vulnerable - exposes sensitive info)
function loadUserData() {
  if (window.currentUser && window.currentUser.user_id) {
    // Simulate loading user data with fetch (vulnerable to XSS)
    fetch(`../api/user_data.php?user_id=${window.currentUser.user_id}`)
      .then(response => response.json())
      .then(userData => {
        window.currentUser = { ...window.currentUser, ...userData };
        console.log('User data loaded:', userData); // Exposed in console
      })
      .catch(error => console.error('Error loading user data:', error));
  }
}

// Process payroll (vulnerable to CSRF)
function processPayroll(employeeId, amount, notes) {
  if (confirm(`Process payroll for employee ID: ${employeeId}?`)) {
    // Vulnerable fetch request without CSRF protection
    fetch('../api/process_payroll.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `employee_id=${employeeId}&amount=${amount}&notes=${encodeURIComponent(notes)}`
    })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          showNotification('Payroll processed successfully!', 'success');
          setTimeout(() => location.reload(), 1500);
        } else {
          showNotification('Error processing payroll: ' + data.message, 'error');
        }
      })
      .catch(error => {
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
      });
  }
}

// Edit employee (vulnerable to XSS)
function editEmployee(employeeId, name, position, salary, department) {
  // Vulnerable request without input validation
  fetch('../api/edit_employee.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `id=${employeeId}&name=${encodeURIComponent(name)}&position=${encodeURIComponent(position)}&salary=${salary}&department=${encodeURIComponent(department)}`
  })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        showNotification('Employee updated successfully!', 'success');
        setTimeout(() => location.reload(), 1500);
      } else {
        showNotification('Error updating employee: ' + data.message, 'error');
      }
    })
    .catch(error => {
      showNotification('Network error occurred', 'error');
      console.error('Error:', error);
    });
}

// Delete employee (vulnerable - no proper authorization check)
function deleteEmployee(employeeId) {
  if (confirm('Are you sure you want to delete this employee?')) {
    // Simple GET request for deletion (bad practice)
    window.location.href = `../api/delete_employee.php?id=${employeeId}`;
  }
}

// Notification system
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;

  // Set colors based on type
  switch (type) {
    case 'success':
      notification.classList.add('bg-green-500', 'text-white');
      break;
    case 'error':
      notification.classList.add('bg-red-500', 'text-white');
      break;
    default:
      notification.classList.add('bg-blue-500', 'text-white');
  }

  // Vulnerable: Direct HTML insertion without sanitization
  notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle mr-2"></i>
            <span>${message}</span>
        </div>
    `;

  document.body.appendChild(notification);

  // Animate in
  setTimeout(() => {
    notification.classList.remove('translate-x-full');
  }, 100);

  // Auto remove
  setTimeout(() => {
    notification.classList.add('translate-x-full');
    setTimeout(() => {
      if (document.body.contains(notification)) {
        document.body.removeChild(notification);
      }
    }, 300);
  }, 3000);
}

// Vulnerable search function (XSS prone)
function searchEmployees(query) {
  fetch(`../api/search.php?q=${encodeURIComponent(query)}`)
    .then(response => response.text())
    .then(data => {
      // Directly inserting search results without sanitization
      const resultsContainer = document.getElementById('searchResults');
      if (resultsContainer) {
        resultsContainer.innerHTML = data;
      }
    })
    .catch(error => console.error('Search error:', error));
}

// Vulnerable file upload function
function uploadFile(file, onSuccess, onError) {
  const formData = new FormData();
  formData.append('file', file);

  // No file type validation
  fetch('../api/upload.php', {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        showNotification('File uploaded successfully!', 'success');
        if (onSuccess) onSuccess(data);
      } else {
        showNotification('Upload failed: ' + data.message, 'error');
        if (onError) onError(data);
      }
    })
    .catch(error => {
      showNotification('Upload error occurred', 'error');
      if (onError) onError(error);
    });
}

// Exposed admin functions (should be protected)
window.adminFunctions = {
  deleteAllEmployees: function () {
    if (confirm('DELETE ALL EMPLOYEES? This cannot be undone!')) {
      window.location.href = '../api/admin_delete_all.php';
    }
  },

  exportData: function (table = 'all') {
    // Vulnerable data export without authorization
    window.open(`../api/export.php?table=${table}`, '_blank');
  },

  viewLogs: function () {
    // Direct access to logs
    window.open('../api/logs.php', '_blank');
  },

  bulkPayroll: function () {
    if (confirm('Process payroll for all active employees?')) {
      fetch('../api/bulk_payroll.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=process_all'
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            showNotification(`Processed ${data.count} payrolls successfully!`, 'success');
            setTimeout(() => location.reload(), 2000);
          } else {
            showNotification('Bulk payroll failed: ' + data.message, 'error');
          }
        })
        .catch(error => {
          showNotification('Network error occurred', 'error');
          console.error('Error:', error);
        });
    }
  }
};

// Debug functions (should not be in production)
window.debug = {
  showSessionData: function () {
    console.log('Session data:', window.currentUser);
  },

  showLocalStorage: function () {
    console.log('Local storage:', localStorage);
  },

  executeSQL: function (query) {
    // Extremely dangerous - direct SQL execution
    fetch('../api/debug_sql.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'query=' + encodeURIComponent(query)
    })
      .then(response => response.text())
      .then(data => {
        console.log('SQL Result:', data);
        try {
          const jsonData = JSON.parse(data);
          console.table(jsonData);
        } catch (e) {
          console.log('Raw response:', data);
        }
      })
      .catch(error => console.error('SQL Error:', error));
  },

  injectXSS: function (payload) {
    // Test XSS injection
    document.body.insertAdjacentHTML('beforeend', payload);
  }
};

// Form validation (intentionally weak)
function validateForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return true;

  // Minimal validation - easily bypassed
  const inputs = form.querySelectorAll('input[required]');
  for (let input of inputs) {
    if (!input.value.trim()) {
      showNotification(`${input.name} is required`, 'error');
      return false;
    }
  }
  return true;
}

// Vulnerable redirect function
function redirectTo(url, delay = 0) {
  setTimeout(() => {
    // No validation of URL - open redirect vulnerability
    window.location.href = url;
  }, delay);
}

// Auto-save functionality (vulnerable)
function autoSave(formId, endpoint) {
  const form = document.getElementById(formId);
  if (!form) return;

  const inputs = form.querySelectorAll('input, textarea, select');
  inputs.forEach(input => {
    input.addEventListener('change', function () {
      // Auto-save without user consent
      const formData = new FormData(form);
      fetch(endpoint, {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          console.log('Auto-saved:', data);
        })
        .catch(error => console.error('Auto-save error:', error));
    });
  });
}

// Initialize vulnerable features
window.addEventListener('load', function () {
  // Enable auto-save on forms (vulnerable)
  const forms = document.querySelectorAll('form[data-autosave]');
  forms.forEach(form => {
    const endpoint = form.dataset.autosave;
    autoSave(form.id, endpoint);
  });

  // Expose sensitive data in console
  if (window.currentUser) {
    console.log('🔓 Current user session:', window.currentUser);
    console.log('🔓 Available debug functions:', Object.keys(window.debug));
    console.log('🔓 Available admin functions:', Object.keys(window.adminFunctions));
  }
});

// Export functions for global access (bad practice)
window.payrollApp = {
  processPayroll,
  editEmployee,
  deleteEmployee,
  searchEmployees,
  uploadFile,
  showNotification,
  validateForm,
  redirectTo
};