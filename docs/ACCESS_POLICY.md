# Access Policy

Dokumen ini menjelaskan policy respons akses untuk aplikasi web.

## Prinsip Umum

- Middleware `role` dipakai untuk pembatasan role di route web.
- Endpoint non-web (JSON/API-like) tetap mengembalikan status HTTP sesuai akses (`401`/`403`/`422`).
- Halaman backoffice berbasis path `/admin/*` memiliki UX khusus: user non-manager diarahkan ke dashboard agar tidak terjebak di halaman error.

## Matriks Respons Akses

1. Guest (belum login)
- Akses route yang butuh auth: redirect ke login (`302`).

2. Authenticated tapi role tidak sesuai
- Route `/admin/*`: redirect ke dashboard (`302`) + flash error.
- Route non-admin yang diblok role (contoh aksi sensitif tertentu): `403 Forbidden`.

3. Input validasi gagal
- Request web biasa: redirect back (`302`) + error bag.
- Request JSON (`Accept: application/json`): `422 Unprocessable Entity`.

## Referensi Implementasi

- Alias middleware role: `bootstrap/app.php`
- Middleware role: `app/Http/Middleware/EnsureUserRole.php`
- Exception customization `/admin/*`: `bootstrap/app.php`
- Regression tests:
  - `tests/Feature/PosFlowAndAccessTest.php`
  - `tests/Feature/ReportFlowTest.php`
