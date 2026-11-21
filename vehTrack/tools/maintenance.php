<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require __DIR__.'/../app/core.php';
try{
 $install=fopen(private_dir().'/install.mutex','c');flock($install,LOCK_EX);$write=fopen(private_dir().'/write.mutex','c');flock($write,LOCK_EX);$action=$argv[1]??'help';
 if($action==='recover-pending-config'){
  if(is_file(cfgpath()))throw new RuntimeException('Configuration already exists.');$pending=cfgpath().'.pending';if(!is_file($pending))throw new RuntimeException('No pending configuration. Restore your backed-up config.php instead.');$c=require $pending;$p=db($c);$marker=sql($p,'SELECT value FROM meta WHERE name=?',['installation_id'])->fetchColumn();$users=sql($p,'SELECT COUNT(*) FROM users')->fetchColumn();if(!$marker||!$users)throw new RuntimeException('Database installation did not commit. Restore backup or inspect the failed new installation.');if(!rename($pending,cfgpath()))throw new RuntimeException('Cannot publish configuration.');echo "Configuration recovered. Run recover-lock next.\n";
 }elseif($action==='recover-lock'){
  if(is_file(private_dir().'/config.lock'))throw new RuntimeException('Installation already locked; no action taken.');$p=db();$marker=sql($p,'SELECT value FROM meta WHERE name=?',['installation_id'])->fetchColumn();if(!$marker||!sql($p,'SELECT COUNT(*) FROM users')->fetchColumn())throw new RuntimeException('Not a recognized initialized database. Do not run setup over unknown data.');file_put_contents(private_dir().'/config.lock',"Recovered installation lock ".gmdate('c')."\n",LOCK_EX);chmod(private_dir().'/config.lock',0600);echo "Lock restored. No application data changed.\n";
 }elseif($action==='migrate'){
  if(!in_array('--backup-confirmed',$argv,true))throw new RuntimeException('Back up database + private storage first, then pass --backup-confirmed.');if(!installed())throw new RuntimeException('Restore the installation lock before upgrading.');$p=db();$version=(int)sql($p,'SELECT value FROM meta WHERE name=?',['schema_version'])->fetchColumn();if($version<1||$version>2)throw new RuntimeException('Unknown schema version. No changes made. External legacy schemas need an explicit import mapping.');if($version===1){require ROOT.'/migrations/002-preserve-driver-attribution.php';migration_002($p);}echo "Database schema 2 ready. Existing records and lock preserved.\n";
 }elseif($action==='info'){
  $p=db();echo 'Private directory: '.private_dir()."\nLocked: ".(installed()?'yes':'NO')."\nDriver: ".$p->getAttribute(PDO::ATTR_DRIVER_NAME)."\nSchema: ".sql($p,'SELECT value FROM meta WHERE name=?',['schema_version'])->fetchColumn()."\nRecords: ".sql($p,'SELECT COUNT(*) FROM records')->fetchColumn()."\n";
 }else echo "Commands: info | recover-lock | recover-pending-config | migrate --backup-confirmed\nSet VT_PRIVATE to the installation's exact private directory.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
