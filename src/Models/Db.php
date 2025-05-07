<?php
namespace App\Models;

use PDO;
use PDOException;
use Exception;

class Db {
  private PDO $pdo;
  private array $appSettings;

  private const STATUS_PENDING = 'pending';
  private const STATUS_COMPLETE = 'complete';
  private const STATUS_CANCELED = 'canceled';
  private const STATUS_FAILED = 'failed';

  /**
   * create db file
   * 
   * @return void
   */
  public function __construct() {
    try {
      $this->appSettings = require __DIR__ .'/../../config/settings.php';
      $this->pdo = new PDO($this->appSettings['database']['dsn']);
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $createTableQuery = "CREATE TABLE IF NOT EXISTS downloads (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, status TEXT NOT NULL, username TEXT NOT NULL);";
      $this->pdo->exec($createTableQuery);
      $createIndexQuery = "CREATE INDEX IF NOT EXISTS idx_downloads_username ON downloads(username);";
      $this->pdo->exec($createIndexQuery);
    } catch (PDOException $e) {
      throw new Exception('PDO error creating database file: ' . $e->getMessage());
    } catch (Exception $e) {
      throw new Exception($this->formatErrorMessage('create database file', $e->getMessage()));
    }
  }

  /**
   * insert a pending download
   * 
   * @param string $name File name to download
   * @param string $username User requesting download
   * @throws Exception When insertion fails
   * @return int ID of inserted record
   */
  public function insertDownloadEntry(string $name, string $username): int {
    $name = trim($name);

    if (strlen($name) > 255) {
      throw new Exception('Name is too long');
    }

    try {
      $status = self::STATUS_PENDING;
      $insertQuery = "INSERT INTO downloads (name, status, username) VALUES (:name, :status, :username);";
      $stmt = $this->pdo->prepare($insertQuery);
      $stmt->bindParam(":name", $name);
      $stmt->bindParam(":status", $status);
      $stmt->bindParam(":username", $username);
      if ($stmt->execute()) {
        return $this->pdo->lastInsertId();
      } else {
        throw new Exception('Failed saving entry to database');
      }
    } catch (PDOException $e) {
      throw new Exception($this->formatErrorMessage('nsert download', $e->getMessage()));
    } 
  }

  /**
   * get all downloads
   * 
   * @param string $dbFilename
   * 
   * @return array
   */
  public function getDownloads(string $username) {
    try {
      $query = "SELECT * FROM downloads where username = :username;";
      $stmt = $this->pdo->prepare($query);
      $stmt->bindParam(":username", $username);
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return array_map(function ($download) {
        $path = explode('/', $download['name']);
        $download['name'] = end($path);
        return $download;
      }, $result);
    } catch (PDOException $e) {
      throw new Exception($this->formatErrorMessage("get download list",  $e->getMessage()));
    }
  }

  /**
   * Updates the status of a download
   *
   * @param int    $id     Download ID
   * @param string $status New status
   * 
   * @throws Exception If status update fails
   * @return void
   */
  private function updateDownloadStatus(int $id, string $status): void {
    $validStatuses = [
      self::STATUS_PENDING,
      self::STATUS_COMPLETE,
      self::STATUS_CANCELED,
      self::STATUS_FAILED
    ];
    
    if (!in_array($status, $validStatuses, true)) {
      throw new Exception($this->formatErrorMessage(
        'update download status',
        'Invalid status provided'
      ));
    }

    $id = $this->validateAndSanitizeId($id);
    $status = htmlspecialchars($status, ENT_QUOTES, $this->appSettings['app']['encoding']);
    try {
      $query = 'UPDATE downloads SET status = :status WHERE id = :id';
      $stmt = $this->pdo->prepare($query);
      $stmt->bindParam(':status', $status);
      $stmt->bindParam(':id', $id);
      $stmt->execute();
    } catch (PDOException $e) {
      throw new Exception($this->formatErrorMessage('update download status', $e->getMessage()));
    }
  }

  /**
   * clear download history
   * 
   * @param string $db
   * 
   * @return void
   */
  public function clearDownloads(string $username): void {
    try {
      $this->pdo->beginTransaction();
      
      $query = 'DELETE FROM downloads where username = :username';
      $stmt = $this->pdo->prepare($query);
      $stmt->bindParam(":username", $username);
      $stmt->execute();
      
      $this->pdo->commit();
    } catch (PDOException $e) {
      $this->pdo->rollBack();
      throw new Exception($this->formatErrorMessage('clear downloads', $e->getMessage()));
    }
  }

  /**
   * ensure id is valid
   * 
   * @param string $id
   * 
   * @return int
   */
  private function validateAndSanitizeId(string $id) {
    if (!filter_var($id, FILTER_VALIDATE_INT)) {
      throw new Exception('Invalid ID provided. ID must be a valid intager');
    }
    return (int)$id; 
  }

  /**
   * mark a download with a completed status
   * 
   * @param string $ndx
   * @param string $status
   * 
   * @return void
   */
  public function downloadStatusChanged(int $ndx, string $status) {
    $ndx = $this->validateAndSanitizeId($ndx);
    $status = htmlspecialchars($status, ENT_QUOTES, $this->appSettings['app']['encoding']);
    return match($status) {
      'true' => $this->updateDownloadStatus($ndx, self::STATUS_COMPLETE),
      'canceled' => $this->updateDownloadStatus($ndx, self::STATUS_CANCELED),
      'failed' => $this->updateDownloadStatus($ndx, self::STATUS_FAILED),
      default => throw new Exception($this->formatErrorMessage('set complete status', 'Invalid status.')),
    };
  }

  /**
   * format error message
   * 
   * @param string $operation
   * @param string $error
   * 
   * @return string
   */
  private function formatErrorMessage(string $operation, string $error): string {
    return sprintf('Failed to %s: %s', $operation, $error);
  }
}