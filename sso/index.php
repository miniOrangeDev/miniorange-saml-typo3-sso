<?php
/*
Plugin Name: miniOrange PHP SAML 2.0 Connector
Version: 11.0.0
Author: miniOrange
*/
require 'connector.php';

if(!is_user_registered()){
    header("Location: register.php");
    exit();
} else {
    header("Location: admin_login.php");
    exit();
}

 ?>