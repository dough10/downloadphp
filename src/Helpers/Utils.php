<?php
namespace App\Helpers;

const allowedExtensions = [
  // 'txt',
  // 'pdf',
  // 'jpg',
  // 'png',
  // 'zip',
  // 'mp3',
  // 'flac',
  // 'log',
  'json'
];

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
  if ($handle = opendir($dir)) {
    while (false !== ($entry = readdir($handle))) {
      if ($entry != "." && $entry != "..") {
        // file is not a child of the directory containing this file
        $filePath = realpath($dir . DIRECTORY_SEPARATOR . $entry);
        if (strpos($filePath, $dir) !== 0) {
          continue;
        }

        // file is not an allowed type
        $fileExtension = pathinfo($entry, PATHINFO_EXTENSION);
        if (!in_array($fileExtension, $allowedExtensions)) {
          continue;
        }

        // file is hidden
        if (strpos($entry, '.') === 0) {
          continue;
        }

        // build list of files in this directory, that are not hidden and of the correct file extension 
        if (is_file($filePath)) {
          $files[] = [
            'name' => $entry,
            'path' => basename($filePath),
            'size' => filesize($filePath),
            'modified' => filemtime($filePath) 
          ];
        }
      }
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
  session_start();
  return $username;
}