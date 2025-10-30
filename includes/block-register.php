<?php

/**
 * block.php – Registrierung des Loop-Blocks und zusätzlicher Block-Style
 *
 * - Registriert den Block über block.json (mit dynamischem Render-Callback)
 */

defined('ABSPATH') || exit;

add_action('init', function () {

    register_block_type_from_metadata(__DIR__ . '/../', [
        'render_callback' => 'ud_loop_block_render',
    ]);
});
