<?php

/**
 * Plugin Name:     UD Block: Loop
 * Description:     Block zum Darstellen von Beiträgen, Veranstaltungen oder Unterseiten in flexiblen Loops.
 * Version:         1.2.1
 * Author:          ulrich.digital gmbh
 * Author URI:      https://ulrich.digital/
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     loop-block-ud
 */

defined('ABSPATH') || exit;

// Plugin-Funktionalitäten laden
foreach (
    [
        'helpers.php',
        'block-register.php',
        'enqueue.php',
        'render.php',
    ] as $file
) {
    $path = plugin_dir_path(__FILE__) . 'includes/' . $file;
    if (file_exists($path)) {
        require_once $path;
    } else {
        error_log("content-for-loop-block: Missing required file $file");
    }
}
