<?php
// Unified front controller. Requires the existing extra/ connection and helpers.
ob_start();
require_once __DIR__ . '/extra/liteConnect.php';
require_once __DIR__ . '/extra/functions.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
ob_clean();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
if (empty($_SESSION['site_csrf'])) $_SESSION['site_csrf'] = bin2hex(random_bytes(32));
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf() { return '<input type="hidden" name="csrf" value="'.e($_SESSION['site_csrf']).'">'; }
function go($url) { header('Location: '.$url, true, 303); exit; }
function flash($text) { $_SESSION['site_flash'] = $text; }
function query_rows($sql, $params = []) { global $db; $s=$db->prepare($sql); if (!$s->execute($params)) throw new RuntimeException('Database operation failed'); return $s->fetchAll(PDO::FETCH_ASSOC); }
function scalar_post($key) { return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : ''; }
function read_answers($row) {
    $salt = (string)getCode($row['username']);
    if ($salt !== '' && (string)($row['keycode'] ?? '') === $salt) {
        foreach (['madAbout','express','thoughts','feelings','act','better'] as $key) $row[$key] = binary_decoded((string)($row[$key] ?? ''));
    }
    return $row;
}
$Q = [["madAbout", "What was happening?", "Describe the situation or moment that brought up a strong mood or emotion."], ["express", "How did I respond?", "Think about what you said, your tone, or your body language."], ["angerScale", "How intense was this mood or emotion?", "Choose from 1 (subtle) to 10 (very intense)."], ["thoughts", "What were my thoughts?", "What was going through your mind?"], ["feelings", "What emotions did I notice?", "Name the emotions you noticed, including anything beneath the surface."], ["act", "What did I do next?", "What did you do or say?"], ["better", "What might support me next time?", "Choose one thing you could try differently next time."], ["public", "Would I like to share this reflection?", "Choose whether to keep this reflection private or share it, then review before saving."]];
$allowed=['home','login','register','treatment','anger','publicanger','allLogs','viewLog','budget','logout'];
$route=isset($_GET['p']) && is_string($_GET['p']) ? $_GET['p'] : 'home';
if ($route==='') $route='home';
$notFound=!in_array($route,$allowed,true);
$logged=!empty($_SESSION['user']);
$uid=$logged ? getUserID($_SESSION['user']) : null;
if ($logged && !$uid) { unset($_SESSION['user']); $logged=false; }
if (!$logged && !in_array($route,['login','register'],true) && !$notFound) $route='login';
$error='';$old=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $token=$_POST['csrf']??null;
    if (!is_string($token) || !hash_equals($_SESSION['site_csrf'],$token)) { http_response_code(403); $error='Your session verification failed. Reload the page before trying again.'; }
    else try {
        if ($route==='logout' && $logged) {
            $_SESSION=[]; session_regenerate_id(true); $_SESSION['site_csrf']=bin2hex(random_bytes(32)); go('?p=login');
        } elseif ($route==='login') {
            $user=scalar_post('username');$pass=isset($_POST['pass'])&&is_string($_POST['pass'])?$_POST['pass']:'';
            if ($user===''||$pass==='') $error='Enter your username and password.';
            elseif (!checkLogin($user,$pass)) $error='The username or password is incorrect.';
            else { session_regenerate_id(true); $_SESSION['user']=$user; $_SESSION['site_csrf']=bin2hex(random_bytes(32)); go('?p=home'); }
        } elseif ($route==='register') {
            $user=scalar_post('username');$display=scalar_post('display');$email=scalar_post('email');
            $pass=isset($_POST['pass'])&&is_string($_POST['pass'])?$_POST['pass']:'';
            $pass2=isset($_POST['pass2'])&&is_string($_POST['pass2'])?$_POST['pass2']:'';
            if ($user===''||$display===''||$email===''||$pass==='') $error='Complete every account field.';
            elseif (!preg_match('/^[A-Za-z0-9_.-]{3,64}$/D',$user)) $error='Use 3–64 letters, numbers, dots, underscores, or hyphens for your username.';
            elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254||strlen($display)>200) $error='Check your email and display name.';
            elseif (strlen($pass)<12||strlen($pass)>72) $error='Use a password between 12 and 72 bytes long.';
            elseif ($pass!==$pass2) $error='The passwords do not match.';
            elseif (checkUser($user)||checkEmail($email)) $error='That username or email is already registered.';
            else {
                $s=$db->prepare('INSERT INTO users (name,email,username,password,admin,status,salt) VALUES (:name,:email,:username,:password,0,1,:salt)');
                if (!$s->execute(['name'=>$display,'email'=>$email,'username'=>$user,'password'=>password_hash($pass,PASSWORD_DEFAULT),'salt'=>bin2hex(random_bytes(16))])) throw new RuntimeException('Registration failed');
                flash('Account created. You can now sign in.'); go('?p=login');
            }
        } elseif ($route==='treatment'&&$logged&&scalar_post('action')==='save') {
            foreach ($Q as $question) $old[$question[0]]=scalar_post($question[0]);
            foreach (['madAbout','express','thoughts','feelings','act','better'] as $key) {
                $v=$old[$key];
                if (preg_match('//u',$v)!==1 || preg_match('/\S/u',$v)!==1 || strlen($v)>16000 || preg_match_all('/./us',$v)>4000) { $error='Every written answer is required and must contain no more than 4,000 characters.'; break; }
            }
            if (!preg_match('/^option(?:[1-9]|10)$/D',$old['angerScale'])) $error='Select a mood intensity from 1 to 10.';
            if (!in_array($old['public'],['0','1'],true)) $error='Choose whether to keep this entry private or make it public.';
            if (!$error) {
                $values=$old;$values['public']=(int)$values['public'];$marker='plain:v1';if ((string)getCode($uid)===$marker) $marker='plain:v1:alternate';
                $values+=['month'=>date('m'),'day'=>date('d'),'year'=>date('Y'),'username'=>$uid,'keycode'=>$marker];
                $cols=array_keys($values);$s=$db->prepare('INSERT INTO anger ('.implode(',',$cols).') VALUES (:'.implode(',:',$cols).')');
                if (!$s->execute($values)) throw new RuntimeException('Save failed');
                flash('Check-in saved as '.($values['public']?'public. Your displayed name and answers may now appear in public logs.':'private. It will not appear in public logs.'));go('?p=anger');
            }
        } elseif ($logged&&in_array($route,['anger','allLogs','viewLog'],true)&&scalar_post('action')==='visibility') {
            $id=scalar_post('id');$v=scalar_post('public');
            if (!ctype_digit($id)||!in_array($v,['0','1'],true)) $error='Invalid visibility request.';
            else {
                $rows=query_rows('SELECT id FROM anger WHERE id=:id AND username=:owner',['id'=>$id,'owner'=>$uid]);
                if (!$rows) { http_response_code(404);$error='Entry not found.'; }
                else { $s=$db->prepare('UPDATE anger SET public=:public WHERE id=:id AND username=:owner');if (!$s->execute(['public'=>(int)$v,'id'=>$id,'owner'=>$uid])) throw new RuntimeException('Update failed');flash($v==='1'?'Entry is now public.':'Entry is now private.');go($route==='viewLog'?'?p=viewLog&id='.(int)$id:'?p='.$route); }
            }
        }
    } catch (Throwable $ex) { error_log('Mood Tools request failed: '.get_class($ex));$error='We could not complete that request. Please try again. Your form has not been cleared.'; }
}
if ($route==='logout'&&$_SERVER['REQUEST_METHOD']!=='POST') { $route=$logged?'home':'login'; }
if ($logged&&in_array($route,['login','register'],true)) go('?p=home');
$titles=['home'=>['YOUR PERSONAL SPACE','A little awareness. A new perspective.','Notice patterns in your moods and make room for a supportive next step.'],'login'=>['WELCOME BACK','Sign in','Make space for your next step.'],'register'=>['GET STARTED','Create an account','Your personal space for reflection.'],'treatment'=>['GUIDED MOOD CHECK-IN','One question. One moment at a time.','Check in with what you are feeling, then choose whether to share.'],'anger'=>['YOUR REFLECTIONS','This month’s mood check-ins','Review recent moments and choose what stays private.'],'allLogs'=>['YOUR JOURNEY','All my reflections','Every check-in, in one consistent place.'],'publicanger'=>['SHARED REFLECTIONS','Community reflections','Check-ins this month that community members chose to share.'],'viewLog'=>['REFLECTION DETAILS','A moment in perspective','Look back on the trigger, your response, and your next step.'],'budget'=>['LEGACY SCALE DEMO','Explore mood intensity','A standalone intensity control; this page does not save an entry.']];
$title=$titles[$route]??['PAGE NOT FOUND','This page is not available.','Use the navigation to return to your workspace.'];
$nav=[['home','◈','Overview'],['treatment','＋','New check-in'],['anger','▤','This month’s check-ins'],['allLogs','◷','All check-ins'],['publicanger','◎','Community']];
?><!doctype html><html lang="en" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($title[1]) ?> · Mood Tools</title><link rel="stylesheet" href="assets/site.css?v=2"><script src="assets/site.js?v=2" defer></script></head><body>
<a class="quiet" href="#main" style="position:absolute;left:-9999px" onfocus="this.style.left='10px'" onblur="this.style.left='-9999px'">Skip to content</a>
<header class="topbar"><a class="brand" href="?p=home">♡ Mood<span>Tools</span></a><div class="top-actions"><?php if($logged): ?><span class="signed"><?= e(display($_SESSION['user'])) ?></span><?php endif; ?><button class="quiet" type="button" data-theme-toggle>Light mode</button><?php if($logged): ?><form method="post" action="?p=logout"><?=csrf()?><button class="quiet" type="submit">Sign out</button></form><button class="quiet mobile-menu" type="button" data-menu aria-expanded="false" aria-controls="site-nav">Menu</button><?php endif;?></div></header>
<div class="shell"<?=!$logged?' style="grid-template-columns:1fr"':''?>><?php if($logged):?><nav class="sidebar" id="site-nav" aria-label="Main navigation"><div class="nav-label">Your workspace</div><?php foreach($nav as [$key,$icon,$label]):?><a class="navlink <?=$key===$route?'active':''?>" href="?p=<?=e($key)?>" <?=$key===$route?'aria-current="page"':''?>><span class="nav-icon" aria-hidden="true"><?=e($icon)?></span><?=e($label)?></a><?php endforeach;?><div class="sidebar-note">Small steps.<br>More understanding.<p style="margin:12px 0 0">Your check-in starts with a pause.</p></div></nav><?php endif;?>
<main class="main" id="main"><?php if(!in_array($route,['login','register'],true)):?><div class="page-head"><div><div class="eyebrow"><?=e($title[0])?></div><h1><?=e($title[1])?></h1><p><?=e($title[2])?></p></div><?php if($logged&&$route!=='treatment'):?><a class="btn primary" href="?p=treatment">＋ New reflection</a><?php endif;?></div><?php endif;?>
<?php if(isset($_SESSION['site_flash'])):?><div class="notice" role="status"><?=e($_SESSION['site_flash'])?></div><?php unset($_SESSION['site_flash']);endif;?>
<?php if($error):?><div class="notice" role="alert"><?=e($error)?></div><?php endif;?>
<?php if($notFound):http_response_code(404);?><section class="panel empty"><h2>Page not found</h2><a class="btn primary" href="?p=home">Return home</a></section>
<?php elseif($route==='login'||$route==='register'): $reg=$route==='register';?>
<section class="auth"><div class="panel"><div class="auth-mark" aria-hidden="true">♡</div><div class="eyebrow"><?=$reg?'A fresh start':'Welcome back'?></div><h1 style="margin-top:12px"><?=$reg?'Create your account':'Your space to reflect.'?></h1><p class="muted"><?=$reg?'Start with a few details. All fields are required.':'Sign in to track, understand, and reflect.'?></p><form method="post" action="?p=<?=$reg?'register':'login'?>"><?=csrf()?>
<div class="field"><label for="username">Username</label><input id="username" name="username" autocomplete="username" required maxlength="64" value="<?=e(scalar_post('username'))?>" <?=$reg?'minlength="3" pattern="[A-Za-z0-9_.\\-]{3,64}"':''?>></div>
<?php if($reg):?><div class="field"><label for="display">Display name</label><input id="display" name="display" autocomplete="nickname" required maxlength="200" value="<?=e(scalar_post('display'))?>"><small>Shown alongside entries you choose to make public.</small></div><div class="field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" maxlength="254" required value="<?=e(scalar_post('email'))?>"></div><?php endif;?>
<div class="field"><label for="pass">Password</label><input id="pass" name="pass" type="password" autocomplete="<?=$reg?'new-password':'current-password'?>" required <?=$reg?'minlength="12"':''?>><?php if($reg):?><small>12–72 bytes. Non-ASCII characters may use more than one byte.</small><?php endif;?></div><?php if($reg):?><div class="field"><label for="pass2">Confirm password</label><input id="pass2" name="pass2" type="password" autocomplete="new-password" required minlength="12"></div><?php endif;?><button class="btn primary" type="submit"><?=$reg?'Create account':'Sign in →'?></button></form><p class="auth-note"><?=$reg?'Already have an account?':'New here?'?> <a href="?p=<?=$reg?'login':'register'?>"><?=$reg?'Sign in':'Create an account'?></a></p></div></section>
<?php elseif($route==='treatment'):?><div class="wizard-layout"><aside class="wizard-aside"><div class="eyebrow">One step at a time</div><h2 style="margin-top:12px">Pause. Reflect.<br>Move forward.</h2><p>Take your time. Every question is required, and you can go back whenever you need.</p><ol class="steps" id="stepList"></ol><p>Your answers are saved only when you finish and submit.</p></aside><section class="panel" id="wizardPanel"><div class="wizard-meta"><span id="stepText" aria-live="polite"></span><span class="badge">Guided reflection</span></div><progress id="progress" max="8" value="1" aria-label="Current question"></progress><form id="reflection" method="post" action="?p=treatment" novalidate><?=csrf()?><input type="hidden" name="action" value="save"><div id="questions"></div><div class="error" id="wizard-error" role="alert"></div><div class="wizard-actions"><button class="btn" id="back" type="button">← Back</button><button class="btn primary" id="next" type="submit">Continue →</button></div></form><noscript><div class="notice">JavaScript is needed for the guided form. Please enable it to continue.</div></noscript></section></div><script>window.reflectionOld=<?=json_encode($old,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE)?>;</script>
<?php elseif($route==='home'):
$rows=query_rows('SELECT * FROM anger WHERE username=:owner ORDER BY timestamp DESC,id DESC',['owner'=>$uid]);
$current=array_values(array_filter($rows,fn($r)=>(string)$r['month']===date('m')&&(string)$r['year']===date('Y')));
$levels=array_map(fn($r)=>(int)str_replace('option','',(string)$r['angerScale']),$current);$levels=array_values(array_filter($levels,fn($n)=>$n>=1&&$n<=10));
$avg=$levels?number_format(array_sum($levels)/count($levels),1):'—';$private=count(array_filter($rows,fn($r)=>(int)$r['public']===0));
?><div class="stats"><?php foreach([['◈',count($current),'Mood check-ins this month'],['↗',$avg,'Average mood intensity · 1–10'],['◇',$private,'Private entries · all time']] as [$icon,$num,$label]):?><section class="panel stat"><div class="stat-icon" aria-hidden="true"><?=$icon?></div><div class="stat-label"><?=e($label)?></div><div class="stat-value"><?=e($num)?></div></section><?php endforeach;?></div>
<div class="grid2"><section class="panel"><div class="panel-title"><h2>Last 14 days</h2><span class="muted">Daily average mood intensity · 1–10</span></div><?php
$days=[];for($i=13;$i>=0;$i--){$d=date('Y-m-d',strtotime('-'.$i.' days'));$days[$d]=[];}
foreach($rows as $r){$d=substr((string)$r['timestamp'],0,10);$v=(int)str_replace('option','',(string)$r['angerScale']);if(isset($days[$d])&&$v>=1&&$v<=10)$days[$d][]=$v;}
?><div class="chart" role="img" aria-label="Daily average mood intensity over the last fourteen calendar days. Individual values follow in the accessible summary."><?php foreach($days as $d=>$vals):$v=$vals?array_sum($vals)/count($vals):0;?><div class="bar" style="height:<?=$v*10?>%" title="<?=e($d.': '.($vals?number_format($v,1).' out of 10':'no entries'))?>"></div><?php endforeach;?></div><div class="chart-labels"><span><?=e(date('M j',strtotime('-13 days')))?></span><span>Today</span></div><details style="margin-top:16px"><summary class="muted">View daily values</summary><ul class="muted"><?php foreach($days as $d=>$vals):?><li><?=e($d)?>: <?=$vals?e(number_format(array_sum($vals)/count($vals),1)).' / 10':'No entries'?></li><?php endforeach;?></ul></details></section><section class="panel intro-panel"><div class="eyebrow">A moment for yourself</div><h2 style="margin-top:18px">Notice the pattern.<br>Choose your next step.</h2><p>Check in with a recent moment, notice your mood and response, and choose a supportive next step.</p><a class="btn primary" href="?p=treatment">Start a check-in →</a><p class="muted" style="margin:18px 0 0">A gentle guided check-in. Nothing is shared without your explicit choice.</p></section></div>
<section class="panel"><div class="panel-title"><h2>Recent check-ins</h2><a href="?p=allLogs" class="muted">View all →</a></div><?php $listRows=array_slice($rows,0,5);$showNames=false;include __DIR__.'/templates/log-table.php';?></section>
<?php elseif(in_array($route,['anger','allLogs','publicanger'],true)):
$showNames=$route==='publicanger';
if($showNames)$listRows=query_rows('SELECT * FROM anger WHERE public=1 AND month=:m AND year=:y ORDER BY timestamp DESC,id DESC',['m'=>date('m'),'y'=>date('Y')]);
elseif($route==='anger')$listRows=query_rows('SELECT * FROM anger WHERE username=:owner AND month=:m AND year=:y ORDER BY timestamp DESC,id DESC',['owner'=>$uid,'m'=>date('m'),'y'=>date('Y')]);
else $listRows=query_rows('SELECT * FROM anger WHERE username=:owner ORDER BY timestamp DESC,id DESC',['owner'=>$uid]);
?><section class="panel"><div class="searchbar"><div class="field"><label for="logSearch">Search these entries</label><input type="search" id="logSearch" data-log-search placeholder="Search by date, check-in number, or visibility"></div><span class="muted" id="search-status" role="status"><?=count($listRows)?> entries</span></div><?php include __DIR__.'/templates/log-table.php';?></section>
<?php elseif($route==='viewLog'):
$id=isset($_GET['id'])&&is_string($_GET['id'])&&ctype_digit($_GET['id'])?$_GET['id']:'0';
$found=query_rows('SELECT * FROM anger WHERE id=:id AND (username=:owner OR public=1)',['id'=>$id,'owner'=>$uid]);
if(!$found):http_response_code(404);?><section class="panel empty"><h2>Entry not found</h2><p>This entry is unavailable or you do not have access to it.</p><a class="btn" href="?p=anger">Back to my logs</a></section><?php else:$row=read_answers($found[0]);$owner=(string)$row['username']===(string)$uid;?>
<section class="panel"><div class="panel-title"><div><h2>Reflection #<?=e($row['id'])?></h2><span class="muted"><?=e($row['timestamp'])?> · <?=e(getName($row['username']))?></span></div><span class="badge <?=(int)$row['public']===1?'public':''?>"><?=(int)$row['public']===1?'Public':'Private'?></span></div><?php foreach($Q as [$name,$label]):if($name==='public')continue;?><div class="answer"><h3><?=e($label)?></h3><p><?=e($name==='angerScale'?str_replace('option','',$row[$name]).' / 10':$row[$name]??'')?></p></div><?php endforeach;?><?php if($owner):?><div class="inline-actions"><form class="toolbar-form" method="post" action="?p=viewLog&id=<?=e($row['id'])?>" <?=(int)$row['public']===0?'data-confirm-public':''?>><?=csrf()?><input type="hidden" name="action" value="visibility"><input type="hidden" name="id" value="<?=e($row['id'])?>"><input type="hidden" name="public" value="<?=(int)$row['public']===1?'0':'1'?>"><button class="btn" type="submit"><?=(int)$row['public']===1?'Make private':'Make public'?></button></form><a class="btn" href="?p=allLogs">Back to my logs</a></div><?php endif;?></section><?php endif;?>
<?php elseif($route==='budget'):?><section class="panel"><div class="field"><label for="scaleDemo">Anger intensity · <output id="scaleValue" for="scaleDemo">1</output> / 10</label><input id="scaleDemo" type="range" min="1" max="10" value="1" oninput="document.getElementById('scaleValue').value=this.value"><small>This legacy demonstration is separate from the required reflection form and saves nothing.</small></div><a class="btn primary" href="?p=treatment">Open guided reflection</a></section><?php endif;?>
<footer class="footer"><span>Mood Tools · A little awareness. A new perspective.</span><span>© <?=date('Y')?> · Everyday reflection, not a diagnosis or substitute for care.</span></footer></main></div></body></html><?php ob_end_flush(); ?>
