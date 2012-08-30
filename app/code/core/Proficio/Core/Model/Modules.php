<?php

final class Proficio_Core_Model_Modules
{
    private static $_modules = array();
    
    public static function getCallback($command)
    {
        if (is_array(self::$_modules)) {
            foreach (self::$_modules as $module) {
                if (array_key_exists($command, $module['commands'])) {
                    return $module['class'];
                }
            }
        }
                
        return false;
    }
    
    public static function isCommand($command)
    {    
        if (is_array(self::$_modules)) {
            foreach (self::$_modules as $module) {
                if (array_key_exists($command, $module['commands'])) return true;
            }
        }
        
        return false;
    }
    
    public static function moduleIsLoaded($module)
    {
        if (array_key_exists($module, self::$_modules)) {
            if ((count(self::$_modules[$module]['config']) > 0) && (count(self::$_modules[$module]['commands'] > 0))) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }
    
    public static function moduleLoad($module)
    {
        $modulePath = Proficio_Core_Model_Config::getModulePath();
        $moduleName = ucfirst($module) . '.php';

        if (self::moduleIsLoaded($module)) return -1;
        
        if (file_exists($modulePath . DS . $moduleName)) {
            $modLoad = "Proficio_Core_Controller_Modules_$module";
            self::$_modules[$module]          = call_user_func(array($modLoad, 'init'));
            self::$_modules[$module]['class'] = $modLoad;
            return 1;
        }
                
        return -1;
    }
    
    public static function moduleUnload($module)
    {
        if ((count(self::$_modules[$module]['config']) > 0) && (count(self::$_modules[$module]['commands'] > 0))) {
            if (self::$_modules[$module]['core']) {
                return -1;
            }
            
            unset(self::$_modules[$module]);
        }
        else {
            return 0;
        }
        
        return 1;
    }
}