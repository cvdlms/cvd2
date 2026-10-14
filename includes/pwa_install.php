<?php
/**
 * PWA "Install App" button partial.
 *
 * Renders a self-contained button that only appears when the browser can
 * install the web app and the app is not already running in standalone mode.
 *
 * The <style> and <script> blocks are emitted at most once per request, so the
 * partial is safe to include from several headers on the same page.
 *
 * Optional variables to set before including:
 *   $pwaInstallOnDark (bool)  Style the button for a dark header/navbar.
 *   $pwaInstallFixed  (bool)  Anchor the button to the top-right corner.
 */
$pwaInstallOnDark = !empty($pwaInstallOnDark);
$pwaInstallFixed = !empty($pwaInstallFixed);
$pwaInstallEmitAssets = empty($GLOBALS['pwa_install_assets_emitted']);
if ($pwaInstallEmitAssets) {
    $GLOBALS['pwa_install_assets_emitted'] = true;
}
?>
<?php if ($pwaInstallEmitAssets): ?>
<style>
  .pwa-install-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 13px;
    border: 1px solid rgba(26, 86, 219, .32);
    border-radius: 999px;
    background: rgba(26, 86, 219, .08);
    color: #1a56db;
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
    cursor: pointer;
    transition: background .18s ease, color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .12s ease;
  }

  .pwa-install-btn:hover {
    background: #1a56db;
    border-color: #1a56db;
    color: #fff;
    box-shadow: 0 6px 16px rgba(26, 86, 219, .28);
  }

  .pwa-install-btn:active {
    transform: scale(.97);
  }

  .pwa-install-btn:focus-visible {
    outline: 2px solid rgba(26, 86, 219, .55);
    outline-offset: 2px;
  }

  .pwa-install-btn[disabled] {
    opacity: .6;
    cursor: default;
  }

  .pwa-install-btn svg {
    width: 16px;
    height: 16px;
    flex: 0 0 16px;
  }

  .pwa-install-btn[hidden] {
    display: none !important;
  }

  .pwa-install-btn.is-on-dark {
    border-color: rgba(255, 255, 255, .34);
    background: rgba(255, 255, 255, .14);
    color: #fff;
  }

  .pwa-install-btn.is-on-dark:hover {
    background: #fff;
    border-color: #fff;
    color: #1a56db;
    box-shadow: 0 6px 16px rgba(0, 0, 0, .28);
  }

  .pwa-install-fab {
    position: fixed;
    top: 14px;
    right: 14px;
    z-index: 1025;
    display: flex;
  }

  @media (max-width: 640px) {
    .pwa-install-btn {
      padding: 8px;
      border-radius: 50%;
    }

    .pwa-install-btn .pwa-install-btn__label {
      display: none;
    }

    .pwa-install-fab {
      top: 10px;
      right: 10px;
    }
  }
</style>
<?php endif; ?>
<?php if ($pwaInstallFixed): ?><div class="pwa-install-fab"><?php endif; ?>
<button
  type="button"
  class="pwa-install-btn<?php echo $pwaInstallOnDark ? ' is-on-dark' : ''; ?>"
  data-pwa-install
  hidden
  aria-label="Cài đặt ứng dụng EduExams"
  title="Cài đặt ứng dụng"
>
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M12 3v12"/>
    <path d="m7 11 5 5 5-5"/>
    <path d="M5 21h14"/>
  </svg>
  <span class="pwa-install-btn__label">Cài đặt ứng dụng</span>
</button>
<?php if ($pwaInstallFixed): ?></div><?php endif; ?>
<?php if ($pwaInstallEmitAssets): ?>
<script>
(function () {
  'use strict';

  var deferredPrompt = null;
  var buttons = document.querySelectorAll('[data-pwa-install]');

  if (!buttons.length) {
    return;
  }

  function isStandalone() {
    try {
      if (window.matchMedia && (
        window.matchMedia('(display-mode: standalone)').matches ||
        window.matchMedia('(display-mode: fullscreen)').matches ||
        window.matchMedia('(display-mode: minimal-ui)').matches
      )) {
        return true;
      }
    } catch (e) { /* ignore */ }
    return window.navigator.standalone === true;
  }

  function isInstallCapableBrowser() {
    var ua = window.navigator.userAgent || '';
    return /(Chrome|Chromium|CriOS|Edg|EdgA|EdgiOS|OPR|SamsungBrowser)/.test(ua);
  }

  function setVisible(visible) {
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].hidden = !visible;
    }
  }

  function refresh() {
    if (isStandalone()) {
      setVisible(false);
      return;
    }
    setVisible(!!deferredPrompt || isInstallCapableBrowser());
  }

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    deferredPrompt = event;
    refresh();
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    setVisible(false);
  });

  for (var i = 0; i < buttons.length; i++) {
    buttons[i].addEventListener('click', function () {
      var btn = this;

      if (!deferredPrompt) {
        var msg = 'Để cài đặt, mở menu trình duyệt và chọn "Cài đặt ứng dụng" hoặc "Thêm vào màn hình chính".';
        if (window.Toastify && typeof window.Toastify === 'function') {
          window.Toastify({ text: msg, duration: 5000 }).showToast();
        } else {
          window.alert(msg);
        }
        return;
      }

      var promptEvent = deferredPrompt;
      btn.disabled = true;

      try {
        promptEvent.prompt();
      } catch (e) {
        btn.disabled = false;
        deferredPrompt = null;
        return;
      }

      promptEvent.userChoice.then(function (choice) {
        btn.disabled = false;
        deferredPrompt = null;
        if (choice && choice.outcome === 'accepted') {
          setVisible(false);
        } else {
          refresh();
        }
      }).catch(function () {
        btn.disabled = false;
        deferredPrompt = null;
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refresh);
  } else {
    refresh();
  }
})();
</script>
<?php endif; ?>
