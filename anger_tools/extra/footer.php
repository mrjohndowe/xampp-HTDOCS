<hr>
<center>
	<footer class="container shadow footer">
		Mr John Dowe ©<?=auto_copyright(2019)?>
	</footer>
</center>


<hr>
<? 
$debug = false; if($debug){

	$words = 'Try to incoporate more security';
	$user = 'mrjohndowe';
	$pass = '1204563';

	$bencode = binary_encoded($words);

?>
<textarea style="width:100%;height:250px;background-color:rgba(75,0,0,0.25);color:white;" disabled class="container form-control" placeholder="testing area">
	
	Binary encoded: <?= $bencode ?>
	
	
	Binary decoded: <?= binary_decoded($bencode) ?>
	
	
</textarea>
<?}?>
</body>
</html>
