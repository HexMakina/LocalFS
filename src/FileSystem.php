<?php

namespace HexMakina\LocalFS;

/**
 * FILEPATH: /var/www/dev.engine/krafto-cinergie/lib/LocalFS/FileSystem.php
 * 
 * The FileSystem class provides a set of methods to interact with a directory and subdirectories.
 * The root is the starting point of the represented file system.
 * It allows to work with relative paths and provides methods to get absolute paths, list directories and files,
 * and ensure that a path is writable.
 * 
 * @package HexMakina\LocalFS
 */

class FileSystem
{

    private $rootPath = null;

    /**
     * Constructs a new FileSystem object with the given root path.
     *
     * @param string $rootPath The starting point of the represented file system.
     * @throws \InvalidArgumentException If the root path is invalid.
     */
    function __construct(string $rootPath)
    {
        $rootPath = realpath($rootPath);
        if (!$rootPath)
            throw new \InvalidArgumentException('INVALID_ROOT_PATH');

        $this->rootPath = $rootPath;
    }
    
    public function __toString()
    {
        return $this->root();
    }

    /**
     * Returns the starting point of a filesystem's abstraction, not the machine's filesystem root. 
     * It can be a project's root, or any directory you may want to work with.
     *
     * @return string The root path of the file system.
     */
    public function root(): string
    {
        return $this->rootPath;
    }

    /**
     * Returns the absolute path for a given relative path.
     *
     * @param string $relativePath The relative path to get the absolute path for.
     * @param bool $checkExistence Whether to check if the file exists or not. Default is false.
     * @return string The absolute path for the given relative path.
     * @throws \InvalidArgumentException If the relative path is empty or invalid.
     */
    public function absolutePathFor(string $relativePath, bool $checkExistence = false): string
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        $absolute = $relativePath === '' ? $this->root() : sprintf('%s/%s', $this->root(), $relativePath);

        if ($checkExistence) {
            $resolvedAbsolute = realpath($absolute);
            if (!$resolvedAbsolute) {
                throw new \InvalidArgumentException('INVALID_PATH');
            }

            $this->assertPathInsideRoot($resolvedAbsolute);
        }

        return $absolute;
    }

    public function pathIsInsideRoot(string $absolutePath): bool
    {
        try {
            $resolvedPath = realpath($absolutePath);
            $this->assertPathInsideRoot($resolvedPath ?: $this->normalizeAbsolutePath($absolutePath));
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    private function normalizeRelativePath(string $relativePath): string
    {
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);

        if ($this->isAbsolutePath($relativePath)) {
            throw new \InvalidArgumentException('PATH_NOT_INSIDE_ROOT_PATH');
        }

        $parts = [];
        foreach (explode(DIRECTORY_SEPARATOR, $relativePath) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                if (empty($parts)) {
                    throw new \InvalidArgumentException('PATH_NOT_INSIDE_ROOT_PATH');
                }

                array_pop($parts);
                continue;
            }

            $parts[] = $part;
        }

        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    private function isAbsolutePath(string $path): bool
    {
        return $path !== '' && ($path[0] === DIRECTORY_SEPARATOR || preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1);
    }

    private function normalizeAbsolutePath(string $absolutePath): string
    {
        $absolutePath = str_replace('\\', DIRECTORY_SEPARATOR, $absolutePath);

        if (!$this->isAbsolutePath($absolutePath)) {
            throw new \InvalidArgumentException('PATH_NOT_INSIDE_ROOT_PATH');
        }

        if (preg_match('~^[A-Za-z]:[\\\\/]~', $absolutePath) === 1) {
            $prefix = substr($absolutePath, 0, 2) . DIRECTORY_SEPARATOR;
            $absolutePath = substr($absolutePath, 3);
        } else {
            $prefix = DIRECTORY_SEPARATOR;
            $absolutePath = ltrim($absolutePath, DIRECTORY_SEPARATOR);
        }

        $parts = [];
        foreach (explode(DIRECTORY_SEPARATOR, $absolutePath) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                if (!empty($parts)) {
                    array_pop($parts);
                }

                continue;
            }

            $parts[] = $part;
        }

        return $prefix . implode(DIRECTORY_SEPARATOR, $parts);
    }

    private function assertPathInsideRoot(string $absolutePath): void
    {
        $root = rtrim($this->root(), DIRECTORY_SEPARATOR);
        $path = rtrim($absolutePath, DIRECTORY_SEPARATOR);

        if ($path !== $root && strpos($path, $root . DIRECTORY_SEPARATOR) !== 0) {
            throw new \InvalidArgumentException('PATH_NOT_INSIDE_ROOT_PATH');
        }
    }

    /**
     * Lists the contents of a directory.
     *
     * @param string $relativePath The relative path of the directory to list.
     * @return array An array of filenames in the directory.
     * @throws \InvalidArgumentException If the specified path is not a directory.
     */
    public function list(string $relativePath): array
    {
        $absolutePath = $this->absolutePathFor($relativePath);

        if (!is_dir($absolutePath)) {
            throw new \InvalidArgumentException('RELATIVE_PATH_NOT_A_DIRECTORY');
        }

        return array_diff(scandir($absolutePath), ['.', '..']);
    }

    /**
     * Returns an array of files in the specified directory.
     *
     * @param string $relativePath The relative path of the directory to search.
     * @return array An array of file names.
     */
    public function files(string $relativePath): array
    {
        $absolutePath = $this->absolutePathFor($relativePath);
        // Filter the list of files to include only files (not directories).
        $files = array_filter($this->list($relativePath), function ($filename) use ($absolutePath) {
            return is_file($absolutePath . DIRECTORY_SEPARATOR . $filename);
        });

        return $files;
    }

    /**
     * Returns an array of directories in the specified relative path.
     *
     * @param string $relativePath The relative path to search for directories.
     * @return array An array of directories in the specified relative path.
     */
    public function directories(string $relativePath): array
    {
        $absolutePath = $this->absolutePathFor($relativePath);
        // Filter the list of files to include only files (not directories).
        $files = array_filter($this->list($relativePath), function ($filename) use ($absolutePath) {
            return is_dir($absolutePath . DIRECTORY_SEPARATOR . $filename);
        });

        return $files;
    }

    /**
     * Ensures that the specified relative path is writable.
     *
     * @param string $relativePath The relative path to ensure is writable.
     * @throws \InvalidArgumentException If the path is not inside the root path, the target directory cannot be created, or the target directory is not writable.
     * @return bool True if the path is writable, false otherwise.
     */

    public function ensureWritablePath(string $absoluteDirectoryPath, ?string $filename = null): bool
    {
        if (!$this->pathIsInsideRoot($absoluteDirectoryPath)) {
            throw new \InvalidArgumentException('PATH_NOT_INSIDE_ROOT_PATH');
        }

        $relativeDirectoryPath = substr($absoluteDirectoryPath, strlen($this->root()) + 1);
        $pathParts = explode('/', $relativeDirectoryPath);
        

        $targetDir = $this->root();
        foreach ($pathParts as $i => $part) {
            $targetDir .= '/' . $part;

            // Create the folder if it doesn't exist
            if (!file_exists($targetDir) && mkdir($targetDir, 0755, true) === false) {
                throw new \InvalidArgumentException('UNABLE_TO_CREATE_MISSING_TARGET_DIRECTORY');
            }

            if (!is_dir($targetDir)) {
                throw new \InvalidArgumentException('TARGET_DIRECTORY_NOT_A_DIRECTORY');
            }
            if (!is_writable($targetDir)) {
                throw new \InvalidArgumentException('TARGET_DIRECTORY_NOT_WRITABLE (' . $targetDir . ')');
            }
        }
        return true;
    }

    public function filenames($regex = null): array
    {
        if (!file_exists($this->rootPath) && mkdir($this->rootPath) === false) {
            return [];
        }

        $filenames = self::preg_scandir($this->rootPath, $regex); // ID_SEQUENCENUMBER.ext
        if (!is_null($filenames)) {
            sort($filenames);
        }

        return $filenames;
    }

    // previous implementation, to update
    public function filepathes($regex = null): array
    {
        $filenames = $this->filenames($regex);
        $filepathes = [];
        foreach ($filenames as $filename) {
            $filepathes[] = $this->absolutePathFor($filename);
        }

        return $filepathes;
    }

    /**
     * Resolves a symbolic link to its target path.
     *
     * @param string $path The path to resolve.
     * @throws \Exception If `readlink` fails to resolve the symbolic link.
     * @return string The resolved path.
     */
    public static function resolve_symlink($path)
    {
        if (is_link($path)) {
            if (($path = readlink($path)) === false) {
                throw new \Exception('Failed to resolve symbolic link');
            }
        }

        return $path;
    }
    /**
     * Copies a file from the source path to the destination path.
     *
     * @param string $sourcePath The path of the source file.
     * @param string $destinationPath The path of the destination file.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public static function copy($sourcePath, $destinationPath)
    {
        if (file_exists($sourcePath) && is_file($sourcePath)) {
            $destination = new FilePath($destinationPath);
            if (file_exists($destination->dir()) && is_dir($destination->dir())) {
                return copy($sourcePath, $destinationPath);
            }
        }
        return false;
    }

    /**
     * Moves a file or directory from the source path to the destination path.
     *
     * @param string $sourcePath The path of the source file or directory.
     * @param string $destinationPath The path of the destination file or directory.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public static function move($sourcePath, $destinationPath): bool
    {
        if (!file_exists($sourcePath)) {
            throw new \InvalidArgumentException('SOURCE_PATH_DOES_NOT_EXIST');
        }

        $destination = new FilePath($destinationPath);
        $destinationDir = $destination->dir();

        if (!file_exists($destinationDir)) {
            if (!mkdir($destinationDir, 0755, true)) {
                throw new \InvalidArgumentException('DESTINATION_DIRECTORY_DOES_NOT_EXIST_AND_CANNOT_BE_CREATED');
            }
        }

        if (!is_dir($destinationDir) || !is_writable($destinationDir)) {
            throw new \InvalidArgumentException('DESTINATION_DIRECTORY_NOT_WRITABLE');
        }

        return rename($sourcePath, $destinationPath);
    }

    /**
     * Creates a directory with the specified path and permissions.
     *
     * @param string $directoryPath The path of the directory to create.
     * @param int $permission The permissions to set for the directory.
     * @param bool $recursive Whether to create parent directories if they don't exist.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public static function makeDirectory($directoryPath, $permission = 0777, $recursive = true): bool
    {
        return mkdir($directoryPath, $permission, $recursive);
    }

    /**
     * Removes a file or directory with the specified path.
     *
     * @param string $sourcePath The path of the file or directory to remove.
     * @param bool $followLink Whether to follow symbolic links.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public static function remove($sourcePath, $followLink = false): bool
    {
        $success = false;
        if (file_exists($sourcePath)) {
            if (is_link($sourcePath) && $followLink === true) {
                $success = self::remove(readlink($sourcePath));
            } elseif (is_file($sourcePath)) {
                $success = unlink($sourcePath);
            } elseif (is_dir($sourcePath)) {
                $success = rmdir($sourcePath);
            }
        }
        return $success;
    }

    /**
     * Scans a directory for files and directories that match a regular expression.
     *
     * @param string $directoryPath The path of the directory to scan.
     * @param string|null $regex The regular expression to match against file and directory names.
     * @return array|null Returns an array of file and directory names that match the regular expression, or NULL if the directory doesn't exist.
     * @throws \Exception If the directory cannot be scanned.
     */
    public static function preg_scandir($directoryPath, $regex = null)
    {
        if (!file_exists($directoryPath) || !is_dir($directoryPath)) {
            return null;
        }
        
        if (($fileNames = scandir($directoryPath, SCANDIR_SORT_ASCENDING)) !== false) {
            return is_null($regex) ? $fileNames : preg_grep($regex, $fileNames);
        }

        throw new \Exception("directory path '$directoryPath' cannot be scanned");
    }
}
