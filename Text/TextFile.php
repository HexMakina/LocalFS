<?php

namespace HexMakina\LocalFS\Text;

class TextFile extends \HexMakina\LocalFS\File
{

    public static function identical($filepath_1, $filepath_2, $read_length = 8192)
    {
      //** TEST FOR EXISTENCE
        if (!file_exists($filepath_1) || !file_exists($filepath_2)) {
            throw new \Exception('file_exists false');
        }

      //** TEST FOR SYMLINK
        $filepath_1 = self::resolve_symlink($filepath_1);
        $filepath_2 = self::resolve_symlink($filepath_2);

      //** TEST FOR IDENTICAL TYPE AND SIZE
        if (filetype($filepath_1) !== filetype($filepath_2) || filesize($filepath_1) !== filesize($filepath_2)) {
            return false;
        }

      //** TEST FOR IDENTICAL CONTENT
        return self::compare_content($filepath_1, $filepath_2, $read_length);
    }

    public static function compare_content($filepath_1, $filepath_2, $read_length = 8192) : bool
    {

        $file_1 = new TextFile($filepath_1, 'r');
        $file_2 = new TextFile($filepath_2, 'r');

        if ($file_1->size() !== $file_2->size()) {
            return false;
        }

        $filepointer_1 = $file_1->open();
        $filepointer_2 = $file_2->open();

        $identical = true;
        while (!feof($filepointer_1) && $identical === true) {
            $chunk_1 = fread($filepointer_1, $read_length);
            $chunk_2 = fread($filepointer_2, $read_length);

            if ($chunk_1 === false || $chunk_2 === false) {
                $file_1->close();
                $file_2->close();
                throw \RuntimeException('fread returned false');
            }

            if ($chunk_1 !== $chunk_2) {
                $identical = false;
            }
        }

        $file_1->close();
        $file_2->close();

        return $identical;
    }


    public function __toString()
    {
        return file_get_contents($this->filepath());
    }
}
