<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../lib/core.php';
require __DIR__.'/../lib/fleet.php';
require __DIR__.'/../lib/documents.php';
require __DIR__.'/../lib/services.php';

function maintenance_lock(PDO $db, array $config, callable $run): mixed {
    $name=null;
    try {
        if ($config['driver']==='mysql') {
            $name='motorlog-setup-'.substr(hash('sha256',$config['database']),0,40);
            if ((int)val($db,'SELECT GET_LOCK(?, 10)',[$name])!==1) fail('Another installer or migration is using the database.');
        }
        return $run();
    } finally { if ($name) val($db,'SELECT RELEASE_LOCK(?)',[$name]); }
}
function verify_installation(PDO $db): void {
    $list=tables($db);
    foreach (['schema_migrations','app_settings','users','vehicles'] as $t) if (!in_array($t,$list,true)) fail('The database does not contain a recognized Motorlog installation. Nothing was changed.');
    if (schema_version($db)<1 || !(int)val($db,'SELECT COUNT(*) FROM app_settings WHERE id=1') || !(int)val($db,'SELECT COUNT(*) FROM users')) fail('No committed workspace/administrator was found. Nothing was unlocked or seeded.');
}
function demo_day(int $offset=0): string { return (new DateTimeImmutable('today',new DateTimeZone('UTC')))->modify(($offset>=0?'+':'').$offset.' days')->format('Y-m-d'); }
function demo_install(): void {
    if (!preview_mode()) fail('Demo seeding is only available in the isolated preview.');
    if (!installed()) setup_install(['driver'=>'sqlite','site_name'=>'Motorlog','currency'=>'USD','unit'=>'mi','username'=>'demo','display_name'=>'Alex Morgan','password'=>bin2hex(random_bytes(24)),
        'vehicle'=>['kind'=>'car','make'=>'Porsche','model'=>'911 Carrera','model_year'=>2022,'color'=>'Graphite gray','plate'=>'ML-911','vin'=>'DEMO-PORSCHE-001','initial_odometer'=>'27520','at_engine'=>'27520']]);
    $db=checked_db();
    $db->beginTransaction();
    try {
        $db->exec('UPDATE app_settings SET id=id WHERE id=1');
        if ((int)val($db,'SELECT COUNT(*) FROM vehicles')>1) { $db->commit(); return; }
        $actor=one($db,"SELECT id FROM users WHERE username='demo'");
        if (!$actor) fail('This is not the demo installation.');
        $p=(int)val($db,'SELECT MIN(id) FROM vehicles');
        $c=write_vehicle($db,'create',['kind'=>'car','make'=>'Honda','model'=>'Civic','model_year'=>2021,'color'=>'Lunar silver','plate'=>'ML-CVC','vin'=>'DEMO-HONDA-001','initial_odometer'=>'63900','at_engine'=>'25000','at_odometer'=>'63900'])['id'];
        $y=write_vehicle($db,'create',['kind'=>'motorcycle','make'=>'Yamaha','model'=>'MT-07','model_year'=>2023,'color'=>'Matte black','plate'=>'ML-MT7','vin'=>'DEMO-YAMAHA-001','initial_odometer'=>'8880','at_engine'=>'8880'])['id'];
        foreach ([$p,$c,$y] as $id) update_row($db,'vehicles',$id,['created_at'=>demo_day(-40).'T12:00:00Z']);
        $d1=write_driver($db,'create',['name'=>'Alex Morgan','jurisdiction'=>'California','expiration_date'=>demo_day(200),'active'=>true])['id'];
        $d2=write_driver($db,'create',['name'=>'Jamie Lee','jurisdiction'=>'California','expiration_date'=>demo_day(104),'active'=>true])['id'];
        $d3=write_driver($db,'create',['name'=>'Sam Rivera','jurisdiction'=>'California','expiration_date'=>demo_day(347),'active'=>true])['id'];
        write_driver($db,'record',['id'=>$d2,'kind'=>'Training','title'=>'Defensive driving refresher','record_date'=>demo_day(-21)]);
        foreach ([[$p,$d1,-22,27520,27906,'Northern coast drive'],[$p,$d1,-9,27906,28166,'Airport transfer'],[$c,$d2,-16,63900,64045,'Client visits'],
            [$y,$d3,-3,8880,8940,'Weekend ride'],[$c,$d2,-2,64045,64232,'Client visit'],[$p,$d1,-1,28166,28412,'Weekend road trip']] as [$vid,$did,$ago,$a,$b,$why]) {
            write_trip($db,'create',['vehicle_id'=>$vid,'driver_id'=>$did,'trip_date'=>demo_day($ago),'start_odometer'=>$a,'end_odometer'=>$b,'purpose'=>$why,'notes'=>'']);
        }
        foreach ([[$c,-24,63900,9.8,3.56,true],[$c,-2,64058,3.6,3.48,false],[$c,-1,64285,7.4,3.37,true],[$p,-23,27520,12.2,4.09,true],[$p,0,28412,15.8,4.19,true]] as [$vid,$ago,$odo,$g,$price,$full]) {
            write_expense($db,'create',['vehicle_id'=>$vid,'expense_date'=>demo_day($ago),'category'=>'Fuel','vendor'=>'Neighborhood fuel','odometer'=>$odo,'gallons'=>$g,'price_per_gallon'=>$price,'full_tank'=>$full]);
        }
        write_expense($db,'create',['vehicle_id'=>$p,'expense_date'=>demo_day(-7),'category'=>'Maintenance','vendor'=>'Oak Street Auto','description'=>'Annual oil and filter service','amount'=>'310.00']);
        write_expense($db,'create',['vehicle_id'=>$y,'expense_date'=>demo_day(-12),'category'=>'Parts','vendor'=>'Metro Moto','description'=>'Brake pads and chain service kit','amount'=>'70.47']);
        $m=write_mechanic($db,'create',['name'=>'Oak Street Auto','email'=>'','notes'=>'Independent service and repairs.'])['id'];
        $inv=write_invoice($db,'create',['vehicle_id'=>$c,'mechanic_id'=>$m,'invoice_number'=>'OS-1042','invoice_date'=>demo_day(-18),'due_date'=>demo_day(7),'amount'=>'680.00','description'=>'Front suspension repair'])['id'];
        write_invoice($db,'pay',['id'=>$inv,'amount'=>'250.00','payment_date'=>demo_day(-7),'method'=>'Card']);
        $items=[]; foreach (CHECKLIST as $k=>$unused) $items[$k]=['result'=>'pass','notes'=>''];
        $week=monday(today_iso());
        $last=(new DateTimeImmutable($week))->modify('-7 days')->format('Y-m-d');
        $cItems=$items; $cItems['tires']=['result'=>'attention','notes'=>'Left front tire needs pressure checked before the next long trip.'];
        foreach ([[$y,$d3,'Sam Rivera',$last,8880,$items],[$p,$d1,'Alex Morgan',today_iso(),28412,$items],[$c,$d2,'Jamie Lee',today_iso(),64285,$cItems]] as [$vid,$did,$name,$date,$odo,$checks]) {
            $row=require_row($db,'vehicles',$vid,'Vehicle');
            write_document($db,$actor,'inspection','submit',['vehicle_id'=>$vid,'driver_id'=>$did,'inspector_name'=>$name,'inspection_date'=>$date,'inspection_time'=>'08:30','odometer'=>$odo,'unit'=>'mi',
                'make'=>$row['make'],'model'=>$row['model'],'model_year'=>$row['model_year'],'vin'=>$row['vin'],'plate'=>$row['plate'],'color'=>$row['color'],'items'=>$checks,'acknowledged'=>true,'ack_name'=>$name]);
        }
        foreach ([[$p,'garage-hero.jpg'],[$c,'civic.jpg'],[$y,'yamaha.jpg']] as [$vid,$file]) {
            write_photo($db,['owner_kind'=>'vehicle','owner_id'=>$vid,'view_label'=>'Front','caption'=>'Demo workspace vehicle illustration','cover'=>true,'image'=>base64_encode((string)file_get_contents(APP_ROOT.'/assets/'.$file))]);
            $GLOBALS['motorlog_staged_upload']=null;
        }
        $db->commit();
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
}

try {
    $command=$argv[1] ?? '';
    if ($command==='demo') { demo_install(); echo "Demo ready.\n"; exit; }
    if (!in_array($command,['status','migrate','recover-lock'],true)) { echo "Usage: php tools/console.php status|migrate|recover-lock\n"; exit(1); }
    guarded(static function () use ($command,$argv): void {
        if ($command==='status') {
            echo 'Private directory: '.private_root()."\n";
            echo 'Lock: '.(is_file(private_file('config.lock'))?'present':'missing')."\n";
            echo 'Application: '.installed_version()."\n";
            if (is_file(private_file('config.php'))) { $cfg=config_read(); $db=connect_db($cfg); echo 'Driver: '.$cfg['driver']."\nSchema: ".schema_version($db).' / '.SCHEMA_VERSION."\n"; }
            return;
        }
        if (!in_array('--backed-up',$argv,true)) fail('Stop Apache and back up the database and complete private directory first, then pass --backed-up.');
        if ($command==='recover-lock' && is_file(private_file('config.lock'))) fail('The lock is already present. Do not remove it. Restore a missing configuration from a private backup.');
        if ($command==='migrate' && !installed()) fail('The configuration and lock must exist. Recover the lock first.');
        $from=is_file(private_file('config.php'))?'config.php':'setup.pending.php';
        $cfg=config_read($from); $db=connect_db($cfg);
        maintenance_lock($db,$cfg,static function () use ($db,$cfg,$from,$command): void {
            verify_installation($db);
            if ($command==='migrate') {
                migrate($db); echo 'Database upgraded to schema '.schema_version($db).". Records and installer lock were retained.\n";
            } else {
                if ($from!=='config.php') write_config(private_file('config.php'),$cfg);
                atomic_private(private_file('config.lock'),json_text(['format'=>1,'recovered_at'=>now_iso()])."\n");
                @unlink(private_file('setup.pending.php'));
                echo "Lock recovered without changing existing rows.\n";
            }
        });
    });
} catch (Throwable $e) { fwrite(STDERR,($e instanceof HttpFault ? $e->getMessage() : 'Operation failed; check private storage and the database service ('.get_class($e).', '.$e->getCode().').')."\n"); exit(1); }
