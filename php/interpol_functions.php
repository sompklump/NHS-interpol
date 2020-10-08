<?php
class IntFunctions {
  function LastSeen($steamid) {
    $json = file_get_contents("https://phoenixrp.co.uk/wl.php?faction=medic&lastseen=$steamid");
    $obj = json_decode($json, true);
    return $obj["lastMedicOnline"];
  }
  
  function GetActivity($statusCode, $lastLogin, $joinDate){
    $status = null;
    if($statusCode == 1){
      $status = "Absent";
    }
    elseif($statusCode == 2){
      $status = "Semi Active";
    }
    elseif($statusCode == 3){
      $status = "Active (Static)";
    }
    else{
      if(strtotime($joinDate) > strtotime("-10 days")){
        $status = "Active";
        $statusCode = 3;
      }
      else{
        if(strtotime($lastLogin) < strtotime("-10 days")){
          $status = "Inactive";
          $statusCode = 0;
        }
        else{
          $status = "Active";
          $statusCode = 3;
        }
      }
    }
    
    return [$status, $statusCode];
  }
  
  function SetRank($steamid, $rank){
    $json = file_get_contents("https://phoenixrp.co.uk/wl.php?steamid=$steamid&rank=$rank&password=ZDEu25EiWi9WNqsA9ncHCn6lDvGLp2mlYLJCo725&faction=medic");
    $obj = json_decode($json, true);
    $errorCode = $obj["error"];
    $newRank = $obj["newrank"];
    return [$errorCode, $newRank];
  }
  function SetMasRank($steamid, $rank){
    $json = file_get_contents("https://phoenixrp.co.uk/wl.php?steamid=$steamid&rank=$rank&password=1Kk8mHQED287UwBKPq54jFYOZ8d2EEkxDy923pZ&faction=mas");
    $obj = json_decode($json, true);
    $errorCode = $obj["error"];
    $newRank = $obj["newrank"];
    return [$errorCode, $newRank];
  }
}
?>