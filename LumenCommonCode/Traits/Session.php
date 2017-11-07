<?php

// Simple Session management

namespace App\Traits;

class Session
{
    public function __construct()
    {
        if (! seesion_id()) {
            session_start();
        }
    }

    public function set($key, $val)
    {
        $_SESSION[$key] = $val;
    }

    public function get($key)
    {
        return $_SESSION[$key] ?? false;
    }

    public function destory()
    {
        session_destroy();
        setcookie('PHPSESSID', '', time()-1);
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

    public function flush($key)
    {
        $value = $this->get($key);
        $this->delete($key);
        return value;
    }
}