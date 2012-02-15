<?php

defined('PAL_PATH')
    || define('PAL_PATH', realpath(dirname(__FILE__)));

defined('PAL_LIB_PATH')
    || define('PAL_LIB_PATH', PAL_PATH . '/_lib');

require_once PAL_LIB_PATH . '/PalAntiLeech.php';

PalAntiLeech::run();
