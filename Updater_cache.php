<?php

class Updater_cache
{
    protected
        $filename=NULL,
        $expiry=43200; // 12 jam

    /**
     * Konstruktor
     * @param string  $file   Nama file cache
     * @param integer $expiry Lamanya waktu cache sampai dianggap kadaluarsa
     */
    public function __construct($file,$expiry=43200) {
        $this->filename=$file;
        $this->expiry=$expiry;
    }

    /**
     * Apakah cache sudah ada?
     * @return boolean TRUE jika sudah ada, sebaliknya FALSE
     */
    public function isCached() {
        if (!Updater_utility::fileExists($this->filename))
            return FALSE;
        clearstatcache();
        if (filemtime($this->filename)<(time()-$this->expiry)) {
            unlink($this->filename);
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Tulis konten cache ke disk (Paksa agar membuat direktori/file jika belum ada)
     * @return void
     */
    protected function fileForceContents() {
        $args=func_get_args();
        $path=str_replace(array('/','\\'),DIRECTORY_SEPARATOR,$args[0]);
        $parts=explode(DIRECTORY_SEPARATOR,$path);
        array_pop($parts);
        $dir='';
        foreach ($parts as $part) {
            $checkpath=$dir.$part;
            if (is_dir($checkpath.DIRECTORY_SEPARATOR)===FALSE)
                mkdir($checkpath,0755);
            $dir=$checkpath.DIRECTORY_SEPARATOR;
        }
        call_user_func_array('file_put_contents',$args);
    }

    /**
     * Ambil data cache
     * @return string
     */
    public function get() {
        return file_get_contents($this->filename);
    }

    /**
     * Simpan data cache
     * @param void
     */
    public function set($content) {
        $this->fileForceContents($this->filename,$content);
    }
}
