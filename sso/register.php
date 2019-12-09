<?php

require 'connector.php';

if(!isset($_SESSION)){
  session_id("connector");
  session_start();
}
$data_folder = __DIR__ . '\helper\data';
if(!file_exists($data_folder))
mkdir($data_folder);
if(isset($_POST['option']) && !empty($_POST['option'])){

  $email='';
  $password = '';
  if(isset($_POST['email']) && !empty($_POST['email']))
      $email = $_POST['email'];
  if(isset($_POST['password']) && !empty($_POST['password']))
      $password = $_POST['password'];
  if(!empty($password)){
      $password = sha1($password);
  }
  if($_POST['option'] === 'register'){
      $user_credential = array($email => $password);
      $file = dirname(__FILE__) . '\helper\data\credentials.json';
      $json_string = json_encode($user_credential, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      file_put_contents($file, $json_string);
      $_SESSION['authorized'] = true;
      $_SESSION['admin_email'] = $email;
      if(mo_saml_is_customer_license_verified()){
        header("Location: setup.php");
        exit();
      } else {
        header("Location: account.php");
        exit();
      }

}
}

if(is_user_registered()){
  header('Location: admin_login.php');
  exit();
}


?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="includes/css/main.css">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Register - miniOrange Admin</title>
  </head>
  <body>
    <section class="material-half-bg">
      <div class="cover"></div>
    </section>
    <section class="login-content">
      <div class="logo">
        <h1><img src="resources/images/logo_large.png"></h1>
      </div>
      <div class="col-md-6">
          <div class="tile">
            <h3 class="tile-title" title="This will restrict unauthorized entity from accessing the Connector">Create a local account</h3>
            <form class="register_form" id="register_form" method="POST" action="">
            <input type="hidden" name="option" value="register">
            <div class="tile-body">
              
                <div class="form-group row">
                  <label class="control-label col-md-3">Email</label>
                  <div class="col-md-8">
                    <input class="form-control col-md-10" type="email" name="email" placeholder="Enter email address" required>
                  </div>
                </div>
                <div class="form-group row">
                <label class="control-label col-md-3">Password</label>
                <div class="col-md-8">
                  <input class="form-control col-md-10" type="password" id="password" name="password" placeholder="Enter a password" minlength="6" required>
                </div>
                </div>
                <div class="form-group row">
                <label class="control-label col-md-3">Confirm Password</label>
                <div class="col-md-8">
                  <input class="form-control col-md-10" type="password" id="confirm_password" placeholder="Re-type the password" minlength="6" required>
                </div>
                </div>
                <script>
                var password = document.getElementById("password")
                , confirm_password = document.getElementById("confirm_password");

                function validatePassword(){
                  if(password.value != confirm_password.value) {
                    confirm_password.setCustomValidity("Passwords Don't Match");
                  } else {
                    confirm_password.setCustomValidity('');
                  }
                }

                password.onchange = validatePassword;
                confirm_password.onkeyup = validatePassword;
                </script>
                
            </div>
            <div class="tile-footer">
              <div class="row">
                <div class="col-md-8 col-md-offset-3">
                  <button class="btn btn-primary" type="submit" id="register"><i class="fa fa-fw fa-lg fa-check-circle"></i>Register</button>
                </div>
              </div>
            </div>
            </form>
          </div>
        </div>

    </section>
    <!-- Essential javascripts for application to work-->
    <script src="includes/js/jquery-3.2.1.min.js"></script>
    <script src="includes/js/popper.min.js"></script>
    <script src="includes/js/bootstrap.min.js"></script>
    <script src="includes/js/main.js"></script>
    <!-- The javascript plugin to display page loading on top-->
    <script src="includes/js/plugins/pace.min.js"></script>
  </body>
</html>