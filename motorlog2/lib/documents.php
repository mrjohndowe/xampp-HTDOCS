<?php
declare(strict_types=1);

const CHECKLIST = [
    'tires'=>['Wheels & brakes','Tire condition, tread and pressure'],
    'service_brake'=>['Wheels & brakes','Service brakes'], 'parking_brake'=>['Wheels & brakes','Parking brake'],
    'headlights'=>['Lights & visibility','Headlights and running lights'], 'brake_lights'=>['Lights & visibility','Brake lights'],
    'signals'=>['Lights & visibility','Turn signals and hazards'], 'windshield'=>['Lights & visibility','Windshield and mirrors'],
    'wipers'=>['Lights & visibility','Wipers and washer fluid'], 'oil'=>['Under the hood','Engine oil'],
    'coolant'=>['Under the hood','Coolant and other fluids'], 'leaks'=>['Under the hood','Visible leaks'],
    'battery'=>['Under the hood','Battery and connections'], 'steering'=>['Safety & equipment','Steering and suspension'],
    'belts'=>['Safety & equipment','Seat belts'], 'horn'=>['Safety & equipment','Horn'], 'body'=>['Safety & equipment','Body damage'],
    'equipment'=>['Safety & equipment','Emergency equipment and spare tire'],
    'documents'=>['Safety & equipment','Registration and insurance'],
];
const MOBILE_CHECKLIST = [
    'mobile_76dd380030e7e0dc1ede2ad2'=>['IDENTIFICATION & DOCUMENTS','VIN / vehicle identification'],
    'mobile_51608e89efcd76d6298b61c8'=>['IDENTIFICATION & DOCUMENTS','License plate and registration'],
    'mobile_04c9656241d48e39cd035053'=>['IDENTIFICATION & DOCUMENTS','Insurance card / proof of insurance'],
    'mobile_a39d260a0949c7af085432e2'=>['IDENTIFICATION & DOCUMENTS','Odometer reading recorded'],
    'mobile_22e6bf471465bc42f16e89bc'=>['EXTERIOR & BODY','Body panels free of unsafe damage'],
    'mobile_a0bd6c7bc63e6978c6c4af92'=>['EXTERIOR & BODY','Bumpers and guards secure'],
    'mobile_ecc86e34010a6483e4bb7dce'=>['EXTERIOR & BODY','Doors open, close and latch'],
    'mobile_4d46335c38e2d289a52e1407'=>['EXTERIOR & BODY','Hood and trunk/hatch latch securely'],
    'mobile_52af19bc6769991433b4b100'=>['EXTERIOR & BODY','Windows free of dangerous cracks'],
    'mobile_16ae6a7e33483542f3addfd3'=>['EXTERIOR & BODY','Windshield free of critical damage'],
    'mobile_39b355462369174d4bbac641'=>['EXTERIOR & BODY','Mirrors present, secure and properly positioned'],
    'mobile_99e33c24b6c6d6f0a2d31bd9'=>['TIRES & WHEELS','Left front tire condition and tread'],
    'mobile_e297d6481888b0e8ce6b9cb2'=>['TIRES & WHEELS','Right front tire condition and tread'],
    'mobile_122628c3376a718a47783162'=>['TIRES & WHEELS','Left rear tire condition and tread'],
    'mobile_7ab9a1316256878e7434f8a7'=>['TIRES & WHEELS','Right rear tire condition and tread'],
    'mobile_7c5e3ad972aa84380111c8f2'=>['TIRES & WHEELS','Spare tire / inflator / repair kit present'],
    'mobile_8039db226148a4a99e36851b'=>['TIRES & WHEELS','Wheels/rims free of dangerous damage'],
    'mobile_cabcda64629f33ca12e8b687'=>['TIRES & WHEELS','Lug nuts/studs appear secure'],
    'mobile_afca00915eac9143d04f4e86'=>['TIRES & WHEELS','Tire pressures checked'],
    'mobile_aba48195353d6846454950ea'=>['LIGHTS & ELECTRICAL','Low-beam headlights'],
    'mobile_a94dc6852d1fcf665b76fe30'=>['LIGHTS & ELECTRICAL','High-beam headlights'],
    'mobile_469d5b8779f47f22cbc2bf1d'=>['LIGHTS & ELECTRICAL','Front turn signals'],
    'mobile_04cdc30c189a147c9bac634d'=>['LIGHTS & ELECTRICAL','Rear turn signals'],
    'mobile_033a8e75153b6df6b95623b1'=>['LIGHTS & ELECTRICAL','Brake lights'],
    'mobile_d70ef01baaedf29eb2eab580'=>['LIGHTS & ELECTRICAL','Tail/marker lights'],
    'mobile_a36a2d3f3d95b95994a8d043'=>['LIGHTS & ELECTRICAL','Hazard flashers'],
    'mobile_b97c4b3d71e0f41a41bd1972'=>['LIGHTS & ELECTRICAL','Reverse lights'],
    'mobile_6797962cd4708f298ca81e3d'=>['LIGHTS & ELECTRICAL','License plate light'],
    'mobile_a2f63ea2218f7a6746578ce7'=>['LIGHTS & ELECTRICAL','Interior lights'],
    'mobile_0a88ae7d59ef30e56a3ede22'=>['LIGHTS & ELECTRICAL','Battery / terminals appear secure'],
    'mobile_fc1660de0ab56b40db4a82f5'=>['LIGHTS & ELECTRICAL','No critical warning lights illuminated'],
    'mobile_9ebf503462b39881494f5a5c'=>['CONTROLS & VISIBILITY','Horn'],
    'mobile_a07e5b8f9a68c6c14423689c'=>['CONTROLS & VISIBILITY','Windshield wipers'],
    'mobile_9e088210dd08f00678342856'=>['CONTROLS & VISIBILITY','Windshield washer fluid/spray'],
    'mobile_e4e0db528b4aa9c3e41ae747'=>['CONTROLS & VISIBILITY','Defroster / defogger'],
    'mobile_53d4b3db98eb14d757dac0b6'=>['CONTROLS & VISIBILITY','Parking brake'],
    'mobile_5ae6e9a96a1c23a313875bdc'=>['CONTROLS & VISIBILITY','Steering wheel/control response'],
    'mobile_a6a7ae60c9fd56a13c67cf90'=>['CONTROLS & VISIBILITY','Seat adjustment controls lock securely'],
    'mobile_fa9e57bb5f754f5d3a4fdcf9'=>['BRAKES, STEERING & SUSPENSION','Brake pedal feels firm and normal'],
    'mobile_66c899f750f4808018285dc8'=>['BRAKES, STEERING & SUSPENSION','Parking brake holds vehicle'],
    'mobile_f54e3fba891f874d0d57a46c'=>['BRAKES, STEERING & SUSPENSION','No abnormal brake noise/vibration'],
    'mobile_d201bd3a79ca13cc2dfa1b7f'=>['BRAKES, STEERING & SUSPENSION','Steering response is normal'],
    'mobile_7ae8386be3d62cd328865664'=>['BRAKES, STEERING & SUSPENSION','No excessive steering play'],
    'mobile_80ebaf98171681b65f2bcff4'=>['BRAKES, STEERING & SUSPENSION','No unusual suspension noise'],
    'mobile_414bb2b4f8d240437685def8'=>['BRAKES, STEERING & SUSPENSION','Vehicle sits level with no obvious suspension issue'],
    'mobile_42297c8b30675c5ab72f08ac'=>['ENGINE & UNDER-HOOD','Engine oil level/condition checked'],
    'mobile_29b75f575139dd39f29f0617'=>['ENGINE & UNDER-HOOD','Coolant level checked when safe'],
    'mobile_109cd4fb1381525b8b6211ec'=>['ENGINE & UNDER-HOOD','Brake fluid level checked'],
    'mobile_7e8ad7d1863fa5d1499517e1'=>['ENGINE & UNDER-HOOD','Power steering fluid checked if equipped'],
    'mobile_f29aff77565c990971620549'=>['ENGINE & UNDER-HOOD','Washer fluid level checked'],
    'mobile_ac0a4542d083df87ffa31c27'=>['ENGINE & UNDER-HOOD','Belts/hoses appear serviceable'],
    'mobile_8ad0c4885c905d1d052c08ec'=>['ENGINE & UNDER-HOOD','No visible fluid leaks'],
    'mobile_05835887fc06879a1564d370'=>['ENGINE & UNDER-HOOD','Engine starts and idles normally'],
    'mobile_402fc01581829dd47978a58d'=>['EXHAUST & EMISSIONS','Exhaust system appears secure'],
    'mobile_8cc45fa6c27e5a407391a897'=>['EXHAUST & EMISSIONS','No unusual exhaust noise'],
    'mobile_e02922d5e2ddf3f1c1a48cab'=>['EXHAUST & EMISSIONS','No visible exhaust leak'],
    'mobile_e72327063d7fd6672bb97f54'=>['EXHAUST & EMISSIONS','No unusual smoke/odor'],
    'mobile_f03959f85f5f062ac510697a'=>['INTERIOR & PASSENGER SAFETY','Driver seat secure'],
    'mobile_55fa59f19a315deafdaf8649'=>['INTERIOR & PASSENGER SAFETY','Passenger seats secure'],
    'mobile_3c45a823d46ac13562fda7f5'=>['INTERIOR & PASSENGER SAFETY','All accessible seat belts latch and retract'],
    'mobile_016146a8ca96661bf7e89ff1'=>['INTERIOR & PASSENGER SAFETY','Seat belt webbing undamaged'],
    'mobile_ef3190bb8ba89c0cbfb43e52'=>['INTERIOR & PASSENGER SAFETY','Airbag/SRS warning status checked'],
    'mobile_5c2ba3b2a742b7e2e465369c'=>['INTERIOR & PASSENGER SAFETY','Child-seat anchors/latches accessible if equipped'],
    'mobile_f78a0d9caa4465708ac29a97'=>['INTERIOR & PASSENGER SAFETY','Floor areas clear of loose hazards'],
    'mobile_c9c5636f38f91f21fcef809a'=>['INTERIOR & PASSENGER SAFETY','Emergency exit/access paths clear'],
    'mobile_69cd7ac0c5ec47b494cd9596'=>['SAFETY EQUIPMENT','First-aid kit present if required'],
    'mobile_1723e24bcad28e8a6abe032b'=>['SAFETY EQUIPMENT','Fire extinguisher present if required'],
    'mobile_77a699f9b289957c26d654a8'=>['SAFETY EQUIPMENT','Warning triangles/reflectors present if required'],
    'mobile_d5cd6f8bcdbadfa2467524c6'=>['SAFETY EQUIPMENT','Flashlight/emergency supplies present'],
    'mobile_83599ec7131c64406477a255'=>['SAFETY EQUIPMENT','Jack/tools present if applicable'],
    'mobile_81137567cf0205ff36d448c8'=>['ROAD TEST / FINAL CHECK','Vehicle accelerates normally'],
    'mobile_e25e27c4197fdf142747cc8c'=>['ROAD TEST / FINAL CHECK','Vehicle tracks straight'],
    'mobile_31d881ce31bb5a1dd98ca04e'=>['ROAD TEST / FINAL CHECK','Brakes operate normally during controlled test'],
    'mobile_e2ccf2ec2a6a303f7d514fef'=>['ROAD TEST / FINAL CHECK','No abnormal vibration'],
    'mobile_9ebbf72e0f91413340415638'=>['ROAD TEST / FINAL CHECK','No abnormal warning lights after test'],
    'mobile_56ecf730e8f45424be310a24'=>['ROAD TEST / FINAL CHECK','No unusual noises during operation'],
];

function inspection_checklist(array $items): array {
    return array_intersect_key($items, CHECKLIST) ? CHECKLIST : MOBILE_CHECKLIST;
}

const RESULTS = ['pass'=>'Pass','attention'=>'Needs attention','fail'=>'Fail','na'=>'N/A'];
const ISSUE_STATES = ['open'=>'Open','in_progress'=>'In progress','resolved'=>'Resolved'];
const DISCLOSURES = ['Actual mileage','Exceeds mechanical limits','Not actual mileage'];
const TITLE_STATES = ['Lien-free title declared','Lien or claim disclosed','Title status uncertain / explained'];
const WARRANTY_STATES = ['Sold as-is (review local requirements)','Written warranty applies','Other terms specified'];
const PAYMENT_STATES = ['Unpaid','Deposit received','Paid in full','Other arrangement'];

function audit_doc(PDO $db, int $id, int $user, string $action, array $snapshot): void {
    insert_row($db,'document_audit',['document_id'=>$id,'action'=>$action,'actor_id'=>$user,'logged_at'=>now_iso(),'snapshot'=>json_text($snapshot)]);
}
function template_list(PDO $db): array {
    return [['id'=>0,'name'=>'General vehicle bill of sale','jurisdiction'=>'General template','terms'=>'','witness'=>0,'notary'=>0],
        ...rows($db,'SELECT * FROM sale_templates ORDER BY name,id')];
}
function write_template(PDO $db, string $action, array $d): array {
    $v=['name'=>text_field($d['name'] ?? '', 'Template name',150,true),'jurisdiction'=>text_field($d['jurisdiction'] ?? '', 'Jurisdiction',150,true),
        'terms'=>text_field($d['terms'] ?? '', 'Suggested terms',6000),'witness'=>bool_field($d['witness'] ?? false)?1:0,'notary'=>bool_field($d['notary'] ?? false)?1:0];
    if ($action === 'update') { $id=id_field($d['id'] ?? null,'Template'); require_row($db,'sale_templates',$id,'Template'); update_row($db,'sale_templates',$id,$v); }
    elseif ($action === 'create') $id=insert_row($db,'sale_templates',[...$v,'created_at'=>now_iso()]);
    else fail('Unknown template action.',400);
    return ['id'=>$id,'message'=>'Jurisdiction template saved.'];
}
function document_row(PDO $db, int $id, string $type = ''): array {
    $r = require_row($db, 'documents', $id, 'Document');
    if ($type !== '' && $r['doc_type'] !== $type) fail('Document was not found.',404,'not_found');
    return $r;
}
function document_pack(PDO $db, array $r, bool $detail = true): array {
    $id=(int)$r['id']; $r['data']=json_array($r['payload']); unset($r['payload']);
    if (!isset($r['vehicle_name'])) $r['vehicle_name'] = $r['vehicle_id'] ? vehicle_name(require_row($db,'vehicles',(int)$r['vehicle_id'],'Vehicle')) : '';
    if (($name=trim(($r['data']['model_year'] ?? '').' '.($r['data']['make'] ?? '').' '.($r['data']['model'] ?? ''))) !== '') $r['vehicle_name']=$name;
    $r['driver_name']=$r['data']['inspector_name'] ?? '';
    if (!$r['driver_name'] && $r['driver_id']) $r['driver_name']=(string)val($db,'SELECT name FROM drivers WHERE id=?',[$r['driver_id']]);
    if ($r['doc_type']==='inspection') {
        $r['issues']=rows($db,'SELECT * FROM inspection_issues WHERE document_id=? ORDER BY id',[$id]);
        $r['unresolved']=count(array_filter($r['issues'],static fn($i)=>$i['status']!=='resolved'));
    }
    if ($detail) {
        $r['photos']=rows($db,"SELECT id,item_key,caption,view_label,mime,created_at FROM photos WHERE owner_kind='inspection' AND owner_id=? ORDER BY id",[$id]);
        if ($r['doc_type'] !== 'inspection') $r['photos']=[];
        $r['audit']=rows($db,'SELECT a.id,a.action,a.logged_at,a.snapshot,u.display_name AS actor FROM document_audit a JOIN users u ON u.id=a.actor_id WHERE document_id=? ORDER BY a.id DESC',[$id]);
        foreach ($r['audit'] as &$a) $a['snapshot']=json_array($a['snapshot']); unset($a);
        $r['revisions']=rows($db,'SELECT id,reference,status,created_at FROM documents WHERE parent_id=? ORDER BY id DESC',[$id]);
        if ($r['parent_id']) $r['parent_reference']=(string)val($db,'SELECT reference FROM documents WHERE id=?',[$r['parent_id']]);
    } else {
        // Do not load personal information or lengthy notes into lists and unrelated screens.
        $r['data']=array_intersect_key($r['data'],array_flip(['buyer_name','seller_name','balance','currency','price','inspector_name','odometer','unit','template_name']));
        unset($r['issues']);
    }
    return $r;
}
function document_list(PDO $db, string $type): array {
    $out=[];
    $people = []; foreach (rows($db,'SELECT id,name FROM drivers') as $d) $people[$d['id']]=$d['name'];
    $names = []; foreach (rows($db,'SELECT id,model_year,make,model FROM vehicles') as $v) $names[$v['id']]=vehicle_name($v);
    $unresolved=[];
    if ($type==='inspection') foreach (rows($db,"SELECT document_id,COUNT(*) AS total FROM inspection_issues WHERE status<>'resolved' GROUP BY document_id") as $i) $unresolved[$i['document_id']]=(int)$i['total'];
    foreach (rows($db,'SELECT id,doc_type,parent_id,reference,status,vehicle_id,driver_id,document_day,week_start,due_next,issue_count,result_count,payload,revision,created_by,created_at,updated_at,completed_at FROM documents WHERE doc_type=? ORDER BY updated_at DESC,id DESC',[$type]) as $r) {
        $p=json_array($r['payload']); unset($r['payload']);
        $r['data']=array_intersect_key($p,array_flip(['buyer_name','seller_name','balance','currency','price','inspector_name','odometer','unit','template_name']));
        $r['vehicle_name']=trim(($p['model_year'] ?? '').' '.($p['make'] ?? '').' '.($p['model'] ?? '')) ?: ($names[$r['vehicle_id'] ?? 0] ?? '');
        if ($type==='inspection') { $r['driver_name']=$p['inspector_name'] ?? ($people[$r['driver_id'] ?? 0] ?? ''); $r['unresolved']=$unresolved[$r['id']] ?? 0; }
        $out[]=$r;
    }
    return $out;
}
function weekly_statuses(PDO $db, array $vehicles): array {
    $week=monday(today_iso()); $due=[]; $out=[];
    foreach ($vehicles as $v) $out[$v['id']]=['status'=>'Not started','state'=>'not_started','overdue'=>false,'unresolved'=>0,'reference'=>null,'id'=>null,'week_start'=>$week,'due_next'=>next_monday($week)];
    $docs=rows($db,"SELECT id,parent_id,status,reference,vehicle_id,week_start,due_next,issue_count FROM documents WHERE doc_type='inspection' AND vehicle_id IS NOT NULL ORDER BY week_start DESC,completed_at DESC,id DESC");
    $old=array_map('intval',array_column(array_filter($docs, static fn($d)=>$d['status']==='submitted' && $d['parent_id']), 'parent_id'));
    foreach ($docs as $d) {
        $vid=$d['vehicle_id']; if (!isset($out[$vid]) || in_array((int)$d['id'],$old,true)) continue;
        if ($d['status']==='submitted' && $d['week_start'] <= $week && !isset($due[$vid])) $due[$vid]=$d['due_next'];
        if ($d['week_start']!==$week || in_array($out[$vid]['state'],['clear','findings'],true)) continue;
        if ($d['status']==='draft' && $out[$vid]['state']==='draft') continue;
        $s=$d['status']==='draft' ? ['Draft','draft'] : ((int)$d['issue_count']>0 ? ['Completed with findings','findings'] : ['Completed with no findings','clear']);
        $out[$vid]=[...$out[$vid],'status'=>$s[0],'state'=>$s[1],'reference'=>$d['reference'],'id'=>$d['id']];
    }
    foreach (rows($db,"SELECT d.vehicle_id,COUNT(*) AS total FROM inspection_issues i JOIN documents d ON d.id=i.document_id WHERE i.status<>'resolved' GROUP BY d.vehicle_id") as $r) if (isset($out[$r['vehicle_id']])) $out[$r['vehicle_id']]['unresolved']=(int)$r['total'];
    foreach ($vehicles as $v) {
        $vid=$v['id']; $oldWeek = substr($v['created_at'],0,10)<$week;
        $out[$vid]['overdue']=in_array($out[$vid]['state'],['not_started','draft'],true) && (isset($due[$vid]) ? $due[$vid]<=today_iso() : $oldWeek);
        $out[$vid]['due_next']=$due[$vid] ?? ($oldWeek ? $week : next_monday($week));
    }
    return $out;
}

function snapshot_vehicle(array $d, bool $final): array {
    return ['model_year'=>integer_field($d['model_year'] ?? '', 'Vehicle year',1885, (int)gmdate('Y')+2, false),
        'make'=>text_field($d['make'] ?? '', 'Vehicle make',150,$final),'model'=>text_field($d['model'] ?? '', 'Vehicle model',150,$final),
        'vin'=>text_field($d['vin'] ?? '', 'Vehicle identification number',70), 'plate'=>text_field($d['plate'] ?? '', 'Plate',50),
        'color'=>text_field($d['color'] ?? '', 'Color',70), 'odometer'=>odo_field($d['odometer'] ?? '', 'Odometer',$final),
        'unit'=>choice_field($d['unit'] ?? '', ['mi','km'], 'odometer unit',$final)];
}
function inspection_values(PDO $db, array $d, bool $final, ?array $old): array {
    $vid=id_field($d['vehicle_id'] ?? '', 'Vehicle',$final);
    if ($vid) require_row($db,'vehicles',$vid,'Vehicle');
    $preserve=isset($old['driver_id']) ? (int)$old['driver_id'] : null;
    $driver=driver_link($db,$d['driver_id'] ?? '',$preserve);
    $name=text_field($d['inspector_name'] ?? '', 'Inspector name',150);
    if ($final && !$driver && $name==='') fail('Select a driver or enter the inspector’s name.');
    if ($final && $name==='' && $driver) $name=(string)val($db,'SELECT name FROM drivers WHERE id=?',[$driver]);
    $date=date_field($d['inspection_date'] ?? '', 'Inspection date',$final);
    $time=clock_field($d['inspection_time'] ?? '', 'Inspection time',$final);
    if ($date && $date>today_iso() && $final) fail('A completed inspection cannot be scheduled in the future.');
    $snap=snapshot_vehicle($d,false);
    if ($final && ($snap['odometer']===null || $snap['unit']==='')) fail('Enter a valid odometer reading and distance unit.');
    if ($snap['unit']!=='' && $snap['unit']!==settings($db)['unit']) fail('Use the workspace odometer unit for inspections so mileage remains comparable.');
    if ($final) {
        // A correction replaces one document's mileage observation; the original remains in history.
        $skip=isset($old['parent_id']) ? [(int)$old['parent_id']] : [];
        check_observation($db,$vid,$date,'inspection',$snap['odometer'],$snap['odometer'],null,$time,$skip);
    }
    $items=[]; $issues=[]; $count=0;
    $given=is_array($d['items'] ?? null) ? $d['items'] : [];
    foreach (inspection_checklist($old ? (json_array($old['payload'])['items'] ?? []) : []) as $key=>[$group,$label]) {
        $i=is_array($given[$key] ?? null) ? $given[$key] : [];
        $result=choice_field($i['result'] ?? '', array_keys(RESULTS), 'result for '.$label,false);
        $notes=text_field($i['notes'] ?? '', 'Notes for '.$label,2200);
        if ($final && $result==='') fail('Select a result for “'.$label.'”.');
        if (in_array($result,['attention','fail'],true)) {
            if ($final && $notes==='') fail('Add explanatory notes for “'.$label.'” before submitting.');
            $issues[]=$key;
        }
        if ($result!=='') $count++;
        $items[$key]=['result'=>$result,'notes'=>$notes];
    }
    $ack=bool_field($d['acknowledged'] ?? false);
    $ackName=text_field($d['ack_name'] ?? '', 'Acknowledgment name',150,$final);
    if ($final && !$ack) fail('The inspector must actively acknowledge the completed inspection.');
    $payload=[...$snap,'items'=>$items,'inspector_name'=>$name,'inspection_date'=>$date,'inspection_time'=>$time,
        'comments'=>text_field($d['comments'] ?? '', 'Overall comments',6000),'acknowledged'=>$ack,'ack_name'=>$ackName,'ack_at'=>$final ? now_iso() : null];
    return [ 'vehicle_id'=>$vid,'driver_id'=>$driver,'document_day'=>$date,'week_start'=>$date ? monday($date) : null,'due_next'=>$date ? next_monday($date) : null,
        'issue_count'=>count($issues),'result_count'=>$count,'payload'=>json_text($payload), '_issues'=>$issues];
}
function sale_values(PDO $db, array $d, bool $final, ?array $old): array {
    $vid=id_field($d['vehicle_id'] ?? '', 'Linked vehicle',false);
    if ($vid) require_row($db,'vehicles',$vid,'Vehicle');
    $snap=snapshot_vehicle($d,$final);
    if ($final && ($snap['model_year']===null || $snap['vin']==='')) fail('Vehicle year and VIN are required to finalize.');
    $day=date_field($d['sale_date'] ?? '', 'Sale date',$final);
    $currency=currency_field($d['currency'] ?? settings($db)['currency']);
    $price=money_value($d['price'] ?? null,$currency,'Sale price',$final);
    $deposit=money_value($d['deposit'] ?? null,$currency,'Deposit',false);
    if ($price!==null && $deposit!==null && $deposit>$price) fail('Deposit cannot exceed the sale price.');
    $balance=$price===null ? null : $price-($deposit ?? 0);
    $payment=choice_field($d['payment_status'] ?? '',PAYMENT_STATES,'payment status',$final);
    $due=date_field($d['balance_due'] ?? '', 'Balance due date',false);
    if ($final && $due && $day && $due<$day) fail('Balance due date cannot precede the sale date.');
    if ($final && $payment==='Paid in full' && $balance!==0) fail('Paid in full requires zero remaining balance. Review the price, deposit and payment status.');
    $prev=json_array($old['payload'] ?? null);
    $template=integer_field($d['template_id'] ?? 0,'Template',0,2147483647);
    if ($old && isset($prev['template_id']) && $template===(int)$prev['template_id']) {
        $meta=['name'=>$prev['template_name'],'jurisdiction'=>$prev['template_jurisdiction'],'witness'=>$prev['template_witness'],'notary'=>$prev['template_notary']];
    } else $meta=$template ? require_row($db,'sale_templates',$template,'Template') : template_list($db)[0];
    $p=[...$snap,'template_id'=>$template,'template_name'=>$meta['name'],'template_jurisdiction'=>$meta['jurisdiction'],
        'template_witness'=>(bool)$meta['witness'],'template_notary'=>(bool)$meta['notary'],
        'sale_date'=>$day, 'sale_time'=>clock_field($d['sale_time'] ?? '', 'Sale time',$final),'sale_location'=>text_field($d['sale_location'] ?? '', 'Sale location',200,$final),
        'disclosure'=>choice_field($d['disclosure'] ?? '',DISCLOSURES,'mileage disclosure',$final),
        'price'=>$price===null ? null : money_display($price,$currency),'deposit'=>$deposit===null && !$final ? null : money_display($deposit ?? 0,$currency),
        'balance'=>$balance===null ? null : money_display($balance,$currency),'currency'=>$currency,'payment_status'=>$payment,'balance_due'=>$due,
        'title_status'=>choice_field($d['title_status'] ?? '',TITLE_STATES,'title disclosure',$final),
        'warranty'=>choice_field($d['warranty'] ?? '',WARRANTY_STATES,'warranty choice',$final),
        'delivery_date'=>date_field($d['delivery_date'] ?? '', 'Delivery or possession date',false),
        'terms_reviewed'=>bool_field($d['terms_reviewed'] ?? false),
        'buyer_sign_date'=>date_field($d['buyer_sign_date'] ?? '', 'Buyer signing date',false),
        'seller_sign_date'=>date_field($d['seller_sign_date'] ?? '', 'Seller signing date',false)];
    foreach (['buyer_name'=>['Buyer legal name',180,true], 'buyer_address'=>['Buyer mailing address',1400,true], 'buyer_contact'=>['Buyer contact information',250,false],
        'seller_name'=>['Seller legal name',180,true], 'seller_address'=>['Seller mailing address',1400,true], 'seller_contact'=>['Seller contact information',250,false],
        'payment_method'=>['Payment method',180,true], 'payment_terms'=>['Agreed payment terms',6000,false],
        'condition'=>['Vehicle condition',6000,false], 'defects'=>['Known defects',6000,false], 'accessories'=>['Included accessories',6000,false],
        'title_notes'=>['Title or lien details',6000,false], 'additional_terms'=>['Additional terms',6000,false], 'warranty_notes'=>['Warranty details',6000,false],
        'buyer_printed'=>['Buyer printed name',180,false], 'seller_printed'=>['Seller printed name',180,false],
        'witness_name'=>['Witness printed name',180,false], 'notary_details'=>['Notary information',1200,false]] as $k=>[$label,$limit,$required]) $p[$k]=text_field($d[$k] ?? '',$label,$limit,$required && $final);
    if ($final && !$p['terms_reviewed']) fail('Review the terms and explicitly confirm before finalizing.');
    if ($final && $p['title_status']!==TITLE_STATES[0] && $p['title_notes']==='') fail('Explain the lien or uncertain title status.');
    if ($final && $p['warranty']!==WARRANTY_STATES[0] && $p['warranty_notes']==='') fail('Describe the selected warranty terms.');
    return ['vehicle_id'=>$vid,'driver_id'=>null,'document_day'=>$day,'week_start'=>null,'due_next'=>null,'issue_count'=>0,'result_count'=>0,'payload'=>json_text($p)];
}
function match_revision(array $old, array $d): void {
    if (integer_field($d['revision'] ?? null,'Draft revision',1,2147483647)!==(int)$old['revision']) fail('A newer version exists in the other interface. Your edits were not discarded. Copy them before reopening the latest draft.',409,'revision_conflict');
}
function write_document(PDO $db, array $actor, string $type, string $action, array $d): array {
    $id=id_field($d['id'] ?? '', 'Document',false);
    $old=$id ? document_row($db,$id,$type) : null;
    $user=(int)$actor['id'];
    if ($action==='revise') {
        if (!$old || $old['status']==='draft') fail('Only a completed or finalized document can be revised.');
        if (val($db,'SELECT id FROM documents WHERE parent_id=?',[$id])) fail('A correction already exists for this version. Open that revision instead.',409,'revision_exists');
        $ref=($type==='inspection'?'WI':'BOS').'-'.gmdate('ym').'-'.strtoupper(bin2hex(random_bytes(5)));
        $p=json_array($old['payload']);
        unset($p['signed']);
        if ($type==='inspection') { $p['acknowledged']=false; $p['ack_name']=''; $p['ack_at']=null; }
        else { $p['terms_reviewed']=false; $p['buyer_sign_date']=null; $p['seller_sign_date']=null; }
        $next=insert_row($db,'documents',['doc_type'=>$type,'reference'=>$ref,'parent_id'=>$id,'status'=>'draft','vehicle_id'=>$old['vehicle_id'],'driver_id'=>$old['driver_id'],
            'document_day'=>$old['document_day'],'week_start'=>$old['week_start'],'due_next'=>$old['due_next'],'issue_count'=>$old['issue_count'],'result_count'=>$old['result_count'],
            'payload'=>json_text($p),'revision'=>1,'created_by'=>$user,'created_at'=>now_iso(),'updated_at'=>now_iso()]);
        if ($type==='inspection') foreach (rows($db,"SELECT * FROM photos WHERE owner_kind='inspection' AND owner_id=?",[$id]) as $photo) { unset($photo['id']); $photo['owner_id']=$next; insert_row($db,'photos',$photo); }
        audit_doc($db,$next,$user,'revision_created',['parent_id'=>$id,'parent_reference'=>$old['reference']]);
        audit_doc($db,$id,$user,'revision_opened',['new_id'=>$next,'new_reference'=>$ref]);
        return ['id'=>$next,'reference'=>$ref,'revision'=>1,'message'=>'Correction draft created. The original remains available.'];
    }
    if ($action==='signed' && $type==='sale') {
        if (!$old || $old['status']!=='finalized') fail('Only a finalized agreement can be marked signed.');
        match_revision($old,$d);
        if (!bool_field($d['confirmed'] ?? false)) fail('Explicitly confirm that both parties signed the printed agreement.');
        $p=json_array($old['payload']);
        $p['signed']=['mode'=>'Handwritten signature recorded by administrator','buyer_printed'=>text_field($d['buyer_printed'] ?? '', 'Buyer printed name',180,true),
            'seller_printed'=>text_field($d['seller_printed'] ?? '', 'Seller printed name',180,true),'buyer_sign_date'=>date_field($d['buyer_sign_date'] ?? '','Buyer signing date'),
            'seller_sign_date'=>date_field($d['seller_sign_date'] ?? '','Seller signing date'),'logged_at'=>now_iso()];
        if (max($p['signed']['buyer_sign_date'],$p['signed']['seller_sign_date'])>today_iso()) fail('Enter the dates on which the parties actually signed, not a future date.');
        update_row($db,'documents',$id,['payload'=>json_text($p),'status'=>'signed','revision'=>(int)$old['revision']+1,'updated_at'=>now_iso()]);
        audit_doc($db,$id,$user,'signed_offline',$p['signed']);
        return ['id'=>$id,'revision'=>(int)$old['revision']+1,'message'=>'Handwritten signing recorded. No electronic signature was inserted.'];
    }
    $finalAction=$type==='inspection'?'submit':'finalize';
    if (!in_array($action,['draft',$finalAction],true)) fail('Unknown document action.',400);
    if ($old && $old['status']!=='draft') fail('Completed and finalized snapshots are locked. Create a correction revision instead.',409,'document_locked');
    if ($old) match_revision($old,$d);
    $final=$action===$finalAction;
    $v=$type==='inspection' ? inspection_values($db,$d,$final,$old) : sale_values($db,$d,$final,$old);
    $findings=$v['_issues'] ?? []; unset($v['_issues']);
    $rev=$old ? (int)$old['revision']+1 : 1;
    $status=$final ? ($type==='inspection'?'submitted':'finalized') : 'draft';
    $timestamp=now_iso();
    $v+=[ 'status'=>$status,'revision'=>$rev,'updated_at'=>$timestamp,'completed_at'=>$final ? $timestamp : null];
    $ref=$old['reference'] ?? (($type==='inspection'?'WI':'BOS').'-'.gmdate('ym').'-'.strtoupper(bin2hex(random_bytes(5))));
    if ($old) update_row($db,'documents',$id,$v);
    else $id=insert_row($db,'documents',[...$v,'doc_type'=>$type,'reference'=>$ref,'created_by'=>$user,'created_at'=>$timestamp]);
    if ($final && $type==='inspection') {
        $payload=json_array($v['payload']);
        peak($db,(int)$v['vehicle_id'],$payload['odometer']);
        foreach ($findings as $key) insert_row($db,'inspection_issues',['document_id'=>$id,'item_key'=>$key,'status'=>'open','action_taken'=>'','responsible'=>'','notes'=>'','updated_at'=>$timestamp]);
    }
    if (!$old || $final) audit_doc($db,$id,$user,$final ? $action : 'draft_created',$final ? json_array($v['payload']) : ['reference'=>$ref]);
    if ($old && !$final) audit_doc($db,$id,$user,'draft_saved',['revision'=>$rev]);
    return ['id'=>$id,'reference'=>$ref,'status'=>$status,'revision'=>$rev,'balance'=>json_array($v['payload'])['balance'] ?? null,
        'message'=>$final ? ($type==='inspection'?'Inspection submitted. Original findings are preserved.':'Agreement finalized and locked.') : 'Draft saved.'];
}
function write_issue(PDO $db, array $actor, array $d): array {
    $id=id_field($d['id'] ?? null, 'Finding');
    $r=require_row($db,'inspection_issues',$id,'Finding');
    $status=choice_field($d['status'] ?? '',array_keys(ISSUE_STATES),'follow-up status');
    $closed=$status==='resolved';
    $date=date_field($d['resolved_date'] ?? '', 'Resolution date',$closed);
    if ($date && ($doc=document_row($db,(int)$r['document_id'],'inspection')) && ($date<$doc['document_day'] || $date>today_iso())) fail('Resolution date must be between the inspection date and today.');
    $v=['status'=>$status,'action_taken'=>text_field($d['action_taken'] ?? '', 'Action taken',6000,$closed),
        'responsible'=>text_field($d['responsible'] ?? '', 'Responsible person',150,$closed),'resolved_date'=>$closed ? $date : null,
        'notes'=>text_field($d['notes'] ?? '', 'Follow-up notes',6000),'updated_at'=>now_iso()];
    update_row($db,'inspection_issues',$id,$v);
    audit_doc($db,(int)$r['document_id'],(int)$actor['id'],'issue_followup',['item_key'=>$r['item_key'],'previous'=>$r,'current'=>$v]);
    return ['id'=>$id,'document_id'=>(int)$r['document_id'],'message'=>'Follow-up saved. The original result is unchanged.'];
}

function photo_root(): string {
    $path=private_file('photos');
    if (!is_dir($path) && !@mkdir($path,0700,true) && !is_dir($path)) fail('Private photo storage is not writable.',503,'storage');
    return $path;
}
function write_photo(PDO $db, array $d): array {
    $type=choice_field($d['owner_kind'] ?? '', ['vehicle','inspection'],'photo owner');
    $id=id_field($d['owner_id'] ?? null,'Photo owner');
    $key=text_field($d['item_key'] ?? '', 'Checklist item',40);
    $view='';
    if ($type==='vehicle') { require_row($db,'vehicles',$id,'Vehicle'); $key=''; $view=choice_field($d['view_label'] ?? 'Other',VIEW_TYPES,'photo view'); }
    else {
        $doc=document_row($db,$id,'inspection');
        if ($doc['status']!=='draft') fail('Completed inspection evidence is locked. Add photos to a correction draft.',409,'document_locked');
        if ($key!=='' && !isset(inspection_checklist(json_array($doc['payload'])['items'] ?? [])[$key])) fail('Choose a known checklist item.');
    }
    $caption=text_field($d['caption'] ?? '', 'Photo caption',220);
    $b64=$d['image'] ?? '';
    if (!is_string($b64) || strlen($b64)>7_000_000) fail('Choose a JPEG or PNG image no larger than 5 MB.',413,'file_size');
    $bytes=base64_decode($b64,true);
    if ($bytes===false || strlen($bytes)<20 || strlen($bytes)>5*1024*1024) fail('Choose a JPEG or PNG image no larger than 5 MB.',413,'file_size');
    $size=@getimagesizefromstring($bytes);
    $mime=(new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
    if (!$size || !in_array($mime,['image/jpeg','image/png'],true) || $size['mime']!==$mime || $size[0]*$size[1]>20_000_000) fail('The file is not a valid JPEG or PNG (maximum 20 megapixels).',422,'bad_image');
    if (function_exists('imagecreatefromstring')) {
        $pixels=@imagecreatefromstring($bytes);
        if (!$pixels) fail('The image is damaged or unreadable.',422,'bad_image');
        unset($pixels);
    }
    $name=bin2hex(random_bytes(28));
    $path=photo_root().DIRECTORY_SEPARATOR.$name;
    atomic_private($path,$bytes);
    $GLOBALS['motorlog_staged_upload']=$path;
    $photo=insert_row($db,'photos',['owner_kind'=>$type,'owner_id'=>$id,'item_key'=>$key,'storage_name'=>$name,'mime'=>$mime,'caption'=>$caption,'view_label'=>$view,'created_at'=>now_iso()]);
    if ($type==='vehicle' && bool_field($d['cover'] ?? false)) update_row($db,'vehicles',$id,['cover_id'=>$photo]);
    return ['id'=>$photo,'message'=>'Photo uploaded.'];
}
function photo_file(PDO $db, int $id): array {
    $p=require_row($db,'photos',$id,'Photo');
    if ($p['owner_kind']==='vehicle') require_row($db,'vehicles',(int)$p['owner_id'],'Vehicle');
    elseif ($p['owner_kind']==='inspection') document_row($db,(int)$p['owner_id'],'inspection');
    else fail('Photo was not found.',404,'not_found');
    if (!preg_match('/^[a-f0-9]{56}$/D',$p['storage_name'])) fail('Photo was not found.',404,'not_found');
    $p['path']=photo_root().DIRECTORY_SEPARATOR.$p['storage_name'];
    if (!is_file($p['path'])) fail('This photo file is missing from private storage.',404,'not_found');
    return $p;
}
