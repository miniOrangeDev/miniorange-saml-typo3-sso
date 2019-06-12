<?php

class DB {
    // private static $db_file_path = dirname(__FILE__) . '\helper\data\options.json';
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
?>