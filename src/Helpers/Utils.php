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
 * Helper to POST a token to the auth server and return the decoded response.
 *
 * @param string $endpoint The endpoint path (e.g., '/token/verify' or '/token/refresh')
 * @param string $token The token to send
 * @param mixed $logger Logger for debug (optional)
 * @return array The decoded JSON response as an associative array
 * @throws \RuntimeException
 */
function postTokenToAuthServer(string $endpoint, string $token, $logger = null): array {
    $settings = require __DIR__ . '/../../config/settings.php';
    $url = $settings['app']['auth-server'] . $endpoint;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['token' => $token]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $result = curl_exec($ch);
    if ($result === false) {
        if ($logger) $logger->error('Auth server request failed: ' . curl_error($ch));
        throw new \RuntimeException('Auth server request failed: ' . curl_error($ch));
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        if ($logger) $logger->error('Auth server returned HTTP ' . $httpCode);
        throw new \RuntimeException('Auth server returned HTTP ' . $httpCode);
    }

    $data = json_decode($result, true);
    if (!is_array($data) || !$data['valid']) {
        if ($logger) $logger->error('Invalid response from auth server: ' . $result);
        throw new \RuntimeException('Invalid response from auth server');
    }
    return $data;
}

/**
 * Verifies a token and returns the decoded user info as an object.
 *
 * @param string $token
 * @param mixed $logger
 * @return object
 */
function decodeToken($token, $logger): object {
    $data = postTokenToAuthServer('/token/verify', $token, $logger);
    if (empty($data['user'])) {
        if ($logger) $logger->error('No user field in auth server response');
        throw new \RuntimeException('No user field in auth server response');
    }
    // Return as object for consistency
    return is_array($data['user']) ? (object)$data['user'] : (object)['email' => $data['user']];
}

/**
 * Attempts to refresh a token and returns the new access token as a string.
 *
 * @param string $refresh
 * @param mixed $logger
 * @return string
 */
function attemptTokenRefresh($refresh, $logger = null): string {
    $data = postTokenToAuthServer('/token/refresh', $refresh, $logger);
    if (empty($data['access_token'])) {
        if ($logger) $logger->error('No access_token field in auth server response');
        throw new \RuntimeException('No access_token field in auth server response');
    }
    return (string)$data['access_token'];
}

/**
 * authentication redirect url
 * 
 * @param mixed $request
 * 
 * @return string
 */
function auth_redirect_address($request): string {
  $settings = require __DIR__ . '/../../config/settings.php';
  $host = $request->getUri()->getHost();
  $scheme = $request->getUri()->getScheme();
  $port = $request->getUri()->getPort();
  $hostWithPort = $host;
  if ($port && !in_array($port, [80, 443])) {
    $hostWithPort .= ':' . $port;
  }
  $fullHost = $scheme . '://' . $hostWithPort;
  return $settings['app']['auth-server'] . '?next=' . urlencode($fullHost);
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
function sessionjs($user, $downloads) {
  $template = file_get_contents(__DIR__ . '/../../templates/session.js.template');
  
  $replacements = [
    '{{USERNAME}}' => htmlspecialchars($user),
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