<?php

namespace App\Session;

/**
* Simple PHP Native Session class
*
* @author Moderator <pitsolu@gmail.com>
*/
class Native{

    /**
    * Constructor
    *
    * Initialize session
    */
    public function __construct(){

        @session_start();
    }

    /**
    * Session variable getter
    *
    * @param string $key
    *
    * @return mixed
    */
    public function get($key){

        return @$_SESSION[$key];
    }

    /**
    * Session variable setter
    *
    * @param string $key
    * @param string $val
    *
    * @return App\Session\Native
    */
    public function set($key, $val){

        $_SESSION[$key] = $val;

        return $this;
    }

     /**
    * Session variable getter
    *
    * @param string $key
    *
    * @return App\Session\Native
    */
    public function remove($key){

        unset($_SESSION[$key]);

        return $this;
    }

    /**
    * Destroy session
    *
    * @return void
    */
    public function kill(){

        session_destroy();
    }
}