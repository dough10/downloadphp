<?php
namespace App\Helpers;

/**
 * creates a list of downloadable files
 * 
 * @param string $dir
 * @param array $allowedExtensions
 * 
 * @return array
 */
function generateFileList($dir, $allowedExtensions) {
  // walk folder structure for files
  $files = [];
  if (!is_dir($dir)) {
    return $files;
  }
  if ($handle = opendir($dir)) {
    while (false !== ($entry = readdir($handle))) {
      if ($entry === "." || $entry === "..") {
        continue;
      }

      $filePath = realpath($dir . DIRECTORY_SEPARATOR . $entry);
      if (strpos($filePath, $dir) !== 0) {
        continue;
      }

      $fileExtension = pathinfo($entry, PATHINFO_EXTENSION);
      if (!in_array($fileExtension, $allowedExtensions)) {
        continue;
      }

      if (strpos($entry, '.') === 0) {
        continue;
      }

      if (!is_file($filePath)) {
        continue;
      }
      $files[] = [
        'name' => $entry,
        'path' => basename($filePath),
        'size' => filesize($filePath),
        'modified' => filemtime($filePath) 
      ];
    }
    closedir($handle);

    // newest file to the top of list
    usort($files, function($a, $b) {
      return $b['modified'] - $a['modified'];
    });
  }
  return $files;
}

/**
 * parse basic auth header for username
 * 
 * @return void
 */
function decodeAuthHeader($header): string {
  if (preg_match('/^Basic\s(.+)$/i', $header, $matches)) {
    $base64Credentials = $matches[1];
    $credentials = base64_decode($base64Credentials);
    list($username, $password) = explode(":", $credentials, 2);
  }
  if (empty($username)) {
    return "Default";
  }
  return $username;
}

function getUserIP() {
  $ip = '';
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($ipArray[0]);
  }
  if (empty($ip) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  }
  if (empty($ip)) {
    $ip = $_SERVER['REMOTE_ADDR'];
  }
  return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'Invalid IP';
}

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