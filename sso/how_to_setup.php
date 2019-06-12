<?php
if (session_status() == PHP_SESSION_NONE){
  session_id("connector");
  session_start();
}

if(isset($_SESSION['authorized']) && !empty($_SESSION['authorized'])){
  if($_SESSION['authorized'] != true){
    header('Location: admin_login.php');
    exit();
  }
} else {
  header('Location: admin_login.php');
  exit();
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
        <!-- Sidebar toggle button -->
        <ul class="app-nav">
          <li class="dropdown"><a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Open Profile Menu"><i class="fa fa-user fa-lg"><span  style="margin-left:5px"><?php echo $_SESSION['admin_email']; ?></span><span style="padding-left:5px;"><i class="fa fa-caret-down"></i></span></i></a>
            <ul class="dropdown-menu settings-menu dropdown-menu-right">
          <li><a class="dropdown-item" href="admin_logout.php"><i class="fa fa-sign-out fa-lg"></i>Logout</a></li>
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
        <li><a class="app-menu__item active" href="how_to_setup.php"><i style="font-size:20px;" class="app-menu__icon fa fa-info-circle"></i><span class="app-menu__label"><b>How to Setup?</b></span></a></li>
        <li><a class="app-menu__item" href="account.php"><i style="font-size:20px;" class="app-menu__icon fa fa-user"></i><span class="app-menu__label"><b>Account Setup</b></span></a></li>
        <li><a class="app-menu__item" href="support.php"><i style="font-size:20px;" class="app-menu__icon fa fa-support"></i><span class="app-menu__label"><b>Support</b></span></a></li>
              </ul>
    </aside>
    <main class="app-content">
    <div class="app-title">
          <div>
            <h1><i class="fa fa-info-circle"></i>  How to Setup?</h1>
            
          </div>
          <ul class="app-breadcrumb breadcrumb">
            <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
            <li class="breadcrumb-item"><a href="#">How to Setup?</a></li>
          </ul>
        </div>
        <p id="saml_message"></p>

        <?php 
          require 'connector.php';
        ?>
        <div class="row">
            <div class="col-md-12">
              <div class="tile">
                <div class="row">
                  <div class="col-lg-10">
                    <h3>Follow these steps to setup the plugin:</h3>
                    <h4>Step 1:</h4>
                    <p>In <b>Plugin Settings</b>, use your Identity Provider details to configure the plugin</p>
                    <img src="resources/images/setup_1.png" style="width:800px;height:380px;margin-left:50px; border:1px solid;"> 
                    <br/><br/>
                    <h4>Step 2:</h4>
                    <ul>
                      <li>Configure the plugin using the Service Provider details.</li>
                      <li>You need to provide these <b>SP Entity ID</b>, <b>ACS URL</b> and <b>Single Logout URL</b> values while configuring your Identity Provider</li>
                      <li>Click on the <b>Save</b> button to save your configuration.</li>
                    </ul>
                    <img src="resources/images/setup_2.png" style="width:800px;height:380px;margin-left:50px; border:1px solid;">
                    <br/><br/>
                    <h4>Step 3:</h4>
                    <ul>
                    <li>You can test if the plugin configured properly or not by clicking  on the <b>Test Configuration</b> button.</li>
                    </ul>
                    <img src="resources/images/setup_3.png" style="width:800px;height:380px;margin-left:50px; border:1px solid;">
                    <ul>
                    <br/>
                    <li>If the configuration is correct, you should see a Test Successful screen with the user's attribute values.</li>
                    </ul>
                    <img src="resources/images/setup_4.png" style="width:600px;height:400px;margin-left:50px; border:1px solid;">
                    <br/><br/>
                    <h4>Step 4:</h4>
                    <p>Use the following URL as a link in your application from where you want to perform SSO:<br/>
                    <code>http://&lt;your-domain&gt;/sso/login.php</code></p>
                    <p>For example, you can use it as:<br/>
                    <code>&lt;a href="http://&lt;your-domain&gt;/sso/login.php”&gt;Log in&lt;/a&gt;</code>
                    <h4>Step 5:</h4>

                    <form id="setup_form" method="POST" action="">
                      <input type="hidden" name="option" value="save_endpoint_url">
                      <div class="form-group">
                        <label><b>Application URL</b>
                        <!-- <span class="badge badge-pill badge-primary float-right" onclick="" title="The users will be redirected to this URL after logging in.">?</span> -->
                        <i class="fa fa-question-circle" id="application_url_help"></i>
                        <div style="display:none" class="help_desc" id="application_url_help_desc">
                          <span>The users will be redirected to this URL after logging in.</span>
                        </div>
                        </label>
                        
                        <input class="form-control" name="application_url" id="application_url" type="text"
                        <?php
                        echo ' value="' . DB::get_option('application_url') .'" ';  
                        ?>>
                        </div>

                      <div class="form-group">
                        <label><b>Site Logout URL</b>
                        <!-- <span class="badge badge-pill badge-primary float-right" onclick="" title="The users will be redirected to this URL after logging in.">?</span> -->
                        <i class="fa fa-question-circle" id="logout_url_help"></i>
                        <div style="display:none" class="help_desc" id="logout_url_help_desc">
                        The users will be redirected to this URL after logging out.  
                        </div>
                        </label>
                        <input class="form-control" name="site_logout_url" id="site_logout_url" type="text"
                        <?php
                        echo ' value="' . DB::get_option('site_logout_url') .'" ';  
                        ?>>
                        <br/>
                        <button class="btn btn-primary" type="submit" name="submit" id="submit">Update</button></td>
                        
                      </div>

                    <h4>Step 6:</h4>
                      <p>Please provide your applicaion URL in the above field and use the following code in your application to access the user attributes:</p>
                      <figure style="background-color:#979b9f; border-radius:5px;">
                      <pre>
                      <code>
    if(session_status() == PHP_SESSION_NONE){
      session_id('attributes');
      session_start();
    }
    echo '&lt;p&gt;Attributes Received:&lt;/p&gt;';
    foreach($_SESSION as $key=>$values){
      echo $key . ' : ';
      if(is_array($values))
      foreach($values as $value){
        if(next($values))
          echo $value . ' | ';
        else
          echo $value;
      }
      echo '&lt;br/&gt;';
    }
    
    // The above piece of code will display all the attributes received and mapped in the connector.

                      </code>
                      </pre>
                      </figure>
                    </form>

                    <h4>Step 7:</h4>
                    <p>Use the following URL as a link in your application from where you want to perform SLO:<br/>
                    <code>http://&lt;your-domain&gt;/sso/logout.php</code></p>
                    <p>For example, you can use it as:<br/>
                    <code>&lt;a href="http://&lt;your-domain&gt;/sso/logout.php”&gt;Log out&lt;/a&gt;</code>
                    
                  </div>
                </div>
              </div>
            </div>
        </div>
    </main>

    </body>
</html>