<?php
namespace Tests\Helpers;

use PHPUnit\Framework\TestCase;
use App\Helpers;

class UtilsTest extends TestCase {
  private string $fixturesDir;

  protected function setUp(): void {
    $this->fixturesDir = __DIR__ . '/fixtures';
    if (!file_exists($this->fixturesDir)) {
      mkdir($this->fixturesDir, 0755, true);
    }
    file_put_contents($this->fixturesDir . '/test.txt', 'test content');
  }

  protected function tearDown(): void {
    if (file_exists($this->fixturesDir . '/test.txt')) {
      unlink($this->fixturesDir . '/test.txt');
    }
    if (file_exists($this->fixturesDir)) {
      rmdir($this->fixturesDir);
    }
  }

  public function testGenerateFileList(): void {
    $files = Helpers\generateFileList($this->fixturesDir, ['txt']);
    
    $this->assertIsArray($files);
    $this->assertCount(1, $files);
    $this->assertArrayHasKey('name', $files[0]);
    $this->assertArrayHasKey('size', $files[0]);
    $this->assertStringEndsWith('.txt', $files[0]['name']);
  }

  public function testInvalidDirectory(): void {
    $this->expectException(\RuntimeException::class);
    Helpers\generateFileList('/nonexistent/dir', []);
  }
}