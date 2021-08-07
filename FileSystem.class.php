<?php

namespace HexMakina\LocalFS;

class FileSystem
{

    private $root_path = null;

    function __construct($root_path) // create a filesystem to handle directory work
    {
        $this->root_path = $root_path;
    }

    public function path_to($filename)
    {
        return $this->root_path.'/'.$filename;
    }

    public function filenames($regex = null) : array
    {
        if (!file_exists($this->root_path) && mkdir($this->root_path) === false) {
            return [];
        }

        $filenames = self::preg_scandir($this->root_path, $regex);// ID_SEQUENCENUMBER.ext
        if (!is_null($filenames)) {
            sort($filenames);
        }

        return $filenames;
    }

    public function filepathes($regex = null) : array
    {
        $filenames = $this->filenames($regex);
        $filepathes = [];
        foreach ($filenames as $filename) {
            $filepathes[] = $this->path_to($filename);
        }

        return $filepathes;
    }


  // kontrolas ĉu la dosiero aŭ dosierujo ekzistas
    public static function exists($src_path)
    {
        return file_exists($src_path);
    }
    public static function is_file($src_path)
    {
        return is_file($src_path);
    }
    public static function is_dir($src_path)
    {
        return is_dir($src_path);
    }
    public static function is_link($src_path)
    {
        return is_link($src_path);
    }


    public static function resolve_symlink($path)
    {
        if (is_link($path)) {
            $res = readlink($path);
            if ($res === false) {
                throw new \Exception('readlink failed on '.$path);
            }

            $path = $res;
        }
        return $path;
    }

    public static function copy($src_path, $dst_path)
    {
        if (self::exists($src_path) && self::is_file($src_path)) {
            $dst = new FilePath($dst_path);
            if (self::exists($dst->dir()) && self::is_dir($dst->dir())) {
                return copy($src_path, $dst_path); // Returns TRUE on success or FALSE on failure.
            }

          // vd(__FUNCTION__.'ERR: self::exists('.$dst->dir().') && self::is_dir('.$dst->dir().')');
        }
      // vd(__FUNCTION__."ERR: self::exists($src_path) && self::is_file($src_path)")
        return false;
    }

    public static function move($src_path, $dst_path)
    {
        if (self::exists($src_path) && self::is_file($src_path)) {
            $dst = new FilePath($dst_path);
            if (self::exists($dst->dir()) && self::is_dir($dst->dir())) {
                return rename($src_path, $dst_path); // Returns TRUE on success or FALSE on failure.
            }

          // vd(__FUNCTION__.' ERR: self::exists('.$dst->dir().') && self::is_dir('.$dst->dir().')');
        }
      // vd(__FUNCTION__."ERR: self::exists($src_path) && self::is_file($src_path)")

        return false;
    }


    public static function make_dir($dst_path, $permission = 0777, $recursive = true) : bool
    {
        return mkdir($dst_path, $permission, $recursive);
    }

    public static function remove($src_path, $follow_link = false)
    {
        $ret = false;
        if (self::exists($src_path)) {
            if (self::is_link($src_path) && $follow_link === true) {
                $ret = self::remove(readlink($src_path));
            } elseif (self::is_file($src_path)) {
                $ret = unlink($src_path); // Returns TRUE on success or FALSE on failure.
            } elseif (self::is_dir($src_path)) {
                $ret = rmdir($src_path); // Returns TRUE on success or FALSE on failure.
            }
        }
        return $ret;
    }

    public static function preg_scandir($dir_path, $regex = null)
    {
        if (!self::exists($dir_path) || !self::is_dir($dir_path)) {
            return null;
        }

        if (($filenames = scandir($dir_path, SCANDIR_SORT_ASCENDING)) !== false) {
            return is_null($regex)? $filenames : preg_grep($regex, $filenames);
        }

        throw new \Exception("directory path '$dir_path' cannot be scanned");
    }

  // TODO
  // readdir() - Read entry from directory handle
  // chdir() - Change directory
  // dir() - Return an instance of the Directory class
  // opendir() - Open directory handle

    public static function server_info()
    {
        return 'PHP '.PHP_VERSION.' | OS '.PHP_OS_FAMILY.' ('.PHP_OS.') | SAPI '.PHP_SAPI.' | GENESIS '.date('Y-m-d h:i:s');
    }
}
