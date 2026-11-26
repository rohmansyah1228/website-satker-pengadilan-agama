<?php

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.2'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    // Ditulis tanpa sintaks PHP baru agar PHP lama (5.x/7.x) tetap bisa menampilkan pesan ini.
    $message = 'Website ini membutuhkan PHP ' . $minPhpVersion . ' atau lebih baru. Versi PHP di server/komputer ini: ' . PHP_VERSION
        . '. Perbarui XAMPP (pilih PHP 8.2/8.3) atau ubah versi PHP di cPanel (MultiPHP Manager / Select PHP Version).'
        . "\n\nBila sebelumnya website berjalan normal (misalnya error ini muncul sesudah Update Website): buka cPanel > MultiPHP Manager,"
        . ' centang domain/subdomain website ini, lalu pilih lagi PHP 8.2 atau 8.3 dan klik Apply. Data website tidak terpengaruh.';

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// LOAD OUR PATHS CONFIG FILE
// This is the line that might need to be changed, depending on your folder structure.
require FCPATH . '../app/Config/Paths.php';
// ^^^ Change this line if you move your application folder

$paths = new Paths();

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
