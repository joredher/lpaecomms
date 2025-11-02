// LPAEcomms - Idle Session Timeout (client-side helper)
(function () {
  try {
    var FIVE_MINUTES = 5 * 60 * 1000; // Dev default
    var DEFAULT_TIMEOUT = FIVE_MINUTES;
    var CHECK_EVERY = 5000; // 5s
    var STORAGE_KEY = 'lpa_idle_logout';
    var cookieName = 'lpa_idle_logout';
    var active = true;
    var timer = null;
    var lastActivity = Date.now();

    function readCookie(name) {
      var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'));
      return m ? decodeURIComponent(m[1]) : null;
    }

    function writeCookie(name, value, seconds) {
      var expires = '';
      if (seconds) {
        var d = new Date();
        d.setTime(d.getTime() + (seconds * 1000));
        expires = '; expires=' + d.toUTCString();
      }
      document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
    }

    function clearCookie(name) {
      document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/; SameSite=Lax';
    }

    function now() { return Date.now(); }

    function markActivity() { lastActivity = now(); }

    function injectStylesOnce() {
      if (document.getElementById('idle-timeout-styles')) return;
      var css = '\n' +
        '.idle-overlay{position:fixed;inset:0;z-index:2147483000;display:flex;align-items:center;justify-content:center;'+
        'background:rgba(0,0,0,0.45);backdrop-filter:saturate(120%) blur(6px);}\n' +
        '.idle-modal{max-width:520px;width:92%;border-radius:12px;padding:24px;background:#c1dcdc;box-shadow:0 10px 25px rgba(0,0,0,.25);font-family:\"Poppins\",system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,\"Helvetica Neue\",Arial;}\n' +
        'body.theme-dark .idle-modal{background:#1f2937;color:#e5e7eb;}\n' +
        '.idle-h1{font-size:20px;margin:0 0 8px;font-weight:600;}\n' +
        '.idle-p{margin:0 0 18px;color:#4a5568;}\n' +
        'body.theme-dark .idle-p{color:#cbd5e1;}\n' +
        'body.idle-locked{overflow:hidden;}\n';
      var style = document.createElement('style');
      style.id = 'idle-timeout-styles';
      style.textContent = css;
      document.head.appendChild(style);
    }

    function showBlockingModal() {
      if (document.getElementById('idle-overlay')) return;
      injectStylesOnce();
      var overlay = document.createElement('div');
      overlay.className = 'idle-overlay';
      overlay.id = 'idle-overlay';
      overlay.setAttribute('aria-modal', 'true');
      overlay.setAttribute('role', 'dialog');

      var inner = document.createElement('div');
      inner.className = 'idle-modal';
      inner.innerHTML = ''+
        '<h1 class="idle-h1">Session expired</h1>'+
        '<p class="idle-p">You were logged out due to inactivity.</p>'+
        '<button id="idle-continue" class="btn btn-primary btn-lg w-100" type="button">'+
        '<i class="bi bi-arrow-repeat me-1"></i> Extend Session</button>';

      overlay.appendChild(inner);
      document.body.appendChild(overlay);
      document.body.classList.add('idle-locked');

      var btn = document.getElementById('idle-continue');
      btn.addEventListener('click', function () {
        // If server already expired the session, redirect to login
        if (readCookie(cookieName) === '1') {
          clearCookie(cookieName);
          try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
          var href = (location.pathname.startsWith('/admin')) ? '/admin-login' : '/login';
          location.assign(href);
          return;
        }
        // Attempt to keep the current session alive
        btn.disabled = true;
        fetch('/session.keepAlive', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': (window.CSRF_TOKEN||'') },
          credentials: 'same-origin'
        }).then(function (res) {
          if (!res.ok) {
            throw res;
          }
          return res.json();
        }).then(function () {
          // Clear local flags and resume
          try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
          clearCookie(cookieName);
          lastActivity = now();
          active = true;
          document.body.classList.remove('idle-locked');
          var ov = document.getElementById('idle-overlay');
          if (ov) ov.remove();
        }).catch(function () {
          // If keep-alive fails, redirect to login
          var href = (location.pathname.startsWith('/admin')) ? '/admin-login' : '/login';
          location.assign(href);
        }).finally(function () {
          btn.disabled = false;
        });
      });
    }

    function expireClientSide() {
      if (!active) return;
      active = false;
      try { localStorage.setItem(STORAGE_KEY, '1'); } catch (e) {}
      writeCookie(cookieName, '1', 1800);

      // Request server logout (best-effort)
      // Do NOT log out automatically; allow user to extend the session
      showBlockingModal();
    }

    function tick() {
      // If server already flagged expiration via cookie, show immediately
      if (readCookie(cookieName) === '1') {
        showBlockingModal();
        return;
      }
      var elapsed = now() - lastActivity;
      if (elapsed >= DEFAULT_TIMEOUT) {
        expireClientSide();
        return;
      }
    }

    function startTimer() {
      if (timer) clearInterval(timer);
      timer = setInterval(tick, CHECK_EVERY);
    }

    function wireActivityListeners() {
      ['mousemove','keydown','click','scroll','touchstart','wheel'].forEach(function (evt) {
        window.addEventListener(evt, markActivity, { passive: true });
      });
      document.addEventListener('visibilitychange', function () {
        if (!document.hidden) markActivity();
      });
    }

    function bootstrap() {
      var isAuth = !!(window.IS_AUTHENTICATED);
      if (!isAuth) {
        // Not logged in: ensure leftover flags don't trigger overlay
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
        clearCookie(cookieName);
        return; // do not initialize timers or show modal
      }
      // If server has already expired the session, show immediately
      if (readCookie(cookieName) === '1' || (function(){ try { return localStorage.getItem(STORAGE_KEY) === '1'; } catch(e){ return false; } })()) {
        showBlockingModal();
        return;
      }
      wireActivityListeners();
      startTimer();
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
      bootstrap();
    }
  } catch (e) {
    // Fail-safe: never break the page
    console.error('Idle timeout init error:', e);
  }
})();
