# Kişi Yönetimi

PHP 8.1+ ve PDO MySQL ile hazırlanmış, Türkçe arayüzlü kişi yönetim uygulaması.
Kişiler MySQL'de saklanır; public sayfada yalnızca görünür kişiler yer alır.

## Gereksinimler

- PHP 8.1 veya üzeri; `pdo_mysql` ve `mbstring` eklentileri
- MySQL 8.0+ (veya uyumlu MariaDB)
- Apache ya da Nginx gibi bir web sunucusu

## Kurulum

1. Veritabanını ve tabloları oluşturun:

   ```sh
   mysql -u root -p < database.sql
   ```

2. Örnek yapılandırmayı kopyalayın ve `config.php` içindeki veritabanı ayarlarını düzenleyin:

   ```sh
   cp config.example.php config.php
   ```

   İsterseniz `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` ve `DB_PASSWORD`
   ortam değişkenlerini kullanabilirsiniz. `config.php` Git'e eklenmez.

3. İlk admin kullanıcısını etkileşimli terminalden oluşturun. Şifre terminalde
   gösterilmez ve en az 12 karakter olmalıdır:

   ```sh
   php admin/create-admin.php yonetici
   ```

4. Yerel geliştirme sunucusunu proje kökünde başlatın:

   ```sh
   php -S 127.0.0.1:8000 -t . router.php
   ```

   Uygulama `http://127.0.0.1:8000/`, admin paneli
   `http://127.0.0.1:8000/admin/` adresindedir. Admin ilk girişte şifre
   değişikliğine yönlendirilir.

Üretim ortamında HTTPS kullanın ve web sunucusunun uygulama kökünü belge kökü
olarak ayarlarken `includes/`, `config.php` ve `database.sql` yollarına doğrudan
erişimi engelleyin. Apache için depodaki `.htaccess` dosyaları bu yolları kapatır;
Nginx kullanıyorsanız eşdeğer `deny all` kuralları ekleyin. Yerel PHP sunucusunda
`router.php` aynı hassas yolları engeller.

## Özellikler

- Admin panelinden kişi ekleme, düzenleme, silme, gizleme ve yeniden gösterme
- Admin dashboard'unda toplam, görünür ve gizli kişi sayıları
- Admin listesinde görünür/gizli/tümü filtresi ve ad, soyad, telefon, e-posta araması
- Public listede gizli kayıtları göstermeme
- Her iki listede 10, 25 veya 50 kayıt seçeneği, sayfa numaraları ve önceki/sonraki
- PDO prepared statements, oturum yenileme, CSRF token ve HTML çıktı kaçışlama
