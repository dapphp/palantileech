<?php
/**
 * PAL (PHP Anti-Leech)
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available at this URL: https://github.com/dapphp/palantileech
 *
 * @package    palantileech
 * @copyright  Copyright (c) 2012 Drew Phillips (https://github.com/dapphp/palantileech)
 * @license    BSD License
 * @version    0.1-alpha
 */

class Pal_DownloadCounter
{
    const LOGTYPE_DOWNLOAD = 0;
    const LOGTYPE_FAILURE  = 1;
    
    protected static $logfile = './_includes/downloadcount.dat';
    
    public static function logDownload($filename)
    {
        self::logEntry($filename, self::LOGTYPE_DOWNLOAD);
    }
    
    public static function getDownloadCount($filename)
    {
        $key = base64_encode($filename);
        $data = @file_get_contents(self::$logfile);
        
        if ($data === false) {
            return 0;
        }
        
        $data = unserialize($data);
        
        if ($data === false) {
            return 0;
        }
        
        if (isset($data[$key])) {
            return $data[$key]['downloads'];
        } else {
            return 0;
        }
    }
    
    public static function logFailedDownload($filename)
    {
        self::logEntry($filename, self::LOGTYPE_FAILURE);
    }
    
    protected static function logEntry($filename, $type)
    {
        $size = @filesize(self::$logfile);
        
        if ($size === false || $size === 0) {
            $data = array();
        } else {
            $data = unserialize(file_get_contents(self::$logfile));
            if (!$data) {
                return false; // corrupted file?
            }
        }
        
        $fp = @fopen(self::$logfile, 'w+');
        if (!$fp) {
            return false;
        }
        
        flock($fp, LOCK_EX);
        
        $key = base64_encode($filename);
        
        if (!isset($data[$key])) {
            $data[$key] = array('downloads' => 0);
        }
        
        switch($type) {
            case self::LOGTYPE_DOWNLOAD:
                $data[$key]['downloads']++;
                break;
                
            case self::LOGTYPE_FAILURE:
                $data[$key]['failed']++;
                break;
        }
        
        fwrite($fp, serialize($data));
        
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
