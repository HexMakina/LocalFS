<?php

namespace HexMakina\LocalFS;

class FilePath
{
    private $filepath = null;

    private $already_parsed = false;

    private $directories = null;
    private $file = null;
    private $file_extension = null;

    public function __construct($filepath)
    {
        $this->filepath = $filepath;
    }

    public function __toString()
    {
        return ''.$this->filepath;
    }

    public function dir() : string
    {
        return $this->parse()->directories;
    }

    public function file() : string
    {
        return $this->parse()->file;
    }

    public function ext() : string
    {
        return $this->parse()->file_extension;
    }

    private function parse()
    {
        if ($this->already_parsed === false) {
            $res = pathinfo($this->filepath);

            $this->directories = $res['dirname'];
            $this->file = $res['basename'];
            $this->file_extension = $res['extension'];

            $this->already_parsed=true;
        }
        return $this;
    }
}

// mime_content_type() - Detect MIME Content-type for a file
// stat()
