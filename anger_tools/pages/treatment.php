<?php
	$self = $_SERVER['PHP_SELF'];

	// Handle AJAX form submission
	if(!empty($_POST) && isset($_POST['ajax'])){

		//INSERT INTO "anger" ("id","madAbout","express","angerScale","thoughts","feelings","act","better") VALUES (NULL,'madAbout','express','1','thoughts','feelings','act','better')

		$q = 'INSERT INTO anger (
			madAbout,
			express,
			angerScale,
			thoughts,
			feelings,
			act,
			better,
			month,
			day,
			year,
			username,
			keycode,
			public
		)VALUES(
			:madAbout,
			:express,
			:angerScale,
			:thoughts,
			:feelings,
			:act,
			:better,
			:month,
			:day,
			:year,
			:username,
			:code,
			:public
		)';
		$p = [
			':madAbout' => binary_encoded($_POST['madAbout']),
			':express' => binary_encoded($_POST['express']),
			':angerScale' => $_POST['angerScale'],
			':thoughts' => binary_encoded($_POST['thoughts']),
			':feelings' => binary_encoded($_POST['feelings']),
			':act' => binary_encoded($_POST['act']),
			':better' => binary_encoded($_POST['better']),
			':month' => date('m'),
			':day' => date('d'),
			':year' => date('Y'),
			':username' => getUserID($_SESSION['user']),
			':code' => secureDB($_SESSION['user']),
			':public' => 0
		];
		$stmt = $db->prepare($q);
		$r = $stmt->execute($p);

		header('Content-Type: application/json');
		echo json_encode(['success' => true, 'message' => 'Anger Log has been recorded.']);
		exit;
	}

	// Handle regular form submission (fallback)
	if(!empty($_POST)){

		//INSERT INTO "anger" ("id","madAbout","express","angerScale","thoughts","feelings","act","better") VALUES (NULL,'madAbout','express','1','thoughts','feelings','act','better')

		$q = 'INSERT INTO anger (
			madAbout,
			express,
			angerScale,
			thoughts,
			feelings,
			act,
			better,
			month,
			day,
			year,
			username,
			keycode,
			public
		)VALUES(
			:madAbout,
			:express,
			:angerScale,
			:thoughts,
			:feelings,
			:act,
			:better,
			:month,
			:day,
			:year,
			:username,
			:code,
			:public
		)';
		$p = [
			':madAbout' => binary_encoded($_POST['madAbout']),
			':express' => binary_encoded($_POST['express']),
			':angerScale' => $_POST['angerScale'],
			':thoughts' => binary_encoded($_POST['thoughts']),
			':feelings' => binary_encoded($_POST['feelings']),
			':act' => binary_encoded($_POST['act']),
			':better' => binary_encoded($_POST['better']),
			':month' => date('m'),
			':day' => date('d'),
			':year' => date('Y'),
			':username' => getUserID($_SESSION['user']),
			':code' => secureDB($_SESSION['user']),
			':public' => 0
		];
		$stmt = $db->prepare($q);
		$r = $stmt->execute($p);

		echo '<div style="width:450px;" class="container card mb-4 py-3 border-bottom-success">
                <div class="card-body">
                  <b>Anger Log has been recorded.</b>
                </div>
             </div>';
		exit;
	}



?>
<div class="container">
<div class="box">
	<div class="ribbon"><span>NEW!!!</span></div>





	<div class='ribbon1'>
		<div class='theribbon'>Anger Management Log</div>
	</div>
	<u><h1 class="treat">Treatment Tool</h1></u>
	<hr>
	<center><strong>Anger Log #<?= date('ynjHis') ?></strong></center>
	<hr>
	<div class="Container">
		<div class="borderContainer">
			We can often manage anger better just by keeping track of the times when we become angry. This could help you better
			understand your anger "cues"—the thoughts and feelings that cause you to act in certain ways.

		</div>
		<div class="borderContainer">
			Date:<u> <?= date("F jS, Y")?>    </u><?php for($i=0; $i <= 80; $i++ ){echo '&nbsp';}?> Time of Day:<u> <?= date('H:i:s')?>    </u><br><br>
			<form id="angerForm" action="?p=treatment" method="post">
				<input type="hidden" name="ajax" value="1">
				Current User...
				<input class="form-control" type="text" disabled name="username" value="<?= display($_SESSION['user']) ?>" placeholder="Enter Your Name"/>
				<br>

				What did I get mad about?
				<textarea class="form-control" style="height:150px; type="text" placeholder="Enter a brief reason why you are mad." name="madAbout" /></textarea>

				<br>

				How did I express my anger?
				<input class="form-control" type="text" placeholder="How did you express your anger towards others?" name="express"/>

				<br>
				Select the # that describes how angry you felt <br><small>( 1 = not at all; 5 = medium; 10 = very, very angry)</small><br>
				<div class="scaleGradient">
				<?php
					for($i=1; $i <= 10; $i++ ) {
						//$i = $i++;
						/* switch($i){
							case 1:
								$color = 'style="color:lime;"';
								break;
							case 5:
								$color = 'style="color:blue;"';
								break;
							case 10:
								$color = 'style="color:red;"';
								break;
							default:
								$color = 'style="color:black;"';
						} */

						echo '<div class="form-check form-check-inline">';
						echo '<input class="form-check-input" type="radio" value="option'. $i .'" name="angerScale" id="angerScale'.$i.'">';
						echo '<b><label  class="form-check-label" for="angerScale'.$i.'"> '. $i .' </label></b>';
						echo '</div>';
					}
				?>
				</div>
				<br>

				What were my thoughts?
				<input class="form-control" type="text" placeholder="Express your thought process." name="thoughts"/>
				<br>
				What were my feelings?
				<input class="form-control" type="text" placeholder="Express how you felt." name="feelings"/>
				<br>
				How did I act?
				<input class="form-control" type="text" placeholder="What did you do or say?." name="act"/>
				<br>
				What could I have done better?
				<input class="form-control" type="text" placeholder="Express steps for a better anger control?." name="better"/>
				<br>
				<div style="justify-content:center;" class="form-inline">
					<button style="width:25%;" class="btn btn-secondary" type="reset">Clear Form</button>
					&nbsp&nbsp
					<button style="width:25%;" class="btn btn-danger" type="submit" id="submitBtn">Enter Anger Log</button>


				</div>

		<br>
		</div>

		</form>
		<div id="formMessage"></div>
	</div>
</div>
</div>

<script>
	$(document).ready(function() {
		$('#angerForm').submit(function(e) {
			e.preventDefault();

			// Validate form
			const angerScale = $('input[name="angerScale"]:checked').val();
			if (!angerScale) {
				$('#formMessage').html('<div class="alert alert-danger">Please select an anger level.</div>');
				return;
			}

			// Show loading state
			$('#submitBtn').prop('disabled', true).html('<span class="loading"></span> Submitting...');

			// Submit via AJAX
			$.ajax({
				url: '?p=treatment',
				method: 'POST',
				data: $(this).serialize(),
				success: function(response) {
					if (response.success) {
						$('#formMessage').html('<div class="success-message">' + response.message + '</div>');
						$('#angerForm')[0].reset();
						setTimeout(function() {
							$('#formMessage').fadeOut();
						}, 3000);
					} else {
						$('#formMessage').html('<div class="alert alert-danger">Error submitting form.</div>');
					}
				},
				error: function() {
					$('#formMessage').html('<div class="alert alert-danger">Error submitting form. Please try again.</div>');
				},
				complete: function() {
					$('#submitBtn').prop('disabled', false).text('Enter Anger Log');
				}
			});
		});
	});
</script>
