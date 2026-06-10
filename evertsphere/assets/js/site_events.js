// assets/js/site_events.js
// Site-wide event handlers and register form live validation
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    // Simple toast helper
    function showToast(msg, timeout) {
      timeout = timeout || 3000;
      var t = document.createElement('div');
      t.className = 'fixed bottom-6 right-6 bg-coffee-800 text-coffee-50 px-4 py-2 rounded shadow-lg z-50';
      t.style.transition = 'opacity 240ms';
      t.textContent = msg;
      document.body.appendChild(t);
      setTimeout(function () { t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 260); }, timeout);
    }

    window.showToast = showToast;

    // Global confirm handler for elements with data-confirm="Message"
    document.addEventListener('click', function (e) {
      var el = e.target.closest && e.target.closest('[data-confirm]');
      if (!el) return;
      var msg = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!confirm(msg)) {
        e.preventDefault();
        e.stopPropagation();
      }
    }, true);

    // Register form live validation
    var registerForm = document.getElementById('registerForm');
    if (registerForm) {
      var fields = {
        name: { el: document.getElementById('name'), validate: function (v) {
          if (!v) return 'Name is required';
          if (v.length < 2) return 'Name must be at least 2 characters';
          if (v.length > 100) return 'Name must not exceed 100 characters';
          return null;
        } },
        email: { el: document.getElementById('email'), validate: function (v) {
          if (!v) return 'Email is required';
          var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!re.test(v)) return 'Enter a valid email address';
          return null;
        } },
        phone: { el: document.getElementById('phone'), validate: function (v) {
          if (!v) return 'Phone number is required';
          var re = /^[0-9+\-\s()]+$/;
          if (!re.test(v)) return 'Phone number format is invalid';
          return null;
        } },
        city: { el: document.getElementById('city'), validate: function (v) {
          if (!v) return 'City is required';
          if (v.length > 50) return 'City must not exceed 50 characters';
          return null;
        } },
        password: { el: document.getElementById('password'), validate: function (v) {
          if (!v) return 'Password is required';
          if (v.length < 6) return 'Password must be at least 6 characters';
          return null;
        } },
        password_confirm: { el: document.getElementById('password_confirm'), validate: function (v) {
          var p = document.getElementById('password');
          if (!v) return 'Confirm your password';
          if (p && v !== p.value) return 'Passwords do not match';
          return null;
        } },
        role: { els: registerForm.querySelectorAll('input[name="role"]'), validate: function () {
          var checked = registerForm.querySelector('input[name="role"]:checked');
          if (!checked) return 'Select a role';
          return null;
        } }
        ,
        terms: { el: document.getElementById('terms'), validate: function () {
          var el = document.getElementById('terms');
          if (!el || !el.checked) return 'You must agree to the Terms & Conditions';
          return null;
        } }
      };

      function showFieldError(name, message) {
        var id = 'error-' + name;
        var el = document.getElementById(id);
        if (!el) return;
        if (message) {
          el.textContent = message;
          el.classList.remove('hidden');
          var field = fields[name];
          if (field && field.el) field.el.setAttribute('aria-invalid', 'true');
          if (field && field.els) field.els.forEach(function (r) { r.setAttribute('aria-invalid', 'true'); });
        } else {
          el.textContent = '';
          el.classList.add('hidden');
          var field = fields[name];
          if (field && field.el) field.el.removeAttribute('aria-invalid');
          if (field && field.els) field.els.forEach(function (r) { r.removeAttribute('aria-invalid'); });
        }
      }

      // Attach live input handlers
      Object.keys(fields).forEach(function (name) {
        var f = fields[name];
        if (f.el) {
          f.el.addEventListener('input', function () {
            var v = (f.el.value || '').trim();
            var err = f.validate(v);
            showFieldError(name, err);
          });
        } else if (f.els) {
          f.els.forEach(function (r) {
            r.addEventListener('change', function () {
              var err = f.validate();
              showFieldError(name, err);
            });
          });
        }
      });

      registerForm.addEventListener('submit', function (e) {
        var firstInvalid = null;
        Object.keys(fields).forEach(function (name) {
          var f = fields[name];
          var val = f.el ? (f.el.value || '').trim() : undefined;
          var err = f.el ? f.validate(val) : f.validate();
          showFieldError(name, err);
          if (err && !firstInvalid) {
            firstInvalid = f.el || (f.els && f.els[0]);
          }
        });
        if (firstInvalid) {
          e.preventDefault();
          try { firstInvalid.focus(); } catch (ex) {}
          showToast('Please correct the highlighted fields');
        }
      });
    }

    // Login form client validation and AJAX sign-in
    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
      var lEmail = document.getElementById('email');
      var lPassword = document.getElementById('password');
      var lErrorEmail = document.getElementById('error-email');
      var lErrorPass = document.getElementById('error-password');

      function setLoginError(el, msg) {
        if (!el) return;
        if (msg) {
          el.textContent = msg;
          el.classList.remove('hidden');
          el.setAttribute('aria-invalid', 'true');
        } else {
          el.textContent = '';
          el.classList.add('hidden');
          el.removeAttribute('aria-invalid');
        }
      }

      if (lEmail) lEmail.addEventListener('input', function () {
        var v = (lEmail.value || '').trim();
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        setLoginError(lErrorEmail, !v ? 'Email is required' : (!re.test(v) ? 'Enter a valid email address' : null));
      });

      if (lPassword) lPassword.addEventListener('input', function () {
        var v = (lPassword.value || '').trim();
        setLoginError(lErrorPass, !v ? 'Password is required' : (v.length < 6 ? 'Password must be at least 6 characters' : null));
      });

      loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var vEmail = (lEmail && lEmail.value || '').trim();
        var vPass = (lPassword && lPassword.value || '').trim();
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        var errEmail = !vEmail ? 'Email is required' : (!re.test(vEmail) ? 'Enter a valid email address' : null);
        var errPass = !vPass ? 'Password is required' : (vPass.length < 6 ? 'Password must be at least 6 characters' : null);
        setLoginError(lErrorEmail, errEmail);
        setLoginError(lErrorPass, errPass);
        if (errEmail || errPass) {
          try { (errEmail ? lEmail : lPassword).focus(); } catch (ex) {}
          showToast('Please correct the highlighted fields');
          return;
        }

        // AJAX submit
        var btn = document.getElementById('submit-btn');
        var loader = document.getElementById('btn-loader');
        var btnText = document.getElementById('btn-text');
        if (loader) loader.classList.remove('hidden');
        if (btnText) btnText.textContent = 'Signing in...';

        var formData = new FormData(loginForm);
        formData.append('ajax', '1');

        fetch(window.location.pathname, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (loader) loader.classList.add('hidden');
            if (btnText) btnText.textContent = 'Sign In';
            if (data && data.success) {
              showToast('Signed in successfully');
              window.location = data.redirect || '/';
            } else {
              showToast((data && data.message) ? data.message : 'Sign in failed');
            }
          }).catch(function (err) {
            if (loader) loader.classList.add('hidden');
            if (btnText) btnText.textContent = 'Sign In';
            console.error('Login AJAX error', err);
            showToast('Network error while signing in');
          });
      });
    }

    // Verify form helper (OTP length guidance)
    var verifyForm = document.getElementById('verifyForm');
    if (verifyForm) {
      var otp = document.getElementById('otp');
      var otpErr = document.getElementById('error-otp');
      if (otp) {
        otp.addEventListener('input', function () {
          if (otp.value.trim().length === 6) {
            if (otpErr) { otpErr.classList.add('hidden'); otpErr.textContent = ''; }
          } else {
            if (otpErr) { otpErr.classList.remove('hidden'); otpErr.textContent = 'Enter the 6-digit code'; }
          }
        });
      }
    }

    // Terms & Conditions modal handlers
    var termsLink = document.getElementById('terms-link');
    var termsModal = document.getElementById('terms-modal');
    var termsOverlay = document.getElementById('terms-modal-overlay');
    var termsClose = document.getElementById('terms-close');

    function openTerms() {
      if (!termsModal) return;
      termsModal.classList.remove('hidden');
      termsModal.classList.add('flex');
      if (termsClose) termsClose.focus();
    }

    function closeTerms() {
      if (!termsModal) return;
      termsModal.classList.add('hidden');
      termsModal.classList.remove('flex');
    }

    if (termsLink) {
      termsLink.addEventListener('click', function (e) {
        e.preventDefault();
        openTerms();
      });
    }
    if (termsOverlay) termsOverlay.addEventListener('click', closeTerms);
    if (termsClose) termsClose.addEventListener('click', closeTerms);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeTerms(); });

    // Removed page entrance animation to keep pages' appearance simple

    // Mobile menu toggle for small screens
    (function () {
      var mobileBtn = document.getElementById('mobile-menu-btn');
      var mainNav = document.querySelector('header nav');
      if (!mobileBtn || !mainNav) return;
      mobileBtn.addEventListener('click', function (e) {
        e.preventDefault();
        mainNav.classList.toggle('hidden');
      });
      // Ensure nav becomes visible on resize to larger screens
      window.addEventListener('resize', function () {
        if (window.innerWidth >= 768) mainNav.classList.remove('hidden');
      });
    })();

    // Generic form validator for pages that do not use the register/login specific handlers
    document.querySelectorAll('form').forEach(function (form) {
      if (['registerForm', 'loginForm', 'verifyForm'].indexOf(form.id) !== -1) return;
      form.addEventListener('submit', function (e) {
        var inputs = form.querySelectorAll('input,textarea,select');
        var firstInvalid = null;
        var hasError = false;
        inputs.forEach(function (inp) {
          var val = (inp.value || '').trim();
          var req = inp.hasAttribute('required');
          var type = (inp.getAttribute('type') || '').toLowerCase();
          var err = null;
          if (req && !val) err = 'This field is required';
          else if (type === 'email' && val) { var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; if (!re.test(val)) err = 'Enter a valid email address'; }
          else if (inp.hasAttribute('minlength') && val.length > 0) { var min = parseInt(inp.getAttribute('minlength'), 10); if (val.length < min) err = 'Minimum ' + min + ' characters'; }
          if (err) {
            hasError = true;
            if (!firstInvalid) firstInvalid = inp;
            inp.classList.add('invalid');
            var eid = inp.getAttribute('data-error-id') || ('error-' + (inp.id || inp.name));
            var errEl = document.getElementById(eid);
            if (errEl) { errEl.textContent = err; errEl.classList.remove('hidden'); }
          } else {
            inp.classList.remove('invalid');
            var eid2 = inp.getAttribute('data-error-id') || ('error-' + (inp.id || inp.name));
            var errEl2 = document.getElementById(eid2);
            if (errEl2) { errEl2.textContent = ''; errEl2.classList.add('hidden'); }
          }
        });
        if (hasError) { e.preventDefault(); if (firstInvalid) try { firstInvalid.focus(); } catch (ex) {} showToast('Please correct highlighted fields'); }
      }, false);
    });

  });
})();
