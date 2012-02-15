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

class PalAntiLeech
{
    protected $_invalidFiles       = array();
    protected $_allowedReferrers   = array();
    protected $_allowEmptyReferrer = false;
    protected $_countDownloads     = true;
    protected $_requireCapthca     = false;
    
    protected static $_instance = null;
    protected $_view;
    
    protected $_filePath;
    protected $_fileUri;
    protected $_fileName;
    
    protected function __construct($options = null)
    {
    	// set incldue path, favor PAL_LIB_PATH (_lib)
        set_include_path(implode(PATH_SEPARATOR, array(
            realpath(PAL_LIB_PATH),
            get_include_path(),
        )));
        
        $this->init();
    }
    
    protected function init()
    {
        require_once 'Zend/Config/Ini.php';
        
        $config = array();
        
        try {
            $cfg    = dirname(__FILE__) . '/config.ini';
            $config = new Zend_Config_Ini($cfg);
            
            if (!isset($config->antileech)) {
                trigger_error("Config section [antileech] not found in $cfg", E_USER_WARNING);
            } else {
                $config = $config->antileech;
            }
            
        } catch (Zend_Config_Exception $cEx) {
            trigger_error("Failed to load config file $cfg", E_USER_WARNING);
        }
        
        if (isset($config->allowEmptyReferrer)) {
            $this->_allowEmptyReferrer = $config->allowEmptyReferrer;
        }
        
        if (isset($config->allowedReferrers)) {
            $this->_allowedReferrers = $config->allowedReferrers->toArray();
        }
        
        if (isset($config->countDownloads)) {
            $this->_countDownloads = $config->countDownloads;
        }
        
        // TODO: Captcha not yet implmented!
        if (isset($config->requireCaptcha)) {
            $this->_requireCapthca = $config->requireCaptcha;
        }
        
        require_once 'Zend/View.php';
        
        $this->_view = new Zend_View();
        $this->_view->setScriptPath(dirname(__FILE__) . '/templates');
        $this->_view->assign('basePath', dirname($_SERVER['SCRIPT_NAME']) . '/');
        
    }
    
    protected function __clone() {}
    
    public static function run()
    {
        $s = self::getInstance();
        
        if ($s->isHandledUrl()) {
            if ($s->dispatchUrlHandler() === 1) {
                exit;
            }
        }
        
        $download = $s->getRequestedFile();
        $s->_fileName = $download['name'];
        $s->_filePath = $download['path'];
        $s->_fileUri  = $download['uri'];

        if ($s->isSecurityViolation($download['uri'])) {
            die("Security violation");
        }
        
        if (!$s->isValidFile($download['path'])) {
            $s->serve404();
        }
        
        if (!$s->isAllowedReferrer()) {
            $s->serveAntiLeech();
        }
        
        $s->serveDownload($download);
    }
    
    /**
     * Get instance of the DlAntiLeecher object
     * 
     * @return PalAntiLeech
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    
    protected function serveAntiLeech()
    {
        $size = filesize($this->_filePath);
        
        $this->_view
             ->assign('fileName', $this->_fileName)
             ->assign('downloadUrl', $this->_fileUri)
             ->assign('fileSize', $size)
             ->assign('longFileSize', $this->getFileDisplaySize($size))
             ->assign('requireCaptcha', $this->_requireCapthca);
             
        if ($this->_requireCapthca) {
        	// TODO: implement
            $this->_view->assign('captchaHtml', 'captcha not supported');
        }
             
        echo $this->_view->render('header.phtml')
            .$this->_view->render('antileech.phtml')
            .$this->_view->render('footer.phtml');
            
        exit;
    }
    
    
    protected function serveDownload($download)
    {   
        $size = filesize($download['path']);
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream'); // TODO: mime types
        header('Content-Disposition: attachment; filename=' . $download['name']);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $size);
        ob_clean();
        flush();

        set_time_limit(0);
        
        $fp = fopen($download['path'], 'rb');
        while(!feof($fp)) {
        	// TODO: throttling/bandwidth limiter?
            echo fread($fp, 4096);
            flush();
        }
        
        include 'DownloadCounter.php';
        Pal_DownloadCounter::logDownload($download['uri']);
        
        exit;
    }
    
    protected function serve404()
    {
        if (php_sapi_name() == 'cgi-fcgi') {
            header('Status: 404 Not Found');
        } else {
            header('HTTP/1.0 404 Not Found');
        }
        
        $this->_view->file = $this->_fileUri;
        
        echo $this->_view->render('404.phtml');
        exit;
    }

    protected function getRequestedFile()
    {
        $file = array('path' => '',
                      'name' => '', 
                      'uri'  => '');
        
        $base_path = $_SERVER['DOCUMENT_ROOT'];
        $uri       = urldecode($_SERVER['REQUEST_URI']);
        $uri       = str_replace('?' . $_SERVER['QUERY_STRING'], '', $uri);
        
        $file['uri']  = $uri;
        $file['name'] = basename($uri);
        $file['path'] = $base_path . dirname($uri) . '/' . $file['name'];
        
        return $file;
    }
    
    protected function getHandlerFromUrl()
    {
        $qs   = $_SERVER['QUERY_STRING'];
        $base = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
        $uri  = str_replace($base, '', $_SERVER['REQUEST_URI']);
        $return = 0;
        
        if ($uri == '?' . $qs) {
            return $qs;
        } else {
            return false;
        }
    }
    
    protected function isHandledUrl()
    {
        return $this->getHandlerFromUrl() !== false;
    }
    
    protected function dispatchUrlHandler()
    {
        $name  = ucwords(str_replace('_', ' ', $this->getHandlerFromUrl()));
        $class = 'Pal_UrlHandler_' . $name;
            
        $file = PAL_PATH . '/_urlhandlers/' . $name . '.php';
            
        if (file_exists($file)) {
            require_once PAL_PATH . '/_lib/UrlHandler_Interface.php';
            require_once $file;
                
            if (class_exists($class)) {
                $url = new $class();
                if (!$url instanceof Pal_UrlHandler_Interface) {
                    trigger_error("$class does not implement Pal_UrlHandler_Interface", E_USER_NOTICE);
                } else {
                    $return = $url->magic();
                }
            }
        }
        
        return $return;
    }
    
    protected function isSecurityViolation($file)
    {
    	// disallow http(s) in filename/query string
        if (preg_match('#https?://#i', $file)) {
            return true;
        }
        
        // no directory traversal attempts
        if (strpos($file, '../') !== false) {
            return true;
        }
        
        return false;
    }
    
    protected function isValidFile($file)
    {
        if (!is_readable($file)) {
            return false;
        } else if (is_dir($file)) {
            return false;
        } else if ($file == __FILE__) {
            return false;
        } else if (preg_match('/_lib\//i', $file)) {
            return false;
        } else {
            foreach($this->_invalidFiles as $badFile) {
                if (preg_match('/' . preg_quote($badFile) . '$/i', $file)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    protected function isAllowedReferrer()
    {
        $ref = isset($_SERVER['HTTP_REFERER']) ?
               $_SERVER['HTTP_REFERER']        :
               '';
        
        if ($ref == '') {
            if ($this->_allowEmptyReferrer) {
                return true;
            } else {
                return false;
            }
        }

        if ( ($parts = parse_url($ref)) == false) {
            return false;
        }
        
        $refHost = @$parts['host'];
        if ($refHost == '') {
            return false;
        }
        
        $allowed = $this->_allowedReferrers;
        $allowed[] = $_SERVER['SERVER_NAME'];
        
        foreach ($allowed as $allow) {
            $allow = preg_quote($allow);
            
            if (preg_match('/' . $allow . '$/i', $refHost)) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function getFileDisplaySize($size)
    {
        if ($size < 1024) {
            return $size .' bytes';
        } elseif ($size < 1048576) {
            return round($size / 1024, 2) .' kB';
        } elseif ($size < 1073741824) {
            return round($size / 1048576, 2) . ' MB';
        } elseif ($size < 1099511627776) {
            return round($size / 1073741824, 2) . ' GB';
        } elseif ($size < 1125899906842624) {
            return round($size / 1099511627776, 2) .' TB';
        } elseif ($size < 1152921504606846976) {
            return round($size / 1125899906842624, 2) .' PB';
        } elseif ($size < 1180591620717411303424) {
            return round($size / 1152921504606846976, 2) .' EB';
        } elseif ($size < 1208925819614629174706176) {
            return round($size / 1180591620717411303424, 2) .' ZB';
        } else {
            return round($size / 1208925819614629174706176, 2) .' YB';
        }
    }
}
