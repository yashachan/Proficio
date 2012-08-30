<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * dAmn (deviantART Message Network) Independant Interface
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
 * @package  Damn
 * @author   Alan Brault <alan.brault@incruentatus.net>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @version  SVN: $Id: Parser.php 37 2010-02-27 17:07:35Z abrault $
 */

// {{{ Proficio_Damn_Helper_Parser
/**
 * dAmn (deviantART Message Network) Client Parser Helper
 *
 * @category Proficio
 * @package  Damn_Helper_Processor
 * @author   Alan Brault <alan.brault@incruentatus.net>
 */
final class Proficio_Damn_Helper_Parser
{
    // {{{ channel()
    /**
     * Fix the incoming channel pointer so that it can be
     * used by Proficio. This function stips both chat: and #
     * prefixes from the channel name.
     * 
     * @param  string $channel Raw channel pointer
     * @return mixed Clean channel pointer or null
     */
    public static function channel($channel)
    {
        if (substr($channel, 0, 5) === 'chat:')
            return str_replace('chat:', '', $channel);
            
        if (substr($channel, 0, 1) === '#')
            return str_replace('#', '', $channel);
            
        return null;
    }
    // }}}
    
    // {{{ parseTabLumps()
    /**
     * Translate the dAmn tablump packets into something 
     * a bit more useful that can be more easily parsed
     * and logged.
     * 
     * @param  string $text Incoming message packet
     * @return string Translated message packet
     */
    public static function parseTablumps($text)
    {
        $search[]        =  "/&emote\t([^\t])\t([0-9]+)\t([0-9]+)\t(.+)\t(.+)\t/U";
        $replace[]       =  ":\\1:";
        $search[]        =  "/&emote\t(.+)\t([0-9]+)\t([0-9]+)\t(.+)\t(.+)\t/U";
        $replace[]       =  "\\1";
        $search[]        =  "/&br\t/";
        $replace[]       =  "\n\t";
        $search[]        =  "/&(b|i|s|u|sub|sup|code|ul|ol|li|p|bcode)\t/";
        $replace[]       =  "<\\1>";
        $search[]        =  "/&\\/(b|i|s|u|sub|sup|code|ul|ol|li|p|bcode)\t/";
        $replace[]       =  "</\\1>";
        $search[]        =  "/&acro\t(.*)\t(.*)&\\/acro\t/U";
        $replace[]       =  "<acronym title=\"\\1\">\\2</acronym>";
        $search[]        =  "/&abbr\t(.*)\t(.*)&\\/abbr\t/U";
        $replace[]       =  "<abbr title=\"\\1\">\\2</abbr>";
        $search[]        =  "/&link\t([^\t]*)\t([^\t]*)\t&\t/U";
        $replace[]       =  "\\1 (\\2)";
        $search[]        =  "/&link\t([^\t]*)\t&\t/U";
        $replace[]       =  "\\1";
        $search[]        =  "/&a\t(.*)\t(.*)\t(.*)&\\/a\t/U";
        $replace[]       =  "<a href=\"\\1\" title=\"\\2\">\\3</a>";
        $search[]        =  "/&(iframe|embed)\t(.*)\t([0-9]*)\t([0-9]*)\t&\\/(iframe|embed)\t/U";
        $replace[]       =  "<\\1 src=\"\\2\" width=\"\\3\" height=\"\\4\" />";
        $search[]        =  "/&img\t(.*)\t([0-9]*)\t([0-9]*)\t/U";
        $replace[]       =  "<img src=\"\\1\" width=\"\\2\" height=\"\\3\" />";
        $search[]        =  "/&thumb\t([0-9]*)\t(.*)\t(.*)\t(.*)\t(.*)\t(.*)\t(.*)\t/U";
        $replace[]       =  ":thumb\\1:";
        $search[]        =  "/&dev\t([^\t])\t([^\t]+)\t/U";
        $replace[]       =  ":dev\\2:";
        $search[]        =  "/&avatar\t([^\t]+)\t[0-9]\t/";
        $replace[]       =  ":icon\\1:";
        $search[]        =  "/ width=\"\"/";
        $replace[]       =  '';
        $search[]        =  "/ height=\"\"/";
        $replace[]       =  '';
        $oldtext         =  '';

        while ($text != $oldtext) {
            $oldtext = $text;
            $text    = preg_replace($search, $replace, $text);
        }

        return $text;
    }
    // }}}
}
// }}}