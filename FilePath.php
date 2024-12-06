<?php

namespace HexMakina\LocalFS;

class FilePath
{
    private $filepath = null;

    private $directories = null;
    private $file = null;
    private $file_extension = null;

    private $already_parsed = false;

    public function __construct($filepath)
    {
        $this->filepath = $filepath;
    }

    public function __toString()
    {
        return '' . $this->filepath;
    }

    public function dir(): string
    {
        $this->parse();
        return $this->directories;
    }

    public function file(): string
    {
        $this->parse();
        return $this->file;
    }

    public function ext(): string
    {
        $this->parse();
        return $this->file_extension;
    }

    private function parse(): FilePath
    {
        if ($this->already_parsed === false) {
            $res = pathinfo($this->filepath);

            $this->directories = $res['dirname'];
            $this->file = $res['basename'];
            $this->file_extension = $res['extension'];

            $this->already_parsed = true;
        }

        return $this;
    }
}

// mime_content_type() - Detect MIME Content-type for a file
// stat()
