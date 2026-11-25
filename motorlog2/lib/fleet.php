<?php
declare(strict_types=1);

const EXPENSE_CATEGORIES = ['Fuel','Repairs','Maintenance','Parts','Other'];
const RECORD_KINDS = ['Training','Qualification','License renewal','Incident','Violation','General note'];
const VIEW_TYPES = ['Front','Rear','Left side','Right side','Interior','Odometer','Other'];

function vehicle_fields(array $data, bool $new): array {
    $out = [
        'kind'=>choice_field($data['kind'] ?? 'car', ['car','motorcycle'], 'vehicle type'),
        'make'=>text_field($data['make'] ?? '', 'Make', 150, true), 'model'=>text_field($data['model'] ?? '', 'Model', 150, true),
        'model_year'=>integer_field($data['model_year'] ?? null, 'Model year', 1885, (int)gmdate('Y')+2, false),
        'vin'=>strtoupper(text_field($data['vin'] ?? '', 'VIN', 70)), 'plate'=>text_field($data['plate'] ?? '', 'License plate', 50),
        'color'=>text_field($data['color'] ?? '', 'Color', 70), 'notes'=>text_field($data['notes'] ?? '', 'Notes', 6000),
    ];
    if ($new) {
        $out['odometer_peak'] = $out['initial_odometer'] = odo_field($data['initial_odometer'] ?? null, 'Vehicle baseline odometer');
        $out['created_at'] = now_iso();
    }
    return $out;
}
function initial_engine_fields(array $data, string $initial): array {
    $at = odo_field(($data['at_odometer'] ?? '') !== '' ? $data['at_odometer'] : $initial, 'Vehicle odometer at engine installation');
    if ((float)$at > (float)$initial) fail('The engine installation odometer cannot be higher than the vehicle baseline.');
    return ['installed_date'=>date_field($data['installed_date'] ?? '', 'Engine installation date', false),
        'at_odometer'=>$at, 'at_engine'=>odo_field($data['at_engine'] ?? '0', 'Engine mileage at installation'),
        'serial_number'=>text_field($data['serial_number'] ?? '', 'Engine serial number', 120),
        'reason'=>text_field($data['reason'] ?? 'Initial engine', 'Engine installation reason', 250), 'notes'=>text_field($data['engine_notes'] ?? '', 'Engine notes', 6000)];
}
function vehicle_link(PDO $db, mixed $value): int { $id = id_field($value, 'Vehicle'); require_row($db, 'vehicles', $id, 'Vehicle'); return $id; }
function driver_link(PDO $db, mixed $value, ?int $previous = null): ?int {
    $id = id_field($value, 'Driver', false);
    if ($id !== null) {
        $driver = require_row($db, 'drivers', $id, 'Driver');
        if (!(int)$driver['active'] && $id !== $previous) fail('This driver is inactive. Choose an active driver or reactivate their profile.');
    }
    return $id;
}
function vehicle_name(array $row): string { return trim(($row['model_year'] ?? '').' '.($row['make'] ?? '').' '.($row['model'] ?? '')); }
function peak(PDO $db, int $vid, string $reading): void { statement($db, 'UPDATE vehicles SET odometer_peak=? WHERE id=? AND odometer_peak<?', [$reading, $vid, $reading]); }

function observations(PDO $db, int $vid, string $skipKind = '', ?int $skipId = null, array $skipDocs = []): array {
    $obs = [];
    foreach (rows($db, 'SELECT id,trip_date,start_odometer,end_odometer,purpose FROM trips WHERE vehicle_id=?', [$vid]) as $r) {
        if ($skipKind === 'trip' && (int)$r['id'] === $skipId) continue;
        $obs[] = ['kind'=>'trip','day'=>$r['trip_date'],'time'=>'','lo'=>odo_ticks($r['start_odometer']),'hi'=>odo_ticks($r['end_odometer']),'label'=>'trip #'.$r['id'].' ('.$r['purpose'].')'];
    }
    foreach (rows($db, "SELECT id,expense_date,odometer FROM expenses WHERE vehicle_id=? AND category='Fuel' AND odometer IS NOT NULL", [$vid]) as $r) {
        if ($skipKind === 'fuel' && (int)$r['id'] === $skipId) continue;
        $o = odo_ticks($r['odometer']);
        $obs[] = ['kind'=>'fuel','day'=>$r['expense_date'],'time'=>'','lo'=>$o,'hi'=>$o,'label'=>'fuel entry #'.$r['id']];
    }
    foreach (rows($db, 'SELECT id,installed_date,at_odometer FROM engines WHERE vehicle_id=? AND installed_date IS NOT NULL', [$vid]) as $r) {
        if ($skipKind === 'engine' && (int)$r['id'] === $skipId) continue;
        $o = odo_ticks($r['at_odometer']);
        $obs[] = ['kind'=>'engine','day'=>$r['installed_date'],'time'=>'','lo'=>$o,'hi'=>$o,'label'=>'engine installation #'.$r['id']];
    }
    $docs = rows($db, "SELECT id, parent_id, document_day, reference, payload FROM documents WHERE doc_type='inspection' AND status='submitted' AND vehicle_id=?", [$vid]);
    $superseded = array_filter(array_map('intval', array_column(rows($db, "SELECT parent_id FROM documents WHERE doc_type='inspection' AND status='submitted' AND parent_id IS NOT NULL"), 'parent_id')));
    foreach ($docs as $r) {
        if (in_array((int)$r['id'], $skipDocs, true) || in_array((int)$r['id'], $superseded, true)) continue;
        $p = json_array($r['payload']);
        if (!isset($p['odometer'])) continue;
        $o = odo_ticks($p['odometer']);
        $obs[] = ['kind'=>'inspection','day'=>$r['document_day'],'time'=>$p['inspection_time'] ?? '','lo'=>$o,'hi'=>$o,'label'=>$r['reference']];
    }
    return $obs;
}
function refresh_odometer_peak(PDO $db, int $vid, mixed $expectedPeak): void {
    $vehicle = require_row($db, 'vehicles', $vid, 'Vehicle');
    if (odo_ticks($vehicle['odometer_peak']) !== odo_ticks($expectedPeak)) return;

    $highest = odo_ticks($vehicle['initial_odometer']);
    foreach (observations($db, $vid) as $reading) {
        if ($reading['lo'] >= 0 && $reading['hi'] >= $reading['lo']) {
            $highest = max($highest, $reading['hi']);
        }
    }
    foreach (rows($db, 'SELECT at_odometer, retired_odometer FROM engines WHERE vehicle_id=?', [$vid]) as $engine) {
        $highest = max($highest, odo_ticks($engine['at_odometer']), odo_ticks($engine['retired_odometer'], 'Retirement odometer', false) ?? 0);
    }
    statement($db, 'UPDATE vehicles SET odometer_peak=? WHERE id=? AND odometer_peak=?',
        [decimal_string($highest, 3), $vid, $expectedPeak]);
}
function check_observation(PDO $db, int $vid, string $day, string $kind, string $a, string $b, ?int $skip = null, string $time = '', array $skipDocs = []): void {
    $vehicle = require_row($db, 'vehicles', $vid, 'Vehicle');
    $lo = odo_ticks($a); $hi = odo_ticks($b);
    $floor = odo_ticks($vehicle['initial_odometer']);
    if ($lo < $floor) fail('Odometer conflict: the reading is below the vehicle baseline of '.rtrim(rtrim(decimal_string($floor, 3), '0'), '.').'.', 422, 'odometer_conflict');
    foreach (observations($db, $vid, $kind, $skip, $skipDocs) as $r) {
        $order = $day <=> $r['day'];
        if (!$order && $time !== '' && $r['time'] !== '') $order = $time <=> $r['time'];
        if (($order > 0 && $lo < $r['hi']) || ($order < 0 && $hi > $r['lo']) ||
            (!$order && $kind === 'trip' && $r['kind'] === 'trip' && max($lo,$r['lo']) < min($hi,$r['hi'])) ||
            (!$order && $time !== '' && $r['time'] !== '' && $lo !== $r['lo'])) {
            $start = decimal_string($r['lo'],3); $end = $r['hi'] !== $r['lo'] ? '–'.decimal_string($r['hi'],3) : '';
            fail('Odometer conflict with '.$r['label'].' on '.$r['day'].' ('.$start.$end.'). Check the date and readings so they form a consistent timeline.', 422, 'odometer_conflict');
        }
    }
}
function write_vehicle(PDO $db, string $action, array $d): array {
    if ($action === 'create') {
        $v = vehicle_fields($d, true);
        $engine = initial_engine_fields($d, $v['initial_odometer']);
        $id = insert_row($db, 'vehicles', $v);
        insert_row($db, 'engines', ['vehicle_id'=>$id,...$engine]);
        return ['id'=>$id,'message'=>'Vehicle added.'];
    }
    $id = vehicle_link($db, $d['id'] ?? null);
    if ($action === 'update') { update_row($db, 'vehicles', $id, vehicle_fields($d, false)); return ['id'=>$id,'message'=>'Vehicle saved.']; }
    if ($action === 'refresh_mileage') {
        $vehicle = require_row($db, 'vehicles', $id, 'Vehicle');
        refresh_odometer_peak($db, $id, $vehicle['odometer_peak']);
        return ['id'=>$id,'message'=>'Mileage recalculated from saved readings.'];
    }
    if ($action === 'cover') {
        $p = require_row($db, 'photos', id_field($d['photo_id'] ?? null, 'Photo'), 'Photo');
        if ($p['owner_kind'] !== 'vehicle' || (int)$p['owner_id'] !== $id) fail('Choose a photo belonging to this vehicle.');
        update_row($db, 'vehicles', $id, ['cover_id'=>(int)$p['id']]);
        return ['id'=>$id,'message'=>'Cover photo updated.'];
    }
    if ($action === 'replace_engine') {
        $active = one($db, 'SELECT * FROM engines WHERE vehicle_id=? AND retired_date IS NULL ORDER BY id DESC', [$id]);
        $date = date_field($d['installed_date'] ?? '', 'Replacement date');
        $at = odo_field($d['at_odometer'] ?? null, 'Vehicle odometer at replacement');
        if ($active && (($active['installed_date'] && $active['installed_date'] > $date) || (float)$active['at_odometer'] > (float)$at)) fail('The replacement must be at or after the current engine’s installation date and reading.');
        check_observation($db, $id, $date, 'engine', $at, $at);
        $engine = ['vehicle_id'=>$id,'installed_date'=>$date,'at_odometer'=>$at,'at_engine'=>odo_field($d['at_engine'] ?? null, 'Replacement engine mileage'),
            'serial_number'=>text_field($d['serial_number'] ?? '', 'Serial number', 120),'reason'=>text_field($d['reason'] ?? '', 'Replacement reason', 250, true),'notes'=>text_field($d['notes'] ?? '', 'Notes', 6000)];
        if ($active) update_row($db, 'engines', (int)$active['id'], ['retired_date'=>$date,'retired_odometer'=>$at]);
        insert_row($db, 'engines', $engine); peak($db, $id, $at);
        return ['id'=>$id,'message'=>'Replacement recorded. The retired engine mileage is frozen.'];
    }
    fail('Unknown vehicle action.', 400);
}
function write_trip(PDO $db, string $action, array $d): array {
    $id = $action === 'create' ? null : id_field($d['id'] ?? null, 'Trip');
    $previous = $id ? require_row($db, 'trips', $id, 'Trip') : null;
    if ($action === 'delete') { statement($db, 'DELETE FROM trips WHERE id=?', [$id]); return ['id'=>$id,'message'=>'Trip deleted. The highest recorded odometer has been retained.']; }
    if (!in_array($action, ['create','update'], true)) fail('Unknown trip action.', 400);
    $vehicle = vehicle_link($db, $d['vehicle_id'] ?? null);
    $driver = driver_link($db, $d['driver_id'] ?? null, isset($previous['driver_id']) ? (int)$previous['driver_id'] : null);
    $day = date_field($d['trip_date'] ?? '', 'Trip date');
    $start = odo_field($d['start_odometer'] ?? null, 'Starting odometer');
    $end = odo_field($d['end_odometer'] ?? null, 'Ending odometer');
    if ((float)$start > (float)$end) fail('The ending odometer must be at or above the starting odometer.');
    check_observation($db, $vehicle, $day, 'trip', $start, $end, $id);
    $data = ['vehicle_id'=>$vehicle,'driver_id'=>$driver,'trip_date'=>$day,'start_odometer'=>$start,'end_odometer'=>$end,
        'purpose'=>text_field($d['purpose'] ?? '', 'Trip purpose', 200, true),'notes'=>text_field($d['notes'] ?? '', 'Notes', 6000)];
    if ($id) update_row($db, 'trips', $id, $data); else $id = insert_row($db, 'trips', [...$data,'created_at'=>now_iso()]);
    if ($previous && (int)$previous['vehicle_id'] === $vehicle && odo_ticks($end) < odo_ticks($previous['end_odometer'])) {
        refresh_odometer_peak($db, $vehicle, $previous['end_odometer']);
    } else {
        peak($db, $vehicle, $end);
    }
    return ['id'=>$id,'message'=>'Trip saved.','distance'=>(odo_ticks($end)-odo_ticks($start))/1000];
}
function write_expense(PDO $db, string $action, array $d): array {
    $id = $action === 'create' ? null : id_field($d['id'] ?? null, 'Expense');
    if ($id) {
        require_row($db, 'expenses', $id, 'Expense');
        if (val($db, 'SELECT id FROM invoices WHERE expense_id=?', [$id])) fail('This repair expense is linked to an invoice. Edit it in Mechanics.', 409, 'linked_invoice');
    }
    if ($action === 'delete') { statement($db, 'DELETE FROM expenses WHERE id=?', [$id]); return ['id'=>$id,'message'=>'Expense deleted. Fuel intervals were recalculated.']; }
    if (!in_array($action, ['create','update'], true)) fail('Unknown expense action.', 400);
    $vehicle = vehicle_link($db, $d['vehicle_id'] ?? null);
    $day = date_field($d['expense_date'] ?? '', 'Expense date');
    $category = choice_field($d['category'] ?? '', EXPENSE_CATEGORIES, 'expense category');
    $currency = settings($db)['currency'];
    $odo = $gallons = $price = $full = null;
    if ($category === 'Fuel') {
        $odo = odo_field($d['odometer'] ?? null, 'Fuel odometer');
        $gt = decimal_ticks($d['gallons'] ?? null, 3, 'Gallons (US)', 10000);
        if ($gt <= 0) fail('Gallons (US) must be greater than zero.');
        $pt = decimal_ticks($d['price_per_gallon'] ?? null, 4, 'Price per gallon', 10000);
        $gallons = decimal_string($gt, 3); $price = decimal_string($pt, 4); $full = bool_field($d['full_tank'] ?? false) ? 1 : 0;
        $divisor = 10 ** (7 - CURRENCIES[$currency]);
        $amount = intdiv($gt * $pt + intdiv($divisor, 2), $divisor);
        check_observation($db, $vehicle, $day, 'fuel', $odo, $odo, $id);
    } else $amount = money_value($d['amount'] ?? null, $currency);
    $data = ['vehicle_id'=>$vehicle,'expense_date'=>$day,'category'=>$category,'vendor'=>text_field($d['vendor'] ?? '', 'Vendor', 180),
        'description'=>text_field($d['description'] ?? '', 'Description or fuel notes', 6000),'amount_minor'=>$amount,
        'odometer'=>$odo,'gallons'=>$gallons,'price_per_gallon'=>$price,'full_tank'=>$full];
    if ($id) update_row($db, 'expenses', $id, $data); else $id = insert_row($db, 'expenses', [...$data,'created_at'=>now_iso()]);
    if ($odo !== null) peak($db, $vehicle, $odo);
    return ['id'=>$id,'message'=>'Expense saved.','amount'=>money_display($amount, $currency)];
}
function fuel_intervals(array $expenses, string $unit): array {
    $byVehicle = [];
    foreach ($expenses as $r) if ($r['category'] === 'Fuel') $byVehicle[(int)$r['vehicle_id']][] = $r;
    $output = [];
    foreach ($byVehicle as $list) {
        usort($list, static fn($a,$b) => [$a['expense_date'], (float)$a['odometer'], (int)$a['id']] <=> [$b['expense_date'], (float)$b['odometer'], (int)$b['id']]);
        $badDays = [];
        foreach ($list as $r) if (!complete_fill($r)) $badDays[$r['expense_date']] = true;
        $base = null; $sum = 0.0; $gap = false;
        foreach ($list as $r) {
            $id = (int)$r['id'];
            if (isset($badDays[$r['expense_date']])) {
                $base = null; $sum = 0.0; $gap = true;
                $output[$id] = ['mpg'=>null,'fuel_interval'=>'Incomplete fuel data; interval reset'];
                continue;
            }
            $full = (int)$r['full_tank'] === 1;
            $odo = (float)$r['odometer'];
            $output[$id] = ['mpg'=>null,'fuel_interval'=>$full ? ($gap ? 'New baseline after a data gap' : 'Full-tank baseline') : 'Partial fill'];
            if ($base !== null) {
                $sum += (float)$r['gallons'];
                if ($full) {
                    $distance = $odo - $base;
                    if ($unit === 'km') $distance /= 1.609344;
                    if ($sum > 0 && $distance > 0) $output[$id] = ['mpg'=>round($distance/$sum, 2),'fuel_interval'=>'Completed full-tank interval'];
                    else $output[$id]['fuel_interval'] = 'No positive distance for this interval';
                }
            }
            if ($full) { $base = $odo; $sum = 0.0; $gap = false; }
        }
    }
    return $output;
}
function complete_fill(array $r): bool {
    return isset($r['odometer'],$r['gallons'],$r['price_per_gallon'],$r['full_tank']) && (float)$r['gallons']>0 && (float)$r['odometer']>=0 && (float)$r['price_per_gallon']>=0 && in_array((string)$r['full_tank'], ['0','1'], true);
}
function write_driver(PDO $db, string $action, array $d): array {
    $id = $action === 'create' ? null : id_field($d['id'] ?? null, 'Driver');
    if ($id) require_row($db, 'drivers', $id, 'Driver');
    if ($action === 'archive' || $action === 'activate') {
        update_row($db, 'drivers', $id, ['active'=>$action === 'activate' ? 1 : 0]);
        return ['id'=>$id,'message'=>$action === 'archive' ? 'Driver deactivated. Historical attribution is unchanged.' : 'Driver reactivated.'];
    }
    if ($action === 'record') {
        $record = insert_row($db, 'driver_records', ['driver_id'=>$id,'record_date'=>date_field($d['record_date'] ?? '', 'Record date'),
            'kind'=>choice_field($d['kind'] ?? '', RECORD_KINDS, 'record type'),'title'=>text_field($d['title'] ?? '', 'Record title', 180, true),
            'notes'=>text_field($d['notes'] ?? '', 'Notes', 6000),'created_at'=>now_iso()]);
        return ['id'=>$record,'message'=>'Driver history updated.'];
    }
    if (!in_array($action, ['create','update'], true)) fail('Unknown driver action.',400);
    $v = ['name'=>text_field($d['name'] ?? '', 'Driver name', 150, true), 'phone'=>text_field($d['phone'] ?? '', 'Phone', 100), 'email'=>email_field($d['email'] ?? ''),
        'license_number'=>text_field($d['license_number'] ?? '', 'License number', 120),'jurisdiction'=>text_field($d['jurisdiction'] ?? '', 'Licensing jurisdiction', 120),
        'expiration_date'=>date_field($d['expiration_date'] ?? '', 'License expiration', false), 'emergency_contact'=>text_field($d['emergency_contact'] ?? '', 'Emergency contact', 250),
        'notes'=>text_field($d['notes'] ?? '', 'Notes', 6000),'active'=>bool_field($d['active'] ?? true) ? 1 : 0];
    if ($id) update_row($db,'drivers',$id,$v); else $id = insert_row($db,'drivers',[...$v,'created_at'=>now_iso()]);
    return ['id'=>$id,'message'=>'Driver profile saved.'];
}
function write_mechanic(PDO $db, string $action, array $d): array {
    if (!in_array($action, ['create','update'], true)) fail('Unknown mechanic action.',400);
    $v = ['name'=>text_field($d['name'] ?? '', 'Mechanic or shop name', 150, true),'phone'=>text_field($d['phone'] ?? '', 'Phone', 100),'email'=>email_field($d['email'] ?? ''),
        'address'=>text_field($d['address'] ?? '', 'Address', 1000),'notes'=>text_field($d['notes'] ?? '', 'Notes', 6000)];
    if ($action === 'update') { $id = id_field($d['id'] ?? null, 'Mechanic'); require_row($db,'mechanics',$id,'Mechanic'); update_row($db,'mechanics',$id,$v); }
    else $id = insert_row($db,'mechanics',[...$v,'created_at'=>now_iso()]);
    return ['id'=>$id,'message'=>'Mechanic saved.'];
}
function write_invoice(PDO $db, string $action, array $d): array {
    $currency = settings($db)['currency'];
    $id = $action === 'create' ? null : id_field($d['id'] ?? null, 'Invoice');
    $old = $id ? require_row($db,'invoices',$id,'Invoice') : null;
    $paid = $id ? (int)val($db,'SELECT COALESCE(SUM(amount_minor),0) FROM payments WHERE invoice_id=?',[$id]) : 0;
    if ($action === 'pay') {
        $amount = money_value($d['amount'] ?? null, $currency, 'Payment amount');
        $balance = (int)$old['amount_minor'] - $paid;
        if ($amount <= 0 || $amount > $balance) fail('Payment must be positive and cannot exceed the remaining balance of '.$currency.' '.money_display($balance,$currency).'.');
        $date = date_field($d['payment_date'] ?? '', 'Payment date');
        if ($date < $old['invoice_date']) fail('Payment date cannot precede the invoice date.');
        $payment = insert_row($db, 'payments', ['invoice_id'=>$id,'payment_date'=>$date,'amount_minor'=>$amount,
            'method'=>text_field($d['method'] ?? '', 'Payment method', 100, true),'notes'=>text_field($d['notes'] ?? '', 'Notes', 6000),'created_at'=>now_iso()]);
        return ['id'=>$id,'payment_id'=>$payment,'balance'=>money_display($balance-$amount, $currency),'message'=>'Payment recorded. No additional expense was created.'];
    }
    if (!in_array($action, ['create','update'], true)) fail('Unknown invoice action.',400);
    $vehicle = vehicle_link($db, $d['vehicle_id'] ?? null);
    $mechanic = require_row($db,'mechanics',id_field($d['mechanic_id'] ?? null,'Mechanic'),'Mechanic');
    $number = text_field($d['invoice_number'] ?? '', 'Invoice number', 100, true);
    $other = val($db, 'SELECT id FROM invoices WHERE mechanic_id=? AND invoice_number=?', [$mechanic['id'],$number]);
    if ($other && (int)$other !== $id) fail('An invoice with this number already exists for that mechanic.',409,'invoice_exists');
    $date = date_field($d['invoice_date'] ?? '', 'Invoice date');
    $due = date_field($d['due_date'] ?? '', 'Due date',false);
    if ($due && $due < $date) fail('The due date cannot precede the invoice date.');
    if ($id && ($earliest = val($db, 'SELECT MIN(payment_date) FROM payments WHERE invoice_id=?', [$id])) && $date > $earliest) fail('Invoice date cannot move after a recorded payment.');
    $amount = money_value($d['amount'] ?? null, $currency);
    if ($amount <= 0 || $amount < $paid) fail('Invoice amount must be positive and cannot be less than payments already recorded.');
    $desc = text_field($d['description'] ?? '', 'Repair description', 6000, true);
    $expense = ['vehicle_id'=>$vehicle,'expense_date'=>$date,'category'=>'Repairs','vendor'=>$mechanic['name'],'description'=>$desc,'amount_minor'=>$amount];
    if ($id) { $expenseId=(int)$old['expense_id']; update_row($db,'expenses',$expenseId,$expense); }
    else $expenseId=insert_row($db,'expenses',[...$expense,'created_at'=>now_iso()]);
    $v = ['vehicle_id'=>$vehicle,'mechanic_id'=>(int)$mechanic['id'],'expense_id'=>$expenseId,'invoice_number'=>$number,'invoice_date'=>$date,'due_date'=>$due,'amount_minor'=>$amount,'description'=>$desc];
    if ($id) update_row($db,'invoices',$id,$v); else $id=insert_row($db,'invoices',[...$v,'created_at'=>now_iso()]);
    return ['id'=>$id,'expense_id'=>$expenseId,'message'=>'Invoice saved and linked to one repair expense.'];
}

function fleet_state(PDO $db): array {
    $settings = settings($db); $currency = $settings['currency'];
    $vehicles = rows($db, 'SELECT * FROM vehicles ORDER BY id');
    $drivers = rows($db, 'SELECT * FROM drivers ORDER BY name,id');
    $vehicleNames = []; $driverNames = []; $dTotals = []; $vTotals = [];
    foreach ($vehicles as $v) $vehicleNames[$v['id']] = vehicle_name($v);
    foreach ($drivers as $d) $driverNames[$d['id']] = $d['name'];
    $trips = rows($db, 'SELECT * FROM trips ORDER BY trip_date DESC,id DESC');
    foreach ($trips as &$r) {
        $dist = odo_ticks($r['end_odometer'])-odo_ticks($r['start_odometer']); $r['distance'] = $dist/1000;
        $r['vehicle_name'] = $vehicleNames[$r['vehicle_id']] ?? '';
        $r['driver_name'] = $driverNames[$r['driver_id'] ?? 0] ?? '';
        $did = $r['driver_id'] ?? 0;
        $dTotals[$did] ??= ['count'=>0,'ticks'=>0]; $dTotals[$did]['count']++; $dTotals[$did]['ticks'] += $dist;
        $vTotals[$r['vehicle_id']] = ($vTotals[$r['vehicle_id']] ?? 0) + $dist;
    } unset($r);
    $expenses = rows($db, 'SELECT e.*,i.id AS invoice_id FROM expenses e LEFT JOIN invoices i ON i.expense_id=e.id ORDER BY e.expense_date DESC,e.id DESC');
    $mpg = fuel_intervals($expenses, $settings['unit']); $latestMPG = []; $expenseTotal = 0;
    foreach ($expenses as &$r) {
        $r['vehicle_name'] = $vehicleNames[$r['vehicle_id']] ?? '';
        $r['amount'] = money_display((int)$r['amount_minor'], $currency);
        $r += $mpg[$r['id']] ?? ['mpg'=>null,'fuel_interval'=>''];
        $expenseTotal += (int)$r['amount_minor'];
        if ($r['mpg'] !== null) {
            $current = $latestMPG[$r['vehicle_id']] ?? null;
            if (!$current || [$r['expense_date'],(float)$r['odometer'],(int)$r['id']] > $current['sort']) $latestMPG[$r['vehicle_id']] = ['value'=>$r['mpg'],'sort'=>[$r['expense_date'],(float)$r['odometer'],(int)$r['id']]];
        }
    } unset($r);
    $payments = rows($db,'SELECT * FROM payments ORDER BY payment_date DESC,id DESC'); $payMap = [];
    foreach ($payments as $r) { $r['amount'] = money_display((int)$r['amount_minor'], $currency); $payMap[$r['invoice_id']][] = $r; }
    $invoices = rows($db, 'SELECT i.*,m.name AS mechanic_name FROM invoices i JOIN mechanics m ON m.id=i.mechanic_id ORDER BY invoice_date DESC,id DESC');
    $debt = 0;
    foreach ($invoices as &$r) {
        $r['payments'] = $payMap[$r['id']] ?? [];
        $paid = (int)array_sum(array_column($r['payments'],'amount_minor')); $due = (int)$r['amount_minor'] - $paid;
        $r['balance_minor']=$due; $r['balance']=money_display($due,$currency); $r['paid']=money_display($paid,$currency); $r['amount']=money_display((int)$r['amount_minor'],$currency);
        $r['vehicle_name']=$vehicleNames[$r['vehicle_id']] ?? '';
        $r['overdue']=($due>0 && $r['due_date'] && $r['due_date']<today_iso());
        $r['status']=$due<=0?'Paid':($r['overdue']?'Overdue':($paid>0?'Partially paid':'Unpaid'));
        $debt += $due;
    } unset($r);
    $records = [];
    foreach (rows($db, 'SELECT * FROM driver_records ORDER BY record_date DESC,id DESC') as $r) $records[$r['driver_id']][] = $r;
    foreach ($drivers as &$d) {
        $d['trip_count'] = $dTotals[$d['id']]['count'] ?? 0;
        $d['distance'] = ($dTotals[$d['id']]['ticks'] ?? 0)/1000;
        $d['license_expired'] = $d['expiration_date'] !== null && $d['expiration_date'] < today_iso();
        $d['records'] = $records[$d['id']] ?? [];
    } unset($d);
    $engines = []; $photos=[];
    foreach (rows($db, 'SELECT * FROM engines ORDER BY id DESC') as $e) $engines[$e['vehicle_id']][]=$e;
    foreach (rows($db, "SELECT id,owner_id,caption,view_label,mime,created_at FROM photos WHERE owner_kind='vehicle' ORDER BY id DESC") as $p) $photos[$p['owner_id']][]=$p;
    $weeks = weekly_statuses($db, $vehicles);
    foreach ($vehicles as &$v) {
        $peakValue = (float)$v['odometer_peak'];
        $v['name']=vehicle_name($v); $v['current_odometer']=$peakValue;
        $v['distance']=($vTotals[$v['id']] ?? 0)/1000; $v['engine_odometer']=null;
        $v['mpg']=$latestMPG[$v['id']]['value'] ?? null; $v['inspection']=$weeks[$v['id']];
        $v['photos']=$photos[$v['id']] ?? []; $v['engines']=[];
        foreach ($engines[$v['id']] ?? [] as $e) {
            $e['current_mileage']=round((float)$e['at_engine'] + max(0,(float)($e['retired_odometer'] ?? $peakValue)-(float)$e['at_odometer']),3);
            if ($e['retired_date'] === null) $v['engine_odometer']=$e['current_mileage'];
            $v['engines'][]=$e;
        }
    } unset($v);
    return ['settings'=>$settings,'vehicles'=>$vehicles,'trips'=>$trips,'expenses'=>$expenses,'drivers'=>$drivers,'invoices'=>$invoices,
        'mechanics'=>rows($db,'SELECT * FROM mechanics ORDER BY name,id'),'totals'=>['distance'=>array_sum($vTotals)/1000,'expenses'=>money_display($expenseTotal,$currency),
        'debt'=>money_display($debt,$currency),'trip_count'=>count($trips),'active_drivers'=>count(array_filter($drivers, static fn($d)=>(bool)$d['active']))]];
}

function filter_fleet_rows(array $list, array $q, string $day): array {
    $from=date_field($q['from'] ?? '', 'Start date', false); $to=date_field($q['to'] ?? '', 'End date', false);
    if ($from && $to && $from>$to) fail('Start date cannot be after end date.');
    $vehicle=id_field($q['vehicle_id'] ?? '', 'Vehicle',false);
    $category=choice_field($q['category'] ?? '', EXPENSE_CATEGORIES,'category',false);
    $unassigned=($q['driver_id'] ?? '') === 'unassigned';
    $driver = $unassigned ? null : id_field($q['driver_id'] ?? '', 'Driver',false);
    return array_values(array_filter($list, static fn($r) => (!$vehicle || (int)$r['vehicle_id']===$vehicle) && (!$category || ($r['category'] ?? '')===$category)
        && (!$driver || (int)($r['driver_id'] ?? 0)===$driver) && (!$unassigned || ($r['driver_id'] ?? null)===null)
        && (!$from || $r[$day]>=$from) && (!$to || $r[$day]<=$to)));
}
