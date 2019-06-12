<?php
require 'connector.php';

if(!isset($_SESSION)){
    session_id("connector");
    session_start();
}

if(!is_user_registered()){
    header('Location: register.php');
    exit();
}


if(isset($_POST['option']) && $_POST['option'] == 'admin_login'){
    
    $email='';
    $password = '';
    if(isset($_POST['email']) && !empty($_POST['email']))
        $email = $_POST['email'];
    if(isset($_POST['password']) && !empty($_POST['password']))
        $password = $_POST['password'];
    if(!empty($password)){
        $password = sha1($password);
    }
        $str='';
        if((file_exists(dirname(__FILE__) . '\helper\data\credentials.json')))
            $str = file_get_contents(dirname(__FILE__) . '\helper\data\credentials.json');
        
        $credentials_array = json_decode($str, true);
        
        $password_check = '';
        if(is_array($credentials_array))
            if(array_key_exists($email, $credentials_array))
                $password_check = $credentials_array[$email];
            else {
                $_SESSION['invalid_credentials'] = true;

            }

        if(!empty($password_check)){
            if($password === $password_check){
                
                if(!isset($_SESSION['authorized']) || $_SESSION['authorized'] != true){
                    $_SESSION['authorized'] = true;
                }
                $_SESSION['admin_email'] = $email;
                if(mo_saml_is_customer_license_verified()){
                    header("Location: setup.php"); 
                    exit();
                } else {
                    header("Location: account.php");
                    exit();
                }
                
            } else {
                $_SESSION['invalid_credentials'] = true;

            }
        }
    
}

if(isset($_SESSION['authorized']) && !empty($_SESSION['authorized'])){
    if($_SESSION['authorized'] == true){
        if(mo_saml_is_customer_license_verified()){
            header("Location: setup.php");
            exit();
        } else {
            header("Location: account.php");
            exit();
        }
    }
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
    <title>Login - miniOrange Admin</title>
  </head>
  <body>
  <section class="material-half-bg">
      <div class="cover"></div>
    </section>
    <section class="login-content">
      <div class="logo">
        <h1><img src="resources/images/logo_large.png"></h1>
      </div>
      <div class="col-md-5">
          <div class="tile">
            <h3 class="tile-title">Login</h3>
            <form class="login_form" method="POST" action="">
            <input type="hidden" name="option" value="admin_login">
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
                  <input class="form-control col-md-10" type="password" name="password" id="password" placeholder="Enter a password" minlength="6" required>
                </div>
                </div>
            </div>
            <div class="tile-footer">
              <div class="row">
                <div class="col-md-8 col-md-offset-3">
                <button class="btn btn-primary" type="submit"><i class="fa fa-fw fa-lg fa-check-circle"></i>Login</button>
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
    <script type="text/javascript" src="includes/js/plugins/bootstrap-notify.min.js"></script>
    <script type="text/javascript" src="includes/js/plugins/sweetalert.min.js"></script>
    <?php
    if(isset($_SESSION['invalid_credentials']) && !empty($_SESSION['invalid_credentials'])){
        if($_SESSION['invalid_credentials'] === true){
            echo'<script>
                $(document).ready(function(){
                $.notify({
                    title: "ERROR: ",
                    message: "Invalid username or password",
                    icon: \'fa fa-times\' 
                },{
                    type: "danger"
                });
            });
            </script>';
            unset($_SESSION['invalid_credentials']);
        }
    }
    ?>
  </body>
</html>