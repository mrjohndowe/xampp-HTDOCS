<?php
// Called only by the CLI maintenance command under the global writer lock.
function migration_002(PDO $p): void {
 $p->beginTransaction();
 try {
  foreach(sql($p,'SELECT id,kind,payload FROM records')->fetchAll() as $row){
   $d=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);$changed=false;
   if(in_array($row['kind'],['trips','inspections'],true)&&!array_key_exists('driver_id',$d)){$d['driver_id']=null;$changed=true;}
   if($row['kind']==='expenses'&&($d['category']??'')==='Fuel'&&(!isset($d['gallons'],$d['odometer'],$d['full'])||!is_numeric($d['gallons'])||(float)$d['gallons']<=0)){$d['fuel_details_incomplete']=true;$changed=true;}
   if($changed){sql($p,'UPDATE records SET payload=? WHERE id=?',[json_encode($d),$row['id']]);sql($p,'INSERT INTO revisions(record_id,user_id,payload,action,created) VALUES(?,?,?,?,?)',[$row['id'],0,json_encode(['migration'=>2,'before'=>json_decode($row['payload'],true),'after'=>$d]),'migration',gmdate('c')]);}
  }
  sql($p,'UPDATE meta SET value=? WHERE name=?',['2','schema_version']);
  sql($p,'INSERT INTO meta(name,value) VALUES(?,?)',['migration_002_at',gmdate('c')]);$p->commit();
 }catch(Throwable $e){if($p->inTransaction())$p->rollBack();throw $e;}
}
