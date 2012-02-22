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

class Pal_UrlHandler_Captcha implements Pal_UrlHandler_Interface
{
    public function magic(PalAntiLeech &$pal, array $args = array())
    {
        require_once PAL_LIB_PATH . '/securimage/securimage.php';
        
        if (!isset($args['r'])) {
            return 0;
        }
        
        $id = $args['r'];
        
        $img = new Securimage(array('nosession' => true,
                                    'captchaId' => $id,
                                    'use_sqlite_db' => true));

        $img->perturbation = 0.87;
        $img->line_color = new Securimage_Color('#000');
        $img->noise_color = $img->text_color = $img->line_color;
        $img->image_width = 150;
        $img->image_height = 55;
        $img->noise_level = rand(1,4);
        $img->num_lines = rand(1,4);
        $img->code_length = (isset($pal->getConfig()->captchaCodeLength)) ?
                             $pal->getConfig()->captchaCodeLength         :
                             5;
        
        $img->charset = (isset($pal->getConfig()->captchaCharset) &&
        		         strlen($pal->getConfig()->captchaCharset) > 0) ?
        		         $pal->getConfig()->captchaCharset              :
        		         'ABCDEFGHKMNPQRSTUVWXYZabcdefghkmnpqrstuvwxyz23456789';
        
        $img->show();
        
        return 1;
    }
}
