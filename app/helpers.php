<?php
declare(strict_types=1);

/**
 * Global helpers used by controllers and views.
 */

/** HTML-escape. Every dynamic value printed in a view goes through this. */
function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return App::url($path);
}

function asset(string $path): string
{
    return App::asset($path);
}

function redirect(string $path = ''): never
{
    header('Location: ' . App::url($path));
    exit;
}

function redirect_back(string $fallback = ''): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref !== '') {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

/* ---------------------------------------------------------------- CSRF */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['_csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('คำขอไม่ถูกต้อง (CSRF token mismatch) — กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง');
    }
}

/* -------------------------------------------------------------- flashes */

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['_flash'][] = ['message' => $message, 'type' => $type];
}

/** @return array<int,array{message:string,type:string}> */
function take_flash(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/* --------------------------------------------------------- view helpers */

/** Accent colour for a ฝ่าย by its position. */
function division_color(int $index): string
{
    $c = App::DIVISION_COLORS;
    return $c[$index % count($c)];
}

/** Shorten a Thai string with an ellipsis. */
function excerpt(?string $s, int $len = 110): string
{
    $s = trim(preg_replace('/\s+/u', ' ', (string) $s) ?? '');
    return mb_strlen($s, 'UTF-8') > $len ? mb_substr($s, 0, $len, 'UTF-8') . '…' : $s;
}

/** Thai Buddhist-era date, e.g. "5 ก.พ. 2569". */
function thai_date(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '—';
    }
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
               'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . ((int) date('Y', $ts) + 543);
}

/** Human-readable file size. */
function human_size(?int $bytes): string
{
    if (!$bytes) {
        return '—';
    }
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = max(0, min((int) floor(log($bytes, 1024)), count($units) - 1));
    return round($bytes / (1024 ** $i), 1) . ' ' . $units[$i];
}

/**
 * Escape text, then wrap every occurrence of the search term in <mark>.
 * Thai has no word boundaries, so this is a plain case-insensitive substring
 * highlight over the already-escaped string.
 */
function highlight(?string $text, string $term): string
{
    $safe = h($text);
    $term = trim($term);
    if ($term === '' || mb_strlen($term, 'UTF-8') < 2) {
        return $safe;
    }
    $quoted = preg_quote(h($term), '/');
    return preg_replace('/(' . $quoted . ')/iu', '<mark>$1</mark>', $safe) ?? $safe;
}

/** Render a stored paragraph blob as HTML paragraphs. */
function paragraphs(?string $text): string
{
    $text  = trim((string) $text);
    if ($text === '') {
        return '';
    }
    $out = '';
    foreach (preg_split('/\n{2,}/u', $text) ?: [] as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        $out .= '<p>' . nl2br(h($block)) . '</p>';
    }
    return $out;
}

/** Inline SVG icon by name (24px, stroke = currentColor). */
function icon(string $name, int $size = 20): string
{
    $paths = [
        'home'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/>',
        'folder'   => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'bookmark' => '<path d="M6 3h12v18l-6-4.5L6 21z"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/>',
        'list'     => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'file'     => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
        'print'    => '<path d="M7 9V3h10v6"/><rect x="4" y="9" width="16" height="7" rx="2"/><path d="M7 14h10v7H7z"/>',
        'back'     => '<path d="M15 19 8 12l7-7"/>',
        'chev'     => '<path d="m9 5 7 7-7 7"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.3 3.1-5.5 7-5.5s7 2.2 7 5.5"/><path d="M17 8.5a3 3 0 0 0 0-5"/><path d="M18.5 20c0-2.2-.9-3.9-2.3-5"/>',
        'shield'   => '<path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6z"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'edit'     => '<path d="M4 20h4l10-10-4-4L4 16z"/><path d="M13.5 6.5 17.5 10.5"/>',
        'trash'    => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'download' => '<path d="M12 3v12"/><path d="m7 11 5 5 5-5"/><path d="M4 21h16"/>',
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'logout'   => '<path d="M15 12H4"/><path d="m8 8-4 4 4 4"/><path d="M10 4h7a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-7"/>',
        'refresh'  => '<path d="M20 11a8 8 0 1 0-2.3 6.3"/><path d="M20 5v6h-6"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
        'eye'      => '<path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.5"/>',
        'check'    => '<path d="m5 13 4 4 10-10"/>',
    ];
    $body = $paths[$name] ?? $paths['file'];
    return '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" '
        . 'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" '
        . 'aria-hidden="true">' . $body . '</svg>';
}
