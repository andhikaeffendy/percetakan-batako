# AGENTS.md — umkm-percetakan-batako

## Identity
- Native PHP operational system for Percetakan Batako Maros (Ambon).
- PHP >=7.4, MySQL/InnoDB, Composer. No framework/build step.
- Web root is the repository root. `.htaccess` sends non-file routes to `index.php`.
- Absolute repository path: `/Users/avowsmacbook/Andhika/Joki/umkm-percetakan-batako`.

## Remote MacBook Boundary
- SSH work is edit/verify only. Never start/restart `php -S`, Apache, or any server/process remotely.
- Never commit or push unless the owner explicitly asks.
- Before any mutation: `cp <file> <file>.bak-YYYYMMDD-HHMMSS`.
- Do not expose, paste, or commit `.env` values. `.env` is ignored.

## Commands
```bash
# Run only when the owner authorizes it; tests connect to the configured DB and write temporary rows.
/opt/homebrew/bin/php tests/TestRunner.php

# Safe syntax check; does not connect to DB.
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 /opt/homebrew/bin/php -l

# Local bootstrap (auto-create DB, apply migrations, serve) — owner runs this, not via SSH.
./run-local.sh
# Equivalent manual flow (owner runs): /opt/homebrew/bin/php seeder.php && /opt/homebrew/bin/php -S localhost:8000 -t .
```

## Architecture
- Request flow: route page PHP file → page-local query/POST logic → shared layout → MySQL via PDO.
- `config/database.php`: Dotenv loading and `getDB()` PDO singleton.
- `helpers/auth.php`: session start, `requireLogin()`, `requireRole()`, flash messages, `e()` output escaping.
- `helpers/functions.php`: stock sync (`updateStok` produk, `updateStokBahan` bahan baku), stock reads, unit validation (`validasiSatuanBahan`), unused-by-pages CSRF helpers.
- `layouts/header.php` selects the role sidebar; all protected page UI uses `layouts/footer.php`.
- `assets/css/style.css`: global Bootstrap overrides + "Industrial Soft UI" design tokens. `assets/js/app.js`: mobile sidebar, client-side sale total, destructive-action confirmation, local-timezone default dates.

## UI/UX System (2026-08 revision)
- Theme tokens: concrete `#E8E3DA`, surface `#FFFFFF`, shadow `#B8B2A8`, ink `#27313A`, accent terracotta `#C65A32`, cobalt `#2F67C7`, good `#287A5A`, warn `#B67A12`, bad `#B84444`.
- Soft-neomorphism is hierarchy only: CTA/input/focus/table/status stay high-contrast. No glass, decorative gradients, or heavy animation.
- All create/edit modals use `modal-dialog-centered modal-dialog-scrollable`; footer buttons stack full-width under 576px.
- Page hierarchy: `page-toolbar` (title + context) → filter panel → KPI → table/chart.
- Primary action is `btn-primary` (terracotta); red is reserved for destructive/error. Badge ukuran batako = `badge-primary` everywhere.
- Icons: Bootstrap Icons only; no emoji in UI (login, sidebar, headers, stat cards).
- Charts (Chart.js 4): time-series → line/area; static category comparison (e.g. Semen/Pasir stock) stays bar. Rp axis ticks use whole numbers via `fmtRp`; X-axis uses `autoSkip` + `maxRotation:0`.
- Tables scroll horizontally inside their card on mobile (`table-responsive`), never page-level.
- Default date inputs use local browser timezone (see app.js), not UTC.

## Routes and Ownership
| Area | Pages | Role | Primary tables |
|---|---|---|---|
| Public | `index.php`, `login.php`, `logout.php` | anonymous/session | `users` |
| Operator | `operator/index.php`, `input_bahan_baku.php`, `input_produksi.php`, `input_penjualan.php`, `input_pengeluaran.php` | operator | operational tables |
| Owner data | `pemilik/bahan_baku.php`, `produksi.php`, `penjualan.php`, `tenaga_kerja.php`, `pengeluaran.php`, `gaji.php`, `persediaan.php` | pemilik | CRUD + payroll + stock monitor |
| Owner reports | `pemilik/dashboard.php`, `laporan_produksi.php`, `laporan_keuangan.php`, `laporan_gaji.php` | pemilik | aggregate reads; PDF/Excel where implemented |

Note: `pemilik/input_bahan_baku.php` is an operator-role page (same form as operator copy); do not treat it as an owner CRUD page.

## Database Contract
Schema sources: `database.sql` (local), `deploy_all.sql` (hosting deploy + seeded users), `migrations/revisi_persediaan.sql` (additive stock revision).

Tables:
- `users`: authenticated accounts; roles only `pemilik` or `operator`.
- `pekerja`: worker master and `tarif_per_sak`.
- `kategori_pengeluaran`: expense-category master.
- `bahan_baku`: materials use, owned by `operator_id`.
- `produksi`: production, references `pekerja_id` and `operator_id`.
- `penjualan`: sales, references `operator_id`.
- `pengeluaran`: expenses, references category and operator.
- `gaji`: payroll snapshot for a period, references worker and nullable operator.
- `stok`: legacy denormalized aggregate by `ukuran_batako` (kept for compatibility).
- `stok_bahan_baku`: canonical stock per material (`Semen` in Sak, `Pasir` in m³).
- `stok_produk`: canonical stock per size (`standar`, `besar`), kept in sync with source tables.

Business invariants:
- Canonical product stock = `SUM(produksi.realisasi_produksi) - SUM(penjualan.jumlah_terjual)` per size, stored in `stok_produk`.
- Canonical material stock = purchases minus usage per material, stored in `stok_bahan_baku`.
- After every production/sale/material mutation, call `updateStok($db)` / `updateStokBahan($db)` in the same request.
- A sale must not exceed current available stock (`stok_produk`). Re-read/lock inside a DB transaction if concurrent writes become a requirement.
- Payroll formula: `SUM(produksi.jumlah_sak_semen) × pekerja.tarif_per_sak - panjar` for the selected period.
- Material unit rule (`validasiSatuanBahan`): Semen must be `Sak`, Pasir must be `m3` (rendered `m³` in UI).
- Preserve FK integrity. Existing FKs are `RESTRICT` except `gaji.operator_id` uses `SET NULL`.
- Any schema revision must be an additive SQL migration under `migrations/`; keep `database.sql`/`deploy_all.sql` in sync when their deployment contracts require it.

## Editing Rules
- Follow existing page-local PDO prepared-statement style; never interpolate user input into SQL.
- Validate/cast all POST/GET values at the page boundary. Escape any text output using `e()`.
- Keep operator input pages append-only unless the requested business rule explicitly changes it. Owner pages own edit/delete master/history operations.
- Keep `operator_id` derived from `$_SESSION['user_id']`, never from a form field.
- Preserve Bootstrap 5.3 CDN, Bootstrap Icons CDN, Chart.js CDN, and shared CSS/JS unless scope asks for a dependency/UI change.
- Do not move to a framework, add a package, or redesign state architecture for a revision.
- UI changes must keep `modal-dialog-centered modal-dialog-scrollable`, `page-toolbar`, token colors, and icon-only (no emoji) conventions above.

## Known Risks / Review Targets
- `config/database.php` has production DB fallback credentials in source. Treat rotation and fallback removal as a security task requiring owner-approved deployment coordination.
- CSRF helpers exist but no production page calls them. All state-changing forms and GET delete/toggle links need a scoped security revision before internet-exposed production use.
- Login does not regenerate the session ID after successful authentication.
- Owner CRUD deletes/toggles are GET actions. Convert to POST + CSRF when revising those flows.
- `tests/TestRunner.php` is DB-connected and writes/deletes rows. It is not a pure unit suite; run only against an approved non-production DB.
- `pemilik/gaji.php`: payroll recompute `$hasil` on POST + DB transaction (fixed 2026-08-10, verified 0→4 rows); keep `periode_awal`/`periode_akhir` hidden inputs in `#formGaji` when editing.
- `helpers/functions.php` is the single active helper. `pemilik/functions.php` is a duplicate/dead file — do not require it; keep new business helpers in `helpers/functions.php` only.
- Root-repo duplicate files were removed 2026-08-10 (legacy copies of `dashboard.php`, `laporan_*.php`, `persediaan.php`, `gaji.php`, `input_bahan_baku.php`, `pengeluaran.php`, `tenaga_kerja.php`, `style.css`, `app.js` that lived beside the real files in `pemilik/`, `operator/`, `assets/`). Backup: `/tmp/batako-root-duplicates-*/`. All pages live ONLY under `pemilik/`, `operator/`, `assets/`; never recreate root copies.
- `database.sql` and `deploy_all.sql` are parallel schema sources. Compare both whenever DB changes are made.
- `pemilik/laporan_gaji.php` previously misaligned Panjar/Gaji Bersih columns (P0, fixed 2026-08). Keep header count equal to body `<td>` count when editing payroll tables.
- Live/client deployment fix is NOT yet shipped: root causes were fixed and verified locally (Bahan Baku undefined function, Gaji silent-success). For the client, re-upload at least `helpers/functions.php` + `pemilik/gaji.php` and run `migrations/revisi_persediaan.sql` on the existing DB (see `DEPLOY.md` §6b/§6c, `README.md` deploy section).

## Required Verification
1. PHP syntax check for every edited PHP file (`php -l`).
2. `git diff --check` and inspect the exact diff.
3. For DB/business-rule changes: use an approved non-production database, execute the focused workflow, then verify source-table totals and stock consistency.
4. For protected/mutating flows: verify both roles and unauthenticated access paths.
5. For UI changes: headless render QA at 375/768/1440 (Playwright Chromium at `~/Library/Caches/ms-playwright`), assert no horizontal overflow and centered modals (`dx/dy≈0, inViewport:true`), then screenshot review.
6. Update `docs/SNAPSHOT.md` and `docs/PROFILE.md` after meaningful structural changes. Keep this file below 300 lines and fact-only.

## Git
- Current baseline branch is `main`; repository had untracked `docs/` at audit time.
- Do not stage existing user changes indiscriminately.
- No commit or push without explicit owner instruction.
