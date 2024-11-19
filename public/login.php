   <?php
   // Set the desired session cookie parameters
   ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60); // 30 days
   session_set_cookie_params([
       'lifetime' => 30 * 24 * 60 * 60,
       'path' => '/',
       'domain' => '', // Set to your domain
       'secure' => true,
       'httponly' => true,
       'samesite' => 'Lax' // Or 'Strict', 'None'
   ]);

   // Start the session
   session_start();

   // ... rest of your initialization code

// public/login.php
require '../bootstrap.php';
require_once "/var/www/nestogy/includes/rfc6238.php";

$domain = $_SERVER['HTTP_HOST'];

use Twetech\Nestogy\Database;
$config = require '/var/www/nestogy/config/' . $domain . '/config.php';
$company_logo = $config['company_logo'];
$database = new Database($config['db']);
$pdo = $database->getConnection();

use Twetech\Nestogy\Auth\Auth;

$auth = new Auth($pdo);

// Check if the user has a valid "Remember Me" cookie
$auth->checkRememberMe();

if (Auth::check()) {
    // User is already logged in, redirect them to the dashboard
    header('Location: /public/');
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'];

if ($auth->isIPBlocked($ip)) {
    $response = 'Too many login attempts from your IP address. Please try again later.';
} else {
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $remember_me = isset($_POST['remember_me']) ? true : false;

        if ($auth->isAccountLocked($email)) {
            $response = 'Your account is locked due to too many failed login attempts. Please try again after 15 minutes.';
        } else {
            $user = $auth->findUser($email, $password);

            if ($user) {
                if (isset($user['user_token'])) {
                    $token_field = '<div class="form-group mb-4"><label for="token">Token</label><input type="text" class="form-control" placeholder="2FA Token" name="token" required></div>';
                } else {
                    $userLogin = [
                        'user_id' => $user['user_id'],
                        'user_name' => $user['user_name'],
                        'user_role' => $user['user_role'],
                        'user_avatar' => $user['user_avatar'],
                        'remember_me' => $remember_me,
                        'user_specific_encryption_ciphertext' => $user['user_specific_encryption_ciphertext'],
                        'user_password' => $password
                    ];
                    $auth->login($userLogin);
                    exit;
                }

                if (isset($_POST['token'])) {
                    if (TokenAuth6238::verify($user['user_token'], $_POST['token'])) {
                        $userLogin = [
                            'user_id' => $user['user_id'],
                            'user_name' => $user['user_name'],
                            'user_role' => $user['user_role'],
                            'user_avatar' => $user['user_avatar'],
                            'remember_me' => $remember_me,
                            'user_specific_encryption_ciphertext' => $user['user_specific_encryption_ciphertext'],
                            'user_password' => $password
                        ];
                        $auth->login($userLogin);
                        exit;
                    } else {
                        $response = 'Invalid token.';
                    }
                }
            } else {
                $response = 'Invalid email or password.';
            }
        }
    }
}



?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Employee Login</title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="/includes/plugins/fontawesome-free/css/all.min.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="keywords" content="Bootstrap Theme, Freebies, Dashboard, MIT license">
    <meta name="description" content="Stream - Dashboard UI Kit">
    <meta name="author" content="htmlstream.com">

    <!-- Favicon -->
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

    <!-- Web Fonts -->
    <link href="//fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">

    <!-- Components Vendor Styles -->
    <link rel="stylesheet" href="/includes/dist/vendor/font-awesome/css/all.min.css">

    <!-- Theme Styles -->
    <link rel="stylesheet" href="/includes/dist/css/theme.css">
</head>

<body class="hold-transition login-page">
		<main class="container-fluid w-100" role="main">
			<div class="row">
				<div class="col-lg-6 d-flex flex-column justify-content-center align-items-center bg-white mnh-100vh login-box">
					<a class="u-login-form py-3 mb-auto login-logo" href="index.html">
                        <img alt="logo" height="110" width="380" class="img-fluid" src="<?= "/uploads/settings/$domain.jpg"; ?>">
					</a>

                    <?php if(!empty($config_login_message)){ ?>
                        <p class="login-box-msg px-0"><?= nl2br($config_login_message); ?></p>
                    <?php } ?>

                    <?php if (isset($response)) { ?>
                        <p class="login-box-msg px-0 text-danger">
                            <?= $response; ?>
                        </p>
                    <?php } ?>

					<div class="u-login-form">
						<form method="post">
							<div class="mb-3">
								<h1 class="h2">Employee Login</h1>
								<p class="small">Login with technician email address and password.</p>
							</div>

							<div class="form-group mb-4" <?php if (isset($token_field)) { echo "style='display:none;'"; } ?> >
								<label for="email">Technician Email</label>
                                <input type="text" class="form-control" placeholder="Agent Email" name="email" value="<?php if (isset($token_field)) { echo $email; }?>" required <?php if (!isset($token_field)) { echo "autofocus"; } ?> >
							</div>

							<div class="form-group mb-4" <?php if (isset($token_field)) { echo "style='display:none;'"; } ?> >
								<label for="password">Password</label>
                                <input type="password" class="form-control" placeholder="Agent Password" name="password" value="<?php if (isset($token_field)) { echo $password; } ?>" required>
							</div>

                            <?php if (isset($token_field)) { echo $token_field; ?>

                            <div class="form-group d-flex justify-content-between align-items-center mb-4">
                                <div class="custom-control custom-checkbox">
                                    <input id="remember_me" class="custom-control-input" name="remember_me" type="checkbox">
                                    <label class="custom-control-label" for="remember_me">Remember me</label>
                                </div>
                            </div>

                            <?php } ?>

							<button class="btn btn-label-primary btn-block" type="submit" name="login">Login</button>
						</form>

                        <hr>
                            <h3 class="text-center">Looking for the <a href="/">Client Portal?</a></h3>
					</div>

					<div class="u-login-form text-muted py-3 mt-auto">
						<small><i class="far fa-question-circle mr-1"></i> If you are not able to sign in, please <a href="mailto:help@twe.tech">contact us</a>.</small>
					</div>
				</div>

				<div class="col-lg-6 d-none d-lg-flex flex-column align-items-center justify-content-center bg-light">
					<img class="img-fluid position-relative u-z-index-3 mx-5" src="/includes/dist/svg/mockups/mockup.svg" alt="Image description">

					<figure class="u-shape u-shape--top-right u-shape--position-5">
						<img src="/includes/dist/svg/shapes/shape-1.svg" alt="Image description">
					</figure>
					<figure class="u-shape u-shape--center-left u-shape--position-6">
						<img src="/includes/dist/svg/shapes/shape-2.svg" alt="Image description">
					</figure>
					<figure class="u-shape u-shape--center-right u-shape--position-7">
						<img src="/includes/dist/svg/shapes/shape-3.svg" alt="Image description">
					</figure>
					<figure class="u-shape u-shape--bottom-left u-shape--position-8">
						<img src="/includes/dist/svg/shapes/shape-4.svg" alt="Image description">
					</figure>
				</div>
			</div>
		</main>

        <!-- jQuery -->
        <script src="/includes/plugins/jquery/jquery.min.js"></script>

        <!-- Bootstrap 4 -->
        <script src="/includes/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

        <!-- Prevents resubmit on refresh or back -->
        <script src="/js/login_prevent_resubmit.js"></script>

    </body>
</html>