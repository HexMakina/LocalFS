<?php

namespace HexMakina\LocalFS\Text;

class JSON extends TextFile
{

    public static function to_php($string, $assoc = false, $depth = 512, $options = 0)
    {
        $ret = json_decode($string, $assoc, $depth, $options);

        $error = self::has_errors();
        if ($error !== false) {
            throw new \Exception("ParsingException: $error");
        }

        return $ret;
    }

    public static function from_php($var, $options = 0, $depth = 512)
    {
        $ret = json_encode($var, $options, $depth);

        $error = self::has_errors();
        if ($error !== false) {
            throw new \Exception("ParsingException: $error");
        }

        return $ret;
    }

    private static function has_errors()
    {
        if (json_last_error() === JSON_ERROR_NONE) {
            return false;
        }

        return json_last_error_msg();
    }
}
