<?php
require("../php/msql.php");
ob_start();
session_start();

$error_msg = null;

if(isset($_SESSION['steamid']) && !isset($_GET['logout'])){
  echo "<script>location.replace('../');</script>";
  exit;
}
if(isset($_COOKIE['intphx_steamid']) && !isset($_GET['logout'])) {
  $_SESSION['steamid'] = $_COOKIE['intphx_steamid'];
  echo "<script>location.replace('../');</script>";
  exit;
}

if (isset($_GET['login'])){
	require 'openid.php';
	try {
		require 'SteamConfig.php';
    
		$openid = new LightOpenID($steamauth['domainname']);
		
		if(!$openid->mode) {
			$openid->identity = 'https://steamcommunity.com/openid';
			header('Location: ' . $openid->authUrl());
		} elseif ($openid->mode == 'cancel') {
			$error_msg = 'User has canceled authentication!';
		} else {
			if($openid->validate()) { 
				$id = $openid->identity;
				$ptn = "/^https?:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
				preg_match($ptn, $id, $matches);
        $sql = "SELECT * FROM members WHERE steamid = '" . mysqli_real_escape_string($conn, $matches[1]) . "' AND dept = '4'";
        $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0) {
          if (!headers_sent()) {
            // Cookie expires after 2 days
            setcookie("intphx_steamid", $matches[1], time()+2*24*60*60, "../", $_SERVER['SERVER_NAME']);
            $_SESSION['steamid'] = $matches[1];
            header('Location: ?update');
            exit();
          }
        }
        else {
          $sql = "SELECT * FROM members WHERE steamid = '" . mysqli_real_escape_string($conn, $matches[1]) . "' AND com_board = true";
          $result = mysqli_query($conn, $sql);
          if(mysqli_num_rows($result) > 0) {
            if (!headers_sent()) {
              // Cookie expires after 2 days
              setcookie("intphx_steamid", $matches[1], time()+2*24*60*60, "../", $_SERVER['SERVER_NAME']);
              $_SESSION['steamid'] = $matches[1];
              header('Location: ?update');
              exit();
            }
          }
          else{
            $error_msg = "Failed, no access!";
          }
        }
			} 
      else {
				$error_msg = "User is not logged in.\n";
			}
		}
	} catch(ErrorException $e) {
		echo $e->getMessage();
	}
}

if(isset($_GET['logout'])){
	require 'SteamConfig.php';
	session_unset();
	session_destroy();
  setcookie("intphx_steamid", null, time()-60, "../", $_SERVER['SERVER_NAME']);
	header('Location: '.$steamauth['logoutpage']);
	exit;
}

if(isset($_GET['update'])){
  if(isset($_SESSION['steamid'])) {
    unset($_SESSION['steam_uptodate']);
    require '../php/userInfo.php';
    $sql = "UPDATE users SET username='". mysqli_real_escape_string($conn, $steamprofile['personaname']) ."' WHERE steamid='{$_SESSION['steamid']}'";
    if(mysqli_query($conn, $sql)) {
      header("Location: {$steamauth['loginpage']}");
      exit;
    }
    else{
      $error_msg = "Could not update credentials!";
    }
  }
  else{
    echo "Not logged in!";
    exit();
  }
}

// Version 4.0
?>
<!DOCTYPE html>
<html>
  <head>
    <link href="https://getbootstrap.com/docs/4.0/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="../assets/css/style.css" rel="stylesheet" type="text/css">
    <link href="../assets/css/fontawesome-free-5.14.0-web/css/all.min.css" rel="stylesheet" type="text/css">
    <title>PhoenixRP - NHS Interpol</title>
    <link rel="icon" href="../assets/imgs/favico.ico">
  </head>
  <body>
    <div class="centerDiv">
      <?php
      echo "<h3 style='color:#cc4343;'>$error_msg</h3>";
      if(!isset($_SESSION['steamid'])) {
        echo '<h3 style="color: white;">Sign in through steam to continue</h3>';
      }
      else{
        echo '<h3 style="color: white;">Sign out</h3>';
      }
      ?>
      <br>
      <?php
      if(!isset($_SESSION['steamid'])) {echo "<a href='?login'><img src='https://steamcommunity-a.akamaihd.net/public/images/signinthroughsteam/sits_02.png'></a>";}
      else {echo "<form method='get'><button name='logout' type='submit'>Logout</button></form>";}
      ?>
    </div>
  </body>
</html>