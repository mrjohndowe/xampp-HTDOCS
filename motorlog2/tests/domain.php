<?php
declare(strict_types=1);
require __DIR__.'/../lib/core.php';
require __DIR__.'/../lib/fleet.php';
require __DIR__.'/../lib/documents.php';
require __DIR__.'/../lib/services.php';

function ensure(bool $v,string $message): void { if (!$v) throw new RuntimeException($message); }
function yes_fault(callable $fn): void { try { $fn(); } catch (HttpFault) { return; } throw new RuntimeException('Expected rejection'); }
$db=connect_db(['driver'=>'sqlite'], ':memory:');
migrate($db,1);
insert_row($db,'app_settings',['id'=>1,'site_name'=>'Legacy test','currency'=>'USD','unit'=>'km','installed_at'=>now_iso()]);
insert_row($db,'users',['username'=>'legacy','display_name'=>'Legacy test','password_hash'=>password_hash('only-for-unit-test',PASSWORD_DEFAULT),'created_at'=>now_iso()]);
$v=insert_row($db,'vehicles',vehicle_fields(['kind'=>'car','make'=>'Dodge','model'=>'Dart','initial_odometer'=>0],true));
$legacy=insert_row($db,'trips',['vehicle_id'=>$v,'trip_date'=>'2024-03-01','start_odometer'=>0,'end_odometer'=>130,'purpose'=>'Legacy trip','notes'=>'Preserve me','created_at'=>now_iso()]);
$old=insert_row($db,'expenses',['vehicle_id'=>$v,'expense_date'=>'2024-03-05','category'=>'Fuel','vendor'=>'Legacy pump','description'=>'No recorded gallons','amount_minor'=>5900,'created_at'=>now_iso()]);
migrate($db);migrate($db);
ensure(schema_version($db)===SCHEMA_VERSION,'Wrong version');
ensure(require_row($db,'trips',$legacy,'Trip')['driver_id']===null,'Unassigned legacy trip changed');
ensure(require_row($db,'trips',$legacy,'Trip')['notes']==='Preserve me','Trip notes not preserved');
ensure(require_row($db,'expenses',$old,'Expense')['gallons']===null,'Missing fuel history must not be fabricated');
ensure(fleet_state($db)['vehicles'][0]['current_odometer']===130.0,'Mileage not recovered from legacy trip');
$mileageVehicle=write_vehicle($db,'create',['make'=>'Test','model'=>'Mileage','initial_odometer'=>'120000','at_engine'=>'25000'])['id'];
$tripData=['vehicle_id'=>$mileageVehicle,'trip_date'=>'2024-04-01','start_odometer'=>'120000','end_odometer'=>'120300','purpose'=>'Mileage test'];
$tripId=write_trip($db,'create',$tripData)['id'];
$mileage=static fn()=>array_column(fleet_state($db)['vehicles'],null,'id')[$mileageVehicle];
ensure($mileage()['current_odometer']===120300.0 && $mileage()['engine_odometer']===25300.0,'Trip did not advance both readings');
write_trip($db,'update',array_replace($tripData,['id'=>$tripId,'end_odometer'=>'120200']));
ensure($mileage()['current_odometer']===120200.0 && $mileage()['engine_odometer']===25200.0,'Corrected trip did not lower both readings');
$nextTrip=array_replace($tripData,['trip_date'=>'2024-04-02','start_odometer'=>'120200','end_odometer'=>'120400']);
$nextId=write_trip($db,'create',$nextTrip)['id'];
write_trip($db,'update',array_replace($tripData,['id'=>$tripId,'end_odometer'=>'120100']));
ensure($mileage()['current_odometer']===120400.0,'Earlier edit lowered a later reading');
write_trip($db,'delete',['id'=>$nextId]);
ensure($mileage()['current_odometer']===120400.0,'Deletion did not retain the saved reading');
write_vehicle($db,'refresh_mileage',['id'=>$mileageVehicle]);
ensure($mileage()['current_odometer']===120100.0 && $mileage()['engine_odometer']===25100.0,'Mileage refresh did not use the remaining trip');
write_vehicle($db,'replace_engine',['id'=>$mileageVehicle,'installed_date'=>'2024-04-03','at_odometer'=>'120150','at_engine'=>'500','reason'=>'Replacement']);
write_vehicle($db,'refresh_mileage',['id'=>$mileageVehicle]);
ensure($mileage()['current_odometer']===120150.0 && $mileage()['engine_odometer']===500.0,'Refresh ignored the replacement reading or engine baseline');
ensure($mileage()['engines'][1]['current_mileage']===25150.0,'Refresh changed retired engine mileage');

$f=fn(int $id,string $day,mixed $odo,mixed $g,mixed $price,mixed $full)=>['vehicle_id'=>$v,'id'=>$id,'expense_date'=>$day,'category'=>'Fuel','odometer'=>$odo,'gallons'=>$g,'price_per_gallon'=>$price,'full_tank'=>$full];
$list=[$f(1,'2024-03-02',130,4,2,1),$f(2,'2024-03-03',160,2,2,0),$f(3,'2024-03-05',null,null,null,null),$f(4,'2024-03-06',200,5,2,1),$f(5,'2024-03-07',240.2336,1,2,0),$f(6,'2024-03-09',360.9344,4,2,1)];
$mpg=fuel_intervals($list,'km');ensure($mpg[1]['mpg']===null && $mpg[2]['mpg']===null && $mpg[4]['mpg']===null,'Gap or first fill fabricated MPG');
ensure($mpg[6]['mpg']===20.0,'Kilometer conversion/full tank calculation failed');
ensure(monday('2026-11-22')==='2026-11-16' && next_monday('2026-11-22')==='2026-11-23','Monday date calculation failed');
yes_fault(fn()=>date_field('2026-02-29','Date'));yes_fault(fn()=>odo_field(INF));yes_fault(fn()=>money_value('-1','USD'));
yes_fault(fn()=>money_value('0.123','USD'));
ensure(money_value('0','JPY')===0 && money_value('1.234','BHD')===1234,'Currency precision');
ensure(version_compare(release_version('v1.10.0'),release_version('1.9.0'),'>'),'Lexical instead of semantic version comparison');
ensure(release_version('v1.10.0-rc.1')===null && release_version('garbage')===null && release_version('01.1.0')===null,'Invalid tags accepted');
$release=['tag_name'=>'v1.10.0','published_at'=>'2026-09-18','draft'=>false,'prerelease'=>false,'assets'=>[['name'=>'Motorlog-Windows.zip','browser_download_url'=>'https://github.com/judge/example/releases/download/v1.10.0/Motorlog-Windows.zip']]];
$r=release_candidate($release,'judge/example');ensure($r['download_label']==='Application ZIP','App asset not used');
$release['assets'][0]['browser_download_url']='https://example.org/Motorlog-Windows.zip';
$r=release_candidate($release,'judge/example');ensure($r['download']==='https://github.com/judge/example/archive/refs/tags/v1.10.0.zip','Tagged source fallback invalid');
$release['prerelease']=true;ensure(release_candidate($release,'judge/example')===null,'Prerelease accepted');
echo "PASS: additive legacy migration, fixed-point money, missing and partial fuel, US MPG km conversion, calendar and release policy\n";
