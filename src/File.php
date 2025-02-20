<?php

namespace HexMakina\LocalFS;

class File extends FileSystem
{
    private FilePath $path;
    private string $mode;
    private int $size_in_bytes = -1;
    private $pointer = null;

    private const MODE_REQUIRES_EXISTING_FILE = ['r', 'r+'];
    private const MODE_REQUIRES_UNEXISTING_FILE = ['x', 'x+'];

    // private static $modes_create_unexisting_file = ['w', 'w+', 'a', 'a+', 'c', 'c+'];

    public function __construct(string $path, string $mode = 'r')
    {
        if (!file_exists($path) && self::requiresExistingFile($mode)) {
            throw new \InvalidArgumentException('MODE_REQUIRES_EXISTING_FILE');
        }

        $this->path = new FilePath($path);
        $this->mode = $mode;
    }

    public function path(): string
    {
        return $this->path->__toString();
    }

    public function getFilePath(): FilePath
    {
        return $this->path;
    }

    public function open()
    {
        $this->pointer = fopen($this->path(), $this->mode);
        if ($this->pointer === false) {
            throw new \Exception('FILE_OPEN_FAILURE');
        }
        return $this->pointer;
    }

    public function pointer()
    {
        return $this->pointer ?? $this->open();
    }

    public function close()
    {
        if (!is_resource($this->pointer)) {
            return true;
        }

        if (fclose($this->pointer) === false) {
            throw new \Exception('FILE_CLOSE_FAILURE');
        }

        return true;
    }

    public function array()
    {
        return file($this->path);
    }

    public function setContent($content)
    {
        if (is_writable($this->path()) !== true && self::requiresExistingFile($this->mode)) {
            throw new \Exception('FILE_IS_NOT_WRITABLE');
        }

        $this->open();
        if (fwrite($this->pointer, $content) === false) {
            throw new \Exception('FILE_WRITE_FAILURE');
        }
        $this->close();
    }

   
    public function getMIMEType($fast=true): string
    {
        if($fast === true || extension_loaded('fileinfo') === false){
            return mime_content_type($this->path());
        }

        $res = finfo_open(FILEINFO_MIME_TYPE);

        if ($res === false) {
            throw new \Exception('UNABLE_TO_OPEN_FILEINFO');
        }

        $mimeType = finfo_file($res, $this->path());

        finfo_close($res);

        if ($mimeType === false) {
            throw new \Exception('UNABLE_TO_DETECT_MIME_TYPE');
        }

        return $mimeType;
    }

    public function size(): int
    {
        if ($this->size_in_bytes === -1 && ($res = filesize($this->path())) !== false) {
            $this->size_in_bytes = $res;
        }

        return $this->size_in_bytes;
    }

    private static function requiresExistingFile($mode)
    {
        return in_array($mode, self::MODE_REQUIRES_EXISTING_FILE);
    }

}
