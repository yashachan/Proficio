<?php

final class Proficio_Core_Controller_Modules_Core
{
    private static $_msgtab  = array();
    private static $_timeout = 30;
    private static $_version = '$Revision: 9 $';
        
    public static function init()
    {
        self::$_msgtab['core']             = true;
        self::$_msgtab['channels']         = ('h3lpdev');
        self::$_msgtab['commands']['test'] = array('active' => false);
        self::$_msgtab['version']          = self::$_version;
        
        return self::$_msgtab;
    }

    public static function test($client, $channel)
    {
        if (self::$_msgtab['commands'][__FUNCTION__]['active'] === true)
        {
            Proficio_Damn_Model_Client::say("$client: This is a test command you stupid whore, stop that", $channel);
        }
        else {
            Proficio_Damn_Model_Client::say("$client: Sorry the command 'test' is disabled.", $channel);
        }
    }
}