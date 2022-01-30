<?php
/*
	Name: Anti Review Site & Adsense Enabler
	URI: http://arsae.dojo.cc/
	Description: TOS friendly way to legally bypass adsense review site and ad crawler not showing restrictions.
	Version: 2.1.0
	Author: Internet Marketing Dojo
	Author URI: http://www.dojo.com
	License: GPL V3
*/

/*
Changelog:
- 3.0.0: 
+ Double meta refresh untuk mengamankan akun adsense dari limit karena invalid traffic.
+ Refresh delay untuk mengatur lamanya jeda antar refresh
+ konfigurasi alamat server otomatis, tak perlu lagi setting alamat arsae server

- 2.1.0:
Konfigurasi terpusat. Kini ubah konfigurasi cukup di file config.php
+ Auto placement. mode Tak perlu lagi pasang kode iklan maupun placeholder. Cukup kode klien aja. Lebih hemat waktu dan praktis. Bisa diset off jika ingin placement manual. 
+ Integrasi dengan Dojo popunder. Kini ente bisa naikin lagi penghasilan dengan integrasi ARSAE + popunder + floating banner. Bisa kombinasi adsense, adsterra,  ecomobi, aliexpress dll. Sudah ane coba dan alhamdulillah earning naik  berlipat sob! Seperti biasa, semua dengan cara legal.  Sudah ane test dan berhasil payout.
+ Tutorial [[Dobel Earning dari Adsense dan Adsterra]]. Umumnya ketika kita pakai adsense dan adsterra, earning tidak selalu naik dan timbul masalah baru, seperti popunder cenderung menghalangi klik adsense, .atau jenis iklan adsterra dianggap bermasalah. Untuk mengimbangi update di atas ane sediakan ebook tutorial sehingga ente bisa dapat dobel earning adsense dan adsterra tanpa melanggar tos. 
+ Whitelist. Bisa diatur hanya monetize link dengan pola tertentu.

- 2.0.2:
* Bugfix: Pinterest redirect ke server tapi konten bukan konten klien

- 2.0.0:
* InsyaAllah Meningkatkan potensi earning berlipat lagi karena kini arsae bisa deteksi sumber trafik berdasarkan user agent. Artinya kita sekarang bisa nampilin iklan walau user datang dari aplikasi semacam Facebook, Twitter atau Pinterest. Sekarang ente bisa main FB Ads to Adsense juga Pinterest to Adsense walau blog ente ga di approve adsense 😁😁😁.
* Makin hemat biaya server karena tidak memakai database. Kini arsae bisa di hajar lebih banyak trafik daripada dulu. Lebih sedikit makan resource sehingga speed makin cepat. Makin banyak trafik yang bisa di handle = lebih banyak earning =  lebih hemat biaya server.
* Peningkatan kecepatan loading dengan cache halaman klien. Kecepatan adalah hal yang sangat penting untuk adsense, maka dengan adanya cache, user bisa loading lebih cepat untuk halaman yang sering dikunjungi.
* Server support Multi PHP Platform. Dulu, arsae support monetize hampir semua jenis website dengan syarat, kita harus ada WordPress yang approved sebagai server. Kini di versi terbaru, dia tetap bisa monetize hampir semua jenis web, namun lebih jauh lagi, sekarang webserver ga harus WordPress, melainkan support semua jenis script PHP seperti shuriken, Drupal, dan script lainnya, asal PHP.  Jadi jika ente ada situs yang approved tapi bukan wp, selama dia jalan di bahasa PHP, arsae bisa jadikan dia sumber duit InsyaaAllah. 
* Bugfix: Notice: Only variables should be passed by reference in helpers/functions.php on line 173

- 0.2.0:
* Enable Automatic ad insertion by ads folder
ads/auto.txt => <!--ads/auto.txt-->
* Bugfix: Never convert static link to arsae
*/
define("WP_ARSAE_PATH", dirname(__FILE__));
require "helpers/functions.php";
wp_arsae_start_session();

define("WP_ARSAE_SESSION_NAME", "wp_arsae_display");

if (isset($_GET["arsae"]) || isset($_SESSION[WP_ARSAE_SESSION_NAME])) {
    require "vendor/autoload.php";
    wp_arsae_set_session();

    if (arsae_config()->anti_invalid_traffic) {
        arsae_meta_refresh();
    }

    wp_arsae_forget_session();
}
