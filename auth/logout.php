<?php
require_once __DIR__ . '/../helpers/session.php';

logout_user();
header('Location: ../index.php');
exit;
