<?php
require_once __DIR__ . '/config/database.php';
session_start();
$_SESSION['user'] = [
    'id' => 32,
    'name' => 'Akıllı Sporcu',
    'fitness_level' => 'intermediate',
    'email' => 'sporcu@test.com'
];
header("Location: /workout/dashboard");
