<?php
session_start();
require_once __DIR__ . '/db.php';
db();
csrf_check();
