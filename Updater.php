<?php

class Updater {

    protected
        // Stream context
        $stream=NULL,
        // Array rilis
        $releases=array(),
        // Opsi konfigurasi default
        $options=array(
            'repository'=>'',
            'set_time_limit'=>3600, // 1 jam
            'cachefile'=>'cache.json',
            'expiry'=>43200, // 12 jam
            'version'=>'version.json',
            'zipball'=>'zipball.zip',
            'skipped'=>'updater.skip',
            'branch'=>'master',
            'cachedir'=>'cache/',
            'saveto'=>'test/',
            'prerelease'=>FALSE,
            'exceptions'=>FALSE,
        );

        const
            // Pesan - pesan error internal pustaka
            E_NO_REPO='Repositori tujuan belum diatur',
            E_NO_CONFIG='Pustaka belum dikonfigurasi',
            E_NO_HTTPS_WRAPPER='Tidak ditemukan wrapper https',
            E_API_EXCEPTION='Kesalahan API: %s',
            E_DOWNLOAD_FAILED='Gagal mengunduh file arsip dari repositori',
            E_NO_INTERNET='Tidak ada koneksi internet',
            E_NO_SUITABLE_RELEASE='Tidak ada rilis yang cocok dengan konfigurasi yang diberikan';

    /**
     * Konstruktor
     * @param array|string $configs Konfigurasi library updater
     */
    public function __construct($configs) {
        if (is_array($configs)) {
            if (!isset($configs['repository'])||empty($configs['repository']))
                throw new \Exception(self::E_NO_REPO);
            $this->options=$configs+$this->options;
        }
        elseif (is_string($configs)) {
            if (empty($configs))
                throw new \Exception(self::E_NO_REPO);
            $this->options['repository']=$configs;
        }
        else throw new \Exception(self::E_NO_CONFIG);

        $this->options['saveto']=rtrim($this->options['saveto'],'/');
        if ($this->options['saveto']!=='') {
            $this->options['saveto'].='/';
            if (!Updater_utility::fileExists($this->options['saveto']))
                mkdir($this->options['saveto']);
        }

        $this->options['cachedir']=$this->options['saveto'].rtrim($this->options['cachedir'],'/');
        if ($this->options['cachedir']!=='') {
            $this->options['cachedir'].='/';
            if (!Updater_utility::fileExists($this->options['cachedir']))
                mkdir(Updater_utility::getScriptDir().$this->options['cachedir']);
        }
        $certdir=dirname(__FILE__);
        if (Updater_utility::isPharFile()) {
            $certdir=Updater_utility::getScriptDir().$this->options['cachedir'];
            if (!Updater_utility::fileExists($this->options['cachedir'].'cacert.pem'))
                copy(dirname(__FILE__).'/cacert.pem',$certdir.'cacert.pem');
        }

        $this->cache=new Updater_cache(
            Updater_utility::getScriptDir().$this->options['cachedir'].$this->options['cachefile'],
            $this->options['expiry']
        );

        $this->stream=stream_context_create(
            array(
                'http'=>array('header'=>"User-Agent: OpenSID-Updater\r\nAccept: application/vnd.github.v3+json\r\n"),
                'ssl'=>array('cafile'=>$certdir.'/cacert.pem','verify_peer'=>TRUE),
            )
        );
        $this->stream2=stream_context_create(
            array(
                'http'=>array('header'=>"User-Agent: OpenSID-Updater\r\n"),
                'ssl'=>array('cafile'=>$certdir.'/cacert.pem','verify_peer'=>TRUE),
            )
        );
        $this->releases=$this->getRemoteInfos();
    }

    /**
     * Ambil info detail dari repo di github
     * @return array Array asosiatif berisi info repo
     */
    protected function getRemoteInfos() {
        $path='https://api.github.com/repos/'.$this->options['repository'].'/releases';
        if ($this->cache->isCached())
            $content=$this->cache->get();
        else {
            if (!in_array('https',stream_get_wrappers())) {
                if ($this->options['exceptions'])
                    throw new \Exception(self::E_NO_HTTPS_WRAPPER);
                else return array();
            }
            $content=@file_get_contents($path,FALSE,$this->stream);
            if ($content===FALSE) {
                if ($this->options['exceptions'])
                    throw new \Exception(self::E_NO_INTERNET);
                else return array();
            }
            $json=json_decode($content,TRUE);
            if (isset($json['message'])) {
                if ($this->options['exceptions'])
                    throw new \Exception(sprintf(self::E_API_EXCEPTION,$json['message']));
                else $json=array();
            }
            $content=json_encode($json,defined('JSON_PRETTY_PRINT')?JSON_PRETTY_PRINT:0);
            $this->cache->set($content);
            return $json;
        }
        return json_decode($content,TRUE);
    }

    /**
     * Apakah bisa melakukan update?
     * @return boolean TRUE jika bisa, sebaliknya FALSE
     */
    public function isAbleToUpdate() {
        if (!in_array('https',stream_get_wrappers())||empty($this->releases))
            return FALSE;
        $this->getNewestInfo();
        if (Updater_utility::fileExists($this->options['cachedir'].$this->options['version'])) {
            $content=file_get_contents(
                Updater_utility::getScriptDir().$this->options['cachedir'].$this->options['version']
            );
            $current=json_decode($content,TRUE);
            if ((isset($current['id'])&&$current['id']==$this->newestInfo['id'])
           ||(isset($current['tag_name'])&&$current['tag_name']==$this->newestInfo['tag_name']))
                return FALSE;
        }
        return TRUE;
    }

    /**
     * Lakukan proses update
     * @return boolean TRUE jika berhasil, sebaliknya FALSE
     */
    public function doUpdate() {
        set_time_limit($this->options['set_time_limit']);
        // TODO: Tambahkan progress bar agar tahu progress updatenya sampai mana.
        echo 'Update ini memakan waktu lama, tergantung ukuran file di github, sabar...';

        $newestRelease=$this->getNewestInfo();
        if ($this->isAbleToUpdate()) {
            if ($this->download($newestRelease['zipball_url'])) {
                if ($this->unZip()) {
                    unlink(Updater_utility::getScriptDir().$this->options['cachedir'].$this->options['zipball']);
                    file_put_contents(
                        Updater_utility::getScriptDir().$this->options['cachedir'].$this->options['version'],
                        json_encode(
                            array('id'=>$newestRelease['id'],'tag_name'=>$newestRelease['tag_name']),
                            defined('JSON_PRETTY_PRINT')?JSON_PRETTY_PRINT:0
                        )
                    );
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    /**
     * Download file ZIP dari repo di github
     * @param  string $url URL file utuh dari github (file ZIP)
     * @return boolean TRUE jika berhasil mendownload file, sebaliknya FALSE
     */
    protected function download($url) {
        $file=@fopen($url,'r',FALSE,$this->stream2);
        if ($file==FALSE) {
            if ($this->options['exceptions'])
                throw new \Exception(self::E_DOWNLOAD_FAILED);
            else return FALSE;
        }

        file_put_contents(
            Updater_utility::getScriptDir().$this->options['cachedir'].$this->options['zipball'],
            $file
        );
        fclose($file);
        return TRUE;
    }

    /**
     * Ekstrak file ZIP
     * @return boolean TRUE jika file berhasil diestrak, sebaliknya FALSE
     */
    protected function unZip() {
        $path=Updater_utility::getScriptDir().$this->options['cachedir'].$this->options['zipball'];
        $ignores=array();
        if (Updater_utility::fileExists($this->options['skipped'])) {
            $ignores=file($this->options['skipped']);
            foreach ($ignores as &$ignore)
                $ignore=$this->options['saveto'].trim($ignore);
        }
        $zip=new \ZipArchive();
        if ($zip->open($path)===TRUE) {
            $maxlen=strlen($zip->getNameIndex(0));
            for ($i=1;$i<$zip->numFiles;$i++) {
                $name=$this->options['saveto'].substr($zip->getNameIndex($i),$maxlen);
                $do=TRUE;
                foreach ($ignores as $ignore) {
                    if (substr($name,0,strlen($ignore))==$ignore) {
                        $do=FALSE;
                        break;
                    }
                }
                if ($do) {
                    $stat=$zip->statIndex($i);
                    if ($stat['crc']==0) {
                        if (!Updater_utility::fileExists($name))
                            mkdir(Updater_utility::getScriptDir().$name);
                    }
                    else copy('zip://'.$path.'#'.$zip->getNameIndex($i),Updater_utility::getScriptDir().$name);
                }
            }
            $zip->close();
            return TRUE;
        }
        else return FALSE;
    }

    /**
     * Ambil info app di versi yang saat ini terinstall (ambil dari cache)
     * @return void
     */
    public function getCurrentInfo() {
        if (isset($this->currentInfo))
            return $this->currentInfo;
        $this->currentInfo=NULL;
        if (Updater_utility::fileExists($this->options['cachedir'].$this->options['version'])) {
            $content=file_get_contents(
                Updater_utility::getScriptDir().$this->options['cachedir'].$this->options['version']
            );
            $current=json_decode($content,TRUE);
            foreach ($this->releases as $release) {
                if ((isset($current['id'])&&$current['id']==$release['id'])
               ||(isset($current['tag_name'])&&$current['tag_name']==$release['tag_name'])) {
                    $this->currentInfo=$release;
                    break;
                }
            }
        }
        return $this->currentInfo;
    }

    /**
     * Ambil info app versi terbaru (dari repo di github)
     * @return void
     */
    public function getNewestInfo() {
        if (isset($this->newestInfo))
            return $this->newestInfo;
        foreach ($this->releases as $release) {
            if ((!$this->options['prerelease']&&$release['prerelease'])
           ||($this->options['branch']!==$release['target_commitish']))
                continue;
            $this->newestInfo=$release;
            break;
        }
        if (!isset($this->newestInfo)) {
            if ($this->options['exceptions'])
                throw new \Exception(self::E_NO_SUITABLE_RELEASE);
            else return array();
        }
        return $this->newestInfo;
    }
}
