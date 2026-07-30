<?php
declare(strict_types=1);

/**
 * Front controller — every request except real files routes through here.
 */

session_start();

require __DIR__ . '/app/App.php';
require __DIR__ . '/app/Database.php';
require __DIR__ . '/app/Auth.php';
require __DIR__ . '/app/ManualParser.php';
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/Repository.php';
require __DIR__ . '/app/Importer.php';
require __DIR__ . '/app/controllers/PublicController.php';
require __DIR__ . '/app/controllers/AuthController.php';
require __DIR__ . '/app/controllers/AdminController.php';

App::boot();

/* ---- resolve the route path relative to the app base ---- */
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$base = App::basePath();
if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$path   = trim(rawurldecode($uri), '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/**
 * Route table: [method, pattern, [Controller, action], access?]
 * access: absent = public, 'login' = signed in, 'admin' = admin role.
 * {id} captures a numeric segment.
 */
$routes = [
    // --- public: the manual ---
    ['GET',  '',                          [PublicController::class, 'home']],
    ['GET',  'divisions',                 [PublicController::class, 'divisions']],
    ['GET',  'division/{id}',             [PublicController::class, 'division']],
    ['GET',  'section/{id}',              [PublicController::class, 'section']],
    ['GET',  'procedure/{id}',            [PublicController::class, 'procedure']],
    ['GET',  'search',                    [PublicController::class, 'search']],
    ['GET',  'info/{id}',                 [PublicController::class, 'info']],
    ['GET',  'attachment/{id}',           [PublicController::class, 'attachment']],

    // --- signed-in users ---
    ['GET',  'saved',                     [PublicController::class, 'saved'],          'login'],
    ['POST', 'procedure/{id}/favorite',   [PublicController::class, 'toggleFavorite'], 'login'],
    ['GET',  'account',                   [AuthController::class, 'account'],          'login'],
    ['POST', 'account/password',          [AuthController::class, 'changePassword'],   'login'],

    // --- auth ---
    ['GET',  'login',                     [AuthController::class, 'showLogin']],
    ['POST', 'login',                     [AuthController::class, 'login']],
    ['POST', 'logout',                    [AuthController::class, 'logout']],

    // --- admin ---
    ['GET',  'admin',                            [AdminController::class, 'dashboard'],        'admin'],
    ['GET',  'admin/procedures',                 [AdminController::class, 'procedures'],       'admin'],
    ['GET',  'admin/procedures/new',             [AdminController::class, 'procedureForm'],    'admin'],
    ['POST', 'admin/procedures/new',             [AdminController::class, 'saveProcedure'],    'admin'],
    ['GET',  'admin/procedures/{id}/edit',       [AdminController::class, 'procedureForm'],    'admin'],
    ['POST', 'admin/procedures/{id}/edit',       [AdminController::class, 'saveProcedure'],    'admin'],
    ['POST', 'admin/procedures/{id}/delete',     [AdminController::class, 'deleteProcedure'],  'admin'],
    ['POST', 'admin/attachments/{id}/delete',    [AdminController::class, 'deleteAttachment'], 'admin'],

    ['GET',  'admin/taxonomy',                   [AdminController::class, 'taxonomy'],         'admin'],
    ['POST', 'admin/divisions/save',             [AdminController::class, 'saveDivision'],     'admin'],
    ['POST', 'admin/divisions/{id}/delete',      [AdminController::class, 'deleteDivision'],   'admin'],
    ['POST', 'admin/sections/save',              [AdminController::class, 'saveSection'],      'admin'],
    ['POST', 'admin/sections/{id}/delete',       [AdminController::class, 'deleteSection'],    'admin'],

    ['GET',  'admin/info',                       [AdminController::class, 'info'],             'admin'],
    ['POST', 'admin/info/save',                  [AdminController::class, 'saveInfo'],         'admin'],
    ['POST', 'admin/info/{id}/delete',           [AdminController::class, 'deleteInfo'],       'admin'],

    ['GET',  'admin/users',                      [AdminController::class, 'users'],            'admin'],
    ['POST', 'admin/users/save',                 [AdminController::class, 'saveUser'],         'admin'],
    ['POST', 'admin/users/{id}/delete',          [AdminController::class, 'deleteUser'],       'admin'],

    ['GET',  'admin/import',                     [AdminController::class, 'importForm'],       'admin'],
    ['POST', 'admin/import',                     [AdminController::class, 'runImport'],        'admin'],
];

foreach ($routes as $route) {
    [$rMethod, $pattern, $handler] = $route;
    $access = $route[3] ?? null;

    if ($rMethod !== $method) {
        continue;
    }
    $regex = '#^' . preg_replace('/\\\{id\\\}/', '(\d+)', preg_quote($pattern, '#')) . '$#';
    if (!preg_match($regex, $path, $m)) {
        continue;
    }

    if ($access === 'admin') {
        Auth::requireAdmin();
    } elseif ($access === 'login') {
        Auth::requireLogin();
    }

    $params = isset($m[1]) ? ['id' => (int) $m[1]] : [];

    [$class, $action] = $handler;
    (new $class())->$action($params);
    return;
}

(new PublicController())->notFound();
