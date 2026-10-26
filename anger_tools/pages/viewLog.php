<?php

	$logID = intval($_GET['id']);
	$q = 'SELECT * FROM anger WHERE id = :id';
	$p = [':id' => $logID];
	$stmt = $db->prepare($q);
	$result = $stmt->execute($p);
	$anger = $stmt->fetchAll();

	$public = $anger['public'] == 1?true:false;
	if(!$public && getUserID($_SESSION['user']) !== $anger['username']){
		header("Location: ?p=anger");
		exit;
	}
	if(!$public && getUserID($_SESSION['user']) === $anger['username']){
		if(angerCode($anger['id']) === getCode(getUserID($_SESSION['user']))){
			$madAbout = binary_decoded($anger['madAbout']) ?? '';
			$express = binary_decoded($anger['express']) ?? '';
			$thoughts = binary_decoded($anger['thoughts']) ?? '';
			$feelings = binary_decoded($anger['feelings']) ?? '';
			$act = binary_decoded($anger['act']) ?? '';
			$better = binary_decoded($anger['better']) ?? '';

		}
	}

	$timestamp = $anger['timestamp'];
	$angerLogID = date('ynjHis', strtotime($timestamp));
	$angerDate = date('F jS, Y', strtotime($timestamp));
	$angerTime = date('H:i:s', strtotime($timestamp));

?>
	<div class="container">
		<div class="box">
			<div class="ribbon"><span>REVIEW</span></div>

			<div class='ribbon1'>
				<div class='theribbon'>Anger-Management Review</div>
			</div>
			<u><h1 class="treat">Anger Review</h1></u>
			<hr>
			<center><strong>Anger Log #<?= $angerLogID ?></strong></center>
			<hr>
			<div class="Container">
				<div class="borderContainer">
					We can often manage anger better just by keeping track of the times when we become angry. This could help you better
					understand your anger "cues"—the thoughts and feelings that cause you to act in certain ways.

				</div>
				<div class="borderContainer">
						Date:<u> <?= $angerDate?>    </u><?php for($i=0; $i <= 90; $i++ ){echo '&nbsp';}?> Time of Day:<u> <?= $angerTime?>    </u><br><br>
					<input disabled class="form-control" value="<?=ucwords(getName($anger['username']))?>"/>
					</br>
					What did I get mad about?<br>
					<textarea disabled class="form-control"><?= $madAbout?>
					</textarea disabled>

					<br>

					How did I express my anger?<br>
					<textarea disabled class="form-control"><?= $express?>
					</textarea disabled>

					<br>

					The color represents how angry you felt <br><small>( 1 = not at all; 5 = medium; 10 = very, very angry)</small></br>
					<span class="form-control" style="color:white;background-color:<?= getAngerLevel($anger['angerScale'])?>;"><?= getAngerLevelScale($anger['angerScale'])?>
					</span>

					<br>

					What were my thoughts?<br>
					<textarea disabled class="form-control"><?= $thoughts?>
					</textarea disabled>

					<br>

					What were my feelings?<br>
					<textarea disabled class="form-control"><?= $feelings?>
					</textarea disabled>

					<br>

					How did I act?<br>
					<textarea disabled class="form-control"><?= $act?>
					</textarea disabled>

					<br>

					What could I have done better?<br>
					<textarea disabled class="form-control"><?= $better?>
					</textarea disabled>
				</div>

			</div>

		</div>
	</div>
