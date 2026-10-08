# Product management API

Status: Frozen
Owner: Recruitment test candidate
Last updated: 2026-10-08
Approval: Approved by user on 2026-10-08

## Problem and context

BTS.id recruitment test membutuhkan REST API untuk authentication dan manajemen
produk. API harus dapat dijalankan sebagai proyek Laravel dengan PostgreSQL,
memiliki dokumentasi Swagger, dan dapat dikirim sebagai repository public.

Pengerjaan bersifat take-home dengan deadline 2026-10-08 pukul 17.00 WIB.

## Users and stakeholders

- API client yang melakukan register dan login.
- User terautentikasi yang mengelola produk.
- Reviewer BTS.id yang menjalankan API, membaca dokumentasi, dan menilai source code.

## Product goals

- Menyediakan authentication berbasis username.
- Menyediakan CRUD produk dengan filtering dan pagination.
- Mencatat user yang membuat dan mengubah setiap produk serta metadata file.
- Menyediakan kontrak API yang dapat dipahami melalui Swagger.
- Menyediakan artefak Docker untuk menjalankan aplikasi bersama PostgreSQL.

## Success metrics

| Metric | Baseline | Target | Measurement window/source |
| --- | --- | --- | --- |
| Required API endpoints available | 0 | Semua endpoint pada requirement dapat dipanggil | Feature tests dan manual API check |
| Protected product mutations | Belum ada | Request tanpa authentication ditolak | Feature tests |
| Product audit attribution | Belum ada | Create/update menyimpan user yang benar | Database assertions |
| API documentation coverage | Belum ada | Semua endpoint publik dan protected terdokumentasi | Swagger review |
| Public repository delivery | Belum ada | Repository dapat diakses reviewer | Repository URL |

## User flows

### Register and login

1. Client mengirim username, password, dan password confirmation.
2. Sistem membuat user dengan `name` sama dengan `username`.
3. Client login menggunakan username dan password.
4. Sistem mengembalikan authentication token dan refresh token.
5. Client memakai authentication token untuk endpoint product yang protected.

### List and inspect products

1. Client meminta daftar produk.
2. Client dapat mengirim search, category, page, dan limit.
3. Sistem mengembalikan produk beserta images, audit user, dan metadata pagination.
4. Client dapat meminta detail satu produk berdasarkan ID.

### Manage a product

1. User terautentikasi mengirim data produk dan daftar path images.
2. Sistem menyimpan produk dan file metadata dalam satu operasi konsisten.
3. Sistem mencatat user pada `created_by` dan `updated_by`.
4. Saat update, daftar images yang dikirim menjadi daftar final.
5. Saat delete, produk dan metadata file terkait dihapus.

## Functional requirements

| FR ID | Requirement | Priority | Status | Notes |
| --- | --- | --- | --- | --- |
| FR-001 | Sistem harus menyediakan register dengan username, password, dan password confirmation. | Must | Draft | Username unik. |
| FR-002 | Sistem harus mengisi `users.name` dengan nilai username saat register. | Must | Draft | Client tidak mengirim `name`. |
| FR-003 | Sistem harus menyediakan login menggunakan username dan password. | Must | Draft | Password tidak boleh muncul di response. |
| FR-004 | Login harus mengembalikan authentication token dan refresh token. | Must | Draft | Token harus berbeda. |
| FR-005 | Sistem harus menyediakan logout untuk mencabut sesi/token aktif. | Must | Draft | Detail revocation ada di technical design. |
| FR-006 | Sistem harus menyediakan daftar semua produk. | Must | Draft | GET products bersifat public. |
| FR-007 | Daftar produk harus mendukung search berdasarkan title. | Must | Draft | Query `search`. |
| FR-008 | Daftar produk harus mendukung filter berdasarkan category. | Must | Draft | Query `category`. |
| FR-009 | Daftar produk harus mendukung pagination. | Must | Draft | Query `page` dan `limit`. |
| FR-010 | Sistem harus menyediakan detail produk berdasarkan ID. | Must | Draft | Resource tidak ditemukan menghasilkan JSON 404. |
| FR-011 | User terautentikasi harus dapat membuat produk. | Must | Draft | Wajib title, price, category, dan minimal satu image path. |
| FR-012 | User terautentikasi harus dapat mengubah produk. | Must | Draft | Semua user terautentikasi boleh mengubah semua produk. |
| FR-013 | User terautentikasi harus dapat menghapus produk. | Must | Draft | Metadata file terkait ikut dihapus. |
| FR-014 | Sistem harus mencatat user pembuat dan pengubah produk. | Must | Draft | Field database bernama `created_by` dan `updated_by`. |
| FR-015 | Sistem harus menyimpan image path dalam entitas file yang dapat direlasikan ke model parent. | Must | Draft | Tabel file memakai `parent_id` dan `parent_table`. |
| FR-016 | Sistem harus mencatat user pembuat dan pengubah metadata file. | Must | Draft | Field database bernama `created_by` dan `updated_by`. |
| FR-017 | Sistem harus mengembalikan validation error dalam format JSON dengan status 400. | Must | Draft | Mengikuti PDF test. |
| FR-018 | Sistem harus menyediakan dokumentasi Swagger untuk semua endpoint. | Must | Draft | Package pilihan: L5-Swagger. |
| FR-019 | Sistem harus menyediakan file Docker untuk aplikasi dan PostgreSQL. | Must | Draft | Local Docker belum tersedia untuk verifikasi. |
| FR-020 | API harus dapat diakses dari web application pada domain berbeda. | Must | Draft | CORS perlu dikonfigurasi. |
| FR-021 | Repository harus dapat diakses public. | Must | Draft | Nama repository bebas. |

## Business rules

- Username harus unik.
- Username disimpan sesuai nilai input yang lolos validasi tanpa lowercase normalization; login memakai pencocokan exact.
- Username terdiri dari 3-50 karakter alfanumerik atau underscore, tanpa spasi.
- Username bersifat case-sensitive; `Jhon_Doe` dan `jhon_doe` adalah nilai berbeda.
- `users.name` selalu sama dengan username pada alur register.
- Semua user yang sudah login boleh membuat, mengubah, dan menghapus produk.
- Client tidak boleh menentukan `created_by`, `updated_by`, `parent_id`, atau `parent_table`.
- `created_by` dan `updated_by` menyimpan ID user, bukan nama user.
- Product create membutuhkan minimal satu image path.
- Image path harus berformat `YYYY/MM/DD/filename`, tanpa `..`, leading slash, URL penuh, atau backslash.
- Price harus lebih besar atau sama dengan 0 dan memiliki maksimal dua angka desimal.
- Saat product update mengirim images, daftar tersebut menggantikan daftar lama.
- Saat product update tidak mengirim images, daftar lama dipertahankan.
- Category disimpan sesuai input dan filter category memakai exact match.
- Description bersifat optional dan nullable pada create maupun update.
- Pagination memakai paginator bawaan Laravel dengan default page 1, default limit 10, dan maksimum limit 100.
- Image path disimpan sebagai metadata; sistem belum mengelola upload atau penghapusan file fisik.
- GET product list dan GET product detail dapat dipanggil tanpa authentication.
- POST, PUT, dan DELETE product membutuhkan authentication.
- CORS membaca allowlist origin dari environment; wildcard hanya untuk local development jika diperlukan.
- Register hanya membuat user dan tidak mengembalikan token.
- Delete berhasil mengembalikan status 204 tanpa response body.
- Validation failure menggunakan HTTP status 400 sesuai requirement PDF.
- Response JSON menyertakan `code` numerik yang sama dengan HTTP status, kecuali response 204 yang tidak memiliki body.
- Pada PUT, `title`, `price`, dan `category` wajib dikirim; `description` dan `images` boleh tidak dikirim.
- Rate limiting dan cache adalah fitur optional dan dikerjakan setelah requirement Must.

## Data lifecycle

- User dibuat melalui register dan dapat login setelah password tersimpan ter-hash.
- Product dibuat oleh user terautentikasi.
- File metadata dibuat bersama product atau saat daftar images product berubah.
- Product update memperbarui `updated_by` product dan metadata file yang berubah.
- Product delete menghapus product dan file metadata terkait.
- Refresh token memiliki masa berlaku dan dapat direvoke.
- Refresh token digunakan melalui `POST /api/auth/refresh` dan dirotasi setiap kali berhasil dipakai.

## In scope

- Laravel 13 dengan PHP 8.5.
- PostgreSQL.
- Laravel built-in users table dengan tambahan username.
- Laravel Sanctum untuk authentication token.
- Refresh token terpisah.
- Product CRUD, filtering, search, dan pagination.
- Polymorphic file metadata dengan path.
- Model Observer untuk audit user.
- Swagger melalui L5-Swagger.
- CORS.
- Dockerfile, Compose file, dan konfigurasi PostgreSQL.
- Feature tests untuk behavior utama.
- README dan public repository delivery.

## Out of scope

- Frontend application.
- Upload file binary.
- Image processing, resizing, atau validation isi file.
- Role dan permission selain authenticated user.
- Ownership restriction per product.
- Soft delete dan deleted-by audit.
- Admin dashboard.
- Payment, inventory, dan order management.
- Cache dan rate limiting sebagai release blocker.

## Non-goals

- Membangun identity provider lengkap.
- Membuat generic file management API untuk semua model.
- Menjamin Docker dapat diverifikasi pada komputer lokal yang belum memiliki Docker.
- Menyimpan raw password atau raw refresh token di database.

## Assumptions

| ID | Assumption | Risk | Validation plan | Status |
| --- | --- | --- | --- | --- |
| A-001 | Laravel 13 adalah versi yang dipakai karena PHP lokal 8.5 mendukungnya dan security support lebih panjang. | Package Swagger/Sanctum harus kompatibel. | Composer dependency resolution dan test suite. | Pending |
| A-002 | `parent_table` menyimpan morph alias yang dikontrol aplikasi, misalnya `products`. | Nilai morph harus konsisten lintas model. | Model relation dan feature tests. | Pending |
| A-003 | Images berupa path string, bukan upload file. | Reviewer mungkin mengharapkan URL atau upload. | Jelaskan kontrak di Swagger dan README. | Pending |
| A-004 | Docker files cukup disiapkan tanpa local Docker build verification. | Kesalahan Dockerfile bisa lolos sampai reviewer menjalankan. | Static review dan CI jika tersedia. | Pending |
| A-005 | Deadline adalah 2026-10-08 pukul 17.00 WIB. | Waktu implementasi dan validasi terbatas. | Gunakan sebagai batas delivery. | Resolved |

## Open product decisions

| Decision ID | Dependency | Options | Recommendation | Impacted FRs | Status |
| --- | --- | --- | --- | --- | --- |
| D-001 | Deadline | Tanggal belum disebutkan atau tanggal tertentu | 2026-10-08 pukul 17.00 WIB | Semua Must | Decided |
| D-002 | Product update images | Replace seluruh daftar atau append | Replace seluruh daftar saat field dikirim; field yang tidak dikirim mempertahankan daftar lama | FR-012, FR-015 | Decided |
| D-003 | Refresh session lifetime | Durasi access/refresh token belum ditentukan | Access token 60 menit, refresh token 30 hari; refresh token dirotasi saat dipakai | FR-004, FR-005 | Decided |
| D-004 | Composer update | Composer 2.10.3 sudah terpasang | Gunakan Composer 2.10.3 | FR-018, FR-019 | Decided |
| D-005 | Docker verification | Local Docker atau CI/remote | Siapkan artefak; verifikasi local bukan blocker | FR-019 | Decided |
| D-006 | Register response | Mengembalikan token atau hanya user | Hanya user, token dibuat saat login | FR-001, FR-003, FR-004 | Decided |
| D-007 | Delete response | 204 kosong atau 200 dengan body | 204 tanpa body | FR-013 | Decided |
| D-008 | Image path format | URL penuh atau relative path | Relative path dengan format `YYYY/MM/DD/filename` | FR-015 | Decided |
| D-009 | Username normalization | Lowercase normalization atau preserve input | Preserve nilai input yang lolos validasi dan exact match saat login | FR-001, FR-003 | Decided |
| D-010 | PUT required fields | Semua field atau field inti | `title`, `price`, dan `category` wajib; `description` dan `images` optional | FR-012 | Decided |
| D-011 | Response code | Tidak ada code atau code di body | Sertakan `code` numerik yang sama dengan HTTP status | FR-017, FR-018 | Decided |
| D-012 | Username format | Bebas atau allowlist karakter | 3-50 karakter alfanumerik atau underscore tanpa spasi; case dipertahankan | FR-001, FR-003 | Decided |
| D-013 | Image path validation | Path bebas atau path terkontrol | Format `YYYY/MM/DD/filename`, tanpa traversal, URL, leading slash, atau backslash | FR-011, FR-015 | Decided |
| D-014 | Price storage | Integer Rupiah atau decimal dengan precision | PostgreSQL `numeric(15,2)` dan nilai minimal 0 | FR-011, FR-012 | Decided |
| D-015 | Pagination implementation | Pagination manual atau paginator framework | Paginator bawaan Laravel dengan default 10 dan maksimum 100 | FR-009 | Decided |
| D-016 | Refresh token usage | Token dikembalikan tanpa endpoint atau endpoint refresh | `POST /api/auth/refresh` dengan rotation | FR-004, FR-005 | Decided |
| D-017 | Category matching | Normalized/contains atau exact match | Preserve input dan exact match pada filter | FR-008, FR-011, FR-012 | Decided |
| D-018 | Description | Wajib atau optional nullable | Optional dan nullable | FR-011, FR-012 | Decided |
| D-019 | CORS origins | Wildcard atau environment allowlist | Environment allowlist; wildcard hanya local development | FR-020 | Decided |

## Decision log

| Decision ID | Decision | Rationale | Decider/date | Impacted FRs |
| --- | --- | --- | --- | --- |
| DL-001 | Laravel dengan PostgreSQL dan Sanctum | Stack dipilih dari opsi recruitment test dan familiar bagi user. | User / 2026-10-08 | FR-001 to FR-021 |
| DL-002 | User memakai tabel bawaan Laravel; `name` diisi sama dengan `username`. | Payload PDF hanya menyediakan username dan password. | User / 2026-10-08 | FR-001, FR-002 |
| DL-003 | Product routes memakai `apiResource`. | Mengikuti resource-oriented Laravel API routing. | User / 2026-10-08 | FR-006 to FR-013 |
| DL-004 | Audit fields diisi oleh Model Observer. | Menjaga audit behavior terpusat dan berlaku di semua caller model. | User / 2026-10-08 | FR-014, FR-016 |
| DL-005 | `created_by` dan `updated_by` menyimpan user ID. | Relasi user dapat digunakan saat response membutuhkan detail user. | User / 2026-10-08 | FR-014, FR-016 |
| DL-006 | Images dipindah ke tabel polymorphic `files`. | Satu product dapat memiliki banyak image path dan model lain dapat memakai relasi yang sama. | User / 2026-10-08 | FR-015, FR-016 |
| DL-007 | Docker disiapkan tetapi tidak diverifikasi di local. | Docker belum terinstall pada environment local. | User / 2026-10-08 | FR-019 |
| DL-008 | Composer diperbarui ke 2.10.3. | Mengatasi advisory yang ditemukan pada Composer 2.10.2. | User / 2026-10-08 | FR-018, FR-019 |
| DL-009 | Deadline ditetapkan 2026-10-08 pukul 17.00 WIB. | Menjadi batas kerja dan validasi. | User / 2026-10-08 | Semua Must |
| DL-010 | Access token berlaku 60 menit dan refresh token 30 hari. Refresh token dirotasi saat dipakai. | Membatasi umur access token dan mengurangi risiko replay refresh token. | User / 2026-10-08 | FR-004, FR-005 |
| DL-011 | PUT hanya mengganti images jika field dikirim; tanpa field images, daftar lama dipertahankan. | Memisahkan update product biasa dari penggantian file metadata. | User / 2026-10-08 | FR-012, FR-015 |
| DL-012 | Register hanya mengembalikan user; token diterbitkan saat login. | Memisahkan pembuatan akun dari pembuatan sesi. | User / 2026-10-08 | FR-001, FR-003, FR-004 |
| DL-013 | Delete berhasil mengembalikan status 204 tanpa body. | Response REST minimal dan jelas. | User / 2026-10-08 | FR-013 |
| DL-014 | Image path relative memakai format `YYYY/MM/DD/filename`. | Storage metadata tidak bergantung pada host atau domain tertentu. | User / 2026-10-08 | FR-015 |
| DL-015 | Username disimpan sesuai input yang lolos validasi tanpa lowercase normalization dan login exact match. | Nilai database menjadi sumber konsistensi username. | User / 2026-10-08 | FR-001, FR-003 |
| DL-016 | PUT mewajibkan title, price, dan category; description serta images optional. | Field inti product harus selalu lengkap saat update. | User / 2026-10-08 | FR-012 |
| DL-017 | Response envelope menyertakan code numerik yang sama dengan HTTP status. | Client dapat membaca status dari body maupun HTTP response. | User / 2026-10-08 | FR-017, FR-018 |
| DL-018 | Username memakai 3-50 karakter alfanumerik atau underscore tanpa spasi; case dipertahankan. | Membatasi karakter ambigu tanpa melakukan normalisasi nilai. | User / 2026-10-08 | FR-001, FR-003 |
| DL-019 | Image path divalidasi dengan format `YYYY/MM/DD/filename` dan menolak traversal, URL, leading slash, serta backslash. | Menjaga format metadata dan menghindari path traversal. | User / 2026-10-08 | FR-011, FR-015 |
| DL-020 | Product price disimpan sebagai PostgreSQL `numeric(15,2)` dengan nilai minimal 0. | Menyimpan nominal Rupiah secara presisi dan kompatibel dengan contoh decimal pada PDF. | User / 2026-10-08 | FR-011, FR-012 |
| DL-021 | Username case-sensitive; `Jhon_Doe` dan `jhon_doe` dianggap berbeda. | Konsisten dengan penyimpanan raw value dan exact login match. | User / 2026-10-08 | FR-001, FR-003 |
| DL-022 | Product list memakai paginator bawaan Laravel dengan default limit 10 dan maksimum 100. | Menggunakan behavior framework dan menghindari pagination manual. | User / 2026-10-08 | FR-009 |
| DL-023 | Refresh token dipakai melalui `POST /api/auth/refresh` dan dirotasi setiap kali berhasil dipakai. | Token yang dikembalikan login memiliki lifecycle yang jelas. | User / 2026-10-08 | FR-004, FR-005 |
| DL-024 | Category disimpan sesuai input dan filter category memakai exact match. | Hasil filter category dapat diprediksi tanpa perubahan nilai. | User / 2026-10-08 | FR-008, FR-011, FR-012 |
| DL-025 | Description optional dan nullable. | Payload PDF tidak mewajibkan description. | User / 2026-10-08 | FR-011, FR-012 |
| DL-026 | CORS memakai environment allowlist; wildcard hanya untuk local development. | Mendukung domain berbeda tanpa membuka production secara default. | User / 2026-10-08 | FR-020 |
