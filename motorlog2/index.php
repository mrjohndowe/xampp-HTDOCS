<?php
declare(strict_types=1);

require_once __DIR__.'/lib/core.php';
require_once __DIR__.'/lib/fleet.php';
require_once __DIR__.'/lib/documents.php';
require_once __DIR__.'/lib/services.php';
require_once __DIR__.'/lib/printing.php';
require_once __DIR__.'/lib/view.php';
ini_set('display_errors','0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), geolocation=(), microphone=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' blob: data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'");
header('Cache-Control: no-store, private');

function send_json(array $data, int $status=200): never {
    http_response_code($status); header('Content-Type: application/json; charset=utf-8');
    echo json_text($data); exit;
}
function payload(): array {
    $length=(int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($length>7_300_000) fail('This upload is too large.',413,'file_size');
    if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''),'application/json')) fail('Send application/json.',415,'json_required');
    $raw=file_get_contents('php://input',false,null,0,7_300_001);
    if ($raw===false || strlen($raw)>7_300_000) fail('This upload is too large.',413,'file_size');
    try { $d=json_decode($raw,true,64,JSON_THROW_ON_ERROR); } catch (JsonException $e) { fail('Invalid JSON request.',400,'bad_json'); }
    if (!is_array($d) || !str_starts_with(ltrim($raw),'{')) fail('Send a JSON object.',400,'bad_json');
    return [$d,$raw];
}
function download_file(string $path,string $name,string $type): never {
    if (!is_file($path)) fail('This download is not available in this release.',404,'not_found');
    header('Content-Type: '.$type); header('Content-Disposition: attachment; filename="'.$name.'"');
    header('Content-Length: '.filesize($path)); readfile($path); exit;
}

$route=is_string($_GET['r'] ?? '') ? ($_GET['r'] ?? '') : '';
$method=$_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if ($route==='ping' && in_array($method,['GET','HEAD'],true)) {
        $ok=installed();
        send_json(['ok'=>true,'app'=>'Motorlog','installed'=>$ok,'version'=>installed_version(), 'message'=>$ok ? 'Motorlog is online.' : 'Website onboarding is not complete. Open the website in a browser.']);
    }
    if ($route==='download' && $method==='GET') download_file(APP_ROOT.'/desktop/motorlog.py','motorlog.py','text/x-python; charset=utf-8');
    if ($route==='desktop-bundle' && $method==='GET') download_file(APP_ROOT.'/desktop/motorlog-desktop.zip','motorlog-desktop.zip','application/zip');
    if ($route==='install') {
        guarded(static function (): void { setup_open(); });
        if ($method==='GET' || $method==='HEAD') { start_session(); render_app('setup',$_SESSION['csrf']); }
        if ($method!=='POST') fail('Use GET or POST for setup.',405,'method');
        check_csrf(); [$d]=payload();
        $a=$d['action'] ?? 'install';
        if ($a==='test') send_json(['ok'=>true,...setup_test($d)]);
        if ($a!=='install') fail('Unknown setup action.',400);
        $result=setup_install($d); set_browser_user((int)$result['user_id']); unset($result['user_id']);
        send_json(['ok'=>true,...$result],201);
    }
    if ($route==='' && in_array($method,['GET','HEAD'],true)) {
        if (!installed()) {
            guarded(static function (): void { setup_open(); });
            start_session(); render_app('setup',$_SESSION['csrf']);
        }
        $db=checked_db(); $user=browser_user($db);
        render_app($user ? 'app' : 'login',$_SESSION['csrf'], $user,settings($db));
    }
    if ($route==='catalog' && $method==='GET' && !installed()) {
        guarded(static function (): void { setup_open(); });
        send_json(['ok'=>true,...catalog_endpoint($_GET)]);
    }
    $db=checked_db();
    if ($route==='login' && $method==='POST') { [$d]=payload(); send_json(['ok'=>true,...sign_in($db,$d)]); }
    if ($route==='logout' && $method==='POST') {
        check_csrf(); $_SESSION=['csrf'=>bin2hex(random_bytes(32)),'demo_signed_out'=>true]; session_regenerate_id(true);
        send_json(['ok'=>true]);
    }
    if ($method==='POST' && $route==='save') {
        $actor=request_actor($db,true); [$d,$raw]=payload();
        $result=mutate($db,$actor,(string)($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? ''),$raw,static fn()=>save_resource($db,$actor,$d));
        send_json(['ok'=>true,...$result]);
    }
    if (!in_array($method,['GET','HEAD'],true)) fail('This method is not available.',405,'method');
    $actor=request_actor($db);
    switch ($route) {
        case 'state': send_json(['ok'=>true,...workspace_state($db,$actor)]);
        case 'catalog': send_json(['ok'=>true,...catalog_endpoint($_GET)]);
        case 'tokens':
            if (!empty($_SERVER['HTTP_AUTHORIZATION']) || !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) fail('Manage desktop tokens in website Settings.',403,'forbidden');
            send_json(['ok'=>true,'tokens'=>token_list($db,$actor)]);
        case 'updates': send_json(['ok'=>true,...updates_check($db,($_GET['manual'] ?? '')==='1')]);
        case 'document': send_json(['ok'=>true,'document'=>document_pack($db,document_row($db,id_field($_GET['id'] ?? null,'Document')))]);
        case 'trip-csv': export_csv($db,'trip',$_GET);
        case 'expense-csv': export_csv($db,'expense',$_GET);
        case 'image':
            $pic=photo_file($db,id_field($_GET['id'] ?? null,'Photo'));
            header('Content-Type: '.$pic['mime']); header('Content-Length: '.filesize($pic['path']));
            header('Content-Disposition: inline; filename="motorlog-image.'.($pic['mime']==='image/png' ? 'png':'jpg').'"');
            readfile($pic['path']); exit;
        case 'print': case 'pdf':
            $type=choice_field($_GET['type'] ?? '',['inspection','sale'],'document type');
            $id=id_field($_GET['id'] ?? '', 'Document',false);
            $d=$id ? document_pack($db,document_row($db,$id,$type)) : [];
            if ($route==='print') print_html($type,$d);
            print_pdf($db,$type,$d);
        default: fail('This page was not found.',404,'not_found');
    }
} catch (HttpFault $e) {
    if (in_array($method,['GET','HEAD'],true) && ($route==='' || $route==='install')) {
        http_response_code($e->status); render_app('blocked','',null,[],['message'=>$e->getMessage(),'code'=>$e->errorCode]);
    }
    send_json(['ok'=>false,'message'=>$e->getMessage(),'code'=>$e->errorCode],$e->status);
} catch (Throwable $e) {
    error_log('Motorlog: '.get_class($e).' stage='.$route.' at '.basename($e->getFile()).':'.$e->getLine().' code='.(string)$e->getCode());
    if ($route==='') { http_response_code(503); render_app('blocked','',null,[],['message'=>'The workspace could not be opened. Verify the private configuration and database service.','code'=>'server_error']); }
    send_json(['ok'=>false,'message'=>'The server could not complete this request. Your input has not been discarded. Check the connection and retry.','code'=>'server_error'],503);
}
