<?php

if (!defined('PHPUNIT_RUN')) {
	define('PHPUNIT_RUN', 1);
}
require_once __DIR__ . '/../../../tests/bootstrap.php';

\OC_App::loadApp('richdocuments');
OC_Hook::clear();
