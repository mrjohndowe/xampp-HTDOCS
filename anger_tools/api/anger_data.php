<?php
ob_start();require_once __DIR__.'/../extra/liteConnect.php';require_once __DIR__.'/../extra/functions.php';if(session_status()!==PHP_SESSION_ACTIVE)session_start();ob_end_clean();
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
function reply($status,$data){http_response_code($status);echo json_encode($data,JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if(empty($_SESSION['user']))reply(401,['error'=>'Not authenticated']);
$uid=getUserID($_SESSION['user']);if(!$uid)reply(401,['error'=>'Invalid user']);
$action=isset($_GET['action'])&&is_string($_GET['action'])?$_GET['action']:'';
try{
if($action==='get_log_details'){
$id=isset($_GET['id'])&&is_string($_GET['id'])&&ctype_digit($_GET['id'])?$_GET['id']:'0';
$s=$db->prepare('SELECT * FROM anger WHERE id=:id AND (username=:owner OR public=1)');$s->execute(['id'=>$id,'owner'=>$uid]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!$r)reply(404,['error'=>'Entry not found']);
$salt=(string)getCode($r['username']);if($salt!==''&&(string)($r['keycode']??'')===$salt)foreach(['madAbout','express','thoughts','feelings','act','better'] as $key)$r[$key]=binary_decoded((string)($r[$key]??''));
$r['userName']=getName($r['username']);$r['angerLogID']=$r['id'];$r['angerDate']=date('F j, Y',strtotime($r['timestamp']));$r['angerTime']=date('H:i:s',strtotime($r['timestamp']));$r['levelColor']=getAngerLevel($r['angerScale']);$r['levelScale']=getAngerLevelScale($r['angerScale']);unset($r['keycode']);reply(200,$r);
}
$s=$db->prepare('SELECT angerScale,timestamp,month,year FROM anger WHERE username=:owner ORDER BY timestamp ASC');$s->execute(['owner'=>$uid]);$rows=$s->fetchAll(PDO::FETCH_ASSOC);
$current=array_values(array_filter($rows,fn($r)=>(string)$r['month']===date('m')&&(string)$r['year']===date('Y')));
$level=fn($r)=>(int)str_replace('option','',(string)$r['angerScale']);
if($action==='get_anger_stats'){$levels=array_values(array_filter(array_map($level,$current),fn($n)=>$n>=1&&$n<=10));reply(200,['count'=>count($current),'avgLevel'=>$levels?round(array_sum($levels)/count($levels),1):0,'month'=>date('F'),'year'=>date('Y')]);}
if($action==='get_anger_distribution'){$d=[];for($i=1;$i<=10;$i++)$d['option'.$i]=0;foreach($current as $r)if(isset($d[$r['angerScale']]))$d[$r['angerScale']]++;reply(200,$d);}
if($action==='get_anger_trend'){$days=[];for($i=29;$i>=0;$i--)$days[date('Y-m-d',strtotime('-'.$i.' days'))]=[];foreach($rows as $r){$d=substr($r['timestamp'],0,10);$v=$level($r);if(isset($days[$d])&&$v>=1&&$v<=10)$days[$d][]=$v;}$result=[];foreach($days as $d=>$vals)$result[]=['day'=>$d,'count'=>count($vals),'avgLevel'=>$vals?round(array_sum($vals)/count($vals),1):null];reply(200,$result);}
reply(400,['error'=>'Invalid action']);
}catch(Throwable $e){error_log('Anger Tools API failed: '.get_class($e));reply(500,['error'=>'Unable to complete request']);}
