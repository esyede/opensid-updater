# opensid-updater
Update script OpenSID langsung dari GitHub repo


## Apa ini?
Script ini dimaksudkan untuk menambahkan fitur update (upgrade) script OpenSID langsung dari github release milik repositori resminya.


## Status
Script ini masih dalam pengembangan. Jangan dulu dipakai di server produksi!


## Cara kerja
Script ini mengambil rilis terbaru dari repo github dengan catatan:
  1. Jika saat script ini dijaalankan dan belum terdapat file cache, dia akan langsung mengunduh veri terbaru dan mengekstraknya ke webserver anda, juga akan dibuat file cache secara otomatis.
  2. Jika file cache sudah ada, dia akan membandingkn Tag versi rilis pada cache dengan data dari repo github sehingga user dapat memilih untuk melanjutkan update atau mebatalknnya. Jika update dilanjutkan, file lama di disk akan ditimpa dengan file baru hasil unduhan.
  3. Jika file `.skipped` disertakann, maka daftar nama-nama file yang sesuai dengan yang ada didalam file `.skipped` ini akan diabaikan (tidak ditimpa), tujuannya untuk mengamankan file-file konfigurasi milik user.


## Kekurangan script ini
  1. Belum ada progess bar untuk indikasi
  2. Lama waktu update tergantung koneksi internet serta prosesor di server
  3. Mungkin akan beresiko terjadi file corrupt jika koneksi lambat (dan memang belum ada fungsi checksum di script ini)


## Cara penggunaan
Sudah disertakan file `test.php` yang dapat anda jalankan untuk ikut mencoba.

Isi file `test.php` tersebut kurang lebih seperti ini:
```php
<?php
require_once 'Updater_cache.php';
require_once 'Updater_utility.php';
require_once 'Updater.php';

$updater = new Updater(array(
    'repository' => 'vlucas/valitron', // Repository yang akan dipantau
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
    echo '<h3>Anda Masih Menggunakan Versi Paling Mutakhir!</h3>';
}
```


## Referensi Bacaan
  1. Repositori Resmi OpenSID: https://github.com/OpenSID/OpenSID
  2. GitHub Release API: https://developer.github.com/v3/repos/releases
  3. CA Certificate Bundle: https://curl.haxx.se/docs/caextract.html
  (untuk pembaruan sertifikat)


### Ikut Membantu
Seperti biasa, silahkan pull request ke repo ini untuk menyalurkan bantuan anda.