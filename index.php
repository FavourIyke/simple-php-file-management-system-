<?php
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$action = filter_input(INPUT_GET, "action");

if (!isset($action) || is_null($action) || $action == "") {
    $action = "list";
    $folder = filter_input(INPUT_GET, "folder");
    if (!isset($folder) || is_null($folder) || $folder == "") {
        $folder = ".";
    }
}

switch ($action) {
    case "expandFolder":
        $path = filter_input(INPUT_GET, "path");
        $level = filter_input(INPUT_GET, "level");
        echo expandFolder($path, $level+1);
        exit();
        break;

        case "Signout":
              session_destroy();
              unset($_SESSION['username']);
              exit();
              break;
}

//action and folder variables are set. Now print the header stuff
?>

<!DOCTYPE HTML>

<html>
<head>
<meta http-equiv="Content-Language" content="cs">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<META NAME="" CONTENT="">
<title>File management system PHP</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">
<script src="http://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>

</head>
<body>
  <?php
// Login and registration
session_start();

//File upload
if(isset($_POST["submit_file"])) {
  $target_dir = "";
  $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
  $uploadOk = 1;
  $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);


// Check if file already exists
if (file_exists($target_file)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
}
// Check file size
if ($_FILES["fileToUpload"]["size"] > 500000) {
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
}

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
} else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}
header("location: index.php");
}

if(isset($_REQUEST["file"])){
    // Get parameters
    $file = urldecode($_REQUEST["file"]); // Decode URL-encoded string

    /* Test whether the file name contains illegal characters
    such as "../" using the regular expression */
    if(1===1){
        $filepath = "images/" . $file;

        // Process download
        if(1===1) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            flush(); // Flush system output buffer
            readfile($filepath);
            die();
        } else {
            http_response_code(404);
	        die();
        }
    } else {
        die("Invalid file name!");
    }
}
// initializing variables
$username = "";
$email    = "";
$errors = array();

// connect to the database
$db = mysqli_connect('localhost', 'root', '', 'fms_db');

if (isset($_GET['logout'])) {
  session_destroy();
  unset($_SESSION['username']);
  header("location: index.php");
}

// REGISTER USER
if (isset($_POST['reg_user'])) {
  // receive all input values from the form
  $username = mysqli_real_escape_string($db, $_POST['username']);
  $email = mysqli_real_escape_string($db, $_POST['email']);
  $password_1 = mysqli_real_escape_string($db, $_POST['password_1']);
  $password_2 = mysqli_real_escape_string($db, $_POST['password_2']);

  // form validation: ensure that the form is correctly filled ...
  // by adding (array_push()) corresponding error unto $errors array
  if (empty($username)) { array_push($errors, "Username is required"); }
  if (empty($email)) { array_push($errors, "Email is required"); }
  if (empty($password_1)) { array_push($errors, "Password is required"); }
  if ($password_1 != $password_2) {
	array_push($errors, "The two passwords do not match");
  }

  // first check the database to make sure
  // a user does not already exist with the same username and/or email
  $user_check_query = "SELECT * FROM users WHERE username='$username' OR email='$email' LIMIT 1";
  $result = mysqli_query($db, $user_check_query);
  $user = mysqli_fetch_assoc($result);

  if ($user) { // if user exists
    if ($user['username'] === $username) {
      array_push($errors, "Username already exists");
    }

    if ($user['email'] === $email) {
      array_push($errors, "email already exists");
    }
  }

  // Finally, register user if there are no errors in the form
  if (count($errors) == 0) {
  	$password = md5($password_1);//encrypt the password before saving in the database

  	$query = "INSERT INTO users (username, email, password)
  			  VALUES('$username', '$email', '$password')";
  	mysqli_query($db, $query);
  	$_SESSION['username'] = $username;
  	$_SESSION['success'] = "You are now logged in";
  }
}


// LOGIN USER
if (isset($_POST['login_user'])) {
  $username = mysqli_real_escape_string($db, $_POST['username']);
  $password = mysqli_real_escape_string($db, $_POST['password']);

  if (empty($username)) {
  	array_push($errors, "Username is required");
  }
  if (empty($password)) {
  	array_push($errors, "Password is required");
  }

  if (count($errors) == 0) {
  	$password = md5($password);
  	$query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
  	$results = mysqli_query($db, $query);
  	if (mysqli_num_rows($results) == 1) {
  	  $_SESSION['username'] = $username;
  	  $_SESSION['success'] = "You are now logged in";
  	}else {
  		array_push($errors, "Wrong username/password combination");
  	}
  }
}



   ?>
    <script language="Javascript">
    function showPrmDlg(src){
        var path = $(src).parents("TR").find("TD#name").html();
        $("DIV#changePermissionsModal DIV.modal-header H4#heading SPAN").html(path);
        $("DIV#changePermissionsModal").modal("show");
    }

    function showUnpDlg(src){
        var path = $(src).parents("TR").find("TD#name").html();
        $("DIV#unpackModal DIV.modal-header H4#heading SPAN").html(path);
        $("DIV#unpackModal").modal("show");
    }

    function showPckDlg(src){
        var path = $(src).parents("TR").find("TD#name").html();
        $("DIV#packModal DIV.modal-header H4#heading SPAN").html(path);
        $("DIV#packModal").modal("show");
    }

    function showUpload(src){
        var path = $(src).parents("TR").find("TD#name").html();
        $("DIV#uploadModal DIV.modal-header H4#heading SPAN").html(path);
        $("DIV#uploadModal").modal("show");
    }

    function showSignup(src){
        var path = $(src).parents("TR").find("TD#name").html();
        $("DIV#SignUpModal DIV.modal-header H4#heading SPAN").html(path);
        $("DIV#SignUpModal").modal("show");
    }

    function showLogin(src){
        var path = $(src).parents("TR").find("TD#name").html();
        $("DIV#LoginModal DIV.modal-header H4#heading SPAN").html(path);
        $("DIV#LoginModal").modal("show");
    }

    function showMkdDlg(src){
        var path = $(src).parents("TR").find("TD#name").html();
        $("DIV#mkDirModal DIV.modal-header H4#heading SPAN").html(path);
        $("DIV#mkDirModal").modal("show");
    }

    function showDltDlg(src){
        var tr = $(src).parents("TR");
        var parent = tr.attr("data-parent");
        var path = "";
        if(typeof parent != "undefined"){
            path = parent + "/" + tr.find("TD#name").html();
        } else {
            path = tr.find("TD#name").html();
        }
        $("DIV#deleteModal DIV.modal-header H4#heading SPAN").html(path);
        $("DIV#deleteModal").modal("show");
    }

    function deleteDir(){
        var path = $("DIV#deleteModal DIV.modal-header H4#heading SPAN").html();
        $.ajax({
            url: "index.php?action=deleteDir&path="+path,
        }).done(function( data ) {
            $("DIV#showLog PRE").html(data);
            $("DIV#showLog").modal("show");
        });
    }

    function makeDir(){
        var path = $("DIV#mkDirModal DIV.modal-header H4#heading SPAN").html();
        var name = $("DIV#mkDirModal TR#dirPath INPUT[type='text']").val();
        $.ajax({
            url: "index.php?action=makeDir&path="+path+"&name="+name,
        }).done(function( data ) {
            location.reload();
        });
    }

    function folderPermCheck(){
        if($("DIV#changePermissionsModal TR#folderPerm INPUT[type='checkbox']").prop('checked') == true){
            $("DIV#changePermissionsModal TR#folderPerm INPUT[type='text']").prop('disabled', false);
        } else {
            $("DIV#changePermissionsModal TR#folderPerm INPUT[type='text']").prop('disabled', true);
        }
    }

    function filePermCheck(){
        if($("DIV#changePermissionsModal TR#filePerm INPUT[type='checkbox']").prop('checked') == true){
            $("DIV#changePermissionsModal TR#filePerm INPUT[type='text']").prop('disabled', false);
        } else {
            $("DIV#changePermissionsModal TR#filePerm INPUT[type='text']").prop('disabled', true);
        }
    }

    function changePerm(){
        var path = $("DIV#changePermissionsModal DIV.modal-header H4#heading SPAN").html();
        var recursively = $("DIV#changePermissionsModal TR#recursively INPUT[type='checkbox']").prop('checked');
        var filesChange = $("DIV#changePermissionsModal TR#filePerm INPUT[type='checkbox']").prop('checked');
        var foldersChange = $("DIV#changePermissionsModal TR#folderPerm INPUT[type='checkbox']").prop('checked');
        var filesPerm = $("DIV#changePermissionsModal TR#filePerm INPUT[type='text']").val();
        var foldersPerm = $("DIV#changePermissionsModal TR#folderPerm INPUT[type='text']").val();
        $.ajax({
            url: "index.php?path="+path+"&action=changeChmod&recursively="+recursively+"&filesChange="+filesChange+"&foldersChange="+foldersChange+"&filesPerm="+filesPerm+"&foldersPerm="+foldersPerm,
        }).done(function( data ) {
            $("DIV#showLog PRE").html(data);
            $("DIV#showLog").modal("show");
        });
    }

    function unPack(){
        var file = $("DIV#unpackModal DIV.modal-header H4#heading SPAN").html();
        var path = $("DIV#unpackModal TR#unpackPath INPUT[type='text']").val();
        $.ajax({
            url: "index.php?action=unpack&file="+file+"&path="+path,
        }).done(function( data ) {
            $("DIV#showLog PRE").html(data);
            $("DIV#showLog").modal("show");
        });
    }

    function pack(){
        var path = $("DIV#packModal DIV.modal-header H4#heading SPAN").html();
        var file = $("DIV#packModal TR#filename INPUT[type='text']").val();
        $.ajax({
            url: "index.php?action=pack&file="+file+"&path="+path,
        }).done(function( data ) {
            location.reload();
        });
    }

    function upload(){
        var path = $("DIV#uploadModal DIV.modal-header H4#heading SPAN").html();
        var file = $("DIV#packModal TR#filename INPUT[type='file']").val();
        $.ajax({
            url: "index.php?action=upload&file="+file+"&path="+path,
        }).done(function( data ) {
            location.reload();
        });
    }

    function Signout(){
        $.ajax({
            url: "?action=Signout",
        }).done(function( data ) {
            location.reload();
        });
    }

    function logout(){
      var path = "vv";
      var file = "ff";
        $.ajax({
            url: "index.php?action=logout&file="+file+"&path="+path,
        }).done(function( data ) {
            location.reload();
        });
    }



    function expandFolder(src){
        var tr = $(src).parents("TR");
        var level = tr.attr("data-level");
        var parent = tr.attr("data-parent");
        var path = "";
        if(typeof parent != "undefined"){
            path = parent + "/" + tr.find("TD#name").html();
        } else {
            path = tr.find("TD#name").html();
        }
        $.ajax({
            url: "index.php?action=expandFolder&path="+path+"&level="+level,
        }).done(function( data ) {
            var arr = tr.find("TD#type SPAN");
            arr.removeClass("glyphicon-folder-close");
            arr.attr("onclick","collapseFolder(this)");
            arr.addClass("glyphicon-folder-open");
            tr.after(data);
        });
    }

    function collapseFolder(src){
        var tr = $(src).parents("TR");
        var level = tr.attr("data-level");
        tr.nextAll("TR").each(function(){
            var lvl = $(this).attr("data-level");
            if(lvl>level){
                $(this).remove();
            } else {
                return;
            }
        });
        $(src).removeClass("glyphicon-folder-open");
        $(src).addClass("glyphicon-folder-close");
        $(src).attr("onclick","expandFolder(this)");
    }
</script>
<?php
if (isset($_GET['logout'])) {
  session_destroy();
  unset($_SESSION['username']);
}

switch ($action) {

    case "list":
        printFolderTable($folder);
        break;
    case "deleteDir":
        $path = filter_input(INPUT_GET, "path");
        delete_directory($path);
        exit();
        break;
    case "makeDir":
        $path = filter_input(INPUT_GET, "path")==""?"":filter_input(INPUT_GET, "path") . "/";
        $name = filter_input(INPUT_GET, "name");
        mkdir(__DIR__ . "/" . $path . $name);
        exit();
        break;
    case "unpack":
        $path = filter_input(INPUT_GET, "path");
        $file = filter_input(INPUT_GET, "file");
        unpackFile($file, $path);
        exit();
        break;
    case "pack":
        $path = filter_input(INPUT_GET, "path");
        $file = filter_input(INPUT_GET, "file") . ".zip";
        packDir($path, $file);
        exit();
        break;

    case "changeChmod":
        $path = filter_input(INPUT_GET, "path");
        $recursively = filter_input(INPUT_GET, "recursively") == "true";
        $filesChange = filter_input(INPUT_GET, "filesChange") == "true";
        $foldersChange = filter_input(INPUT_GET, "foldersChange") == "true";
        $filesPerm = (filter_input(INPUT_GET, "filesPerm"));
        $foldersPerm = (filter_input(INPUT_GET, "foldersPerm"));
        recursiveChmod($path, $recursively, $filesChange, $foldersChange, $filesPerm, $foldersPerm);
        exit();
        break;


        case "Signout":
        session_destroy();
    	unset($_SESSION['username']);
    	header("location: login.php");
            exit();
            break;

}




?>

<!-- Modal - unpack -->
<div class="modal fade" id="unpackModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="heading">Unpack file <span></span></h4>
      </div>
      <div class="modal-body">
          <table>
              <tr id="unpackPath"><td>Path where to unpack:</td><td style="padding-left: 15px"><input type="text" value="." style="margin-left: 5px"></td></tr>
           </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="unPack()">Unpack</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal - pack -->
<div class="modal fade" id="packModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="heading">Pack <span></span></h4>
      </div>
      <div class="modal-body">
          <table>
              <tr id="filename"><td>File name:</td><td style="padding-left: 15px"><input type="text" value="archive" style="margin-left: 5px">.zip</td></tr>
           </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="pack()">Pack</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal - upload -->
<div class="modal fade" id="uploadModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h5 class="modal-title" id="heading">Upload file to <?php echo realpath($folder); ?>\<span>
      </div>
      <div class="modal-body">
          <table>
            <form action="" method="post" enctype="multipart/form-data">
    Select file to upload:
  <input type="file" name="fileToUpload" id="fileToUpload">

           </table>
      </div>
      <div class="modal-footer">

        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>

        <input type="submit" value="Upload" name="submit_file" class="btn btn-primary" >
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal - Make Directory -->
<div class="modal fade" id="mkDirModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h5 class="modal-title" id="heading">Create directory in <?php echo realpath($folder); ?>\<span>
        </span></h5>
      </div>
      <div class="modal-body">
          <table>
              <tr id="dirPath"><td>Name:</td><td style="padding-left: 15px"><input type="text" value="New" style="margin-left: 5px"></td></tr>
           </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="makeDir()">Create</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal - delete -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="heading">Delete: <span></span></h4>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" onclick="deleteDir()">Yes</button>
        <button type="button" class="btn" data-dismiss="modal">No</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="LoginModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content" style="padding:20px;">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="heading">Sign in: <span></span></h4>
      </div>
      <form method="post" action="">
       	<div class="form-group">
       		<label>Username</label>
       		<input class="form-control" type="text" name="username" >
       	</div>
       	<div class="form-group">
       		<label>Password</label>
       		<input class="form-control" type="password" name="password">
       	</div>
       	<div class="form-group">
       		<button  type="submit" class="btn" name="login_user">Sign in</button>
       	</div>
       </form>
    </div>
  </div>
</div>


<div class="modal fade" id="SignUpModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="heading">Sign Up: <span></span></h4>
      </div>
<form method="post" action="" style="padding:15px;">

  <div class="form-group">
    <label>Username</label>
    <input class="form-control" type="text" name="username" value="<?php echo $username; ?>">
  </div>
  <div class="form-group">
    <label>Email</label>
    <input class="form-control" type="email" name="email" value="<?php echo $email; ?>">
  </div>
  <div class="form-group">
    <label>Password</label>
    <input class="form-control" class="form-control" type="password" name="password_1">
  </div>
  <div class="form-group">
    <label>Confirm password</label>
    <input class="form-control" type="password" name="password_2">
  </div>
  <div class="form-group">
    <button type="submit" class="btn" name="reg_user">Register</button>
  </div>
</form>
    </div>
  </div>
</div>

<!-- Log -->
<div class="modal fade" id="showLog" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-body">
          <pre>
          </pre>
      </div>
      <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal" onclick="location.reload()">Close & Refresh</button>
      </div>
    </div>
  </div>
</div>

</body>
</html>

<?php

//functions
function eraseFile($file) {
    $handle = fopen($file, "w+");
    if ($handle == false)
        return false;
    if (!fclose($handle))
        return false;
    return true;
}

function unpackFile($file, $path){
    echo "Unpacking file $file to directory $path";
    if(!is_dir($path)){
        echo "$path does not exist or is not a directory";
        return false;
    }
    if(!is_file($file)){
        echo "$file does not exist";
        return false;
    }
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if (strtoupper($ext) == "ZIP") {
        $zip = new ZipArchive;
        $res = $zip->open($file);
        if ($res === TRUE) {
            $zip->extractTo($path);
            $zip->close();
            echo "File $file unzipped succesfully to path $path";
        } else {
            echo "Unpacking failed";
        }
    }
}

function packDir($source, $destination){
    $txt = "";
    if (!extension_loaded('zip') || ($source != "" && !file_exists($source))) return false;
    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) return false;

    if($source == ""){ // packing root dir
        $sourcePath = str_replace('\\', '/', realpath(__DIR__));
    } else {
        $sourcePath = str_replace('\\', '/', realpath($source));
    }
    if (is_dir($sourcePath) === true){
        if($source != ""){
            $zip->addEmptyDir(str_replace($sourcePath . '/', '', $source . '/'));
        }
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourcePath), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file){
            $file = str_replace('\\', '/', $file);
            // Ignore "." and ".." folders
            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                continue;
            $file = realpath($file);
            if (is_dir($file) === true){
                $zip->addEmptyDir(str_replace($sourcePath . '/', '', $source . '/' . $file . '/'));
            } else if (is_file($file) === true){
                $zip->addFile($file,str_replace($sourcePath . '/', '', $source . '/' . $file));
            }
        }
    } else if (is_file($sourcePath) === true){
        $zip->addFile($file,basename($sourcePath));
    }
    return $zip->close();
}


function delete_directory($path) {
    echo "Deleting Contents Of: $path<br /><br />";
    if(is_file($path)){
        unlink($path);
        echo "Deleted File: $path<br />";
        return true;
    }
    $files = array_diff(scandir($path), array('.', '..'));
     foreach ($files as $file) {
        if(is_dir("$path/$file")){
            delete_directory("$path/$file");
        } else {
           if(unlink("$path/$file"))
                echo "Deleted File: $path/$file<br />";
        }
    }
    if (rmdir($path))
        echo "Deleted directory: $path<br />";
    return true;
}


function printFolderTable($folder){
    echo "<h3 style=\"margin: 2% 0% 0% 5%;\">File list in " . realpath($folder) . "</h3>";
if(!empty($_SESSION['username'])){
  $user = $_SESSION['username'];


  echo "<span style=\"margin: 2% 5% 0% 5%; float:right;\" onclick=\"showSignup(this)\">Logged in as <b>$user</b></span>";
  echo "<a href=\"?logout='1'\" style=\"margin: 2% 0% 0% 5%; color: red; float:right;\">logout</a>";
}else{
  echo "<button class=\"btn btn-default\" style=\"margin: 2% 5% 0% 5%; float:right;\" onclick=\"showSignup(this)\">Sign up</button>";
  echo "<button class=\"btn btn-default\" style=\"margin: 2% 0% 0% 5%; float:right;\" onclick=\"showLogin(this)\">Sign in</button>";
}

    echo "<br><br>";
    $newDir = "<span class=\"glyphicon glyphicon-plus\" aria-hidden=\"true\" style=\"cursor:pointer; top: 0px\" title=\"Create new directory...\"></span>";
    $upload = "<span class=\"glyphicon glyphicon-open-file\" aria-hidden=\"true\" style=\"cursor:pointer; top: 0px\" title=\"Create new directory...\"></span>";
    $packRoot = "<span class=\"glyphicon glyphicon-compressed\" aria-hidden=\"true\" style=\"cursor:pointer; top: 0px\" title=\"Pack root directory to ZIP...\"></span>";
    echo "<button class=\"btn btn-default\" style=\"margin: 0% 0% 0% 5%;\" onclick=\"showMkdDlg(this)\">$newDir Create Directory</button>";
    echo "<button class=\"btn btn-default\" style=\"margin-left: 5px;\" onclick=\"showPckDlg(this)\">$packRoot Pack Root</button>";
    echo "<button class=\"btn btn-default\" style=\"margin-left: 5px;\" onclick=\"showUpload(this)\">$upload Upload file</button>";
    echo "<table class=\"table table-bordered table-hover table-striped\" style=\"margin: 5px 5%; width: 90%\">";
    $contents = scandir($folder);
    echo "<tr>"
                . "<th>Type</th>"
                . "<th>Name</th>"
                . "<th>File size</th>"
                . "<th>Files</th>"
                . "<th>Privacy</th>"
                . "<th>Actions</th>"
                . "</tr>";
    foreach ($contents as $line) {
        if($line == "." || $line == ".." ) continue;
        $dir = is_dir($line);
        $icon = "glyphicon glyphicon-file";
        $cursor = "cursor: auto;";
        $title = "";
        $click = "";
        $iconColor = "";
        if($dir){
            $icon = "glyphicon glyphicon-folder-close";
            $cursor = "cursor: pointer;";
            $title = "Expand";
            $click = "expandFolder(this)";
            $iconColor = "color: #F0AD4E";
        }
        if($line==="private files" & empty($_SESSION['username'])){
            $icon = "glyphicon glyphicon-lock";
            $cursor = "cursor: pointer;";
            $title = "Expand";
            $click = "expandFolder(this)";
            $iconColor = "color: darkgray";
        }
        $data = dirsize($line);
        $size = $data["size"];
        $count = $data["count"];
        if($size > 1048576){
            $size = round($size/1048576,2) . " MB";
        } elseif ($size > 1024){
            $size = round($size/1024,2) . " KB";
        } else {
            $size = $size . " B";
        }
        if($line==="private files" & empty($_SESSION['username'])){
            $download = "<span class=\"glyphicon glyphicon-cloud-download\" aria-hidden=\"true\" style=\" color:darkgray; margin: 0px 2px\"  title=\"Change permissions...\" disabled></span>";
          $changePerm = "<span class=\"glyphicon glyphicon-pencil\" aria-hidden=\"true\" style=\" color:darkgray; margin: 0px 2px\"  title=\"Change permissions...\" disabled></span>";
          $deleteIcon = "<span class=\"glyphicon glyphicon-remove\" aria-hidden=\"true\" style=\" color:darkgray; margin: 0px 2px\"  title=\"Delete...\"></span>";
          $ext = pathinfo($line, PATHINFO_EXTENSION);
          $privacy = "private";
          $packIcon = "<span class=\"glyphicon glyphicon glyphicon-compressed\" aria-hidden=\"true\" style=\" color:darkgray; margin: 0px 2px\"  title=\"Pack to zip...\"></span>";
          $unpackIcon = strtoupper($ext) == "ZIP"?"<span class=\"glyphicon glyphicon-open-file\" aria-hidden=\"true\" style=\" color:darkgray; margin: 0px 2px\"  title=\"Unpack...\"></span>":"";
        }else{
                      $download = '<a class="glyphicon glyphicon-cloud-download" href="?file=' . urlencode($line) . '"></a>';
          $downcload = "<a style=\"text-decoration: none;\" href=\"?file='urlencode($line)'\" ><span class=\"glyphicon glyphicon-cloud-download\" aria-hidden=\"true\" style=\" margin: 0px 2px; \"  title=\"Download...\"></span></a>";
          $changePerm = "<span class=\"glyphicon glyphicon-pencil\" aria-hidden=\"true\" style=\"cursor:pointer; margin: 0px 2px\" onclick=\"showPrmDlg(this)\" title=\"Change permissions...\"></span>";
          $deleteIcon = "<span class=\"glyphicon glyphicon-remove\" aria-hidden=\"true\" style=\"cursor:pointer; margin: 0px 2px\" onclick=\"showDltDlg(this)\" title=\"Delete...\"></span>";
          $ext = pathinfo($line, PATHINFO_EXTENSION);
          $privacy = "public";
          $packIcon = "<span class=\"glyphicon glyphicon glyphicon-compressed\" aria-hidden=\"true\" style=\"cursor:pointer; margin: 0px 2px\" onclick=\"showPckDlg(this)\" title=\"Pack to zip...\"></span>";
          $unpackIcon = strtoupper($ext) == "ZIP"?"<span class=\"glyphicon glyphicon-open-file\" aria-hidden=\"true\" style=\"cursor:pointer; margin: 0px 2px\" onclick=\"showUnpDlg(this)\" title=\"Unpack...\"></span>":"";
        }

        echo "<tr data-level=\"0\" >"
                . "<td id=\"type\" style=\"width: 70px\"><span class=\"$icon\" style=\"$cursor $iconColor\" title=\"$title\" onclick=\"$click\" aria-hidden=\"true\"></span></td>"
                . "<td id=\"name\">$line</td>"
                . "<td id=\"size\" style=\"width: 120px\">$size</td>"
                . "<td id=\"fileCount\" style=\"width: 120px\">$count</td>"
                . "<td id=\"perm\" style=\"width: 120px\">$privacy</td>"
                . "<td style=\"width: 120px\">$download $packIcon $unpackIcon $deleteIcon</td>"
                . "</tr>";
    }
    echo "</table>";
    if(empty($_SESSION['username'])){
      echo "<h4 style=\"margin-left:5%\">Note: you have to <a onclick=\"showLogin(this)\"> Sign in</a> to access the private folder</h4>;";
    }
}

function expandFolder($path, $level) {
    $contents = scandir($path);
    $margin = "margin-left: " . ($level*10) . "px;";
    foreach ($contents as $line) {
        if ($line == "." || $line == "..")
            continue;
        $dir = is_dir($path . "/" . $line);
        $icon = "glyphicon glyphicon-file";
        $cursor = "cursor: auto;";
        $title = "";
        $click = "";
        $iconColor = "";
        if($dir){
            $icon = "glyphicon glyphicon-folder-close";
            $cursor = "cursor: pointer;";
            $title = "Expand";
            $click = "expandFolder(this)";
            $iconColor = "color: #F0AD4E";
        }

        $perm = substr(sprintf('%o', fileperms($path . "/" . $line)), -4);
        $data = dirsize($path . "/" . $line);
        $size = $data["size"];
        $count = $data["count"];
        if($size > 1048576){
            $size = round($size/1048576,2) . " MB";
        } elseif ($size > 1024){
            $size = round($size/1024,2) . " KB";
        } else {
            $size = $size . " B";
        }

        $deleteIcon = "<span class=\"glyphicon glyphicon-remove\" aria-hidden=\"true\" style=\"cursor:pointer; margin: 0px 5px\" onclick=\"showDltDlg(this)\" title=\"Delete...\"></span>";
        $unpackIcon = "<span class=\"glyphicon glyphicon-open-file\" aria-hidden=\"true\" style=\"cursor:pointer; margin: 0px 5px\" onclick=\"showUnpDlg(this)\" title=\"Unpack...\"></span>";
        echo "<tr data-level=\"$level\" data-parent=\"$path\">"
        . "<td id=\"type\" style=\"width: 70px\"><span class=\"$icon\" style=\"$cursor $margin $iconColor\" title=\"$title\" onclick=\"$click\" aria-hidden=\"true\"></span></td>"
        . "<td id=\"name\">$line</td>"
        . "<td id=\"size\">$size</td>"
        . "<td id=\"fileCount\" style=\"width: 120px\">$count</td>"
        . "<td id=\"perm\">$perm</td>"
        . "<td> $deleteIcon</td>"
        . "</tr>";
    }
}

function dirsize($dir) {
    if(is_file($dir)) return array("size" => filesize ($dir), "count"=>1);
    $total_size = 0;
    $files = scandir($dir);
    $total_count = count($files) - 2; //do not count . and ..
    foreach ($files as $t) {
        if (is_dir(rtrim($dir, '/') . '/' . $t)) {
            if ($t <> "." && $t <> "..") {
                $data = dirsize(rtrim($dir, '/') . '/' . $t);
                $size = $data["size"];
                $count = $data["count"];
                $total_size += $size;
                $total_count += $count;
            }
        } else {
            $size = filesize(rtrim($dir, '/') . '/' . $t);
            $total_size += $size;
        }
    }
    return array("size" => $total_size, "count"=>$total_count);
}
