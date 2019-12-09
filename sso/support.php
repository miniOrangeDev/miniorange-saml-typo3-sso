<?php
if(!class_exists("DB")){
  require_once dirname(__FILE__) . '/helper/DB.php';
}
require "connector.php";
if(!isset($_SESSION)){
  session_id("connector");
  session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- Main CSS-->
        <link rel="stylesheet" type="text/css" href="includes/css/main.css">
        <!-- Font-icon css-->
        <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    </head>
    <body class="app sidebar-mini rtl">
        <!-- Navbar-->
        <header class="app-header"><a class="app-header__logo" href="#" style="margin-top:10px;"><img src="resources/images/logo-home.png"></a>
        <!-- Sidebar toggle button<a class="app-sidebar__toggle" href="#" data-toggle="sidebar" aria-label="Hide Sidebar"></a> -->
        <ul class="app-nav">
          <li class="dropdown"><a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Open Profile Menu"><i class="fa fa-user fa-lg"><span  style="margin-left:5px"><?php  echo $_SESSION['admin_email']; ?></span><span style="padding-left:5px;"><i class="fa fa-caret-down"></i></span></i></a>
            <ul class="dropdown-menu settings-menu dropdown-menu-right">
          <li><a class="dropdown-item" href="admin_logout.php"><i class="fa fa-sign-out fa-lg"></i> Logout</a></li>
        </ul>
        </li>
    </ul>
        </header>
        <!-- Sidebar menu-->
    <div class="app-sidebar__overlay" data-toggle="sidebar"></div>
    
    <aside class="app-sidebar">
      <div class="app-sidebar__user" style="padding-left:40px"><img src="resources/images/miniorange.png"  style="width:37.25px; height:50px;" alt="User Image">
        <div style="margin-left:15px;">
          <p class="app-sidebar__user-name">PHP SAML</p>
          <p class="app-sidebar__user-designation">Connector</p>
        </div>
      </div>
      <ul class="app-menu">
        <li><a class="app-menu__item" href="setup.php"><i style="font-size:20px;" class="app-menu__icon fa fa-gear"></i><span class="app-menu__label"><b>Plugin Settings</b></span></a></li>
        <li><a class="app-menu__item" href="how_to_setup.php"><i style="font-size:20px;" class="app-menu__icon fa fa-info-circle"></i><span class="app-menu__label"><b>How to Setup?</b></span></a></li>
        <li><a class="app-menu__item" href="account.php"><i style="font-size:20px;" class="app-menu__icon fa fa-user"></i><span class="app-menu__label"><b>Account Setup</b></span></a></li>
        <li><a class="app-menu__item active" href="support.php"><i style="font-size:20px;" class="app-menu__icon fa fa-support"></i><span class="app-menu__label"><b>Support</b></span></a></li>
              </ul>
    </aside>

    <main class="app-content">
        <div class="app-title">
          <div>
            <h1><i class="fa fa-support"></i>  Support/Contact Us</h1>
            
          </div>
          <ul class="app-breadcrumb breadcrumb">
            <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
            <li class="breadcrumb-item"><a href="#">Support/Contact Us</a></li>
          </ul>
        </div>

        <p id="saml_message"></p>
        
        <div class="row">
            <div class="col-md-12">
              <div class="tile">
                <div class="row">
                  <div class="col-lg-10">
                    <form method="post" action="">
                        <p><b>Need any help? We can help you in configuring the connector with your Identity Provider. Just send us a query and we will get back to you soon.</b></p>
                        <input type="hidden" name="option" value="mo_saml_contact_us_query_option"/>
                        <div class="form-group">
                            <input class="form-control" type="text" name="mo_saml_contact_us_email" placeholder="Enter your email" value="<?php echo DB::get_option('mo_saml_admin_email');?>">
                        </div>
                        <div class="form-group">
                            <input class="form-control" type="tel" name="mo_saml_contact_us_phone" placeholder="Enter your phone number">
                        </div>
                        <div class="form-group">
                        <textarea class="form-control" name="mo_saml_contact_us_query" placeholder="Enter your query here"
                        onkeypress="mo_saml_valid_query(this)" onkeyup="mo_saml_valid_query(this)"
                        onblur="mo_saml_valid_query(this)"
                        ></textarea>
                        </div>

                  </div>
                </div>
                <div class="tile-footer">
                    <button class="btn btn-primary" type="submit" name="submit">Submit</button>
                </div>
                </form>
              </div>
            </div>
        </div>
    </main>
    <?php

    ?>
    <script>
        function mo_saml_valid_query(f) {
            !(/^[a-zA-Z?,.\(\)\/@ 0-9]*$/).test(f.value) ? f.value = f.value.replace(
                /[^a-zA-Z?,.\(\)\/@ 0-9]/, '') : null;
        }
    </script>
