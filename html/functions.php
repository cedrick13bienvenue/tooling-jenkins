<?php
session_start();

$db = mysqli_connect(
    getenv('DB_HOST') ?: 'mysql.tooling.svc.cluster.local',
    getenv('DB_USER') ?: 'admin',
    getenv('DB_PASS') ?: 'admin',
    getenv('DB_NAME') ?: 'tooling'
);

$username = "";
$email    = "";
$errors   = array();

if (isset($_POST['register_btn'])) {
	register();
}

function register(){
	global $db, $errors, $username, $email;

	$username   = trim($_POST['username']);
	$email      = trim($_POST['email']);
	$password_1 = trim($_POST['password_1']);
	$password_2 = trim($_POST['password_2']);

	if (empty($username)) {
		array_push($errors, "Username is required");
	}
	if (empty($email)) {
		array_push($errors, "Email is required");
	}
	if (empty($password_1)) {
		array_push($errors, "Password is required");
	}
	if ($password_1 != $password_2) {
		array_push($errors, "The two passwords do not match");
	}

	if (count($errors) == 0) {
		$password = password_hash($password_1, PASSWORD_BCRYPT);

		if (isset($_POST['user_type'])) {
			$user_type = trim($_POST['user_type']);
			$stmt = mysqli_prepare($db, "INSERT INTO users (username, email, user_type, password) VALUES(?, ?, ?, ?)");
			mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $user_type, $password);
			mysqli_stmt_execute($stmt);
			$_SESSION['success'] = "New user successfully created!!";
			header('location: admin_tooling.php');
		} else {
			$user_type = 'user';
			$stmt = mysqli_prepare($db, "INSERT INTO users (username, email, user_type, password) VALUES(?, ?, ?, ?)");
			mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $user_type, $password);
			mysqli_stmt_execute($stmt);

			$logged_in_user_id = mysqli_insert_id($db);
			$_SESSION['user'] = getUserById($logged_in_user_id);
			$_SESSION['success'] = "You are now logged in";
			header('location: index.php');
		}
	}
}

function getUserById($id){
	global $db;
	$stmt = mysqli_prepare($db, "SELECT * FROM users WHERE id=?");
	mysqli_stmt_bind_param($stmt, "i", $id);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	return mysqli_fetch_assoc($result);
}

function e($val){
	return trim($val);
}

function display_error() {
	global $errors;

	if (count($errors) > 0){
		echo '<div class="error">';
			foreach ($errors as $error){
				echo htmlspecialchars($error) . '<br>';
			}
		echo '</div>';
	}
}

function isLoggedIn()
{
	return isset($_SESSION['user']);
}

if (isset($_GET['logout'])) {
	session_destroy();
	unset($_SESSION['user']);
	header("location: login.php");
}

if (isset($_POST['login_btn'])) {
	login();
}

function login(){
	global $db, $username, $errors;

	$username = trim($_POST['username']);
	$password = trim($_POST['password']);

	if (empty($username)) {
		array_push($errors, "Username is required");
	}
	if (empty($password)) {
		array_push($errors, "Password is required");
	}

	if (count($errors) == 0) {
		$stmt = mysqli_prepare($db, "SELECT * FROM users WHERE username=? LIMIT 1");
		mysqli_stmt_bind_param($stmt, "s", $username);
		mysqli_stmt_execute($stmt);
		$results = mysqli_stmt_get_result($stmt);

		if (mysqli_num_rows($results) == 1) {
			$logged_in_user = mysqli_fetch_assoc($results);
			if (password_verify($password, $logged_in_user['password'])) {
				$_SESSION['user'] = $logged_in_user;
				$_SESSION['success'] = "You are now logged in";
				if ($logged_in_user['user_type'] == 'admin') {
					header('location: admin_tooling.php');
				} else {
					header('location: index.php');
				}
			} else {
				array_push($errors, "Wrong username/password combination");
			}
		} else {
			array_push($errors, "Wrong username/password combination");
		}
	}
}

function isAdmin()
{
	return isset($_SESSION['user']) && $_SESSION['user']['user_type'] == 'admin';
}
