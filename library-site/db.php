<?php
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = 'Karthi@123';
$DB_NAME = 'library_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_errno) {
  die('DB connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
