<?php
declare(strict_types=1);

function catalog_snapshot(): array {
    static $data;
    if (isset($data)) return $data;
    $path=APP_ROOT.'/catalog/vpic.json';
    if (!is_file($path)) fail('The local catalog file is missing. Restore it from the release or run the refresh command; manual vehicle entry is still available.',503,'catalog_missing');
    $data=json_array(file_get_contents($path) ?: '');
    if (!isset($data['counts'],$data['makes'])) fail('The local catalog is unreadable. You can still enter a custom make and model.',503,'catalog_missing');
    return $data;
}
function catalog_endpoint(array $q): array {
    $data=catalog_snapshot();
    $type=choice_field($q['type'] ?? 'car',['car','motorcycle'],'vehicle type');
    $make=id_field($q['make'] ?? '', 'Make',false);
    $meta=array_intersect_key($data,array_flip(['source','source_url','retrieved','types','counts']));
    if ($make) {
        foreach ($data['makes'] as $r) if ((int)$r['id']===$make && array_key_exists($type,$r['types'])) {
            return ['models'=>array_map(static fn($m)=>['id'=>$m[0],'name'=>$m[1],'type'=>$type],$r['types'][$type]),'make'=>['id'=>$make,'name'=>$r['name'],'type'=>$type],'meta'=>$meta];
        }
        return ['models'=>[],'meta'=>$meta];
    }
    $out=[];
    foreach ($data['makes'] as $r) if (array_key_exists($type,$r['types'])) $out[]=['id'=>$r['id'],'name'=>$r['name'],'type'=>$type,'model_count'=>count($r['types'][$type])];
    return ['makes'=>$out,'meta'=>$meta];
}
function release_version(mixed $tag): ?string {
    if (!is_string($tag) || strlen($tag)>100 || !preg_match('/^v?((?:0|[1-9]\d{0,8})\.(?:0|[1-9]\d{0,8})\.(?:0|[1-9]\d{0,8}))(?:\+[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?$/D',$tag,$p)) return null;
    return $p[1];
}
function clean_repo(mixed $v): string {
    $r=text_field($v,'GitHub owner/repository',142);
    if ($r!=='' && (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})\/[A-Za-z0-9_][A-Za-z0-9._-]{0,99}$/D',$r) || str_contains($r,'..') || str_ends_with($r,'.git'))) fail('Enter a public GitHub repository as owner/repository, not a URL.');
    return $r;
}
function release_candidate(array $r, string $repo): ?array {
    if (($r['draft'] ?? true)!==false || ($r['prerelease'] ?? true)!==false || empty($r['published_at']) || !($version=release_version($r['tag_name'] ?? null))) return null;
    $tag=$r['tag_name'];
    $prefix='https://github.com/'.$repo.'/';
    $download=$prefix.'archive/refs/tags/'.rawurlencode($tag).'.zip';
    $downloadType='Tagged source ZIP';
    $zips=[];
    foreach (($r['assets'] ?? []) as $a) {
        $url=$a['browser_download_url'] ?? ''; $name=$a['name'] ?? '';
        if (!is_string($url) || !is_string($name) || strncasecmp($url,$prefix.'releases/download/',strlen($prefix.'releases/download/'))!==0 || !preg_match('~^https://github\.com/[A-Za-z0-9._-]+/[A-Za-z0-9._-]+/releases/download/[^/?#]+/[^/?#]+$~Di',$url)) continue;
        if (str_ends_with(strtolower($name),'.zip') && !preg_match('/(?:source|symbols|debug|checksums|docs)/i',$name)) $zips[]=['url'=>$url,'score'=>preg_match('/motorlog|application|windows/i',$name) ? 1 : 0];
    }
    if ($zips) { usort($zips,static fn($a,$b)=>$b['score']<=>$a['score']); $download=$zips[0]['url']; $downloadType='Application ZIP'; }
    return ['version'=>$version,'tag'=>$tag,'download'=>$download,'download_label'=>$downloadType,'notes'=>$prefix.'releases/tag/'.rawurlencode($tag),'published_at'=>$r['published_at']];
}
function github_read(string $repo, string $route): array {
    $ctx=stream_context_create(['http'=>['method'=>'GET','follow_location'=>0,'timeout'=>4,'ignore_errors'=>true,
        'header'=>"User-Agent: Motorlog-Release-Checker\r\nAccept: application/vnd.github+json\r\nX-GitHub-Api-Version: 2022-11-28\r\n"],
        'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);
    $raw=@file_get_contents('https://api.github.com/repos/'.$repo.'/releases'.$route,false,$ctx,0,2_000_000);
    $status=0;
    foreach (($http_response_header ?? []) as $h) if (preg_match('/^HTTP\/\S+ (\d+)/',$h,$m)) $status=(int)$m[1];
    return [$status,is_string($raw) ? json_array($raw) : []];
}
function updates_check(PDO $db, bool $manual): array {
    $repo=settings($db)['github_repo']; $installed=installed_version();
    $base=['installed'=>$installed,'repo'=>$repo,'latest'=>null,'available'=>false];
    if ($repo==='') return [...$base,'state'=>'unconfigured','message'=>'Set a GitHub owner/repository in Settings to enable update checks.'];
    clean_repo($repo);
    $cache=private_file('release-'.hash('sha256',strtolower($repo)).'.json');
    $guard=fopen(private_file('releases.guard'),'c+b');
    if ($guard) { @chmod(private_file('releases.guard'),0600); flock($guard,LOCK_EX); }
    try {
        $saved=is_file($cache) ? json_array(file_get_contents($cache) ?: '') : [];
        $age=time()-(int)($saved['checked'] ?? 0);
        $ttl=$manual ? 60 : (($saved['state'] ?? '')==='offline' ? 300 : 3600);
        if (!$saved || $age >= $ttl) {
            [$status,$r]=github_read($repo,'/latest');
            $latest=$status===200 ? release_candidate($r,$repo) : null;
            if (!$latest && in_array($status,[200,404],true)) {
                [$fallbackStatus,$many]=github_read($repo,'?per_page=30');
                if ($fallbackStatus===200) foreach ($many as $candidate) {
                    if (!is_array($candidate)) continue;
                    $release=release_candidate($candidate,$repo);
                    if ($release && (!$latest || version_compare($release['version'],$latest['version'],'>'))) $latest=$release;
                }
                else $status=$fallbackStatus;
            }
            $state=$latest ? 'release' : ($status===200 || $status===404 ? 'not_found' : 'offline');
            $saved=['checked'=>time(),'state'=>$state,'latest'=>$latest];
            atomic_private($cache,json_text($saved));
        }
    } finally { if ($guard) { flock($guard,LOCK_UN); fclose($guard); } }
    $latest=$saved['latest'] ?? null; $available=$latest && release_version($installed) && version_compare($latest['version'],release_version($installed),'>');
    $message=match ($saved['state']) {
      'release'=>$available ? 'A newer stable release is available.' : 'You’re on the latest available stable release.',
      'not_found'=>'No valid published stable release was found, or the repository is inaccessible.',
      default=>'GitHub could not be reached. Your workspace is unaffected; try again later.',
    };
    return [...$base,'latest'=>$latest,'available'=>(bool)$available,'state'=>$saved['state'],'message'=>$message];
}
function token_list(PDO $db, array $actor): array {
    return rows($db,'SELECT id,label,created_at,expires_at,revoked_at FROM api_tokens WHERE user_id=? ORDER BY id DESC',[(int)$actor['id']]);
}
function write_token(PDO $db, array $actor, string $action, array $d): array {
    // Tokens cannot mint other tokens. Only the authenticated browser admin can manage them.
    if (!empty($_SERVER['HTTP_AUTHORIZATION']) || !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) fail('Manage desktop tokens in the authenticated website Settings.',403,'forbidden');
    $user=(int)$actor['id'];
    if ($action==='revoke') {
        $id=id_field($d['id'] ?? null,'Token');
        statement($db,'UPDATE api_tokens SET revoked_at=? WHERE id=? AND user_id=? AND revoked_at IS NULL',[now_iso(),$id,$user]);
        return ['id'=>$id,'message'=>'Token revoked.'];
    }
    if ($action!=='create') fail('Unknown token action.',400);
    $days=integer_field($d['days'] ?? 90, 'Days until expiry',1,365);
    $token='ml_'.bin2hex(random_bytes(32));
    $expires=gmdate('Y-m-d\TH:i:s\Z',time()+$days*86400);
    $id=insert_row($db,'api_tokens',['user_id'=>$user,'label'=>text_field($d['label'] ?? 'Windows desktop','Token label',100,true),'token_hash'=>hash('sha256',$token),'created_at'=>now_iso(),'expires_at'=>$expires]);
    return ['id'=>$id,'token'=>$token,'expires_at'=>$expires,'message'=>'Copy this token now. It will not be shown again.'];
}
function write_settings(PDO $db, string $action, array $d): array {
    if ($action!=='update') fail('Unknown settings action.',400);
    $site=text_field($d['site_name'] ?? settings($db)['site_name'],'Site name',100,true);
    update_row($db,'app_settings',1,['site_name'=>$site,'github_repo'=>clean_repo($d['github_repo'] ?? '')]);
    return ['message'=>'Workspace settings saved.'];
}
function workspace_state(PDO $db, array $actor): array {
    return [...fleet_state($db), 'version'=>installed_version(),'actor'=>$actor,'today'=>today_iso(),'week_start'=>monday(today_iso()),
      'inspections'=>document_list($db,'inspection'),'sales'=>document_list($db,'sale'),'templates'=>template_list($db),'demo'=>preview_mode(),
      'options'=>['categories'=>EXPENSE_CATEGORIES,'record_kinds'=>RECORD_KINDS,'views'=>VIEW_TYPES,'checklist'=>CHECKLIST,'results'=>RESULTS,'issue_states'=>ISSUE_STATES,
          'currencies'=>CURRENCIES,'disclosures'=>DISCLOSURES,'titles'=>TITLE_STATES,'warranties'=>WARRANTY_STATES,'payment_states'=>PAYMENT_STATES]];
}
function save_resource(PDO $db, array $actor, array $payload): array {
    $entity=text_field($payload['entity'] ?? '', 'Resource',30,true);
    $action=text_field($payload['action'] ?? '', 'Action',30,true);
    $d=$payload['data'] ?? [];
    if (!is_array($d)) fail('Data must be an object.',400);
    return match ($entity) {
        'vehicle'=>write_vehicle($db,$action,$d),'trip'=>write_trip($db,$action,$d),'expense'=>write_expense($db,$action,$d),
        'driver'=>write_driver($db,$action,$d),'mechanic'=>write_mechanic($db,$action,$d),'invoice'=>write_invoice($db,$action,$d),
        'inspection','sale'=>write_document($db,$actor,$entity,$action,$d),
        'issue'=>$action==='update' ? write_issue($db,$actor,$d) : fail('Unknown issue action.',400),
        'photo'=>$action==='upload' ? write_photo($db,$d) : fail('Unknown photo action.',400),
        'template'=>write_template($db,$action,$d),'settings'=>write_settings($db,$action,$d),'token'=>write_token($db,$actor,$action,$d),
        default=>fail('Unknown resource.',404,'not_found'),
    };
}
function export_csv(PDO $db, string $entity, array $q): never {
    $state=fleet_state($db); $isTrip=$entity==='trip';
    $date=$isTrip?'trip_date':'expense_date';
    $list=filter_fleet_rows($state[$isTrip?'trips':'expenses'],$q,$date);
    $unit=$state['settings']['unit']; $currency=$state['settings']['currency'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="motorlog-'.$entity.'s.csv"');
    $out=fopen('php://output','w');
    fwrite($out,"\xEF\xBB\xBF");
    $cast=static function(mixed $v): string { $s=(string)($v ?? ''); return preg_match('/^\s*[=+\-@]|^[\t\r\n]/u',$s) ? "'".$s : $s; };
    $cols=$isTrip ? ['Date','Vehicle','Driver','Start ('.$unit.')','End ('.$unit.')','Distance ('.$unit.')','Purpose','Notes'] : ['Date','Vehicle','Category','Vendor','Description','Amount ('.$currency.')','Odometer ('.$unit.')','Gallons (US)','Price per gallon','Full tank','MPG (US)','Fuel interval','Linked invoice ID'];
    fputcsv($out,$cols,',','"','');
    foreach ($list as $r) {
        $cells=$isTrip?[$r[$date],$r['vehicle_name'],$r['driver_name'],$r['start_odometer'],$r['end_odometer'],$r['distance'],$r['purpose'],$r['notes']]:
            [$r[$date],$r['vehicle_name'],$r['category'],$r['vendor'],$r['description'],$r['amount'],$r['odometer'],$r['gallons'],$r['price_per_gallon'],$r['full_tank'],$r['mpg'],$r['fuel_interval'],$r['invoice_id']];
        fputcsv($out,array_map($cast,$cells),',','"','');
    }
    fclose($out);
    exit;
}
