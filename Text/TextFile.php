<?php

namespace HexMakina\LocalFS\Text;

class TextFile extends \HexMakina\LocalFS\File
{

  /**
   * @param int<0, max> $read_length
   */
    public static function identical(string $filepath_1, string $filepath_2, int $read_length = 8192) : bool
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

    /**
     * @param int<0, max> $read_length
     */
    public static function compare_content(string $filepath_1, string $filepath_2, int $read_length = 8192): bool
    {

        $file_1 = new TextFile($filepath_1, 'r');
        $file_2 = new TextFile($filepath_2, 'r');

        if ($file_1->size() !== $file_2->size()) {
            return false;
        }

        $identical = true;
        while (!feof($file_1->pointer()) && $identical === true) {
            $chunk_1 = fread($file_1->pointer(), $read_length);
            $chunk_2 = fread($file_2->pointer(), $read_length);

            if ($chunk_1 === false || $chunk_2 === false) {
                $file_1->close();
                $file_2->close();
                throw new \RuntimeException('fread failure');
            }

            $identical = $chunk_1 === $chunk_2; // must be last loop line
        }

        $file_1->close();
        $file_2->close();

        return $identical;
    }


    public function __toString()
    {
        $ret = file_get_contents($this->filepath());
        if($ret === false)
            $ret = '';

        return $ret;
    }
}
