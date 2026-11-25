<?php
declare(strict_types=1);

function print_value(mixed $v): string { return $v === null || $v === '' ? '____________________' : (string)$v; }
function print_groups(string $type, array $d): array {
    $p=$d['data'] ?? [];
    $v=['Year'=>$p['model_year'] ?? '', 'Make'=>$p['make'] ?? '', 'Model'=>$p['model'] ?? '', 'VIN'=>$p['vin'] ?? '', 'Registration / plate'=>$p['plate'] ?? '', 'Color'=>$p['color'] ?? '',
       'Odometer / unit'=>trim(($p['odometer'] ?? '').' '.($p['unit'] ?? ''))];
    if ($type==='inspection') return ['Vehicle identification'=>$v, 'Inspection details'=>['Inspector'=>$p['inspector_name'] ?? '', 'Inspection date'=>$p['inspection_date'] ?? '',
        'Time'=>$p['inspection_time'] ?? '', 'Week beginning Monday'=>$d['week_start'] ?? '', 'Next weekly due (Monday)'=>$d['due_next'] ?? '', 'Completed (UTC)'=>$d['completed_at'] ?? '']];
    $signed=$p['signed'] ?? [];
    return ['Transaction'=>['Sale date'=>$p['sale_date'] ?? '', 'Sale time'=>$p['sale_time'] ?? '', 'Sale location'=>$p['sale_location'] ?? '',
        'Delivery / possession date'=>$p['delivery_date'] ?? '', 'Template / jurisdiction'=>($p['template_name'] ?? 'General vehicle bill of sale').' · '.($p['template_jurisdiction'] ?? 'General template')],
      'Seller'=>['Legal name'=>$p['seller_name'] ?? '', 'Mailing address'=>$p['seller_address'] ?? '', 'Contact'=>$p['seller_contact'] ?? ''],
      'Buyer'=>['Legal name'=>$p['buyer_name'] ?? '', 'Mailing address'=>$p['buyer_address'] ?? '', 'Contact'=>$p['buyer_contact'] ?? ''],
      'Vehicle identification'=>[...$v, 'Mileage disclosure'=>$p['disclosure'] ?? ''],
      'Sale and payment'=>['Currency'=>$p['currency'] ?? '', 'Sale price'=>$p['price'] ?? '', 'Deposit'=>$p['deposit'] ?? '', 'Remaining balance'=>$p['balance'] ?? '',
        'Payment method'=>$p['payment_method'] ?? '', 'Payment status'=>$p['payment_status'] ?? '', 'Balance due date'=>$p['balance_due'] ?? '', 'Agreed payment terms'=>$p['payment_terms'] ?? ''],
      'Condition and disclosures'=>['Vehicle condition'=>$p['condition'] ?? '', 'Known defects'=>$p['defects'] ?? '', 'Included accessories'=>$p['accessories'] ?? '',
        'Title / lien disclosure'=>$p['title_status'] ?? '', 'Title / lien details'=>$p['title_notes'] ?? '', 'Warranty / as-is'=>$p['warranty'] ?? '', 'Warranty details'=>$p['warranty_notes'] ?? '',
        'Additional terms'=>$p['additional_terms'] ?? '', 'Terms reviewed'=>$d ? (($p['terms_reviewed'] ?? false) ? 'Explicitly confirmed' : 'Not confirmed') : ''],
      'Signing information'=>['Seller printed name'=>$signed['seller_printed'] ?? $p['seller_printed'] ?? '', 'Seller signing date'=>$signed['seller_sign_date'] ?? $p['seller_sign_date'] ?? '',
        'Buyer printed name'=>$signed['buyer_printed'] ?? $p['buyer_printed'] ?? '', 'Buyer signing date'=>$signed['buyer_sign_date'] ?? $p['buyer_sign_date'] ?? '',
        ...( !empty($p['template_witness']) ? ['Witness printed name'=>$p['witness_name'] ?? ''] : []),
        ...( !empty($p['template_notary']) ? ['Notary information'=>$p['notary_details'] ?? ''] : []),
        ...( $signed ? ['Signing record'=>$signed['mode'].' · '.$signed['logged_at']] : [])]];
}
function print_intro(string $type): string {
    return $type==='inspection' ? 'A completed sheet records observations; it does not certify the vehicle as safe. Address findings separately.' : 'General agreement template. Check the applicable jurisdiction’s requirements; this does not replace official title-transfer documents.';
}
function signatures(string $type, array $p): array {
    if ($type==='inspection') return ['Inspector signature','Date'];
    $labels=['Seller signature','Date','Buyer signature','Date'];
    if (!empty($p['template_witness'])) $labels[]='Witness signature / date';
    if (!empty($p['template_notary'])) $labels[]='Notary signature / seal / date';
    return $labels;
}
function print_heading(string $type): string { return $type==='inspection'?'Weekly vehicle inspection':'Vehicle bill of sale'; }
function print_html(string $type, array $d): never {
    $p=$d['data'] ?? [];
    $title=print_heading($type); $ref=$d['reference'] ?? 'Blank worksheet';
    header('Content-Type: text/html; charset=utf-8');
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=esc($title)?> · <?=esc($ref)?></title><link rel="stylesheet" href="assets/print.css"></head><body><main class="paper">
    <div class="print-tools"><a href="?">← Back to Motorlog</a><button id="printing" type="button">Print / Save as PDF</button></div>
    <header><div class="brand">MOTORLOG &nbsp; / &nbsp; WORKSPACE RECORD</div><h1><?=esc($title)?></h1><div><?=esc($ref)?> · <?=esc(ucfirst($d['status'] ?? 'Blank'))?><?=isset($d['parent_reference']) ? ' · revision of '.esc($d['parent_reference']) : ''?></div></header>
    <p class="notice"><?=esc(print_intro($type))?></p>
    <?php foreach (print_groups($type,$d) as $name=>$fields): ?><section><h2><?=esc($name)?></h2><dl><?php foreach ($fields as $label=>$value): ?><div><dt><?=esc($label)?></dt><dd><?=esc(print_value($value))?></dd></div><?php endforeach; ?></dl></section><?php endforeach; ?>
    <?php if ($type==='inspection'): $group=''; foreach (inspection_checklist($p['items'] ?? []) as $key=>[$g,$label]): $i=$p['items'][$key] ?? []; if ($g!==$group) { if ($group!=='') echo '</section>'; $group=$g; echo '<section><h2>'.esc($g).'</h2>'; } ?>
    <article class="check"><strong><?=esc($label)?></strong><span><?=esc(RESULTS[$i['result'] ?? ''] ?? '□ Pass · □ Needs attention · □ Fail · □ N/A')?></span><p><?=esc($i['notes'] ?? '')?><?=empty($i['notes']) && !$d ? 'Notes: _________________________________________________________' : ''?></p></article>
    <?php endforeach; ?></section>
    <?php if (!empty($d['issues'])): ?><section><h2>Finding follow-up</h2><?php foreach ($d['issues'] as $issue): ?><article class="check"><strong><?=esc((MOBILE_CHECKLIST + CHECKLIST)[$issue['item_key']][1] ?? $issue['item_key'])?></strong><span><?=esc(ISSUE_STATES[$issue['status']] ?? $issue['status'])?></span><p><?=esc(implode("\n",array_filter([$issue['action_taken'],$issue['responsible'],$issue['resolved_date'],$issue['notes']])))?></p></article><?php endforeach; ?></section><?php endif; ?>
    <?php if (!empty($d['photos'])): ?><section><h2>Photo evidence</h2><div class="evidence"><?php foreach ($d['photos'] as $pic): ?><figure><img src="?r=image&amp;id=<?=esc($pic['id'])?>" alt="<?=esc($pic['caption'] ?: 'Inspection photo')?>"><figcaption><?=esc($pic['caption'])?><?=!empty($pic['item_key']) ? ' · '.esc((MOBILE_CHECKLIST + CHECKLIST)[$pic['item_key']][1] ?? '') : ''?></figcaption></figure><?php endforeach; ?></div></section><?php endif; ?>
    <section><h2>Comments and acknowledgment</h2><p><?=esc(print_value($p['comments'] ?? ''))?></p><p><?=esc(!empty($p['acknowledged']) && !empty($p['ack_at']) ? 'Acknowledged by '.$p['ack_name'].' at '.$p['ack_at'] : 'Inspector acknowledgment: _____________________')?></p></section>
    <?php elseif (!$d || ($d['status'] ?? '')==='draft'): ?><section><h2>Review choices before signing</h2><p>Mileage disclosure: □ Actual mileage · □ Exceeds mechanical limits · □ Not actual mileage</p><p>Title/lien and warranty terms must be selected and explained by the parties before finalizing.</p></section><?php endif; ?>
    <section class="signatures"><h2>Handwritten signatures</h2><div><?php foreach (signatures($type,$p) as $label): ?><p><?=esc($label)?></p><?php endforeach; ?></div></section>
    <footer>Motorlog · <?=esc($ref)?> · Private workspace document</footer></main><script src="assets/print.js" defer></script></body></html><?php
    exit;
}

final class MotorPdf {
    private array $pages=[];
    private array $images=[];
    private string $commands='';
    private float $y=0;
    public function __construct(private string $title, private string $ref) { $this->newPage(); }
    private function bytes(string $s): string { $v=@iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$s); return $v===false ? preg_replace('/[^\x20-\x7E]/','?',$s) : $v; }
    private function text(string $s, float $x, float $y, int $size, bool $bold=false): void {
        $str=strtr($s,['\\'=>'\\\\','('=>'\\(',')'=>'\\)',"\r"=>' ',"\n"=>' ']);
        $this->commands.=sprintf("0.10 0.12 0.18 rg BT /%s %d Tf %.2F %.2F Td (%s) Tj ET\n",$bold?'B':'R',$size,$x,$y,$str);
    }
    private function rule(float $x, float $y, float $end): void { $this->commands.=sprintf("0.80 0.81 0.86 RG 0.7 w %.2F %.2F m %.2F %.2F l S\n",$x,$y,$end,$y); }
    private function newPage(): void {
        if ($this->commands!=='') $this->pages[]=$this->commands;
        $this->commands='';
        $this->text('MOTORLOG / WORKSPACE RECORD',48,748,9,true);
        $this->text($this->bytes($this->title),48,717,24,true);
        $this->text($this->bytes($this->ref),48,695,10);
        $this->rule(48,682,564);
        $this->text('Private workspace document',48,38,9);
        $this->text('Page '.(count($this->pages)+1),520,38,9);
        $this->y=660;
    }
    private function fit(float $size): void { if ($this->y-$size<63) $this->newPage(); }
    public function heading(string $name): void { $this->fit(65); $this->y-=10; $this->text($this->bytes($name),48,$this->y,13,true); $this->y-=19; }
    public function line(string $value, bool $bold=false): void {
        foreach (explode("\n",wordwrap($this->bytes($value),71,"\n",true)) as $line) {
            $this->fit(16); $this->text($line,48,$this->y,10,$bold); $this->y-=15;
        }
        $this->y-=3;
    }
    public function signatureLines(array $labels): void {
        $this->heading('Handwritten signatures');
        foreach (array_chunk($labels,2) as $pair) {
            $this->fit(58); $this->y-=18;
            foreach ($pair as $i=>$name) { $x=48+$i*264; $this->rule($x,$this->y,$x+235); $this->text($this->bytes($name),$x,$this->y-15,10); }
            $this->y-=29;
        }
    }
    public function image(string $path, string $caption): void {
        if (!function_exists('imagecreatefromstring')) { $this->line($caption.' (image available in the website print view)'); return; }
        $pixels=@imagecreatefromstring((string)@file_get_contents($path));
        if (!$pixels) { $this->line($caption.' (image missing)'); return; }
        $w=imagesx($pixels); $h=imagesy($pixels); $scaled=min(1,1050/max($w,$h));
        $out=imagecreatetruecolor(max(1,(int)($w*$scaled)),max(1,(int)($h*$scaled)));
        imagefill($out,0,0,imagecolorallocate($out,255,255,255));
        imagecopyresampled($out,$pixels,0,0,0,0,imagesx($out),imagesy($out),$w,$h); unset($pixels);
        ob_start(); imagejpeg($out,null,80); $jpg=(string)ob_get_clean();
        $n=count($this->images)+1; $this->images[$n]=['data'=>$jpg,'w'=>imagesx($out),'h'=>imagesy($out)]; unset($out);
        $ratio=min(490/$w,235/$h); $actualW=$w*$ratio;$actualH=$h*$ratio;
        $this->fit($actualH+42);
        $this->line($caption,true);
        $this->y-=$actualH+4;
        $this->commands.=sprintf("q %.2F 0 0 %.2F 48 %.2F cm /Im%d Do Q\n",$actualW,$actualH,$this->y,$n);
        $this->y-=17;
    }
    public function finish(): string {
        $this->pages[]=$this->commands;
        $objects=[1=>'<< /Type /Catalog /Pages 2 0 R >>',2=>'',3=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',4=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
        $kid=[]; $imageRefs=[];
        foreach ($this->images as $n=>$im) {
            $id=count($objects)+1; $imageRefs[]='/Im'.$n.' '.$id.' 0 R';
            $objects[$id]='<< /Type /XObject /Subtype /Image /Width '.$im['w'].' /Height '.$im['h'].' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($im['data'])." >>\nstream\n".$im['data']."\nendstream";
        }
        foreach ($this->pages as $page) {
            $id=count($objects)+1; $kid[]=$id.' 0 R'; $stream=$id+1;
            $objects[$id]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /R 3 0 R /B 4 0 R >> /XObject << '.implode(' ',$imageRefs).' >> >> /Contents '.$stream.' 0 R >>';
            $objects[$stream]='<< /Length '.strlen($page)." >>\nstream\n".$page."endstream";
        }
        $objects[2]='<< /Type /Pages /Count '.count($kid).' /Kids ['.implode(' ',$kid).'] >>';
        $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets=[0];
        foreach ($objects as $i=>$object) { $offsets[$i]=strlen($pdf); $pdf.="$i 0 obj\n$object\nendobj\n"; }
        $start=strlen($pdf); $size=count($objects)+1;
        $pdf.="xref\n0 $size\n0000000000 65535 f \n";
        for ($i=1;$i<$size;$i++) $pdf.=sprintf("%010d 00000 n \n",$offsets[$i]);
        return $pdf."trailer\n<< /Size $size /Root 1 0 R >>\nstartxref\n$start\n%%EOF\n";
    }
}
function print_pdf(PDO $db, string $type, array $d): never {
    $p=$d['data'] ?? []; $ref=$d['reference'] ?? 'Blank worksheet';
    $pdf=new MotorPdf(print_heading($type),$ref.' / '.ucfirst($d['status'] ?? 'Blank').(isset($d['parent_reference'])?' / revision of '.$d['parent_reference']:''));
    $pdf->line(print_intro($type));
    foreach (print_groups($type,$d) as $name=>$fields) { $pdf->heading($name); foreach ($fields as $label=>$value) $pdf->line($label.': '.print_value($value)); }
    if ($type==='inspection') {
        $last=null;
        foreach (inspection_checklist($p['items'] ?? []) as $key=>[$group,$label]) {
            if ($group!==$last) { $pdf->heading($group); $last=$group; }
            $i=$p['items'][$key] ?? [];
            $pdf->line($label.' — '.(RESULTS[$i['result'] ?? ''] ?? '[ ] Pass   [ ] Needs attention   [ ] Fail   [ ] N/A'),true);
            if (isset($i['notes']) && $i['notes']!=='') $pdf->line('Notes: '.$i['notes']); elseif (!$d) $pdf->line('Notes: _________________________________________________________________');
        }
        if (!empty($d['issues'])) { $pdf->heading('Finding follow-up'); foreach ($d['issues'] as $i) $pdf->line(((MOBILE_CHECKLIST + CHECKLIST)[$i['item_key']][1] ?? $i['item_key']).': '.(ISSUE_STATES[$i['status']] ?? $i['status'])."\n".implode("\n",array_filter([$i['action_taken'],$i['responsible'],$i['resolved_date'],$i['notes']]))); }
        $pdf->heading('Comments and acknowledgment'); $pdf->line(print_value($p['comments'] ?? ''));
        $pdf->line(!empty($p['acknowledged']) && !empty($p['ack_at']) ? 'Acknowledged by '.$p['ack_name'].' at '.$p['ack_at'] : 'Inspector acknowledgment: _________________________');
        if (!empty($d['photos'])) { $pdf->heading('Photo evidence'); foreach ($d['photos'] as $photo) { try { $file=photo_file($db,(int)$photo['id']); $pdf->image($file['path'],($photo['caption'] ?: 'Inspection photo').' · '.((MOBILE_CHECKLIST + CHECKLIST)[$photo['item_key']][1] ?? 'General')); } catch (HttpFault $e) { $pdf->line('A referenced photo is missing from storage.'); } } }
    } elseif (!$d || $d['status']==='draft') {
        $pdf->heading('Mileage disclosure choices'); $pdf->line('[ ] Actual mileage   [ ] Exceeds mechanical limits   [ ] Not actual mileage');
    }
    $pdf->signatureLines(signatures($type,$p));
    $output=$pdf->finish(); $filename=$d['reference'] ?? ($type==='inspection'?'Blank-Inspection':'Blank-Bill-of-Sale');
    header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="'.$filename.'.pdf"'); header('Content-Length: '.strlen($output));
    echo $output; exit;
}
