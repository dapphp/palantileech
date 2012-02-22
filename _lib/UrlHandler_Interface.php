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

interface Pal_UrlHandler_Interface
{	
    /**
     * The interface special URL handlers must implement
     * @param array $args
     */
    public function magic(PalAntiLeech &$pal, array $args = array());
}
