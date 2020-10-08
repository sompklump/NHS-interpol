<?php
require("../php/msql.php");
require("../php/interpol_functions.php");

// Don't show private information about the page
error_reporting(0);
ini_set('display_errors', 0);

$error = false;
$details = [];

if(isset($_GET["key"])){
  $apiKey = $_GET["key"];
  // Check if the api comes from the db
  if(isset($_GET['password'])){
    if($apiKey == "all"){
      $pw = $_GET['password'];
      $sql = "SELECT * FROM api WHERE name = '$apiKey' AND password = '$pw'";
      $result = mysqli_query($conn, $sql);
      if(mysqli_num_rows($result) > 0) {
        try{
          $_sql = "SELECT * FROM members ORDER BY rank DESC";
          $_result = mysqli_query($conn, $_sql);
          if(mysqli_num_rows($_result) > 0) {
            while($_row = mysqli_fetch_array($_result)){
              $intFuncs = new IntFunctions;
              $status = $intFuncs->GetActivity($_row['status'], $_row['lastLogin'], $_row['joinDate']);
              array_push($details, "{$_row['name']}--{$_row['steamid']}--{$_row['forumid']}--{$_row['rank']}--{$_row['cat']}--{$_row['mas']}--{$_row['rtd']}--{$_row['mfo']}--{$_row['dept']}--{$status[0]}");
            }
          }
          else{
            $error = "Error, could not get member from db";
          }
        }
        catch(Exception $e){
          $error = $e-errorMessage();
        }
      }
      else{
        $error = "Error in db lookup";
      }
    }
    else{
      try{
        $pw = $_GET['password'];
        $sql = "SELECT * FROM api WHERE name = '$apiKey' AND password = '$pw'";
        $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0) {
          try{
            $_sql = "SELECT * FROM members WHERE $apiKey = '{$_GET[$apiKey]}'";
            $_result = mysqli_query($conn, $_sql);
            if(mysqli_num_rows($_result) > 0) {
              while($_row = mysqli_fetch_array($_result)){
                $dateTime = new DateTime($_row['joinDate']);
                $joinDate = $dateTime->format("d/m/Y");
                array_push($details, "{$_row['name']}--{$_row['steamid']}--{$_row['forumid']}--$joinDate");
              }
            }
            else{
              $error = "Error, could not get member from db";
            }
          }
          catch(Exception $e){
            $error = $e-errorMessage();
          }
        }
        else{
          $error = "Error in db lookup";
        }
      }
      catch (Exception $e){
        $error = $e->errorMessage();
      }
    }
  }
}

if(isset($_GET['div'])){
  $div = $_GET['div'];
  if($div == "rtd"){
    $sql = "SELECT * FROM members WHERE rtd >= '1' ORDER BY rtd DESC";
    $result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_array($result)){
        array_push($details, "{$row['name']}--{$row['steamid']}--{$row["rtd"]}");
      }
    }
  }
  elseif($div == "masfi"){
    $sql = "SELECT * FROM members WHERE mas >= '1' ORDER BY mas DESC";
    $result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_array($result)){
        array_push($details, "{$row['name']}--{$row['steamid']}--{$row['mas']}");
      }
    }
  }
  elseif($div == "mascat"){
    $sql = "SELECT * FROM members WHERE cat >= '1' ORDER BY cat DESC";
    $result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($result) > 0) {
      while($row = mysqli_fetch_array($result)){
        array_push($details, "{$row['name']}--{$row['steamid']}--{$row['cat']}");
      }
    }
  }
  else{
    $error = "Can't find '$div'";
  }
}
$obj = (object)[
  "error" => $error,
  "members" => $details
];
echo json_encode($obj);
?>