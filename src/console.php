#!/usr/bin/env php
<?php

// console.php

// This loads all your packages
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use App\Command\NatsListenCommand; // <-- Import your new command
use App\Command\NatsPublishCommand;
use Symfony\Component\Dotenv\Dotenv; // <-- IMPORT THIS

// --- ADD THESE LINES ---
// Load environment variables from .env file
(new Dotenv())->load(__DIR__.'/../.env');
// -----------------------

// 1. Create a new console application
$application = new Application();

// 2. Add your command(s)
$application->add(new NatsListenCommand());
$application->add(new NatsPublishCommand());
// ... you can add more commands here

// 3. Run the application
$application->run();