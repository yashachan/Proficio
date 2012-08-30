<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Proficio: An advanced bot/client for dAmn
 *
 * PHP version 5
 *
 * Copyright 2009-2010 Alan Brault
 * 
 * Licensed under the Apache License, Apache 2.0 (the "License");
 * you may not use this file except in complaince with the License.
 * You may obtain a copy of the License at
 * 
 *      http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for specific language governing permissions and
 * limitations under the License.
 *
 * @category Proficio
 * @package  Core
 * @author   Alan Brault <alan.brault@incruentatus.net>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @version  SVN: $Id: Object.php 37 2010-02-27 17:07:35Z abrault $
 */

// {{{ Proficio_Object
final class Proficio_Object
{
    private static $_data = array();

    private static function _array_key_rename_recursive(&$data, $old_key, $new_key, $child_key = null, $save_old = false)
    {
        if (is_string($child_key)) {
            if (array_key_exists($old_key, $data)) {
                $data[$new_key][$child_key] = $data[$old_key][$child_key];
                
                if ($save_old === true) {
                    $data[$old_key][$child_key] = array();
                }
                else {
                    unset($data[$old_key][$child_key]);
                }
            }
        
            foreach ($data as &$value) {
                if (is_array($value)) {
                    self::_array_key_rename_recursive($value, $old_key, $new_key, $child_key, $save_old);
                }
            }
        }
        else {
            if (array_key_exists($old_key, $data)) {
                $data[$new_key] = $data[$old_key];
                
                if ($save_old === true) {
                    $data[$old_key] = array();
                }
                else {
                    unset($data[$old_key]);
                }
            }
        
            foreach ($data as &$value) {
                if (is_array($value)) {
                    self::_array_key_rename_recursive($value, $old_key, $new_key);
                }
            }
        }
    }
    
    private static function _array_unset_recursive(&$data, $key)
    {
        if (array_key_exists($key, $data)) {
            unset($data[$key]);
        }
        
        foreach ($data as &$value) {
            if (is_array($value)) {
                self::_array_unset_recursive($value, $key);
            }
        }
    }
    
    private static function _constructDataObject($path, $data = null)
    {
        $parts  = explode('/', $path);
        $return = array();
        $parent = &$return;
        
        if (!is_null($data)) {
            $_parts = explode('/', $path);
            $last   = array_pop($_parts);
        }
        
        foreach ($parts as $part) {
            if (!isset($parent[$part])) {
                $parent[$part] = array();
            }
            elseif (!is_array($parent[$part])) {
                $parent[$part] = array();
            }

            if (!is_null($data)) {
                if ($part === $last) $parent[$part] = $data;
            }
            
            $parent = &$parent[$part];
        }
        
        return $return;
    }
    
    private static function _setData($key, $value = null)
    {
        if (is_array($key)) {
            self::$_data = $key;
        }
        else {
            self::$_data[$key] = $value;
        }
    }
    
    public static function addObject($key, $data = null)
    {
        if (empty($key)) return false;
        
        $_data       = self::_constructDataObject($key, $data);
        self::$_data = array_merge_recursive(self::$_data, $_data);
    }

    public static function destroyObject($key = '')
    {
        if (empty($key)) return true;
        
        $root   = explode('/', $key);
        $object = array_pop($root);
        
        self::_array_unset_recursive(self::$_data[$root[0]], $object);
    }
    
    public static function getObject($key = '', $index = null) 
    {
        if (empty($key)) return self::$_data;
        
        $pointer  = explode('/', $key);
        $data     = self::$_data;
        
        foreach ($pointer as $index => $key) {
            if (empty($key)) return null;
            
            if (is_array($data)) {
                if (!isset($data[$key])) return null;
                
                $data = $data[$key];
            }
            elseif ($data instanceof Proficio_Object) {
                $data = $data->getData($key);
            }
            else {
                return null;
            }
        }
        
        return $data;
    }

    public static function moveObjectChildren($old_key, $new_key, $child_key, $save_old = false)
    {
        if (empty($old_key)) return false;
        if (empty($new_key)) return false;
        if (empty($child_key)) return false;
        
        $old_root = explode('/', $old_key);
        $new_root = explode('/', $new_key);
        
        if ($old_root[0] !== $new_root[0]) return false;
        
        $old_object = array_pop($old_root);
        $new_object = array_pop($new_root);
        
        self::_array_key_rename_recursive(self::$_data[$old_root[0]], $old_object, $new_object, $child_key, $save_old);
    }
    
    public static function renameObjectKey($old_key, $new_key)
    {
        if (empty($old_key)) return false;
        if (empty($new_key)) return false;
        
        $old_root = explode('/', $old_key);
        $new_root = explode('/', $new_key);
        
        if ($old_root[0] !== $new_root[0]) return false;
        
        $old_object = array_pop($old_root);
        $new_object = array_pop($new_root);
        
        self::_array_key_rename_recursive(self::$_data[$old_root[0]], $old_object, $new_object);
    }
}
// }}}