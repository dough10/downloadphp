<?php
namespace App\Models;

use PDO;
use PDOException;
use Exception;

class Db {
  private $pdo;
  private $appSettings;

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
      $createTableQuery = "CREATE TABLE IF NOT EXISTS downloads (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, status TEXT NOT NULL);";
      $this->pdo->exec($createTableQuery);
    } catch (PDOException $e) {
      throw new Exception('PDO error creating database file: ' . $e->getMessage());
    } catch (Exception $e) {
      throw new Exception('Failed to create database file: ' . $e->getMessage());
    }
  }

  /**
   * insert a pending download
   * 
   * @param string $dbFilename
   * @param string $name
   * 
   * @return mixed
   */
  public function insertDownloadEntry($name) {
    $name = trim($name);

    if (strlen($name) > 255) {
      throw new Exception('Name is too long');
    }

    try {
      $status = 'pending';
      $insertQuery = "INSERT INTO downloads (name, status) VALUES (:name, :status);";
      $stmt = $this->pdo->prepare($insertQuery);
      $stmt->bindParam(":name", $name);
      $stmt->bindParam(":status", $status);
      if ($stmt->execute()) {
        return $this->pdo->lastInsertId();
      } else {
        throw new Exception('Failed saving entry to database');
      }
    } catch (PDOException $e) {
      throw new Exception('Failed to insert download: ' . $e->getMessage());
    } 
  }

  /**
   * get all downloads
   * 
   * @param string $dbFilename
   * 
   * @return array
   */
  public function getDownloads() {
    try {
      $query = "SELECT * FROM downloads;";
      $stmt = $this->pdo->prepare($query);
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return array_map(function ($download) {
        $path = explode('/', $download['name']);
        $download['name'] = end($path);
        return $download;
      }, $result);
    } catch (PDOException $e) {
      throw new Exception("error geting download list: ". $e->getMessage());
    }
  }

  /**
   * update the status
   * 
   * @param string $dbFilename
   * @param string $id
   * @param string $status
   * 
   * @return void
   */
  private function updateDownloadStatus($id, $status) {
    $id = $this->validateAndSanitizeId($id);
    $status = htmlspecialchars($status, ENT_QUOTES, $this->appSettings['app']['encoding']);
    try {
      $query = 'UPDATE downloads SET status = :status WHERE id = :id';
      $stmt = $this->pdo->prepare($query);
      $stmt->bindParam(':status', $status);
      $stmt->bindParam(':id', $id);
      $stmt->execute();
    } catch (PDOException $e) {
      throw new Exception('Failed to update download status: ' . $e->getMessage());
    }
  }

  /**
   * clear download history
   * 
   * @param string $db
   * 
   * @return void
   */
  public function clearDownloads():void {
    try {
      $query = 'DELETE FROM downloads';
      $stmt = $this->pdo->prepare($query);
      $stmt->execute();
    } catch (PDOException $e) {
      throw new Exception('Failed to clear downloads: ' . $e->getMessage());
    }
  }

  /**
   * ensure id is valid
   * 
   * @param string $id
   * 
   * @return int
   */
  private function validateAndSanitizeId($id) {
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
  public function downloadStatusChanged($ndx, $status) {
    $ndx = $this->validateAndSanitizeId($ndx);
    $status = htmlspecialchars($status, ENT_QUOTES, $this->appSettings['app']['encoding']);
    return match($status) {
      'true' => $this->updateDownloadStatus($ndx, 'complete'),
      'canceled' => $this->updateDownloadStatus($ndx, 'canceled'),
      'failed' => $this->updateDownloadStatus($ndx, 'failed'),
      default => throw new Exception('Invalid completed status.'),
    };
  }
}