<?php
	$self = $_SERVER['PHP_SELF'];

	$month = date('m');
	$year = date('Y');

	$q = 'SELECT * FROM anger WHERE username = :username AND month = :month AND year = :year';
	$p = [':month' => $month, ':year' => $year, ':username' => getUserID($_SESSION['user'])];
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
					header("Location: ?p=anger");
					exit;

				}

				?>
				<tr>
					<td><?=ucwords(getName($row['username']))?></td>
					<td><?= date('ynjHis',strtotime( $row['timestamp'] ) )?></td>
					<td><?= date('m-d-Y // H:i:s', strtotime($row['timestamp']))?></td>
					<td><a class="btn btn-info view-log-btn" data-id="<?=$row['id']?>">View Log</a> <a class="btn btn-<?=$pColor?>" href="?p=anger&public&id=<?=$row['id']?>"><?=$pLabel?></a></td>
				</tr>
				<?php endforeach;?>





			</tbody>
		</table>
	</div>

	<!-- Modal for viewing log details -->
	<div id="logModal" class="modal">
		<div class="modal-content">
			<span class="close-modal">&times;</span>
			<div id="modalContent">
				<div class="loading"></div> Loading...
			</div>
		</div>
	</div>

	<script>
		$(document).ready(function() {
			// View log button click handler
			$('.view-log-btn').click(function() {
				const logId = $(this).data('id');
				$('#logModal').css('display', 'block');
				$('#modalContent').html('<div class="loading"></div> Loading...');

				$.get('api/anger_data.php?action=get_log_details&id=' + logId, function(data) {
					if (data.error) {
						$('#modalContent').html('<div class="alert alert-danger">' + data.error + '</div>');
						return;
					}

					const html = `
						<div class="box">
							<div class="ribbon"><span>REVIEW</span></div>
							<div class='ribbon1'>
								<div class='theribbon'>Anger-Management Review</div>
							</div>
							<u><h1 class="treat">Anger Review</h1></u>
							<hr>
							<center><strong>Anger Log #${data.angerLogID}</strong></center>
							<hr>
							<div class="Container">
								<div class="borderContainer">
									We can often manage anger better just by keeping track of the times when we become angry. This could help you better
									understand your anger "cues"—the thoughts and feelings that cause you to act in certain ways.
								</div>
								<div class="borderContainer">
									Date:<u> ${data.angerDate}</u> Time of Day:<u> ${data.angerTime}</u><br><br>
									<input disabled class="form-control" value="${data.userName}"/>
									</br>
									What did I get mad about?<br>
									<textarea disabled class="form-control">${data.madAbout || ''}</textarea disabled>
									<br>
									How did I express my anger?<br>
									<textarea disabled class="form-control">${data.express || ''}</textarea disabled>
									<br>
									The color represents how angry you felt <br><small>( 1 = not at all; 5 = medium; 10 = very, very angry)</small></br>
									<span class="form-control" style="color:white;background-color:${data.levelColor};">${data.levelScale}</span>
									<br>
									What were my thoughts?<br>
									<textarea disabled class="form-control">${data.thoughts || ''}</textarea disabled>
									<br>
									What were my feelings?<br>
									<textarea disabled class="form-control">${data.feelings || ''}</textarea disabled>
									<br>
									How did I act?<br>
									<textarea disabled class="form-control">${data.act || ''}</textarea disabled>
									<br>
									What could I have done better?<br>
									<textarea disabled class="form-control">${data.better || ''}</textarea disabled>
								</div>
							</div>
						</div>
					`;
					$('#modalContent').html(html);
				}).fail(function() {
					$('#modalContent').html('<div class="alert alert-danger">Error loading log details.</div>');
				});
			});

			// Close modal when clicking X
			$('.close-modal').click(function() {
				$('#logModal').css('display', 'none');
			});

			// Close modal when clicking outside
			$(window).click(function(event) {
				if (event.target.id === 'logModal') {
					$('#logModal').css('display', 'none');
				}
			});
		});
	</script>


