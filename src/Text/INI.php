<?php

namespace HexMakina\LocalFS\Text;

class INI extends TextFile
{

    public static function from_array(array $array) : string
    {
        $ret = '# INI Dump';

        if (is_array(current($array))) { // with sections
            foreach ($array as $section => $data) {
                $ret .= PHP_EOL . PHP_EOL . self::section($section);
                foreach ($data as $key => $value) {
                    $ret .= PHP_EOL . self::line($key, $value);
                }
            }
        } else { // no section
            foreach ($array as $key => $value) {
                $ret .= self::line($key, $value);
            }
        }

        return $ret;
    }

    public static function to_array(string $filepath, bool $with_sections = true, int $mode = INI_SCANNER_RAW) : ?array
    {
        // https://secure.php.net/manual/en/function.parse-ini-file.php
        $ret = parse_ini_file($filepath, $with_sections, $mode);
        if($ret === false)
            return null;

        return $ret;
    }


    private static function section($key) : string
    {
        $key = self::format_key($key);
        return "[$key]";
    }

    private static function line($key, $val) : string
    {
        $key = self::format_key($key);
        $val = self::format_key($val);
        return "$key=\"$val\"";
    }

  // private static function format_value($value)
  // {
  //   return str_replace('"', '\"',$value);
  // }

    private static function format_key($key)
    {
        return str_replace(' ', '_', $key);
    }
}
