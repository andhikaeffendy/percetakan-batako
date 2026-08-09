# PROFILE: umkm-percetakan-batako

## Quick Start
- Path: `/Users/avowsmacbook/Andhika/Joki/umkm-percetakan-batako`
- Stack: native PHP >=7.4, MySQL/InnoDB, Composer; Bootstrap 5.3 CDN.
- PHP binary found on the MacBook: `/opt/homebrew/bin/php`.
- Read `AGENTS.md` before edits. It contains route ownership, DB contract, and remote-work boundary.

## Product
Operational system for Percetakan Batako Maros. Roles are `operator` (input operations) and `pemilik` (CRUD, payroll, reports).

## Critical Files
- DB connection: `config/database.php`
- Auth/session/escaping: `helpers/auth.php`
- Derived stock logic: `helpers/functions.php`
- Schema sources: `database.sql`, `deploy_all.sql`
- Operator inputs: `operator/input_*.php`
- Owner CRUD/reports: `pemilik/*.php`
- Shared UI: `layouts/`, `assets/css/style.css`, `assets/js/app.js`

## Data Model
Eleven tables: `users`, `pekerja`, `kategori_pengeluaran`, `bahan_baku`, `produksi`, `penjualan`, `pengeluaran`, `gaji`, `stok`, `stok_bahan_baku`, `stok_produk`.

`stok_produk` is the canonical product stock (production − sales per size); `stok_bahan_baku` is the canonical material stock (purchases − usage per type; Semen in Sak, Pasir in m³). `stok` is a legacy aggregate kept in sync for older reports. Any production/sales/material revision must preserve these invariants and call `updateStok()`/`updateStokBahan()` in the same request.

## Verification
```bash
# Syntax only; safe and read-only
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 /opt/homebrew/bin/php -l

# DB-connected and mutating temporary test rows: approved non-production DB only
/opt/homebrew/bin/php tests/TestRunner.php
```

## Gotchas
- Never start/restart a server through SSH on the MacBook. Never commit/push unless explicitly requested.
- `config/database.php` has source-embedded fallback DB credentials; do not surface them.
- CSRF helpers are unused by production pages; CRUD deletes/toggles are GET flows.
- `pemilik/gaji.php` fixed 2026-08-10: payroll recomputes on POST, uses a DB transaction, and refuses empty saves. Keep the `periode_awal`/`periode_akhir` hidden inputs in `#formGaji`.
- `helpers/functions.php` is the single active helper; `pemilik/functions.php` is a duplicate/dead file — do not require it.
- `database.sql` and `deploy_all.sql` can drift; compare both for every schema revision.
- At audit time, `docs/` was untracked; preserve the owner worktree and do not bulk-stage.

## Audit Baseline
- 2026-08-09: full source inventory, route/role map, schema/FK review, and PHP syntax pass completed.
- 2026-08-10: create-CRUD root causes fixed and verified end-to-end on the local server — Bahan Baku (missing `validasiSatuanBahan` in active helper) and Gaji (silent-success on POST). Create matrix for all models passes with marker+cleanup. `functions.php` (root legacy duplicate) renamed to `functions.php.legacy-20260810`.
- No server started, no commit made. Live client deployment still requires source re-upload + `migrations/revisi_persediaan.sql` on existing DBs.
