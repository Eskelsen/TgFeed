<?php

# Dev Mode: Show Errors
ini_set('display_errors', DEV);
ini_set('display_startup_erros', DEV);
error_reporting(E_ALL);

# Erros Log Output
ini_set('log_errors', DEV);
ini_set('error_log', __DIR__  . '/errors.log');

