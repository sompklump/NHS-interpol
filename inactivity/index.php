<?php
session_start();
require("../php/msql.php");
require("../php/settings.php");
require("../php/interpol_functions.php");
?>
<!DOCTYPE html>
<html>
  <head>
    <link href="../assets/css/fontawesome-free-5.14.0-web/css/all.min.css" rel="stylesheet" type="text/css">
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <link href="https://getbootstrap.com/docs/4.0/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    
    <link href="<?= "../assets/css/style.css?" . filemtime("../assets/css/style.css") ?>" rel="stylesheet" type="text/css">
    <link href="<?= "../assets/css/ranks.css?" . filemtime("../assets/css/ranks.css") ?>" rel="stylesheet" type="text/css">
    <title>PhoenixRP - NHS Interpol</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../assets/imgs/favico.ico">
  </head>
  <body>
    <?php
    $intFuncs = new IntFunctions;
    $sql = "SELECT * FROM members ORDER BY rank DESC";
    $result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_array($result)){
        $lastLogin = date('d/m/Y H:i:s', strtotime($row['lastLogin']));
        $joinDate = date('d/m/Y H:i:s', strtotime($row['joinDate']));
        $activity = $intFuncs->GetActivity($row['status'], $row['lastLogin'], $row['joinDate']);
        if($activity[1] == 0){
          echo "{$row['name']} - {$activity[1]}<br>";
        }
      }
    }
    ?>
  </body>
</html>