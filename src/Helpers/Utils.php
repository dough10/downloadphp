<?php

namespace App\Helpers;

use Slim\Psr7\Response as Response;

/**
 * Creates a list of downloadable files from a directory
 * 
 * @param string $dir Directory path to scan
 * @param array $allowedExtensions List of allowed file extensions
 * @throws \RuntimeException If directory is invalid or unreadable
 * @return array List of file information arrays
 */
function generateFileList(string $dir, array $allowedExtensions): array {
  $realDir = realpath($dir);
  if ($realDir === false || !is_dir($realDir)) {
    throw new \RuntimeException("Invalid directory: $dir");
  }

  if (!is_readable($realDir)) {
    throw new \RuntimeException("Directory not readable: $dir");
  }

  $files = [];
  $handle = opendir($realDir);
  if ($handle === false) {
    throw new \RuntimeException("Failed to open directory: $dir");
  }

  try {
    while (($entry = readdir($handle)) !== false) {
      if ($entry[0] === '.' || $entry === '..' || !is_file($realDir . DIRECTORY_SEPARATOR . $entry)) {
        continue;
      }

      $filePath = $realDir . DIRECTORY_SEPARATOR . $entry;
      
      $fileExtension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
      if (!in_array($fileExtension, array_map('strtolower', $allowedExtensions), true)) {
        continue;
      }

      $stats = stat($filePath);
      if ($stats === false) {
        error_log("Failed to get stats for file: $filePath");
        continue;
      }

      $files[] = [
        'name' => $entry,
        'path' => basename($filePath),
        'size' => $stats['size'],
        'modified' => $stats['mtime']
      ];
    }

    usort($files, fn($a, $b) => $b['modified'] - $a['modified']);

    return $files;
  } finally {
    closedir($handle);
  }
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
function jsonResponse(Response $response, $data, $status = 200): Response {
  $response->getBody()->write(json_encode($data));
  return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
}

/**
 * Generates JavaScript code for session management
 * 
 * @param \App\Models\Db $db Database instance for fetching download history
 * @return string JavaScript code for session management
 */
function sessionjs($downloads) {
  $template = file_get_contents(__DIR__ . '/../../templates/session.js.template');
  
  $replacements = [
    '{{USERNAME}}' => htmlspecialchars($_SESSION['username']),
    '{{SESSION_ID}}' => session_id(),
    '{{DOWNLOADS}}' => count($downloads),
    '{{CSRF}}' => $_SESSION['csrf_token']
  ];
  
  return str_replace(
    array_keys($replacements),
    array_values($replacements),
    $template
  );
}