<?php

defined('PAL_PATH')
    || define('PAL_PATH', realpath(dirname(__FILE__)));

defined('PAL_LIB_PATH')
    || define('PAL_LIB_PATH', PAL_PATH . '/_lib'); // Change this if you wish to move the _lib files out of the pal directory

require_once PAL_LIB_PATH . '/PalAntiLeech.php';

PalAntiLeech::run();
