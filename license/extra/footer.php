<?php $automatic = new automatic; ?>
	<hr>
	<center>
		<footer class="container shadow footer">
			Mr John Dowe ©<?=$automatic->copyright(2000)?>
		</footer>
	</center>
	<hr>



<?php
	

	class automatic {
		
		public function copyright($year = 'auto'){
			
			if(intval($year) == 'auto')
			{
				$year = date('Y'); 
			} 
			if(intval($year) == date('Y'))
			{ 
				echo intval($year); 
			} 
			if(intval($year) < date('Y'))
			{ 
				echo intval($year) . ' - ' . date('Y'); 
			} 
			if(intval($year) > date('Y'))
			{ 
				echo date('Y'); 
			} 
			
			
		}
		
	}



?>