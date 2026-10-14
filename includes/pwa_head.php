<?php
/**
 * PWA <head> partial.
 *
 * Include from any page in the app (root, student/, teacher/, admin/) to enable
 * the web app manifest, theme color, icons and service worker registration.
 * The base path is detected automatically so relative URLs stay correct at any
 * directory depth.
 */
if (!isset($pwaBase)) {
    $pwaScriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $pwaAppDir = basename(str_replace('\\', '/', realpath(__DIR__ . '/..')));
    $pwaPos = $pwaAppDir !== '' ? strpos($pwaScriptDir, '/' . $pwaAppDir) : false;
    if ($pwaPos !== false) {
        $pwaBase = substr($pwaScriptDir, 0, $pwaPos + strlen($pwaAppDir) + 1);
    } else {
        $pwaBase = rtrim($pwaScriptDir, '/') . '/';
    }
}
$pwaBase = rtrim($pwaBase, '/') . '/';
?>
<link rel="manifest" href="<?php echo htmlspecialchars($pwaBase, ENT_QUOTES, 'UTF-8'); ?>manifest.webmanifest">
<meta name="theme-color" content="#1a56db">
<link rel="icon" type="image/png" href="<?php echo htmlspecialchars($pwaBase, ENT_QUOTES, 'UTF-8'); ?>icons/icon-192.png">
<link rel="apple-touch-icon" href="<?php echo htmlspecialchars($pwaBase, ENT_QUOTES, 'UTF-8'); ?>icons/icon-512.png">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="EduExams">
<script>
(function () {
  if (!('serviceWorker' in navigator)) { return; }
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('<?php echo $pwaBase; ?>sw.js', { scope: '<?php echo $pwaBase; ?>' })
      .catch(function () { /* PWA không khả dụng trong môi trường này */ });
  });
})();
</script>
