<?php
require_once 'Updater_cache.php';
require_once 'Updater_utility.php';
require_once 'Updater.php';

$updater = new Updater(array(
    'repository' => 'OpenSID/OpenSID', // Repository yang akan dipantau
    'branch' => 'master',              // Branch mana yang ingin dipantau?
    'prerelease' => FALSE,             // Gunakan pre-rilis?
    'saveto' => './',                  // Tempat menyimpan file update
    'skipped' => '.skipped',           // Nama - nama file yang akan di skip saat update (taruh perbaris di file ini)
    'version' => 'version.json',       // Nama file tempat menyimpan versi app terinstall
    'zipball' => 'zipball.zip',        // Nama file zip sementara
    'cachefile' => 'cache.json',       // Nama file cache
    'cachedir' => 'mycache/',          // Folder tempat menyimpan file - file cache
    'expiry' => 43200,                 // Waktu kadaluarsa cache
    'exceptions' => TRUE,              // Tampilkan pesan exception?
));


if ($updater->isAbleToUpdate()) {
    if (isset($_GET['mutakhirkan'])) {
            $updater->doUpdate();
        echo '<h3>Aplikasi Berhasil Dimutakhirkan!</h3>';
    }
    else {
        $newest = $updater->getNewestInfo();
        echo '<h2>Versi Mutakhir Saat Ini: '.$newest['tag_name'].'</h2>';
        echo '<h3>Catatan Perubahan:</h3>';
        echo nl2br($newest['body']);
        echo '<br>---------------------------------------<br>';
        echo '<a href="?mutakhirkan">MUTAKHIRKAN</a>';
    }
}
else {
    echo '<h3>Anda Menggunakan Versi Paling Mutakhir!</h3>';
}