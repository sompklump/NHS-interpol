<?php
session_start();
require("php/msql.php");
require("php/settings.php");
require("php/interpol_functions.php");

$user_rank = null;
$user_isComBrd = false;

$editUser_name = null;
$editUser_steamid = null;
$editUser_promoDate = null;
$editUser_forumid = null;
$editUser_forumrank = null;
$editUser_dept = null;
$editUser_rank = 0;
$editUser_notes = null;
$editUser_wps = null;
$editUser_status = null;
$editUser_catrank = 0;
$editUser_masrank = 0;
$editUser_rtdrank = 0;

$InterpolFunctions = new IntFunctions;

$error_msg = null;

if(isset($_SESSION['steamid'])){
  $sql = "SELECT * FROM members WHERE steamid = '{$_SESSION['steamid']}'";
  $result = mysqli_query($conn, $sql);
  $time = null;
  if(mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_array($result)){
      $user_rank = $row['rank'];
      $user_isComBrd = $row['com_board'];
    }
  }
  if($user_isComBrd){
    $user_rank = 8;
  }
}

if(isset($_GET['edit'])){
  $sql = "SELECT * FROM members WHERE id = '{$_GET['id']}'";
  $result = mysqli_query($conn, $sql);
  if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_array($result)){
      $editUser_name = $row['name'];
      $editUser_steamid = $row['steamid'];
      $editUser_forumid = $row['forumid'];
      $editUser_forumrank = $row['forumRank'];
      $editUser_promoDate = $row['promoDate'];
      $editUser_dept = $row['dept'];
      $editUser_rank = $row['rank'];
      $editUser_wps = $row['warnings'];
      $editUser_status = $row['status'];
      $editUser_notes = $row['notes'];
      $editUser_catrank = $row['cat'];
      $editUser_masrank = $row['mas'];
      $editUser_rtdrank = $row['rtd'];
    }
  }
}

$logged_in = false;
$edit_priv = "noselect";
if(isset($_SESSION['steamid'])) {
  $logged_in = true;
  $edit_priv = null;
}
else{
  echo "<script>location.replace('login');</script>";
}

function FormatLinks($text){
  $text = html_entity_decode($text);
  $arr = explode(" ", $text);
  $out = null;
  foreach($arr as $s){
    if(strpos($s, "https://") !== false || strpos($s, "http://") !== false || strpos($s, "www.") !== false){
      $link = preg_replace('@[^\p{L}\p{Z}\p{N}\p{M}.:\/]@', '', $s);
      $s = "<a target='_blank' href='$link'>$s</a>";
    }
    $out = $out." ".$s;
  }
  return $out;
}

function EditPlayerButton($uid, $playerRank){
  require("php/msql.php");
  // Get logged in user's rank
  $userRank = null;
  $isComBrd = false;
  $sql = "SELECT * FROM members WHERE steamid = '{$_SESSION['steamid']}'";
  $result = mysqli_query($conn, $sql);
  if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_array($result)){
      $userRank = $row['rank'];
      $isComBrd = $row['com_board'];
    }
  }
  if($userRank >= 7 || $isComBrd){
    if($userRank > $playerRank || $userRank >= 8 || $isComBrd) {
      echo "<td><a href='?edit&id=$uid' class='btn btn-light' role='button'>Edit</a></td>";
    }
    else{
      echo "<td><label>None</label></form></td>";
    }
  }
  else{
    echo "<td><label>None</label></form></td>";
  }
}

// Create a new player row
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['steamid'])) {
  // Player edit functoins
  if(isset($_POST['editSaveBtn'])){
    if($user_rank > 7){
      $name = $_POST['playerName'];
      $steamid = $_POST['playerSteamid'];
      $forumid = $_POST['playerForumid'];
      $forumRank = $_POST['forumRank'];
      $dept = $_POST['playerDept'];
      $rank = $_POST['rankSel'];
      $cat = $_POST['catSel'];
      $rtd = $_POST['rtdSel'];
      $mas = $_POST['mfiSel'];
      $notes = $_POST['playerNotes'];
      $wps = $_POST['wpsSel'];
      $status = $_POST['playerStatus'];
      
      $isAllowed = true;
      if($rank >= 8 || $editUser_rank >= 8){
        if($user_isComBrd == false){
          $isAllowed = false;
        }
      }
      if(!is_numeric($wps)){
        $error_msg = "Some value(s) was not an integer!";
      }
      else{
        // Check if any ranks has changed before making an API request
        if($editUser_rank != $rank){
          if($rank > $editUser_rank){
            $dateTime = new DateTime();
            $editUser_promoDate = $dateTime->format("Y-m-d");
          }
          else{
            $dateTime = new DateTime($editUser_promoDate);
            $editUser_promoDate = $dateTime->format("Y-m-d");
          }
          $outcome = $InterpolFunctions->SetRank($steamid, $rank);
          if($outcome[0] == true){
            $error_msg = "Something went wrong when updating player $name's rank!";
          }
        }
        if($editUser_catrank != $cat){
          $outcome = $InterpolFunctions->SetMasRank($steamid, $cat);
          if($outcome[0] == true){
            $error_msg = "Something went wrong when updating player $name's MAS rank!";
          }
        }
        
        if($isAllowed){
          $sql = "UPDATE members SET name='". mysqli_real_escape_string($conn, $name) ."',steamid='$steamid',promoDate='". mysqli_real_escape_string($conn, $editUser_promoDate) ."',rank='$rank',cat='$cat',rtd='$rtd',mas='$mas',forumid='$forumid',mfo='0',warnings='$wps',notes='". mysqli_real_escape_string($conn, $notes) ."',dept='$dept',forumRank='$forumRank',status='$status' WHERE id = '{$_GET['id']}'";
          $result = mysqli_query($conn, $sql);
          if(!mysqli_query($conn, $sql)) {
            $error_msg = "An error occurd while updating player details!";
          }
        }
      }
      
      // Get settings for closing the modal after saving
      if(isset($_COOKIE['setting_closeEditPlayerModal'])){
        if($_COOKIE['setting_closeEditPlayerModal']){
          echo "<script>location.replace('./');</script>";
        }
        else{
          echo "<script>location.replace('./?edit&id={$_GET['id']}');</script>";
        }
      }
      else{
        echo "<script>location.replace('./?edit&id={$_GET['id']}');</script>";
      }
    }
  }
  
  if(isset($_POST['editCloseBtn'])){
    echo "<script>location.replace('./');</script>";
  }
  
  if(isset($_POST['insert_player'])){
    $name = $_POST['newP_name'];
    $steamid = $_POST['newP_steamid'];
    $forumid = $_POST['newP_forumid'];
    
    if(!empty($name) && !empty($steamid) && !empty($forumid)){
      $sql = "INSERT INTO members (name, steamid, forumid) VALUES ('". mysqli_real_escape_string($conn, $name) ."', '". mysqli_real_escape_string($conn, $steamid) ."', '". mysqli_real_escape_string($conn, $forumid) ."')";
      if(mysqli_query($conn, $sql)) {
        echo "<script>location.replace('./');</script>";
      }
      else{
        echo "Something went wrong when making a new player row!";
      }
    }
  }
}

if(isset($_SESSION['steamid'])) {
  // Get the last time lastLogin ran from the database
  $sql = "SELECT * FROM settings WHERE skey = 'ran_lastSeen'";
  $result = mysqli_query($conn, $sql);
  $time = null;
  if(mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_array($result)){
      $dateTime = new DateTime($row['svalue']);
      $time = $dateTime->format("Y-m-d H:i:s");
    }
  }

  // Check if it has been 4 hours since last last login check
  if(strtotime(date("Y-m-d H:i:s")) > strtotime($time) + (60*60)*4){
    echo "<script>alert('Please wait!\r\nGathering last login from every player on the interpol.');</script>";
    // Set functions variable

    // Get all members of NHS
    $sql = "SELECT steamid, name FROM members";
    $result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($result) > 0){
      while($row = mysqli_fetch_array($result)){
        $steamid = $row['steamid'];

        try {
          // Get and format the date
          $lastLoginDate_raw = $InterpolFunctions->LastSeen($steamid);
          $lastLoginDate_reg = str_replace("/", "-", $lastLoginDate_raw);
          $dateTime = new DateTime($lastLoginDate_reg);
          $lastLoginDate_format = $dateTime->format("Y-m-d H:i:s");

          // Update their lastlogin date
          $sql = "UPDATE members SET lastLogin = '$lastLoginDate_format' WHERE steamid = '$steamid'";
          if(!mysqli_query($conn, $sql)) {
            echo "Error while updating last login!";
            break;
          }
        }
        catch (Exception $e) {
          echo "<p>{$e->getMessage()}</p>";
        }
      }
      $sql = "UPDATE settings SET svalue = '".date("Y-m-d H:i:s")."' WHERE skey = 'ran_lastSeen'";
      if(!mysqli_query($conn, $sql)) {
        echo "Error while updating latLogin date in settings!";
      }
      echo "<script>location.replace('./');</script>";
    }
  }
}
?>
<!DOCTYPE html>
<html>
  <head>
    <link href="assets/css/fontawesome-free-5.14.0-web/css/all.min.css" rel="stylesheet" type="text/css">
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <link href="https://getbootstrap.com/docs/4.0/dist/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    
    <link href="assets/css/style.css?<?= filemtime("assets/css/style.css") ?>" rel="stylesheet" type="text/css">
    <link href="assets/css/ranks.css?<?= filemtime("assets/css/ranks.css") ?>" rel="stylesheet" type="text/css">
    <title>PhoenixRP - NHS Interpol</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="assets/imgs/favico.ico">
  </head>
  <body>
    <div class="modal" id="editPlayerModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" style="width:98%;">
            
            <!-- Modal Header -->
            <div class="modal-header">
              <h4 class="modal-title"><?= "{$ranks[$editUser_rank]}, $editUser_name" ?></h4>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            
            <!-- Modal body -->
            <div class="modal-body">
              <div class="container-fluid">
                <div class="row">
                  <div class="col-sm">
                    <label for="playerName">Player Name</label>
                    <input id="playerName" name="playerName" value="<?= $editUser_name ?>" type="text" placeholder="Name" class="form-control">
                  </div>
                </div>
                <div class="row">
                  <div class="col-sm">
                    <label for="playerForumid">Forum ID</label>
                    <input id="playerForumid" name="playerForumid" value="<?= $editUser_forumid ?>" type="text" placeholder="Forum ID" class="form-control">
                  </div>
                  <div class="col-sm">
                    <label for="playerSteamid">Steam ID</label>
                    <input id="playerSteamid" name="playerSteamid" value="<?= $editUser_steamid ?>" type="text" placeholder="Steam ID" class="form-control">
                  </div>
                </div>
                <div class="row">
                  <div class="col-sm">
                    <label for="rankSel">NHS Rank</label>
                    <select id="rankSel" name="rankSel" class="form-control">
                      <?php
                      if($user_rank >= 8) {
                        for($i = 0; $i <= $user_rank; $i++){
                          if($i == $editUser_rank){
                            echo "<option selected value='$i'>{$ranks[$i]}</option>\r\n";
                          }
                          else{
                            echo "<option value='$i'>{$ranks[$i]}</option>\r\n";
                          }
                        }
                      }
                      else{
                        for($i = 0; $i < $user_rank; $i++){
                          if($i == $editUser_rank){
                            echo "<option selected value='$i'>{$ranks[$i]}</option>\r\n";
                          }
                          else{
                            echo "<option value='$i'>{$ranks[$i]}</option>\r\n";
                          }
                        }
                      }
                      ?>
                    </select>
                  </div>
                  <div class="col-sm">
                    <label for="catSel">CAT Level</label>
                    <select id="catSel" name="catSel" class="form-control">
                      <?php
                      for($i = 0; $i < count($cat_ranks); $i++){
                        if($i == $editUser_catrank){
                          echo "<option selected value='$i'>{$cat_ranks[$i]}</option>\r\n";
                        }
                        else{
                          echo "<option value='$i'>{$cat_ranks[$i]}</option>\r\n";
                        }
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-sm">
                    <label for="rtdSel">RTD Rank</label>
                    <select id="rtdSel" name="rtdSel" class="form-control">
                      <?php
                      for($i = 0; $i < count($rtd_ranks); $i++){
                        if($i == $editUser_rtdrank){
                          echo "<option selected value='$i'>{$rtd_ranks[$i]}</option>\r\n";
                        }
                        else{
                          echo "<option value='$i'>{$rtd_ranks[$i]}</option>\r\n";
                        }
                      }
                      ?>
                    </select>
                  </div>
                  <div class="col-sm">
                    <label for="mfiSel">FI Rank</label>
                    <select id="mfiSel" name="mfiSel" class="form-control">
                      <?php
                      for($i = 0; $i < count($mas_ranks); $i++){
                        if($i == $editUser_masrank){
                          echo "<option selected value='$i'>{$mas_ranks[$i]}</option>\r\n";
                        }
                        else{
                          echo "<option value='$i'>{$mas_ranks[$i]}</option>\r\n";
                        }
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-lg-8">
                    <label for="playerNotes">Notes</label>
                    <textarea id="playerNotes" rows="1" style="max-height:135px;min-height:15px;" name="playerNotes" class="form-control"><?= $editUser_notes ?></textarea>
                  </div>
                  <div class="col-sm-4">
                    <label for="wpsSel">Warning Points</label>
                    <select id="wpsSel" name="wpsSel" class="form-control">
                      <?php
                      for($i = 0; $i <= 3; $i++){
                        if($i == $editUser_wps){
                          echo "<option selected value='$i'>$i</option>\r\n";
                        }
                        else{
                          echo "<option value='$i'>$i</option>\r\n";
                        }
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-sm">
                    <label for="forumRank">Forum Rank</label>
                    <select id="forumRank" name="forumRank" class="form-control">
                      <?php
                      for($i = 0; $i < count($forum_ranks); $i++){
                        if($i == $editUser_forumrank){
                          echo "<option selected value='$i'>{$forum_ranks[$i]}</option>\r\n";
                        }
                        else{
                          echo "<option value='$i'>{$forum_ranks[$i]}</option>\r\n";
                        }
                      }
                      ?>
                    </select>
                  </div>
                  <div class="col-sm">
                    <label for="playerDept">Department</label>
                    <select id="playerDept" name="playerDept" class="form-control">
                      <?php
                      for($i = 0; $i < count($departments); $i++){
                        if($i == $editUser_dept){
                          echo "<option selected value='$i'>{$departments[$i]}</option>\r\n";
                        }
                        else{
                          echo "<option value='$i'>{$departments[$i]}</option>\r\n";
                        }
                      }
                      ?>
                    </select>
                  </div>
                  <div class="col-sm">
                    <label for="playerStatus">Status</label>
                    <select id="playerStatus" name="playerStatus" class="form-control">
                      <?php
                      for($i = 0; $i < count($player_statuses); $i++){
                        if($i == $editUser_status){
                          echo "<option selected value='$i'>{$player_statuses[$i]}</option>\r\n";
                        }
                        else{
                          echo "<option value='$i'>{$player_statuses[$i]}</option>\r\n";
                        }
                      }
                      ?>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
              <button type="submit" id="editCloseBtn" name="editCloseBtn" class="btn btn-danger">Discard</button>
              <button type="submit" id="editSaveBtn" name="editSaveBtn" class="btn btn-success">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="modal" id="settingsModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" style="width:98%;">
            <!-- Modal Header -->
            <div class="modal-header">
              <h4 class="modal-title">Settings</h4>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            
            <!-- Modal body -->
            <div class="modal-body">
              <div class="container-fluid">
                <div class="row">
                  <div class="col-sm">
                    <div class="form-group form-check">
                      <input type="checkbox" class="form-check-input" id="set_closeEPM" name="set_closeEPM">
                      <label class="form-check-label" for="set_closeEPM">Close edit modal after saving</label>
                    </div>
                  </div>
                </div>
              </div>
              <button class="btn btn-primary" type="submit">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php
    // Open edit modal if url var 'edit' is set
    if(isset($_GET['edit'])){
      if($user_rank > 7 || $user_isComBrd){
        echo "<script type='text/javascript'>$('#editPlayerModal').modal();</script>\r\n";
      }
    }
    ?>
    <div class="constructor">
      <div class="pos-f-t">
        <div class="collapse" id="navbarToggleExternalContent">
          <div class="bg-dark p-4">
            <button id="openSettings_btn" class="btn btn-primary">Settings</button>
            <br>
            <p></p>
            <?php
            if($user_rank >= "7") {
              echo "<form method='post'>
              <input autofocus class='form-control' required name='newP_name' id='newP_name' placeholder='Name'>
              <input class='form-control' required name='newP_steamid' id='newP_steamid' placeholder='Steam ID'>
              <input class='form-control' required name='newP_forumid' id='newP_forumid' placeholder='Forum ID'>
              <button class='btn btn-primary' type='submit' name='insert_player'>Add Player</button>
              </form>";
            }
            ?>
          </div>
        </div>
        <nav class="navbar navbar-dark bg-dark">
          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarToggleExternalContent" aria-controls="navbarToggleExternalContent" aria-expanded="false" aria-label="Toggle navigation">
             <span class="navbar-toggler-icon"></span>
          </button>
          <nav class="navbar-expand-sm">
            <ul class="navbar-nav">
              <li class="nav-item">
                <a class="nav-link active" href="./">Interpol</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="<?php if($user_rank >= 8){echo "inactivity";}else{echo "#";} ?>">Inactivty</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#">Removals</a>
              </li>
            </ul>
          </nav>
          <div>
            <?php
            if(!isset($_SESSION['steamid'])){
              echo '<a href="login/">Login <i class="fas fa-sign-in-alt"></i></a>';
            }
            else{
              echo '<a href="login/?logout">Logout <i class="fas fa-sign-out-alt"></i></a>';
            }
            ?>
          </div>
        </nav>
      </div>
      <br>
      <div class="<?= $edit_priv ?>">
        <?php
        if(!empty($error_msg)){
          echo "<div style='width:45%;text-align:center;display: table;margin: 0 auto;'>
            <div class='alert alert-danger' role='alert'>$error_msg</div>
          </div>";
        }
        ?>
        <div class="category">
          <div class='alert alert-dark header' role='alert'>
            <center><strong><i>NHS Command</i></strong></center>
          </div>
          <div class="table-cont">
            <table class="table table-bordered table-striped table-dark" cellspacing="0" cellpadding="0">
              <tr>
                <th>Action</th>
                <th>Forum Rank</th>
                <th>Name</th>
                <th>Rank</th>
                <th>Mas</th>
                <th>RTD Rank</th>
                <th>FI Rank</th>
                <th>MFO Rank</th>
                <th>Join Date</th>
                <th>Forum ID</th>
                <th>Player ID</th>
                <th>Last Promotion</th>
                <th>Warning Points</th>
                <th>Notes</th>
                <th>Last Login</th>
                <th>Status</th>
              </tr>
              <?php
              // Get command members
              $sql = "SELECT * FROM members WHERE dept = '4' ORDER BY rank DESC";
              $result = mysqli_query($conn, $sql);
              if(mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_array($result)){
                  $forumRank = $forum_ranks[$row['forumRank']];
                  $name = $row['name'];
                  $rank = $row['rank'];
                  $cat = $cat_ranks[$row['cat']];
                  $rtd = $rtd_ranks[$row['rtd']];
                  $mas = $mas_ranks[$row['mas']];
                  $joinDate = date('d/m/Y', strtotime($row['joinDate']));
                  $forumid = $row['forumid'];
                  $steamId = $row['steamid'];
                  $lastProm = date('d/m/Y', strtotime($row['promoDate']));
                  $warnings = $row['warnings'];
                  $notes = $row['notes'];

                  // Check if last login is not set to null or "1970"
                  $lastLogin = date('d/m/Y H:i:s', strtotime($row['lastLogin']));
                  if(strpos($lastLogin, "1970") !== false){
                    $lastLogin = "None";
                  }

                  // Make rtd and fi columns bold if their value is more or equals to 1
                  if($row['rtd'] >= 1){
                    $rtd = "<b>".$rtd."</b>";
                  }
                  if($row['mas'] >= 1){
                    $mas = "<b>".$mas."</b>";
                  }

                  // 'statusCode' is declaring the status interger for coloring
                  $activity = $InterpolFunctions->GetActivity($row['status'], $row['lastLogin'], $row['joinDate']);
                  ?>
                  <tr>
                  <?php
                  $notes = FormatLinks($notes);
                  
                  echo EditPlayerButton($row['id'], $row['rank']);
                  echo "<td class='{$forum_rank_colors[$row['forumRank']]}'>$forumRank</td>";
                  echo "<td>$name</td>";
                  echo "<td class='{$rank_colors[$rank]}'>{$ranks[$rank]}</td>";
                  echo "<td>$cat</td>";
                  echo "<td class='{$department_colors[$row['rtd']]}'>$rtd</td>";
                  echo "<td class='{$department_colors[$row['mas']]}'>$mas</td>";
                  echo "<td></td>";
                  echo "<td>$joinDate</td>";
                  echo "<td>$forumid</td>";
                  echo "<td>$steamId</td>";
                  echo "<td>$lastProm</td>";
                  echo "<td style='background-color: {$warning_colors[$warnings]}'>$warnings</td>";
                  echo "<td class='td-notes'>$notes</td>";
                  echo "<td>$lastLogin</td>";
                  echo "<td class='{$player_status_colors[$activity[1]]}'>{$activity[0]}</td></tr>";
                }
              }
              ?>
            </table>
          </div>
        </div>
        <div>
          <div class='alert alert-dark header' role='alert'>
            <center><strong><i>Qualified</i></strong></center>
          </div>
            <table class="table table-bordered table-striped table-dark">
              <tr>
                <th>Action</th>
                <th>Forum Rank</th>
                <th>Name</th>
                <th>Rank</th>
                <th>Mas</th>
                <th>RTD Rank</th>
                <th>FI Rank</th>
                <th>MFO Rank</th>
                <th>Join Date</th>
                <th>Forum ID</th>
                <th>Player ID</th>
                <th>Last Promotion</th>
                <th>Warning Points</th>
                <th>Notes</th>
                <th>Last Login</th>
                <th>Status</th>
              </tr>
              <?php
              // Get command members
              $sql = "SELECT * FROM members WHERE dept = '3' ORDER BY rank DESC";
              $result = mysqli_query($conn, $sql);
              if(mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_array($result)){
                  $forumRank = $forum_ranks[$row['forumRank']];
                  $name = $row['name'];
                  $rank = $row['rank'];
                  $cat = $cat_ranks[$row['cat']];
                  $rtd = $rtd_ranks[$row['rtd']];
                  $mas = $mas_ranks[$row['mas']];
                  $joinDate = date('d/m/Y', strtotime($row['joinDate']));
                  $forumid = $row['forumid'];
                  $steamId = $row['steamid'];
                  $lastProm = date('d/m/Y', strtotime($row['promoDate']));
                  $warnings = $row['warnings'];
                  $notes = $row['notes'];

                  // Check if last login is not set to null or "1970"
                  $lastLogin = date('d/m/Y H:i:s', strtotime($row['lastLogin']));
                  if(strpos($lastLogin, "1970") !== false){
                    $lastLogin = "None";
                  }

                  // Make rtd and fi columns bold if their value is more or equals to 1
                  if($row['rtd'] >= 1){
                    $rtd = "<b>".$rtd."</b>";
                  }
                  if($row['mas'] >= 1){
                    $mas = "<b>".$mas."</b>";
                  }

                  // 'statusCode' is declaring the status interger for coloring
                  $activity = $InterpolFunctions->GetActivity($row['status'], $row['lastLogin'], $row['joinDate']);
                  ?>
                  <tr>
                  <?php
                  $notes = FormatLinks($notes);
                  
                  echo EditPlayerButton($row['id'], $row['rank']);
                  echo "<td class='{$forum_rank_colors[$row['forumRank']]}'>$forumRank</td>";
                  echo "<td>$name</td>";
                  echo "<td class='{$rank_colors[$rank]}'>{$ranks[$rank]}</td>";
                  echo "<td>$cat</td>";
                  echo "<td class='{$department_colors[$row['rtd']]}'>$rtd</td>";
                  echo "<td class='{$department_colors[$row['mas']]}'>$mas</td>";
                  echo "<td></td>";
                  echo "<td>$joinDate</td>";
                  echo "<td>$forumid</td>";
                  echo "<td>$steamId</td>";
                  echo "<td>$lastProm</td>";
                  echo "<td style='background-color: {$warning_colors[$warnings]}'>$warnings</td>";
                  echo "<td class='td-notes'>$notes</td>";
                  echo "<td>$lastLogin</td>";
                  echo "<td class='{$player_status_colors[$activity[1]]}'>{$activity[0]}</td></tr>";
                }
              }
              ?>
          </table>
        </div>
        <div>
          <div class='alert alert-dark header' role='alert'>
            <center><strong><i>Trainee</i></strong></center>
          </div>
            <table class="table table-bordered table-striped table-dark">
              <tr>
                <th>Action</th>
                <th>Forum Rank</th>
                <th>Name</th>
                <th>Rank</th>
                <th>Join Date</th>
                <th>Forum ID</th>
                <th>Player ID</th>
                <th>Last Promotion</th>
                <th>Warning Points</th>
                <th>Notes</th>
                <th>Last Login</th>
                <th>Status</th>
              </tr>
              <?php
              // Get command members
              $sql = "SELECT * FROM members WHERE dept = '2' ORDER BY rank DESC";
              $result = mysqli_query($conn, $sql);
              if(mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_array($result)){
                  $forumRank = $forum_ranks[$row['forumRank']];
                  $name = $row['name'];
                  $rank = $row['rank'];
                  $cat = $cat_ranks[$row['cat']];
                  $rtd = $rtd_ranks[$row['rtd']];
                  $mas = $mas_ranks[$row['mas']];
                  $joinDate = date('d/m/Y', strtotime($row['joinDate']));
                  $forumid = $row['forumid'];
                  $steamId = $row['steamid'];
                  $lastProm = date('d/m/Y', strtotime($row['promoDate']));
                  $warnings = $row['warnings'];
                  $notes = $row['notes'];

                  // Check if last login is not set to null or "1970"
                  $lastLogin = date('d/m/Y H:i:s', strtotime($row['lastLogin']));
                  if(strpos($lastLogin, "1970") !== false){
                    $lastLogin = "None";
                  }

                  // Make rtd and fi columns bold if their value is more or equals to 1
                  if($row['rtd'] >= 1){
                    $rtd = "<b>".$rtd."</b>";
                  }
                  if($row['mas'] >= 1){
                    $mas = "<b>".$mas."</b>";
                  }

                  // 'statusCode' is declaring the status interger for coloring
                  $activity = $InterpolFunctions->GetActivity($row['status'], $row['lastLogin'], $row['joinDate']);
                  ?>
                  <tr>
                  <?php
                  $notes = FormatLinks($notes);
                  
                  echo EditPlayerButton($row['id'], $row['rank']);
                  echo "<td class='{$forum_rank_colors[$row['forumRank']]}'>$forumRank</td>";
                  echo "<td>$name</td>";
                  echo "<td class='{$rank_colors[$rank]}'>{$ranks[$rank]}</td>";
                  echo "<td>$joinDate</td>";
                  echo "<td>$forumid</td>";
                  echo "<td>$steamId</td>";
                  echo "<td>$lastProm</td>";
                  echo "<td style='background-color: {$warning_colors[$warnings]}'>$warnings</td>";
                  echo "<td class='td-notes'>$notes</td>";
                  echo "<td>$lastLogin</td>";
                  echo "<td class='{$player_status_colors[$activity[1]]}'>{$activity[0]}</td></tr>";
                }
              }
              ?>
          </table>
        </div>
        <div>
          <div class='alert alert-dark header' role='alert'>
            <center><strong><i>Reserve</i></strong></center>
          </div>
            <table class="table table-bordered table-striped table-dark">
              <tr>
                <th>Action</th>
                <th>Forum Rank</th>
                <th>Name</th>
                <th>Rank</th>
                <th>Mas</th>
                <th>Join Date</th>
                <th>Forum ID</th>
                <th>Player ID</th>
                <th>Last Promotion</th>
                <th>Warning Points</th>
                <th>Notes</th>
                <th>Last Login</th>
                <th>Status</th>
              </tr>
              <?php
              // Get command members
              $sql = "SELECT * FROM members WHERE dept = '1' ORDER BY rank DESC";
              $result = mysqli_query($conn, $sql);
              if(mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_array($result)){
                  $forumRank = $forum_ranks[$row['forumRank']];
                  $name = $row['name'];
                  $rank = $row['rank'];
                  $cat = $cat_ranks[$row['cat']];
                  $rtd = $rtd_ranks[$row['rtd']];
                  $mas = $mas_ranks[$row['mas']];
                  $joinDate = date('d/m/Y', strtotime($row['joinDate']));
                  $forumid = $row['forumid'];
                  $steamId = $row['steamid'];
                  $lastProm = date('d/m/Y', strtotime($row['promoDate']));
                  $warnings = $row['warnings'];
                  $notes = $row['notes'];

                  // Check if last login is not set to null or "1970"
                  $lastLogin = date('d/m/Y H:i:s', strtotime($row['lastLogin']));
                  if(strpos($lastLogin, "1970") !== false){
                    $lastLogin = "None";
                  }

                  // Make rtd and fi columns bold if their value is more or equals to 1
                  if($row['rtd'] >= 1){
                    $rtd = "<b>".$rtd."</b>";
                  }
                  if($row['mas'] >= 1){
                    $mas = "<b>".$mas."</b>";
                  }

                  // 'statusCode' is declaring the status interger for coloring
                  $activity = $InterpolFunctions->GetActivity($row['status'], $row['lastLogin'], $row['joinDate']);
                  ?>
                  <tr>
                  <?php
                  $notes = FormatLinks($notes);
                  
                  echo EditPlayerButton($row['id'], $row['rank']);
                  echo "<td class='{$forum_rank_colors[$row['forumRank']]}'>$forumRank</td>";
                  echo "<td>$name</td>";
                  echo "<td class='{$rank_colors[$rank]}'>{$ranks[$rank]}</td>";
                  echo "<td>$cat</td>";
                  echo "<td>$joinDate</td>";
                  echo "<td>$forumid</td>";
                  echo "<td>$steamId</td>";
                  echo "<td>$lastProm</td>";
                  echo "<td style='background-color: {$warning_colors[$warnings]}'>$warnings</td>";
                  echo "<td class='td-notes'>$notes</td>";
                  echo "<td>$lastLogin</td>";
                  echo "<td class='{$player_status_colors[$activity[1]]}'>{$activity[0]}</td></tr>";
                }
              }
              ?>
          </table>
        </div>
      </div>
    </div>
    <script type='text/javascript'>
      document.getElementById("openSettings_btn").onclick = function(){
        $('#settingsModal').modal();
      }
    </script>
  </body>
</html>