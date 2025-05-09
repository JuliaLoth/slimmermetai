<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Infrastructure\Database\Database;
use App\Infrastructure\Repository\UserRepository;
use App\Application\Service\UserService;
use App\Http\Controller\UserController;

$db = Database::getInstance();
$userRepo = new UserRepository($db);
$userService = new UserService($userRepo);
$controller = new UserController($userService);
$controller->register(); 