<?php

namespace HexMakina\LocalFS\Text;

class JSON extends TextFile
{
    /**
     * @param int<1, max> $depth
     */
    public static function to_php(string $string, ?bool $assoc = false, int $depth = 512, int $options = 0)
    {
        // https://www.php.net/manual/en/function.json-decode.php
        $ret = json_decode($string, $assoc, $depth, $options);

        $error = self::hasErrors();
        if ($error !== false) {
            throw new \Exception("ParsingException: $error");
        }

        return $ret;
    }

    /**
     * @param int<1, max> $depth
     */
    public static function from_php($var, $options = 0, $depth = 512): string
    {
        // https://www.php.net/manual/en/function.json-encode.php
        $ret = json_encode($var, $options, $depth);

        $error = self::hasErrors();
        if ($error !== false) {
            throw new \Exception("ParsingException: $error");
        }

        return $ret;
    }

    private static function hasErrors() : bool
    {
        if (json_last_error() === JSON_ERROR_NONE) {
            return false;
        }
        throw new \Exception('ParsingException: '.json_last_error_msg());;
    }
}
