<?php 

$settings = require __DIR__ ."/../config/settings.php";

/**
 * makes bytes readable by humans
 * 
 * @param mixed $bytes
 * 
 * @return string
 */
function formatFileSize($bytes) {
  return match (true) {
    $bytes >= 1073741824 => number_format($bytes / 1073741824, 2) . ' GB',
    $bytes >= 1048576    => number_format($bytes / 1048576, 2) . ' MB',
    $bytes >= 1024       => number_format($bytes / 1024, 2) . ' KB',
    default              => $bytes . ' B'
  };
}
?>

<!DOCTYPE html>
<html lang="<?=  htmlspecialchars($settings['app']['language']) ?>">
<head>
  <meta charset="<?= htmlspecialchars($settings['app']['encoding']) ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="data:image/x-icon;base64,AAABAAEAICAAAAEAIACoEAAAFgAAACgAAAAgAAAAQAAAAAEAIAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP///wD///8C////QP///5H///+l////pf///6X///+l////pf///6X///+l////pf///6X///+l////pf///6X///+l////pf///6X///+l////kf///0D///8C////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA////AP///0T////m////////////////////////////////////////////////////////////////////////////////////////////////////5v///0T///8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD///8A////kP/////////8////5f///+L////i////4v///+L////i////4v///+L////i////4v///+L////i////4v///+L////i////5f////z/////////kP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP///wD///+l/////////+X///83////G////x3///8d////Hf///x3///8d////Hf///x3///8d////Hf///x3///8d////Hf///xv///83////5f////////+l////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAGhoaAP///6X/////////4v///x3///8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA////AP///x3////i/////////6UBAQEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAaGhoA////pf/////////i////Hf///wAAAAAAAAAAAAAAAAAAAAAA////AP///xr///8a////AAAAAAAAAAAAAAAAAAAAAAD///8A////Hf///+L/////////pQEBAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABcXFwD///+S////6P///8j///8a////AAAAAAAAAAAAAAAAAP///wD///8h////tv///7b///8h////AAAAAAAAAAAAAAAAAP///wD///8a////yP///+j///+SAQEBAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAwMDAP///xP///8e////Gv///wP///8AAAAAAAAAAAD///8A////IP///7b//////////////7b///8g////AAAAAAAAAAAA////AP///wP///8a////Hv///xMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA////AP///yD///+2/////////////////////////7b///8g////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP///wD///8g////tv///////////////////////////////////7b///8g////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD///8A////IP///7b//////////v////P////+/////v////P////+/////////7b///8g////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA////AP///yL///+2///////////////D////iv////3////9////iv///8P//////////////7b///8i////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD///8A////XP////b/////////xP///yn///9Z//////////////9Z////Kf///8T/////////9v///1z///8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP///wD///8K////h////7z///8q////AP///1r//////////////1r///8A////Kv///7z///+H////Cv///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP///wD///8N////Gf///wD///8A////Wv//////////////Wv///wD///8A////Gf///w3///8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP///wD///9a//////////////9a////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA////AP///1r//////////////1r///8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD///8A////Wv//////////////Wv///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP///wD///9a//////////////9a////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA////AP///1r//////////////1r///8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD///8A////Wv//////////////Wv///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP///wD///86////pf///6X///86////AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA///////////////////////////4AAAf+AAAH/gAAB/4AAAf+H/+H/h+fh/4fD4f+HgeH//wD///4Af//8AD//+AAf//gAH//4Qh///MM////D////w////8P////D////w////8P////D////////////////////////////8=">
  <title><?= htmlspecialchars($host, ENT_QUOTES, $settings['app']['encoding']) ?></title>
  <meta name="description" content="File downloads">
  <link rel="stylesheet" href="./css/base.css">
</head>
<body>
  <div 
    id="dls" 
    class="dl-bg"></div>
  <div 
    class="card">
    <header aria-label="Site header">
      <h1>
        <svg  
          id="header-logo"
          xmlns="http://www.w3.org/2000/svg" 
          viewBox="0 -960 960 960" 
          fill="currentColor" 
          aria-hidden="true">
          <path 
            d="M480-313 287-506l43-43 120 120v-371h60v371l120-120 43 43-193 193ZM220-160q-24 0-42-18t-18-42v-143h60v143h520v-143h60v143q0 24-18 42t-42 18H220Z"/>
        </svg>
        <?= htmlspecialchars($host, ENT_QUOTES, $settings['app']['encoding']) . "\n"; ?>
      </h1>
      <span id="uname"><?= htmlspecialchars($username, ENT_QUOTES, $settings['app']['encoding']); ?></span>
      <button
        aria-label="Open download history"
        title="Open download history"
        id="hist_but" 
        class="small-button">
        <svg 
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 -960 960 960"
          fill="currentColor">
          <path 
            d="M480-120q-138 0-240.5-91.5T122-440h82q14 104 92.5 172T480-200q117 0 198.5-81.5T760-480q0-117-81.5-198.5T480-760q-69 0-129 32t-101 88h110v80H120v-240h80v94q51-64 124.5-99T480-840q75 0 140.5 28.5t114 77q48.5 48.5 77 114T840-480q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-120Zm112-192L440-464v-216h80v184l128 128-56 56Z"/>
        </svg>
      </button> 
    </header>
    <main>
      <ul>
<?php 
foreach ($files as $file) {
  $formattedSize = formatFileSize($file['size']);

  $escaped_name = htmlspecialchars($file['name'], ENT_QUOTES, $settings['app']['encoding']);
  $escaped_size = htmlspecialchars($formattedSize, ENT_QUOTES, $settings['app']['encoding']);
  $escaped_path = 'files/' . htmlspecialchars($file['path'], ENT_QUOTES, $settings['app']['encoding']);
?>
        <li 
          class='file'
          tabindex="0"
          title='File: <?= $escaped_name . ', ' . $escaped_size; ?>'
          aria-label='File: <?= $escaped_name . ', ' . $escaped_size; ?>' 
          data-path='<?= $escaped_path; ?>' 
          data-name='<?= $escaped_name; ?>'>
          <strong><?= $escaped_name; ?></strong>
          <span>Size: <?= $escaped_size; ?></span>
        </li>
<?php
}
?>
      </ul>
    </main>
    <footer>
      <nav 
        class="flex-row"
        title="Social: github.com/dough10">
        <svg 
          aria-hidden="true" 
          class="margin-right-8" 
          xmlns="http://www.w3.org/2000/svg" 
          viewBox="0 0 100 100">
          <title>GitHub Logo</title>
          <path 
            fill-rule="evenodd" 
            clip-rule="evenodd" 
            fill="currentColor" 
            d="M48.854 0C21.839 0 0 22 0 49.217c0 21.756 13.993 40.172 33.405 46.69 2.427.49 3.316-1.059 3.316-2.362 0-1.141-.08-5.052-.08-9.127-13.59 2.934-16.42-5.867-16.42-5.867-2.184-5.704-5.42-7.17-5.42-7.17-4.448-3.015.324-3.015.324-3.015 4.934.326 7.523 5.052 7.523 5.052 4.367 7.496 11.404 5.378 14.235 4.074.404-3.178 1.699-5.378 3.074-6.6-10.839-1.141-22.243-5.378-22.243-24.283 0-5.378 1.94-9.778 5.014-13.2-.485-1.222-2.184-6.275.486-13.038 0 0 4.125-1.304 13.426 5.052a46.97 46.97 0 0 1 12.214-1.63c4.125 0 8.33.571 12.213 1.63 9.302-6.356 13.427-5.052 13.427-5.052 2.67 6.763.97 11.816.485 13.038 3.155 3.422 5.015 7.822 5.015 13.2 0 18.905-11.404 23.06-22.324 24.283 1.78 1.548 3.316 4.481 3.316 9.126 0 6.6-.08 11.897-.08 13.526 0 1.304.89 2.853 3.316 2.364 19.412-6.52 33.405-24.935 33.405-46.691C97.707 22 75.788 0 48.854 0z"/>
        </svg>
        <a 
          href="https://github.com/dough10" 
          rel="noopener noreferrer" 
          target="_blank" 
          aria-label="Visit my GitHub profile">
          github.com/dough10
        </a>
      </nav>
      <section title="Security contact">
        Security contact: 
        <a 
          href=".well-known/security.txt"
          class="margin-left-4"
          target="_blank"
          aria-label="Security contact details">
          security.txt
        </a>
      </section>
    </footer>
  </div>
  <dialog
    id="history" 
    aria-label="A log of files downloaded">
    <h2>History</h2>
    <button 
      autofocus
      aria-label="Close"
      title="Close"
      class="close small-button">
      <svg viewBox="0 0 24 24">
        <path 
          fill="currentColor"
          d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
      </svg>
    </button>
    <button 
      title="Clear download history"
      aria-label="Clear download history"
      class="button clear" 
      noshadow>
      clear
    </button>
    <!-- 
      acess denied buzz by Jacco18
      https://freesound.org/s/419023/
      License: Creative Commons 0 
    -->
    <audio 
      id="error" 
      src="data:audio/wav;base64, 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PWWkOaem1bh7CviLCtD3nyb1Gz/WtZa/Nf5H0NlanKFiXOurYv3mu7Wzi0VFKXSHzKS6GIBIdovX70cbQMdCzABoAthzY0X1MKFTKSIIITNgQQMZjQ+FQAxM8NrNDb3MOFgMRGAigyPMABMMYOhsABEAAAcdBANhp+N5xBgKZmRRBcIECi6qPqVSTJhyhYBIbDQWLPsyoGgkVDLhhwLOLvBRIwwtUBEYISjoyZwniVNL1FVutFxkgcTMWUQiTNS9U0RSYCyV0vlST7Ok5nWbPEWHA4MXeEYBAakkCBYGCu03FiL+1oaa1MRVorWpnjKHmcJ9VmyyC2fSpW5yWqVbOP819amneM+kjvKdzs/H4dj0ZuUMeeyHJbUicWq75/1qb/3vmUOy2f3IZVF5NLqabt/d5h9N3GrvPu8aDeP/////////97PePf/+8/PmX/hl+9a7VaRMi4wqwJh6bgMKgIW8tCT/lkmSm5u0qhdhdTIc1u9JI2QxFjAIMopox6OguDDD4cEiVGDhJCSHAIlBgHLKmJAaEHQx4DUDUBUFjh4j0A0zhCoBagUFXgdBQysDUgSIyFfqD6kRU4OZPDAsoLUBmBeaU0jWmFDw7RUegMaAQFcQOEKAxClbsidHkvmBId0gHfiseOUYAJBDJkCqkCoyQ2D7Naq0tE5CRThO9G2JGKQq8VDYCnM8ymqmNqAV//uwRPGACchySf5vQAEv7kk/zmQAASgDP9wAACApgGZ7gAAEMZp9lzLmdB02t0dLAjntARuWo9sdcmWNiYekVVwpt441rW3dsJVNeYcxN1pRFodnpDUWBj0CNrGpjlp6cqaVVcJVVx/XxhwZbboMZq3Ul0xTW7lelvfLs8blfuFPcoMe/////vHH/1/87+/7/75zPvM/w3z8udsOCbotWl7GgiXGnhN/+14AAeAAAAAAAcqAAAAAAHgAAH/5ZUxBTUUzLjk5LjNVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVU=" 
      type="audio/wav">
    </audio>
    <ul>
<?php 
if ($downloadList) {
  foreach (array_reverse($downloadList) as $dl) {
    $escaped_dl = htmlspecialchars($dl['name'], ENT_QUOTES, $settings['app']['encoding']);
    $escaped_status = htmlspecialchars($dl['status'], ENT_QUOTES, $settings['app']['encoding']);
    $escaped_ndx = htmlspecialchars($dl['id'], ENT_QUOTES, $settings['app']['encoding']);
?>
      <li data-ndx="<?= $escaped_ndx?>">
        <strong><?= $escaped_dl; ?></strong>
        <span><?= $escaped_status; ?></span>
      </li>
<?php
  }
}
?>
    </ul>
  </dialog>
  <button 
    class="to-top small-button"
    disabled title="Scroll to top"
    aria-disabled="true">
    <svg viewBox="0 0 24 24">
      <path
        fill="currentColor"
        d="M15,20H9V12H4.16L12,4.16L19.84,12H15V20Z"/>
    </svg>
  </button>
  <script type="module" src="./js/app.js" async></script>
</body>
</html>