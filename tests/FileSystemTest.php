<?php

namespace HexMakina\LocalFS\Tests;

use PHPUnit\Framework\TestCase;
use HexMakina\LocalFS\FileSystem;

class FileSystemTest extends TestCase
{
    private $testRootPath;
    private $fileSystem;

    protected function setUp(): void
    {
        $this->testRootPath = sys_get_temp_dir() . '/hexmakina_localfs_test';
        if (!file_exists($this->testRootPath)) {
            mkdir($this->testRootPath);
        }
        $this->fileSystem = new FileSystem($this->testRootPath);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testRootPath);
    }

    private function removeDirectory($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->removeDirectory($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

public function testConstructWithInvalidRootPath()
{
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('INVALID_ROOT_PATH');

    new FileSystem('/non/existent/path');
}

public function testAbsolutePathForReturnsCorrectPath()
{
    $testRootPath = '/tmp/test_root';
    mkdir($testRootPath);
    $fileSystem = new FileSystem($testRootPath);

    $relativePath = 'subdir/file.txt';
    $expectedPath = $testRootPath . '/' . $relativePath;

    $result = $fileSystem->absolutePathFor($relativePath);

    $this->assertEquals($expectedPath, $result);

    rmdir($testRootPath);
}

public function testListNonExistentDirectory()
{
    $nonExistentPath = 'non/existent/directory';
    
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('RELATIVE_PATH_NOT_A_DIRECTORY');
    
    $this->fileSystem->list($nonExistentPath);
}

public function testCopyNonExistentSourceFile()
{
    $nonExistentSourcePath = $this->testRootPath . '/non_existent_file.txt';
    $destinationPath = $this->testRootPath . '/destination.txt';

    $result = FileSystem::copy($nonExistentSourcePath, $destinationPath);

    $this->assertFalse($result);
    $this->assertFileDoesNotExist($destinationPath);
}

public function testResolveSymlink()
{
    $tempDir = sys_get_temp_dir() . '/symlink_test';
    mkdir($tempDir);
    $targetFile = $tempDir . '/target.txt';
    $symlinkFile = $tempDir . '/symlink.txt';

    file_put_contents($targetFile, 'Test content');
    symlink($targetFile, $symlinkFile);

    $resolvedPath = FileSystem::resolve_symlink($symlinkFile);

    $this->assertEquals($targetFile, $resolvedPath);

    unlink($symlinkFile);
    unlink($targetFile);
    rmdir($tempDir);
}

public function testEnsureWritablePathCreatesDirectories()
{
    $nonExistentPath = $this->testRootPath . '/new/nested/directory';
    $this->assertFalse(is_dir($nonExistentPath));

    $result = $this->fileSystem->ensureWritablePath($nonExistentPath);

    $this->assertTrue($result);
    $this->assertTrue(is_dir($nonExistentPath));
    $this->assertTrue(is_writable($nonExistentPath));
}

public function testEnsureWritablePathOutsideRootPath()
{
    $outsidePath = '/tmp/outside_root_path';
    
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('PATH_NOT_INSIDE_ROOT_PATH');
    
    $this->fileSystem->ensureWritablePath($outsidePath);
}

public function testDirectoriesListsOnlyDirectories()
{
    $testDir = $this->testRootPath . '/test_directories';
    mkdir($testDir);
    mkdir($testDir . '/dir1');
    mkdir($testDir . '/dir2');
    touch($testDir . '/file1.txt');
    touch($testDir . '/file2.txt');

    $result = $this->fileSystem->directories('test_directories');

    $this->assertCount(2, $result);
    $this->assertContains('dir1', $result);
    $this->assertContains('dir2', $result);
    $this->assertNotContains('file1.txt', $result);
    $this->assertNotContains('file2.txt', $result);
}

public function testFilesListsOnlyFilesInSpecifiedDirectory()
{
    $testDir = $this->testRootPath . '/test_files';
    mkdir($testDir);
    
    // Create test files and directories
    touch($testDir . '/file1.txt');
    touch($testDir . '/file2.txt');
    mkdir($testDir . '/subdir');
    
    $fileSystem = new FileSystem($this->testRootPath);
    $files = $fileSystem->files('test_files');
    
    $this->assertCount(2, $files);
    $this->assertContains('file1.txt', $files);
    $this->assertContains('file2.txt', $files);
    $this->assertNotContains('subdir', $files);
    
    // Clean up
    unlink($testDir . '/file1.txt');
    unlink($testDir . '/file2.txt');
    rmdir($testDir . '/subdir');
    rmdir($testDir);
}
}
