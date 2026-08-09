# SNAPSHOT: umkm-percetakan-batako

Updated: 2026-08-10 — source audit + create-CRUD fix verified end-to-end on local server.

## Identity
- Path: `/Users/avowsmacbook/Andhika/Joki/umkm-percetakan-batako`
- Branch: `main`
- Product: operational information system for Percetakan Batako Maros, Ambon.
- Stack: native PHP >=7.4, MySQL/InnoDB/PDO, Composer; Bootstrap 5.3 + Bootstrap Icons CDN; Chart.js on reporting/dashboard pages.
- Source size: ~4,200 PHP lines excluding `vendor/`.

## Runtime Shape
- No framework or build pipeline. Repository root is the web root.
- `index.php` redirects by session role. `.htaccess` rewrites unknown paths to it.
- Each route is a PHP page mixing request handling, PDO queries, and markup.
- Shared application seams: `config/database.php`, `helpers/auth.php`, `helpers/functions.php`, `layouts/`, `assets/`, `migrations/`.

## Role/Route Map
- Anonymous: `login.php`, `logout.php`, `index.php`.
- Operator: dashboard plus four append-only input pages for materials (purchase/usage), production, sales, and expenses.
- Owner: dashboard, CRUD history/master pages, stock monitor (`persediaan.php`), payroll calculation, and production/financial/payroll reports.
- Protected routes call `requireRole()` at top of page.

## DB
- Tables (11): `users`, `pekerja`, `kategori_pengeluaran`, `bahan_baku`, `produksi`, `penjualan`, `pengeluaran`, `gaji`, `stok`, `stok_bahan_baku`, `stok_produk`.
- Source schema files: `database.sql`, `deploy_all.sql` (fresh install); `migrations/revisi_persediaan.sql` (additive migration for existing DBs — adds `bahan_baku.jenis_transaksi` + `stok_bahan_baku` + `stok_produk`).
- `stok_produk` = canonical product stock (production − sales per size); `stok_bahan_baku` = canonical material stock (purchases − usage per type; Semen in Sak, Pasir in m³). `updateStok()`/`updateStokBahan()` recompute them from source tables after every mutation; legacy `stok` is kept in sync for older reports.
- FKs cover operational author, worker, and expense-category relationships.

## Verified State (2026-08-10)
- `/opt/homebrew/bin/php -l` passed for all repository PHP files except `vendor/` (legacy root `functions.php` renamed to `functions.php.legacy-20260810`).
- `git diff --check` clean.
- Local server smoke test: all owner routes (11) and operator routes (5) return HTTP 200 after login; no PHP fatal/warning in HTML.
- Create matrix verified browser→PHP→PDO→DB with unique markers + cleanup (all PASS): bahan baku (owner+operator), produksi, penjualan (owner+operator), pengeluaran, pekerja, gaji (0→4 rows). DB left clean of audit markers.
- QA render (Playwright, 375/768/1440): no horizontal overflow; modals centered (`dx=0, dy=0, inViewport`).
- No server was started via SSH. No commit was made.

## Root Causes Fixed (2026-08-10)
- Bahan Baku create failed with `Call to undefined function validasiSatuanBahan()`: function only existed in dead helper `pemilik/functions.php`. Moved to active `helpers/functions.php` with strict Semen/Sak + Pasir/m³ validation.
- Gaji silent-success: `$hasil` computed only on GET `hitung=1`; POST save looped over empty array yet still redirected "success". Now recomputes on POST, carries hidden period inputs, wraps inserts in a DB transaction, and refuses empty saves.

## Risks to Preserve for Revision Planning
- Source-embedded fallback database credentials in `config/database.php`.
- CSRF helpers exist but are not used in production flows; owner deletion/toggle flows use GET.
- Login succeeds without session ID regeneration.
- Live/client deployment still unverified: source must be re-uploaded (at minimum `helpers/functions.php`, `pemilik/gaji.php`) and `migrations/revisi_persediaan.sql` run on existing DBs before claiming client fix.
- Keep `database.sql` and `deploy_all.sql` consistent when schema changes.

## Source Pointers
- Durable instructions: `AGENTS.md`
- Quick context: `docs/PROFILE.md`
- DB connection: `config/database.php`
- Auth: `helpers/auth.php`
- Business helpers: `helpers/functions.php`
- Tests: `tests/TestRunner.php`
- Deployment assumptions: `DEPLOY.md`, `README.md`

## Next Step
Collect exact client revision requirements and map each to routes, table changes, invariants, migration strategy, and role-specific acceptance checks before editing.
