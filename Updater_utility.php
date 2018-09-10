<?php

class Updater_utility {

    /**
     * Apakah path ini absolut?
     * @param  string  $path Path yang akan dicek
     * @return boolean TRUE jika memang absolut path, sebaliknya FALSE
     */
    protected static function isAbsolutePath($path) {
        return ('/'==$path[0]||'\\'==$path[0]
            ||(strlen($path)>3&&ctype_alpha($path[0])&&$path[1]==':'&&('\\'==$path[2]||'/'==$path[2]))
        );
    }

    /**
     * Apakah filenya sudah ada?
     * @param  string $file Path ke file yang akan dicek
     * @return boolean TRUE jika memang sudah ada, sebaliknya FALSE
     */
    public static function fileExists($file) {
        if (is_bool($file)||is_array($file))
            throw new \InvalidArgumentException;
        if (strlen($file)>=3&&static::isAbsolutePath($file))
            return file_exists($file);
        return file_exists(static::getScriptDir().$file);
    }

    /**
     * Apakah file merupakan file arsip khusus php (.phar)?
     * @return boolean TRUE jika memang berupa file .phar, sebaliknya FALSE
     */
    public static function isPharFile() {
        return substr(__FILE__,0,7)==='phar://';
    }

    /**
     * Ambil direktori script saat ini
     * @return string String berisi direktori tempat script ini berada
     */
    public static function getScriptDir() {
        return dirname($_SERVER['SCRIPT_FILENAME']).'/';
    }
}
