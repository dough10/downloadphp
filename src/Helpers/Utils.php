<?php
namespace App\Helpers;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * creates a list of downloadable files
 * 
 * @param string $dir
 * @param array $allowedExtensions
 * 
 * @return array|bool
 */
function generateFileList($dir, $allowedExtensions) {
  // walk folder structure for files
  $files = [];
  if (!is_dir($dir)) {
    error_log("Directory does not exist: $dir");
    return false;
  }
  if ($handle = opendir($dir)) {
    while (false !== ($entry = readdir($handle))) {
      $filePath = realpath($dir . DIRECTORY_SEPARATOR . $entry);
      if ($entry === "." || $entry === ".." || strpos($entry, '.') === 0 || !is_file($filePath)) {
        continue;
      }

      if (realpath($filePath) === false || strpos(realpath($filePath), realpath($dir)) !== 0) {
        continue;
      }

      $fileExtension = pathinfo($entry, PATHINFO_EXTENSION);
      if (!in_array($fileExtension, $allowedExtensions)) {
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
  } else {
    error_log("Failed to open directory: $dir");
    return false;
  }
  return $files;
}

/**
 * parse basic auth header for username
 * 
 * @return string
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

/**
 * Get users IP address
 * 
 * @return string
 */
function getUserIP(): string {
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

/**
 * 
 * 
 */
function jsonResponse(Response $response, $data, $status = 200) {
  $response->getBody()->write(json_encode($data));
  return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
}

/**
 * Generates JavaScript code for session management
 * 
 * @param \App\Models\Db $db Database instance for fetching download history
 * @return string JavaScript code for session management
 */
function sessionjs($db) {
  $template = file_get_contents(__DIR__ . '/../../templates/session.js.template');
  
  $replacements = [
    '{{USERNAME}}' => htmlspecialchars($_SESSION['username']),
    '{{SESSION_ID}}' => session_id(),
    '{{DOWNLOADS}}' => json_encode($db->getDownloads($_SESSION['username']))
  ];
  
  return str_replace(
    array_keys($replacements),
    array_values($replacements),
    $template
  );
}