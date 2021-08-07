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

    public static function compare_content($filepath_1, $filepath_2, $read_length = 8192)
    {
        try {
            $file_1 = new TextFile($filepath_1, 'r');
            $filepointer_1 = $file_1->open();

            $file_2 = new TextFile($filepath_2, 'r');
            $filepointer_2 = $file_2->open();

            $identical = true;
            while (!feof($filepointer_1) && !feof($filepointer_2) && $identical) {
                if (false === ($buffer_1 = fread($filepointer_1, $read_length)) || false === ($buffer_2 = fread($filepointer_2, $read_length))) {
                    throw new \Exception('FAILED fread('.$filepath_1.' or '.$filepath_2.')');
                }

                $identical = (0 === strcmp($buffer_1, $buffer_2));
            }

            if ($identical === true && feof($filepointer_1) !== feof($filepointer_2)) {
                $identical = false;
            }
        } catch (\Exception $e) {
            return false;
        } finally {
            $file_1->close();
            $file_2->close();
        }

        return $identical;
    }


    public function __toString()
    {
        return file_get_contents($this->filepath());
    }
}
