# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

A **Thai-language operations-manual web app** (ระบบคู่มือการปฏิบัติงาน) for วิทยาลัยอาชีวศึกษาร้อยเอ็ด (Roi Et Vocational College). Stack: **vanilla PHP 8.2 + MariaDB 10.4 (PDO), zero Composer dependencies**, served under XAMPP at `http://localhost/rvc.man/`. All UI text is Thai; dates render in the Buddhist era. The design is **mobile-first** — that is a stated product requirement, not a nicety.

The content is not authored in the app: it is **imported from [data/manual-source.md](data/manual-source.md)**, a PDF→Markdown conversion of the printed manual. See "The importer" below before touching anything under `app/ManualParser.php`.

## Commands

- **Install / reset database (destructive):** open `/rvc.man/install.php` — a form-based installer that creates the DB, runs `sql/schema.sql` (drops & recreates), writes `config/config.php`, creates the admin, and runs the importer. CLI fallback `C:/xampp2/php/php.exe install.php` uses the existing `config/config.php` and seeds `admin` / `admin1234`.
- **Re-import content:** `C:/xampp2/php/php.exe bin/import.php` (or admin → นำเข้าข้อมูล). Idempotent.
- **Inspect parser output without touching the DB:** `C:/xampp2/php/php.exe bin/parse-check.php`
- **Lint PHP:** `C:/xampp2/php/php.exe -l <file>`. There is no test suite.
- **Run:** Apache + MySQL in XAMPP. MySQL only: `C:\xampp2\mysql_start.bat`.

## Architecture

Front-controller MVC-ish, no framework — mirrors the sibling `rvc.arch` project. `.htaccess` rewrites everything except real files to **[index.php](index.php)**, which owns the **route table** (`[method, pattern, [Controller, action], access?]`; `{id}` captures a numeric segment) and dispatches. `access` is `'admin'`, `'login'`, or absent (public — the manual is public reading).

- **[app/App.php](app/App.php)** — config loader, base-path detection (`App::url()`; the app lives in a subdirectory, so never hardcode absolute paths), view rendering (`App::render()` wraps a view in `views/layout.php`).
- **[app/Repository.php](app/Repository.php)** — **all** SQL. `saveProcedure()` writes a procedure plus its steps and flow rows in one transaction and recomputes `search_text`.
- **[app/Auth.php](app/Auth.php)** — session auth; roles `ผู้ดูแลระบบ` / `เจ้าหน้าที่`.
- **[app/helpers.php](app/helpers.php)** — `h()` (every dynamic value in a view goes through it), `url()`, CSRF (`csrf_field()` / `verify_csrf()` — every POST controller calls it first), `icon()` (inline SVG sprite), `highlight()`, `thai_date()`.
- **Views** ([views/](views/)) — plain PHP. `layout.php` picks chrome by `$section` (`public` / `admin` / `bare`) and always renders the bottom tab bar plus, at ≥1000px, the sidebar.

### Data model

`divisions` (ฝ่าย) → `sections` (งาน) → `procedures` (เรื่อง) → `procedure_steps` + `procedure_flows`. `procedures.division_id` is denormalised for fast filtering — `Repository::saveSection()` keeps it in step when a งาน moves between ฝ่าย. Plus `info_pages`, `users`, `favorites`, `attachments`, `search_logs`, `settings`.

### Key behaviours to preserve

- **Thai search cannot use FULLTEXT** (no word breaks). `procedures.search_text` stores the text squeezed to bare characters via `ManualParser::key()`, and queries squeeze the term the same way before `LIKE`. Changing one side without the other silently breaks search.
- **Public gating:** only `status = 'เผยแพร่'` procedures are readable; admins can preview drafts. Enforced in `PublicController::procedure()` and the `Repository` read queries.
- **View counting is once per session** (`$_SESSION['_seen']`), so reloads do not inflate the counter.
- **Uploads are never served directly** — `storage/uploads/.htaccess` denies access; downloads go through `/attachment/{id}`.
- **Client JS is enhancement only** ([assets/app.js](assets/app.js): theme toggle, print/share, confirm dialogs, repeatable admin rows, service-worker registration). Every action works without JS.
- **Theme attribute lives on `<html>`**, set by an inline head script before first paint; CSS keys off `:root[data-theme="dark"] .app-root`. Do not move it back onto `.app-root` — that reintroduces the flash.

### The importer

[app/ManualParser.php](app/ManualParser.php) recovers structure the source Markdown does not have (no headings at all) from repeated page furniture: a ฝ่าย line, a งาน + title line, the flowchart tables, and the printed page-number footer used to split pages. [app/Importer.php](app/Importer.php) then upserts — ฝ่าย/งาน by name, procedures by (งาน, title).

Two decisions there are load-bearing and were arrived at by measurement, so do not "simplify" them:

- **`normalize()` only repairs damage that is provably damage.** The `ำ`→`space+า` and `่`→`ุ` faults are reversible because the broken sequences are illegal Thai. The mark-transposition fault (`บันทึก`→`ับนทึก`) is repaired **only** when the mark has no consonant to sit on; a corpus probe showed the unconditional version corrupts correct words (`ไม่รวม`→`ไมร่วม`) and that frequency-based "corrections" produce false positives on genuine text like `ผลผลิตงานฟาร์ม`.
- **Flowchart tables are read by column position**, taken from the `ผู้รับผิดชอบ` / `หลักฐาน` header row, with **no** ±1 tolerance. Neighbouring cells hold the flowchart's arrows and box captions; reading them in produces plausible-looking but wrong "ผู้รับผิดชอบ" values.

Expected output: 4 ฝ่าย / 27 งาน / 122 เรื่อง / ~1,354 ขั้นตอน. `bin/parse-check.php` prints these; a large swing means a regression.

## Gotchas

- **UTF-8 everywhere.** DB is `utf8mb4`; the PDO DSN sets the charset. When verifying Thai data from a Windows shell, note that **Git Bash mangles UTF-8 in command-line arguments** — verify by piping a UTF-8 `.sql` file or via PHP/HTTP with literal strings, not argv. In PowerShell set `[Console]::OutputEncoding=[System.Text.Encoding]::UTF8` before reading Thai output.
- **Credentials:** `config/config.php` is gitignored; `config/config.sample.php` is the template.
- `install.php` is destructive and unauthenticated — local setup only; remove it on any real deployment.
