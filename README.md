# Dashboard Realisasi Fisik Diskominfo Kabupaten Kutai Barat

Aplikasi Laravel read-only untuk menampilkan Dashboard Realisasi Fisik dari Excel Online dan Dashboard APBD lokal. Input dan pembaruan data dilakukan hanya melalui spreadsheet sumber; aplikasi ini tidak menyediakan fitur input, edit, hapus, upload, atau CRUD realisasi.

## Persyaratan

- PHP 8.3 atau lebih baru
- Ekstensi PHP `pdo_mysql` untuk aplikasi dan `pdo_sqlite` untuk menjalankan test
- Composer
- Node.js dan npm
- MariaDB (sesuaikan konfigurasi yang sudah ada pada `.env` bila aplikasi membutuhkannya)

## Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
npm run build
```

Atur koneksi MariaDB pada `.env` sesuai lingkungan Anda. Jangan membagikan atau memasukkan file `.env` ke repository.

## Konfigurasi dashboard

Tambahkan pengaturan berikut ke `.env`:

```env
DASHBOARD_EXCEL_EMBED_URL=
DASHBOARD_EXCEL_SOURCE_URL=
DASHBOARD_APBD_URL=/dashboard-apbd
DASHBOARD_TITLE="Dashboard Realisasi Fisik"
DASHBOARD_AGENCY="Diskominfo Kabupaten Kutai Barat"
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
MICROSOFT_REDIRECT_URI=https://domain-anda.example/auth/microsoft/callback
MICROSOFT_TENANT=consumers
MICROSOFT_WORKBOOK_ITEM_ID=
MICROSOFT_WORKSHEET_NAME=Pivot
```

`DASHBOARD_EXCEL_EMBED_URL` harus berupa tautan embed **HTTPS baca-saja** dari OneDrive atau Microsoft 365 untuk sheet `Dashboard`. Aplikasi menambahkan parameter embed Microsoft untuk membuka Dashboard, menjaga interaktivitas slicer/pivot bila tersedia, dan menyembunyikan tab sheet, grid, header, serta tombol unduh sejauh didukung Excel Online.

`DASHBOARD_EXCEL_SOURCE_URL` bersifat opsional. Bila diisi dengan URL OneDrive/Microsoft yang valid, tombol **Buka Excel Online** akan tampil. Jangan gunakan file `.xlsx` lokal pada iframe dan jangan memindahkan spreadsheet sumber ke `public`.

Konfigurasi `MICROSOFT_*` dipakai server untuk mengambil nilai `Pivot!AC3` dan `Pivot!AI3` melalui Microsoft Graph dengan OAuth Authorization Code delegated. Aplikasi tidak mengirim client secret, item ID, access token, atau refresh token ke browser. Nilai dashboard dicache selama 60 detik.

Untuk menghubungkan OneDrive Personal, daftarkan `MICROSOFT_REDIRECT_URI` sebagai Web redirect URI pada Microsoft Entra, gunakan tenant `consumers`, lalu buka `/auth/microsoft/redirect` menggunakan akun dengan email yang sama dengan `ADMIN_EMAIL`. Aplikasi meminta scope delegated `offline_access Files.ReadWrite openid profile email`; token tersimpan terenkripsi di database dan diperbarui otomatis saat hampir kedaluwarsa.

Setelah embed dipasang, file OneDrive harus selalu file yang sama. Operator memperbarui data melalui Excel Online pada file tersebut, bukan melalui website. Jangan mengunggah file baru untuk setiap perubahan, mengganti nama, memindahkan, atau menghapus file OneDrive. Jika file diganti atau dipindahkan sehingga URL berubah, developer harus memperbarui `.env`.

## Alur pembaruan data

1. Operator membuka workbook OneDrive yang sama.
2. Operator mengubah data pada sheet `Pivot`/`Input`.
3. Jika menambah baris baru, pastikan baris masuk ke Excel Table.
4. Jalankan **Data → Refresh All** apabila dashboard memakai PivotTable.
5. Pastikan grafik `Dashboard` sudah berubah di Excel Online.
6. Buka website dan tekan **Muat Ulang Data**.

Nilai pada website selalu bersumber dari workbook yang sama. Laravel tidak menyimpan salinan Excel. Jika grafik di Excel Online belum berubah, website juga belum dapat menampilkan perubahan tersebut. Baris baru harus termasuk dalam Excel Table/Pivot yang digunakan, dan cache Excel Online dapat menyebabkan pembaruan tampil sedikit terlambat.

## Dashboard APBD

File yang dilayani aplikasi berada pada `storage/app/private/dashboard-apbd/dashboard-apbd.html`; file ini tidak boleh ditempatkan di `public`. Dashboard tersebut mempertahankan CSS, JavaScript, dan data yang tertanam dari file sumber. Untuk memperbaruinya, salin versi HTML baru ke lokasi tersebut dan periksa aset relatif bila ada.

Saat ini data Dashboard APBD tertanam dalam HTML dan belum tersambung otomatis dengan spreadsheet. Pembaruan otomatis hanya terjadi untuk Dashboard Realisasi Fisik apabila iframe menunjuk ke file Excel Online yang sama dan file tersebut diperbarui oleh operator.

## Menjalankan aplikasi lokal

```bash
npm run dev
php artisan serve
```

## Deploy produksi

Di server, gunakan `.env` terpisah berbasis `.env.example`, isi seluruh kredensial dan domain sebenarnya, lalu pastikan `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` dan `MICROSOFT_REDIRECT_URI` memakai HTTPS, serta `SESSION_SECURE_COOKIE=true`. Jangan menyalin `.env` lokal atau menjadikannya bagian dari artefak rilis.

Setelah kode dan environment variables tersedia, jalankan:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan optimize
```

Web server harus mengarahkan document root ke `public/`, menjalankan aplikasi hanya melalui HTTPS, dan memberi hak tulis hanya pada `storage/` serta `bootstrap/cache/`. Jalankan `php artisan queue:work` melalui process manager apabila ada job yang dimasukkan ke queue database.

## Pengujian

```bash
php artisan test
```
