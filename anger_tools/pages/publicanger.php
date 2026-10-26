<?php
	$self = $_SERVER['PHP_SELF'];
	
	$month = date('m');
	$year = date('Y');
	
	$q = 'SELECT * FROM anger WHERE month = :month AND year = :year AND public = 1';
	$p = [':month' => $month, ':year' => $year];
	$stmt = $db->prepare($q);
	$result = $stmt->execute($p);
	$rows = $stmt->fetchAll();


?>

	<div  class="container">
		<table class="table table-bordered table-striped table-danger"> 
			<thead>
				<tr>
					<th>Name</th>
					<th>Anger Log #</th>
					<th>Date/Time</th>
					<th></th>
				</tr>
			</thead>
			<tbody style="font-size:xx-large;color:white;text-shadow:2px 2px black;">
				<?if(count($rows) == 0){?>
					<tr>
						<td>NO</td>
						<td>RECORDS</td>
						<td>FOUND</td>
						<td></td>
						
					</tr>
				<?php } 
					
				
				
				
				foreach($rows as $row):
				
				
				?>
				<tr>
					<td><?=ucwords(getName($row['username']))?></td>
					<td><?= date('ynjHis',strtotime( $row['timestamp'] ) )?></td>
					<td><?= date('m-d-Y // H:i:s', strtotime($row['timestamp']))?></td>
					<td><a class="btn btn-info" href="?p=viewLog&id=<?=$row['id']?>">View Log</a></td>
				</tr>
				<?php endforeach;?>
				
				
				
				
				
			</tbody>
		</table>
	</div>
	

	