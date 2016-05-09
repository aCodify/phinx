<?php
/* Phinx
 *
 * (The MIT license)
 * Copyright (c) 2014 Rob Morgan
 * Copyright (c) 2014 Woody Gilk
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated * documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

// This script can be run as a router with the built in PHP web server:
//
//   php -S localhost:8000 app/web.php
//
// Or can be run from any other web server with:
//
//   require 'phinx/app/web.php';
//
// This script uses the following query string arguments:
//
// - (string) "e" environment name
// - (string) "t" target version
// - (boolean) "debug" enable debugging?

// set show error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the phinx console application and inject it into TextWrapper.
if (!defined('PHINX_VERSION')) {
    define('PHINX_VERSION', (0 === strpos('@PHINX_VERSION@', '@PHINX_VERSION')) ? '0.3.6' : '@PHINX_VERSION@');
}
$files = array(
  $_SERVER["DOCUMENT_ROOT"] . '/vendor/autoload.php',
);

// Mapping of route names to commands.
$routes = [
    'status'   => 'getStatus',
    'migrate'  => 'getMigrate',
    'rollback' => 'getRollback',
    ];

// Extract the requested command from the URL, default to "status".
$command = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if (!$command) {
    $command = 'status';
}

// Verify that the command exists, or list available commands.
if (!isset($routes[$command])) {
    $commands = implode(', ', array_keys($routes));
    header('Content-Type: text/plain', true, 404);
    die("Command not found! Valid commands are: {$commands}.");
}


$found = false;
foreach ($files as $file) {
    if (file_exists($file)) {
        require $file;
        $found = true;
        break;
    }
}
if (!$found) {
    die(
      'You need to set up the project dependencies using the following commands:' . PHP_EOL .
      'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
      'php composer.phar install' . PHP_EOL
    );
}
$app = new Phinx\Console\PhinxApplication(PHINX_VERSION);
// enable running phinx from the web by injecting ArrayInput and StreamOutput
// run locally with: php -S localhost:8080 web.php
$stream = fopen('php://output', 'w');
fwrite($stream, '<pre>');
$output = new Symfony\Component\Console\Output\StreamOutput($stream);
$input = new Symfony\Component\Console\Input\ArrayInput([
    // first arg is the command
    $command,
    // other args are the options for the command
    '-e' => 'development',
]);
$app->run($input, $output);
fwrite($stream, '</pre>');
