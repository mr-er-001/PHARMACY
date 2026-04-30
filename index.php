<?php
require_once __DIR__ . '/include/auth.php';

if (isAdmin()) {
    redirect(BASE_URL . '/admin/index.php');
} else {
    redirect(BASE_URL . '/staff/index.php');
}
