<?php
declare(strict_types=1);
require __DIR__.'/../app/core.php';require ROOT.'/app/updates.php';
$n=0;function check(bool $v,string $label):void {global $n;if(!$v)throw new RuntimeException('FAIL: '.$label);$n++;echo "PASS $label\n";}
check(stable_version('v1.10.0')==='1.10.0','stable v prefix');
check(version_compare('1.10.0','1.9.0','>'),'semantic 1.10 > 1.9');
foreach(['1.0','1.0.0-rc.1','v01.2.3','garbage','2026-11-19','1.0.0+dev'] as $tag)check(stable_version($tag)===null,'reject nonstable/invalid '.$tag);
$r=select_stable_release([
 ['tag_name'=>'v1.9.0','draft'=>false,'prerelease'=>false],
 ['tag_name'=>'v1.10.0','draft'=>false,'prerelease'=>false],
 ['tag_name'=>'v9.0.0','draft'=>true,'prerelease'=>false],
 ['tag_name'=>'v8.0.0','draft'=>false,'prerelease'=>true],
 ['tag_name'=>'v20.0.0-rc.1','draft'=>false,'prerelease'=>false]
]);check($r['tag_name']==='v1.10.0','newest published stable selection');check(select_stable_release([['tag_name'=>'invalid']])===null,'missing stable release');
foreach(['NaN','INF','1e999',-1,[],null] as $value){$rejected=false;try{num($value,'Test');}catch(InvalidArgumentException $e){$rejected=true;}check($rejected,'invalid numeric rejected '.json_encode($value));}
foreach(['2026-02-30','2026-13-01','2026-11-31','01/01/2026',''] as $date){$rejected=false;try{date_ok($date);}catch(InvalidArgumentException $e){$rejected=true;}check($rejected,'invalid date rejected '.$date);}
check(date_ok('2024-02-29')==='2024-02-29','valid leap day');
$c=json_decode(file_get_contents(ROOT.'/data/catalog.json'),true,512,JSON_THROW_ON_ERROR);
check(count($c['makes'])===$c['make_count'],'actual catalog make count');check(array_sum(array_map(fn($m)=>count($m['models']),$c['makes']))===$c['model_count'],'actual catalog model count');
$dodge=array_values(array_filter($c['makes'],fn($m)=>strcasecmp($m['name'],'Dodge')===0))[0];check(in_array('Dart',array_column($dodge['models'],'name'),true),'Dodge Dart bundled offline');
$ford=array_values(array_filter($c['makes'],fn($m)=>strcasecmp($m['name'],'Ford')===0))[0];check(!in_array('Dart',array_column($ford['models'],'name'),true),'Dart not a Ford model');check(!in_array('F-150',array_column($ford['models'],'name'),true),'Ford truck absent');
foreach($c['makes'] as $make){foreach($make['models'] as $model){if(array_diff($model['types'],['Passenger Car','Motorcycle']))throw new RuntimeException('Forbidden catalog type');}}
check(true,'model-level type labels only passenger car or motorcycle');
echo "$n unit checks passed.\n";
