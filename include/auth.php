<?php
session_start();

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';

if (isLoggedIn()) {
    session_regenerate_id(true);
}
