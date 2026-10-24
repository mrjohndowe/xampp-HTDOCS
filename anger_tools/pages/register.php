	<div class="container">
		<center><h1 class="shadowText form form-header">
			<u style="text-decoration-color:red;"> Register to Anger Management System</u>
		</h1></center>
		<form style="width:50%;" class="container user" action="?p=register" method="post">
			<br>
			<div class="form-group row">
				<div class="col-sm-6 mb-3 mb-sm-0">
					What is your username?
					<input required class="form-control" autocomplete="off" type="text" name="username" placeholder="Enter Username"/>
				</div>	
				<div class="col-sm-6">	
					What is your display name?
					<input required class="form-control" autocomplete="off" type="text" name="display" placeholder="Enter display name"/>
				</div>
			</div>
			
			<div class="form-group">
				What is your email?
				<input required class="form-control" autocomplete="off" type="email" name="email" placeholder="Enter email"/>
			</div>
			
			<div class="form-group row">
				<div class="col-sm-6 mb-3 mb-sm-0">
				
					Enter your password.
					<input required autocomplete="off" class="form-control" type="password" name="pass" placeholder="Enter Password"/>
				</div>
				<div class="col-sm-6">
					Confirm Your Password
					<input required autocomplete="off" class="form-control" type="password" name="pass2" placeholder="Re-Enter Password"/>
				</div>
			</div>
			
			<button class="btn btn-danger" type="submit" value="submit"/>Register</button> &nbsp <a href="?p=register" class="btn btn-danger" type="reset" >Clear</a>
		</form>
		<br>
		<center>
	</div>
<?php
	$error = '';
	if(!empty($_POST)){
		$user = isset($_POST['username'])?$_POST['username']:null;
		$email = isset($_POST['email'])?$_POST['email']:null;
		$display = isset($_POST['display'])?$_POST['display']:null;
		$pass = isset($_POST['pass'])?$_POST['pass']:null;
		$pass2 = isset($_POST['pass2'])?$_POST['pass2']:null;
		
		/* $genPass = '';
		$genSalt = '';
		
		$genSalt = md5(microtime());
		$genPass = md5(md5($user.$pass).$genSalt); */
		
		$genPass = md5(md5($user.$pass));
			
		if($pass2 !== $pass){
			$error = '<div style="width:450px;" class="container card mb-4 py-3 border-bottom-danger">
                <div class="card-body">
                  <b>Passwords Do Not Match.</b>
                </div>
             </div>';
		}else if(checkUser($user) == true){
			$error = '<div style="width:450px;" class="container card mb-4 py-3 border-bottom-danger">
                <div class="card-body">
                  <b>Username already exists!</b>
                </div>
             </div>';
			 // exit($error);
		}else if(checkEmail($email) == true){
			$error = '<div style="width:450px;" class="container card mb-4 py-3 border-bottom-danger">
                <div class="card-body">
                  <b>Email already exists!</b>
                </div>
             </div>';
			 // exit($error);
		}else{
			
			
			$q = 'INSERT INTO users(
					name,
					email,
					username,
					password,
					admin,
					status,
					salt
				)VALUES(
					:name,
					:email,
					:username,
					:password,
					:admin,
					:status,
					:salt
				)';
			$p = [
				':name' => $display,
				':email' => $email,
				':username' => $user,
				':password' => password_hash($pass,PASSWORD_DEFAULT),
				':admin' => 0,
				':status' => true,
				':salt' => $genPass
			];
			$stmt = $db->prepare($q);
			$result = $stmt->execute($p);
			
			$error = '<div style="width:450px;" class="container card mb-4 py-3 border-bottom-success">
                <div class="card-body">
                  <b>User Successfully Created!</b>
                </div>
             </div>';
			
		}
	}


?><?=$error?></center>