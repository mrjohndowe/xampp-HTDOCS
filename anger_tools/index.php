<?php
	require_once 'extra/header.php';
	echo date_default_timezone_get();
	$page = isset($_GET['p'])?$_GET['p']:'';

	$loggedIn = !empty($_SESSION['user'])?$_SESSION['user']:'';
	// $loggedIn = true;

	if($page === 'register'){
		include_once 'pages/register.php';
		include_once 'extra/footer.php';
		exit;
	}
	if(!$loggedIn){
		include_once 'pages/login.php';
		include_once 'extra/footer.php';
		exit;
	}

	if($page){
		echo '<br>';
		include_once 'pages/'.$page.'.php';
		//include_once 'extra/footer.php';
	}

	else{


	switch(getAngerCount(getUserID($_SESSION['user']))){
		case 0:
			$label = 'success';
			break;
		case 1:
		case 2:
		case 3:
		case 4:
		case 5:
			$label = 'warning';
			break;
		case 6:
		case 7:
		case 8:
		case 9:
		case 10:
			$label = 'danger';
			break;
	}
?>
	<br>

	<!-- Dashboard Statistics -->
	<div class="container">
		<div class="row">
			<div class="col-md-4">
				<div class="stat-card">
					<div class="stat-icon" style="color: #000;">
						<i class="fas fa-fire"></i>
					</div>
					<div class="stat-value" id="angerCount">Loading...</div>
					<div class="stat-label">Anger Episodes This Month</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="stat-card">
					<div class="stat-icon" style="color: #000;">
						<i class="fas fa-chart-line"></i>
					</div>
					<div class="stat-value" id="avgAngerLevel">Loading...</div>
					<div class="stat-label">Average Anger Level (1-10)</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="stat-card">
					<div class="stat-icon" style="color: #000;">
						<i class="fas fa-calendar-check"></i>
					</div>
					<div class="stat-value" id="currentMonth"><?=date('F')?></div>
					<div class="stat-label">Current Month</div>
				</div>
			</div>
		</div>
	</div>

	<br>

	<!-- Anger Trend Chart -->
	<div class="container">
		<div class="card">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold">30-Day Anger Trend</h6>
			</div>
			<div class="card-body">
				<div class="chart-container">
					<canvas id="angerTrendChart"></canvas>
				</div>
			</div>
		</div>
	</div>

	<br>

	<!-- Anger Level Distribution Chart -->
	<div class="container">
		<div class="card">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold">Anger Level Distribution</h6>
			</div>
			<div class="card-body">
				<div class="chart-container">
					<canvas id="angerDistributionChart"></canvas>
				</div>
			</div>
		</div>
	</div>

	<br>

	<div class="container card shadow mb-4">
		<div class="card-header py-3">
			<h6 class="m-0 font-weight-bold text-danger">Anger Management</h6>
		</div>
		<div class="card-body">
			<div class="chart-area">
				We can often manage anger better just by keeping track of the times when we become angry. This could help you better understand your anger "cues"—the thoughts and feelings that cause you to act in certain ways.<br><hr>
				<div class="col-xl-5 col-md-6 mb-4">
					<div class="card border-left-<?=$label?> shadow h-100 py-2">
						<div class="card-body">

							<div class="row no-gutters align-items-center">
								<div class="col mr-2">

									<div class="text-xs font-weight-bold text-<?=$label?> text-uppercase mb-1"> <?=display($_SESSION['user'])?>'s Anger Count ( <u><?=date('F Y')?></u> )</div>
									<div class="h5 mb-0 font-weight-bold text-gray-800"><?= getAngerCount(getUserID($_SESSION['user']))?></div>
								</div>
								<div class="col-auto">
									<i class="fas fa-calendar fa-2x text-gray-300"></i>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!--<div class="col-xl-4 col-md-6 mb-4">
					<div class="card border-left-danger shadow h-100 py-2">
						<div class="card-body">

							<div class="row no-gutters align-items-center">
								<div class="col mr-2">

									<div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Sierra's Anger Count ( <u><?=date('F')?></u> )</div>
									<div class="h5 mb-0 font-weight-bold text-gray-800"><?=getAngerCount('Sierra')?></div>
								</div>
								<div class="col-auto">
									<i class="fas fa-calendar fa-2x text-gray-300"></i>
								</div>
							</div>
						</div>
					</div>
				</div>-->

			</div>
		</div>
	</div>

	<script>
		// Load dashboard data via AJAX
		$(document).ready(function() {
			// Get anger stats
			$.get('api/anger_data.php?action=get_anger_stats', function(data) {
				console.log('Stats data:', data);
				$('#angerCount').text(data.count);
				$('#avgAngerLevel').text(data.avgLevel);
			}).fail(function(xhr, status, error) {
				console.error('Error loading stats:', error);
				$('#angerCount').text('Error');
				$('#avgAngerLevel').text('Error');
			});

			// Get anger trend chart data
			$.get('api/anger_data.php?action=get_anger_trend', function(data) {
				console.log('Trend data:', data);
				const labels = data.map(item => item.day);
				const counts = data.map(item => item.count);
				const avgLevels = data.map(item => item.avgLevel);

				const ctx = document.getElementById('angerTrendChart').getContext('2d');
				new Chart(ctx, {
					type: 'line',
					data: {
						labels: labels,
						datasets: [{
							label: 'Anger Episodes',
							data: counts,
							borderColor: '#f5576c',
							backgroundColor: 'rgba(245, 87, 108, 0.1)',
							fill: true,
							tension: 0.4
						}, {
							label: 'Avg Anger Level',
							data: avgLevels,
							borderColor: '#38ef7d',
							backgroundColor: 'rgba(56, 239, 125, 0.1)',
							fill: true,
							tension: 0.4
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: {
								position: 'top',
							}
						},
						scales: {
							y: {
								beginAtZero: true
							}
						}
					}
				});
			}).fail(function(xhr, status, error) {
				console.error('Error loading trend:', error);
				$('#angerTrendChart').parent().html('<div class="alert alert-danger">Error loading chart data.</div>');
			});

			// Get anger distribution chart data
			$.get('api/anger_data.php?action=get_anger_distribution', function(data) {
				console.log('Distribution data:', data);
				const labels = [];
				const counts = [];
				const backgroundColors = [
					'rgba(21, 234, 0, 0.7)',
					'rgba(45, 209, 0, 0.7)',
					'rgba(69, 185, 0, 0.7)',
					'rgba(93, 163, 0, 0.7)',
					'rgba(116, 139, 0, 0.7)',
					'rgba(141, 115, 0, 0.7)',
					'rgba(164, 91, 0, 0.7)',
					'rgba(187, 67, 0, 0.7)',
					'rgba(211, 45, 0, 0.7)',
					'rgba(241, 14, 0, 0.7)'
				];
				const borderColors = [
					'rgb(21, 234, 0)',
					'rgb(45, 209, 0)',
					'rgb(69, 185, 0)',
					'rgb(93, 163, 0)',
					'rgb(116, 139, 0)',
					'rgb(141, 115, 0)',
					'rgb(164, 91, 0)',
					'rgb(187, 67, 0)',
					'rgb(211, 45, 0)',
					'rgb(241, 14, 0)'
				];

				for (let i = 1; i <= 10; i++) {
					const key = 'option' + i;
					labels.push('Level ' + i);
					counts.push(data[key] || 0);
				}

				const ctx = document.getElementById('angerDistributionChart').getContext('2d');
				new Chart(ctx, {
					type: 'bar',
					data: {
						labels: labels,
						datasets: [{
							label: 'Count',
							data: counts,
							backgroundColor: backgroundColors,
							borderColor: borderColors,
							borderWidth: 1
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: {
								display: false
							}
						},
						scales: {
							y: {
								beginAtZero: true
							}
						}
					}
				});
			}).fail(function(xhr, status, error) {
				console.error('Error loading distribution:', error);
				$('#angerDistributionChart').parent().html('<div class="alert alert-danger">Error loading chart data.</div>');
			});
		});
	</script>





	<!--<div class="container card shadow mb-4">
		<div class="card-header py-3">
			<h6 class="m-0 font-weight-bold text-danger">Anger Management</h6>
		</div>
		<div class="card-body">
			<div class="chart-area">
				<div class="chartjs-size-monitor">
					<div class="chartjs-size-monitor-expand">
						<div class=""></div>
					</div>
					<div class="chartjs-size-monitor-shrink">
						<div class=""></div>
					</div>
				</div>
				<canvas id="myAreaChart" style="display: block; width: 603px; height: 320px;" width="603" height="320" class="chartjs-render-monitor">
				</canvas>
			</div>

		</div>
	</div>-->




<?php
	}

	include_once 'extra/footer.php';
?>
