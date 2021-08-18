<?php

namespace HexMakina\LocalFS;

class File extends FileSystem
{
    private $filepath = null;
    private $mode = null;
    private $size_in_bytes = null;
    private $pointer = null;

    private static $modes_require_existing_file = ['r', 'r+'];
  // private static $modes_create_unexisting_file = ['w', 'w+', 'a', 'a+', 'c', 'c+'];
  // private static $modes_require_unexisting_file = ['x', 'x+'];

    public function __construct($path_to_file, $mode = 'r')
    {
        if (!FileSystem::exists($path_to_file) && self::requires_existing_file($mode)) {
            throw new \Exception('FILE_MUST_ALREADY_EXIST (' . $this->filepath() . ', ' . $this->mode . ')');
        }

        $this->filepath = new FilePath($path_to_file);
        $this->mode = $mode;
    }

    public function open()
    {
        $this->pointer = fopen($this->filepath, $this->mode);
        if ($this->pointer === false) {
            throw new \Exception('FILE_OPEN_FAILURE (' . $this->filepath() . ', ' . $this->mode . ')');
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
            throw new \Exception('FILE_CLOSE_FAILURE (' . $this->filepath() . ', ' . $this->mode . ')');
        }

        return true;
    }

    public function array()
    {
        return file($this->filepath);
    }

    public function set_content($content)
    {
        if (is_writable($this->filepath) !== true && self::requires_existing_file($this->mode)) {
            throw new \Exception('FILE_IS_NOT_WRITABLE (' . $this->filepath() . ', ' . $this->mode . ')');
        }

        $this->open();
        if (fwrite($this->pointer, $content) === false) {
            throw new \Exception('FILE_WRITE_FAILURE (' . $this->filepath() . ', ' . $this->mode . ')');
        }
        $this->close();
    }

    public function filepath()
    {
        return $this->filepath;
    }

    public function has_mime()
    {
        return mime_content_type($this->filepath()); // Returns the content type in MIME format, like text/plain or application/octet-stream, or FALSE on failure.
    }

    public function size()
    {
        $res = $this->size_in_bytes;

        if (is_null($res) && ($res = filesize("$this->filepath")) !== false) {
            $this->size_in_bytes = $res;
        }

        return $res;
    }

    private static function requires_existing_file($mode)
    {
        return in_array($mode, self::$modes_require_existing_file);
    }
}
