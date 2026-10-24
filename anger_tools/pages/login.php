<main class="am-main">
  <div class="am-login-page">
    <div class="am-login-hero">

      <section class="am-login-card" aria-label="Login">
        <div class="am-login-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="11" width="16" height="10" rx="2"/>
            <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
          </svg>
        </div>

        <h1 class="am-login-title">Login to Anger Management System</h1>
        <p class="am-login-sub">Track, understand, and manage your anger</p>

        <form action="?p=login" method="post">
          <div class="am-field">
            <label class="am-label" for="username">Username</label>
            <input class="am-input" id="username" name="username" type="text"
                   placeholder="Enter username" autocomplete="off">
          </div>

          <div class="am-field">
            <label class="am-label" for="pass">Password</label>
            <input class="am-input" id="pass" name="pass" type="password"
                   placeholder="Enter password" autocomplete="off">
          </div>

          <button class="am-btn am-btn-primary" type="submit">Login</button>
        </form>

        <p class="am-login-alt">
          Need an account? <a href="?p=register">Register</a>
        </p>
      </section>

    </div>

    <footer class="am-footer">
      Mr John Dowe &copy;2019 - 2026
    </footer>
  </div>
</main>
<?php

if (!empty($_POST)) {
	$user = $_POST['username'];
	$pass = $_POST['pass'];

	if (!checkLogin($user, $pass)) {
		echo '
					<div style="width:450px;" class="container card mb-4 py-3 border-bottom-danger">
					<div class="card-body">
					  <b>Username or Password are incorrect.</b>
					</div>
				 </div>
				';
	} else if (checkLogin($user, $pass)) {
		// session_start();
		// ob_clean();

		$_SESSION['user'] = $user;
		$_SESSION['pass'] = password_hash($pass, PASSWORD_DEFAULT);


		echo '
					<div style="width:450px;" class="container card mb-4 py-3 border-bottom-success">
						<div class="card-body">
						  <b>Logged In.</b>
						</div>
					</div>
				';
		sleep(1);
		header("Location: index.php");
		exit;
	}
}

?>
