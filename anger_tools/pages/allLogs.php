<?php
	$self = $_SERVER['PHP_SELF'];
	
	$month = date('m');
	$year = date('Y');
	
	$q = 'SELECT * FROM anger WHERE username = :username';
	$p = [':username' => getUserID($_SESSION['user'])];
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
				
				
				
				$status = $row['public'];
				$status = $status != 0?true:false;
				
				switch($status){
					case true:
						$pLabel = 'Public';
						$pColor = 'warning';
						$pStatus = 0;
						break;
					case false:
						$pLabel = 'Private';
						$pColor = 'danger';
						$pStatus = 1;
						break;
				}
				if(isset($_GET['public'])){
					
					$id = isset($_GET['id'])?$_GET['id']:die('NOT FOUND');
					$q = 'UPDATE anger SET public = :public WHERE id = :id';
					$p = [':public' => $pStatus, ':id' => $id];
					$stmt = $db->prepare($q);
					$result = $stmt->execute($p);
					header("Location: ?p=allLogs");
					exit;
					
				}
				
				?>
				<tr>
					<td><?=ucwords(getName($row['username']))?></td>
					<td><?= date('ynjHis',strtotime( $row['timestamp'] ) )?></td>
					<td><?= date('m-d-Y // H:i:s', strtotime($row['timestamp']))?></td>
					<td><a class="btn btn-info" href="?p=viewLog&id=<?=$row['id']?>">View Log</a> <a class="btn btn-<?=$pColor?>" href="?p=allLogs&public&id=<?=$row['id']?>"><?=$pLabel?></a></td>
				</tr>
				<?php endforeach;?>
				
				
				
				
				
			</tbody>
		</table>
	</div>
	

	