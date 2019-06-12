<?php

namespace  MiniOrange\Helper;

use MiniOrange\Helper\Utilities;
/**
 * This class contains some constant variables to be used
 * across the plugin. All the SP settings are done here.
 * The SP SAML settings and the IDP settings have to be set
 * here.
 */
class PluginSettings
{   
    private static $obj;

    public static function getPluginSettings() {
        if(!isset(self::$obj)) {
            self::$obj = new PluginSettings();
        }
        return self::$obj;
    }

    public function getIdpName(){
        return self::get_option('saml_identity_name');
    }

    public function getIdpEntityId(){
        return self::get_option('idp_entity_id');
    }

    public function getSamlLoginUrl(){
        return self::get_option('saml_login_url');
    }

    public function getX509Certificate(){
        return self::get_option('saml_x509_certificate');
    }

    public function getSamlLogoutUrl(){
        return self::get_option('saml_logout_url');
    }

    public function getLoginBindingType(){
        return self::get_option('saml_login_binding_type');
    }

    public function getForceAuthentication(){
        
        if(self::get_option('force_authentication') != false){
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function getSiteBaseUrl(){
        return self::get_option('sp_base_url');
    }

    public function getSpEntityId(){
        return self::get_option('sp_entity_id');
    }

    public function getAcsUrl(){
        return self::get_option('acs_url');
    }

    public function getSingleLogoutUrl(){
        return self::get_option('single_logout_url');
    }

    public function getAssertionSigned(){
        return true;
    }

    public function getResponseSigned(){
        return true;
    }
    
    public function getSamlAmEmail(){
        return self::get_option('saml_am_email');
    }

    public function getSamlAmUsername(){
        return self::get_option('saml_am_username');
    }

    public function getApplicationUrl(){
        return self::get_option('application_url');
    }

    public function getSiteLogoutUrl(){
        return self::get_option('site_logout_url');
    }

    public function getCustomAttributeMapping(){
        return self::get_option('mo_saml_custom_attrs_mapping');
    }

    public function getSessionIndex(){
        $sessionIndex = self::get_option('session_index');
        self::delete_option('session_index');
        return $sessionIndex;
    }

    public function setSessionIndex($index){
        self::update_option('session_index', $index);
    }

    public static function get_option($key){
        $options = self::get_options();
        if(!empty($options)){
            if(array_key_exists($key, $options)){
                return $options[$key];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function update_option($key, $value){
        $tempOptions = self::get_options();
        if(empty($tempOptions))
            $tempOptions = array();
        $update = array($key => $value);
        $updatedOptions = array_merge($tempOptions, $update);
        $file = self::getOptionsFilePath();
        $json_string = json_encode($updatedOptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($file, $json_string);
    }

    public static function delete_option($key){
        $options = self::get_options();
        unset($options[$key]);
        $file = self::getOptionsFilePath();
        $json_string = json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($file, $json_string);
    }

    private static function get_options(){
        $str='';
        if((file_exists(self::getOptionsFilePath())))
            $str = file_get_contents(self::getOptionsFilePath());
        
        $customer_array = json_decode($str, true);
        return $customer_array;
    }

    public static function getOptionsFilePath(){
        return dirname(__FILE__) . '\data\options.json';
    }
        
}