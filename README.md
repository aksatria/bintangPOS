# 🌟 BINTANG POS - Sistem Point of Sale Untuk UMKM Indonesia

Solusi POS modern, cepat, dan user-friendly khusus untuk bisnis retail dan UMKM Indonesia. Dibangun dengan Laravel 12, mendukung multi-branch, dan dilengkapi dengan sistem manajemen lengkap (inventory, customer, supplier, laporan).

 **[Dokumentasi](./docs)** | **[Issues](https://github.com/aksatria/bintangPOS/issues)**

---

## ✨ Fitur Utama

### 🏪 **Point of Sale**
- ✅ Interface kasir yang intuitif dan responsif
- ✅ Search produk real-time (nama, SKU, barcode)
- ✅ Keranjang belanja dengan perhitungan otomatis
- ✅ Support 5 metode pembayaran (Cash, QRIS, Debit, Transfer, E-Wallet)
- ✅ Split payment (pembayaran ganda dalam 1 transaksi)
- ✅ Hold transaksi (simpan sementara & lanjutkan kemudian)
- ✅ Keyboard shortcut untuk kasir pro (F2, F4, Ctrl+Enter, dll)
- ✅ Auto-print struk & receipt PDF
- ✅ Validasi stok real-time

### 👥 **Manajemen Pelanggan**
- ✅ Database pelanggan dengan contact tracking
- ✅ Riwayat pembelian otomatis tersimpan
- ✅ Auto-complete customer saat checkout
- ✅ Customer follow-up management
- ✅ Tracking customer retention & repeat purchase

### 💳 **Customer Debt Tracking**
- ✅ Manajemen hutang pelanggan (piutang)
- ✅ Payment plan & cicilan otomatis
- ✅ Payment term tracking (tanggal jatuh tempo)
- ✅ Multi-branch debt visibility (hutang global)
- ✅ Debt collection reminder & alerts
- ✅ Export hutang per customer

### 🏭 **Supplier Management**
- ✅ Database supplier lengkap
- ✅ Purchase order system
- ✅ Goods receipt & tracking
- ✅ Supplier invoice management
- ✅ Payment tracking & payment terms
- ✅ Supplier performance analytics

### 📦 **Inventory Management**
- ✅ Manajemen produk lengkap (CRUD)
- ✅ Kategori produk dengan unlimited level
- ✅ Stock tracking real-time
- ✅ Low stock alerts & notifications
- ✅ Stock adjustment & opname
- ✅ Stock transfer antar branch (multi-branch)
- ✅ SKU & barcode support

### 📊 **Dashboard & Reporting**
- ✅ Dashboard owner dengan KPI real-time
- ✅ Omzet hari ini vs kemarin
- ✅ Top selling products
- ✅ Laporan penjualan (harian/mingguan/bulanan/custom)
- ✅ Laporan biaya & laba
- ✅ Expense tracking
- ✅ Export Excel & PDF
- ✅ Ringkasan shift per kasir

### 🏢 **Multi-Branch Support**
- ✅ Support multiple lokasi/cabang
- ✅ Central reporting dashboard
- ✅ Centralized customer database
- ✅ Global debt visibility
- ✅ Stock transfer antar branch
- ✅ Per-location analytics

### 🔐 **Security & Audit**
- ✅ Role-based access control (Owner, Admin, Kasir)
- ✅ Comprehensive audit log
- ✅ Approval request system
- ✅ User activity tracking
- ✅ IP address & device logging
- ✅ Anti-manipulasi data

### 🔧 **Admin Panel**
- ✅ Filament admin panel
- ✅ Easy CRUD untuk semua data
- ✅ Pengaturan toko (nama, alamat, logo, WhatsApp)
- ✅ User management
- ✅ Permission management
- ✅ Bulk operations

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel 12, PHP 8.2+ |
| **Database** | MySQL 8.0+ |
| **Frontend** | Blade Templates, Alpine.js, Tailwind CSS |
| **Admin Panel** | Filament 5 |
| **PDF Generation** | DomPDF |
| **Excel Export** | Maatwebsite Excel |
| **Authentication** | Laravel Breeze |
| **Testing** | Pest PHP |
| **Queue** | Redis (Optional) |
| **Real-time** | Laravel Echo + Websocket (Optional) |

---

## 📋 Requirements

- **PHP** >= 8.2
- **MySQL** >= 8.0 atau **MariaDB** >= 10.3
- **Composer** >= 2.0
- **Node.js** >= 16 & **NPM** >= 8
- **Git**

---

## 🚀 Quick Start

### 1. Clone Repository
```bash
git clone https://github.com/aksatria/bintangPOS.git
cd bintangPOS
```

### 2. Setup Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Database
Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bintang_pos
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Install Dependencies
```bash
composer install
npm install
npm run build
```

### 5. Database Setup
```bash
php artisan migrate --seed
```

### 6. Generate Storage Link
```bash
php artisan storage:link
```

### 7. Run Development Server
```bash
php artisan serve
```

Aplikasi akan berjalan di: **http://127.0.0.1:8000**

---

## 👤 Demo Credentials

Setelah seeding, gunakan akun berikut untuk login:

| Role | Email | Password |
|------|-------|----------|
| **Owner** | owner@bintang.test | password |
| **Admin** | admin@bintang.test | password |
| **Kasir** | kasir@bintang.test | password |

---

## 📁 Struktur Folder

```
bintangPOS/
├── app/
│   ├── Console/         # Console commands & schedulers
│   ├── Enums/           # Application enums
│   ├── Exports/         # Excel exports (Maatwebsite)
│   ├── Filament/        # Filament admin resources
│   ├── Http/
│   │   ├── Controllers/ # Business logic controllers
│   │   ├── Middleware/  # Custom middleware
│   │   └── Requests/    # Form request validation
│   ├── Models/          # Eloquent models
│   ├── Policies/        # Authorization policies
│   ├── Services/        # Business services layer
│   └── View/
│       └── Components/  # Blade components
│
├── bootstrap/           # Laravel bootstrap
├── config/              # Configuration files
├── database/
│   ├── factories/       # Model factories
│   ├── migrations/      # Database migrations
│   └── seeders/         # Database seeders
│
├── docs/                # Documentation & guides
├── public/              # Web root
│   ├── build/           # Compiled assets
│   ├── css/
│   ├── fonts/
│   ├── images/
│   └── js/
│
├── resources/
│   ├── css/             # Source stylesheets (Tailwind)
│   ├── js/              # Alpine.js & JavaScript
│   └── views/           # Blade templates
│       ├── admin/       # Admin pages
│       ├── approvals/   # Approval request pages
│       ├── auth/        # Authentication pages
│       ├── customers/   # Customer pages
│       ├── dashboard/   # Dashboard pages
│       ├── layouts/     # Layout templates
│       ├── pdf/         # PDF templates
│       ├── pos/         # POS interface
│       ├── reports/     # Report pages
│       └── sales/       # Sales pages
│
├── routes/
│   ├── api.php          # API routes
│   ├── auth.php         # Auth routes
│   ├── console.php      # Console routes
│   └── web.php          # Web routes
│
├── storage/             # File storage
├── tests/               # Test files
│   ├── Feature/         # Feature tests
│   └── Unit/            # Unit tests
│
├── vendor/              # Composer dependencies
├── .env.example         # Environment template
├── composer.json        # PHP dependencies
├── package.json         # Node dependencies
├── artisan              # Laravel CLI
└── README.md            # This file
```

---

## 🔄 Development Workflow

### Menjalankan Development Server dengan Watch Mode
```bash
php artisan serve
npm run dev
```

### Menjalankan Tests
```bash
# All tests
php artisan test

# Feature tests only
php artisan test --filter Feature

# Unit tests only
php artisan test --filter Unit

# Dengan coverage report
php artisan test --coverage
```

### Database Fresh & Seed
```bash
php artisan migrate:fresh --seed
```

### Generate IDE Helper
```bash
php artisan ide-helper:generate
```

---

## 📱 API Endpoints

### Authentication
- `POST /login` - Login
- `POST /logout` - Logout
- `POST /register` - Register (jika enabled)

### POS
- `GET /kasir/pos` - Halaman POS
- `GET /kasir/search-products` - Search produk
- `POST /kasir/checkout` - Checkout transaksi
- `POST /kasir/audit-event` - Log event audit

### Sales
- `GET /sales/{sale}` - Detail penjualan
- `GET /sales/{sale}/receipt` - Receipt PDF

### Reports
- `GET /reports` - Halaman laporan
- `GET /reports/export/excel` - Export Excel
- `GET /reports/export/pdf` - Export PDF

### Dashboard
- `GET /dashboard` - Dashboard owner
- `GET /dashboard/export/pdf` - Export dashboard PDF
- `GET /dashboard/audit-anomaly-status` - Audit anomaly check

### Customers (Filament Admin)
- `GET /admin/customers` - Daftar customer
- `POST /admin/customers` - Tambah customer
- `GET /admin/customers/{id}` - Edit customer
- `POST /admin/customers/{id}` - Update customer
- `DELETE /admin/customers/{id}` - Hapus customer

### Suppliers (Filament Admin)
- `GET /admin/suppliers` - Daftar supplier
- `POST /admin/suppliers` - Tambah supplier
- `GET /admin/suppliers/{id}` - Edit supplier
- `POST /admin/suppliers/{id}` - Update supplier
- `DELETE /admin/suppliers/{id}` - Hapus supplier

---

## 🤝 Contributing

Kami terbuka untuk kontribusi! Berikut cara berkontribusi:

1. Fork repository ini
2. Buat branch feature (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

**Guidelines:**
- Follow PSR-12 coding standard
- Buat tests untuk fitur baru
- Update dokumentasi jika diperlukan
- Commit message harus deskriptif
- Jangan commit `.env` atau folder `vendor`

---

## 📝 Roadmap

- [ ] Mobile app (React Native/Flutter)
- [ ] Integrasi payment gateway (Midtrans, Xendit)
- [ ] Advanced analytics & BI dashboard
- [ ] Inventory forecasting dengan AI
- [ ] Integrasi e-commerce (Shopify, Tokopedia, Lazada)
- [ ] Backup & disaster recovery otomatis
- [ ] WhatsApp Bot untuk notifikasi
- [ ] Telegram integration
- [ ] Advanced POS features (split payment, installment plans)
- [ ] Multi-warehouse management
- [ ] B2B portal untuk supplier

---

## 🐛 Issues & Bug Report

Menemukan bug? Silakan buat [Issue](https://github.com/aksatria/bintangPOS/issues) baru dengan:
- Deskripsi masalah yang jelas
- Langkah untuk mereproduksi
- Screenshot/video (jika ada)
- Environment info (OS, PHP version, dll)

---

## 📚 Documentation

Dokumentasi lengkap tersedia di folder `./docs/`:
- [Installation Guide](./docs/INSTALLATION.md)
- [API Documentation](./docs/API.md)
- [Database Schema](./docs/DATABASE.md)
- [Feature Readiness Matrix](./docs/FEATURE_READINESS_MATRIX.md)
- [Development Guide](./docs/DEVELOPMENT.md)

---

## 📄 License

Proyek ini dilisensikan di bawah [MIT License](LICENSE) - lihat file [LICENSE](LICENSE) untuk detail lengkap.

---

## 👨‍💻 Author

**Aksatria** - [@aksatria](https://github.com/aksatria)

---

## 🙏 Acknowledgments

- Laravel community untuk framework yang awesome
- Filament team untuk admin panel yang powerful
- Tailwind CSS untuk utility-first CSS framework
- Semua kontributor yang telah membantu

---

**Made with ❤️ for Indonesian UMKM**
