# Rahmani EduPay

Sistem Pembayaran Sekolah Digital yang terintegrasi dengan Midtrans untuk memudahkan orang tua dalam melakukan pembayaran tagihan sekolah.

## Fitur Utama

### Admin Panel
- **Dashboard Admin**: Statistik lengkap pembayaran, tagihan, dan pendapatan
- **Manajemen Orang Tua**: CRUD data orang tua siswa
- **Manajemen Siswa**: CRUD data siswa dengan relasi ke orang tua
- **Manajemen Tagihan**: Buat, edit, dan kelola tagihan siswa
- **Manajemen Pembayaran**: Monitor dan verifikasi pembayaran
- **Pengumuman**: Buat dan kelola pengumuman untuk orang tua

### Portal Orang Tua
- **Dashboard Orang Tua**: Ringkasan tagihan dan pembayaran anak
- **Daftar Tagihan**: Lihat semua tagihan dengan filter status
- **Pembayaran Online**: Integrasi dengan Midtrans (Credit Card, Bank Transfer, E-Wallet)
- **Upload Bukti Pembayaran**: Untuk pembayaran manual
- **Riwayat Pembayaran**: Histori lengkap pembayaran
- **Pengumuman**: Lihat pengumuman dari sekolah

## Teknologi yang Digunakan

- **Framework**: Laravel 11
- **Frontend**: Blade Templates + Tailwind CSS
- **Database**: MySQL
- **Payment Gateway**: Midtrans
- **Authentication**: Laravel Breeze

## Instalasi

### Prasyarat
- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL

### Langkah Instalasi

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd rahmani-edupay
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Configuration**
   Edit file `.env` dan sesuaikan konfigurasi database:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rahmani_edupay
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Midtrans Configuration**
   Daftar di [Midtrans](https://midtrans.com) dan dapatkan Server Key & Client Key:
   ```env
   MIDTRANS_SERVER_KEY=your_midtrans_server_key
   MIDTRANS_CLIENT_KEY=your_midtrans_client_key
   MIDTRANS_IS_PRODUCTION=false
   ```

6. **Database Migration**
   ```bash
   php artisan migrate
   ```

7. **Build Assets**
   ```bash
   npm run build
   ```

8. **Run Application**
   ```bash
   php artisan serve
   ```

   Aplikasi akan berjalan di `http://localhost:8000`

## Struktur Database

### Tabel Utama
- **users**: Data pengguna (admin & orang tua)
- **siswa**: Data siswa dengan relasi ke orang tua
- **tagihan**: Data tagihan siswa
- **pembayaran**: Data pembayaran dengan integrasi Midtrans
- **pengumuman**: Data pengumuman dari admin

### ERD (Entity Relationship Diagram)
```
users (1) -----> (N) siswa (1) -----> (N) tagihan (1) -----> (N) pembayaran
users (1) -----> (N) pengumuman
```

## Penggunaan

### Akses Admin
1. Buat user admin melalui seeder atau manual di database
2. Set `role = 'admin'` pada tabel users
3. Login dan akses `/admin/dashboard`

### Akses Orang Tua
1. Admin membuat akun orang tua melalui panel admin
2. Orang tua login dengan email dan password yang diberikan
3. Akses `/ortu/dashboard`

### Flow Pembayaran
1. Admin membuat tagihan untuk siswa
2. Orang tua melihat tagihan di dashboard
3. Orang tua memilih metode pembayaran (Midtrans atau Manual)
4. Untuk Midtrans: redirect ke payment gateway
5. Untuk Manual: upload bukti pembayaran
6. Admin verifikasi pembayaran manual
7. Status tagihan otomatis update

## API Endpoints

### Webhook Midtrans
- `POST /payment/webhook` - Menerima notifikasi dari Midtrans

### Payment Redirects
- `GET /payment/finish` - Redirect setelah pembayaran berhasil
- `GET /payment/unfinish` - Redirect pembayaran belum selesai
- `GET /payment/error` - Redirect pembayaran error

## Keamanan

- **CSRF Protection**: Semua form dilindungi CSRF token
- **Role-based Access**: Middleware untuk memisahkan akses admin dan orang tua
- **Input Validation**: Validasi ketat pada semua input
- **Webhook Verification**: Verifikasi signature Midtrans

## Kontribusi

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## License

Project ini menggunakan [MIT License](https://opensource.org/licenses/MIT).

## Support

Untuk pertanyaan atau dukungan, silakan buat issue di repository ini.
