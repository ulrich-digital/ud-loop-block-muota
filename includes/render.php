<?php

/**
 * Render Callback für den Loop-Block (SSR)
 *
 * Nutzt die Block-Attribute aus block.json:
 * - taxonomie: "projekt" | "post" | "erleben"
 * - vorschau: "ausfuhrlich" | "kompakt"
 * - loop: int
 * - nurVerknuepfteMagazinBeitraege: bool
 */

defined('ABSPATH') || exit;

/* ===============================================================
   Helper: Block-Baum prüfen (rekursiv) + Legacy-ACF lesen
   =============================================================== */
if (! function_exists('ud_has_block_recursive')) {
    function ud_has_block_recursive(array $blocks, string $name): bool {
        foreach ($blocks as $b) {
            if (($b['blockName'] ?? null) === $name) {
                return true;
            }
            if (! empty($b['innerBlocks']) && ud_has_block_recursive($b['innerBlocks'], $name)) {
                return true;
            }
        }
        return false;
    }
}

if (! function_exists('ud_find_related_project_in_blocks')) {
    // Legacy-Fallback für alten ACF-Block
    function ud_find_related_project_in_blocks(array $blocks) {
        foreach ($blocks as $b) {
            if (isset($b['blockName']) && $b['blockName'] === 'acf/projekt-zu-magazin') {
                return isset($b['attrs']['data']['projekt_wahlen'])
                    ? (int) $b['attrs']['data']['projekt_wahlen']
                    : null;
            }
            if (! empty($b['innerBlocks']) && is_array($b['innerBlocks'])) {
                $found = ud_find_related_project_in_blocks($b['innerBlocks']);
                if ($found) return (int) $found;
            }
        }
        return null;
    }
}

if (! function_exists('ud_get_current_project_context')) {
 
    function ud_get_current_project_context(int $post_id = 0) {
        if (! $post_id) return null;

        $post_type = get_post_type($post_id);
        if ($post_type === 'projekt') {
            return (int) $post_id;
        }

        // direkt aus Meta lesen, ohne Block-Check
        $from_meta = (int) get_post_meta($post_id, 'ud_projekt_verknuepfen', true);
        if ($from_meta > 0) {
            return $from_meta;
        }

        // Legacy-Fallback (ACF-Block)
        $content = (string) get_post_field('post_content', $post_id);
        $blocks  = parse_blocks($content);
        $legacy  = ud_find_related_project_in_blocks($blocks);

        return $legacy ?: null;
    }
}

/* ===============================================================
   Einzelpost-Renderer
   =============================================================== */
if (! function_exists('ud_render_post_item')) :
    function ud_render_post_item(int $post_id, string $art_des_posts, string $art_der_darstellung, bool $is_featured = false) {
        $featured_class      = $is_featured ? ' is-featured' : '';
        $is_featured_magazin = ($art_des_posts === 'post') && $is_featured;
        $permalink           = get_permalink($post_id);

        $projekt_id_des_externen_posts = null;
        if ($art_des_posts === 'post') {
            $blocks = parse_blocks((string) get_post_field('post_content', $post_id));
            $projekt_id_des_externen_posts = ud_find_related_project_in_blocks($blocks);
        }
?>
        <div class="post_item post-id-<?php echo esc_attr($post_id); ?><?php echo esc_attr($featured_class); ?>">
            <div class="post_content">
                <?php
                if ($art_des_posts === 'post') {
                    if ($is_featured_magazin) {
                        echo '<time>' . esc_html(get_the_date('j. F Y', $post_id)) . '</time>';
                    } elseif ($art_der_darstellung === 'ausfuhrlich' && ! is_singular('projekt') && $projekt_id_des_externen_posts) {
                        echo '<a class="projekt_link" href="' . esc_url(get_the_permalink($projekt_id_des_externen_posts)) . '">'
                            . esc_html(get_the_title($projekt_id_des_externen_posts)) . '</a>';
                    } elseif ($art_der_darstellung === 'ausfuhrlich' && is_singular('projekt')) {
                        echo '<time>' . esc_html(get_the_date('j. F Y', $post_id)) . '</time>';
                    }
                }
                ?>
                <h2 class="post_title"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a></h2>
                <?php if ($art_der_darstellung === 'ausfuhrlich' && $art_des_posts !== 'erleben') : ?>
                    <?php $ex = get_the_excerpt($post_id); ?>
                    <?php if ($ex !== '') : ?>
                        <p class="post_excerpt"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($ex); ?></a></p>
                    <?php endif; ?>
                <?php endif; ?>
                <?php
                if ($art_des_posts === 'post' && ! $is_featured_magazin && $art_der_darstellung === 'kompakt') {
                    echo '<time>' . esc_html(get_the_date('j. F Y', $post_id)) . '</time>';
                }
                ?>
            </div>

            <?php if ($art_des_posts === 'post') : ?>
                <div>&nbsp;</div>
            <?php endif; ?>

            <?php if (($art_des_posts === 'projekt' || $art_des_posts === 'erleben') && $art_der_darstellung === 'ausfuhrlich') : ?>
                <?php if (has_post_thumbnail($post_id)) : ?>
                    <div class="post_image_container">
                        <div class="post_thumbnail">
                            <a href="<?php echo esc_url($permalink); ?>">
                                <?php echo get_the_post_thumbnail($post_id, 'medium'); ?>
                            </a>
                        </div>
                        <svg class="post_overlay" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 409.584 111.466" preserveAspectRatio="none">
                            <path d="M0,0V111.466C169.415,95.866,219.838-22.334,409.584,18.487V0Z" />
                        </svg>
                    </div>
                <?php else : ?>
                    <div>&nbsp;</div>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            $button_class = 'wave_button_only_text';
            if ($is_featured_magazin || $art_der_darstellung === 'ausfuhrlich') {
                $button_class = 'wave_button';
            }
            ?>
            <div class="<?php echo esc_attr($button_class); ?>">
                <?php if ($button_class === 'wave_button_only_text') : ?>
                    <a href="<?php echo esc_url($permalink); ?>" class="post_link">
                        <i class="fa-sharp fa-regular fa-arrow-down-right arrow_before_text"></i>
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url($permalink); ?>" class="post_link">
                        <i class="fa-sharp fa-regular fa-arrow-down-right arrow_before_text"></i> MEHR
                    </a>
                <?php endif; ?>

                <?php
                $show_overlay_bottom = false;
                if ($art_des_posts === 'erleben' || $art_des_posts === 'projekt') {
                    $show_overlay_bottom = true;
                } elseif ($art_des_posts === 'post') {
                    $show_overlay_bottom = true;
                }
                if ($show_overlay_bottom) : ?>
                    <svg class="overlay_bottom" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 293.702 77.466" preserveAspectRatio="none">
                        <path class="button_wave" d="M0,77.4c38.3-2.6,73.7-6.1,129.2-35C169.1,21.7,198.4,0,249.4,0c14.9,0,29.7,1.5,44.3,4.8v72.7H0Z" />
                    </svg>
                <?php endif; ?>
            </div>
        </div>
<?php
    }
endif;

/* ===============================================================
   Soll Post gerendert werden?
   =============================================================== */
if (! function_exists('ud_should_render_post')) {
    function ud_should_render_post(int $post_id, string $art_des_posts, ?int $projekt_id_der_aufrufenden_seite, array $attributes): bool {

        // aktuellen Beitrag nicht doppelt ausgeben
        if (get_the_ID() && (int) $post_id === (int) get_the_ID()) {
            return false;
        }

        if ($art_des_posts === 'post') {
            // Nur-verknüpfte-Modus aktiv?
            if (! empty($attributes['nurVerknuepfteMagazinBeitraege']) && $projekt_id_der_aufrufenden_seite) {
                $linked = (int) get_post_meta($post_id, 'ud_projekt_verknuepfen', true); // EIN Wert
                return $linked === (int) $projekt_id_der_aufrufenden_seite;
            }

            return true;
        }

        // Projekte/Erleben immer rendern (abgesehen vom Self-Post oben)
        return true;
    }
}

/* ===============================================================
   Haupt-Renderer
   =============================================================== */
/* ===============================================================
   Haupt-Renderer
   =============================================================== */
if (! function_exists('ud_loop_block_render')) {
    function ud_loop_block_render($attributes, $content, $block) {


        // =========================
        // Attribute sicher auslesen
        // =========================
        $tax_allowed  = ['projekt', 'post', 'erleben'];
        $view_allowed = ['ausfuhrlich', 'kompakt'];

        $art_des_posts       = in_array($attributes['taxonomie'] ?? 'projekt', $tax_allowed, true) ? $attributes['taxonomie'] : 'projekt';
        $art_der_darstellung = in_array($attributes['vorschau'] ?? 'ausfuhrlich', $view_allowed, true) ? $attributes['vorschau'] : 'ausfuhrlich';
        $anz_posts           = (isset($attributes['loop']) && is_numeric($attributes['loop']) && (int) $attributes['loop'] > 0) ? (int) $attributes['loop'] : 6;

        // Switch robust auswerten
        $nur_verknuepfte_raw = $attributes['nurVerknuepfteMagazinBeitraege'] ?? false;
        $nur_verknuepfte     = ($nur_verknuepfte_raw === true || $nur_verknuepfte_raw === 1 || $nur_verknuepfte_raw === '1');

        $title = isset($attributes['title']) ? trim($attributes['title']) : '';

        // CSS-Mapping
        $classname = match ($art_des_posts) {
            'post'    => 'magazin',
            'erleben' => 'erleben',
            default   => 'projekte',
        };

        // =========================================
        // Kontext: aktuelle Seite / Projektbezug
        // =========================================
        $current_post_id = isset($block->context['postId']) ? (int) $block->context['postId'] : (int) get_the_ID();
        $current_post_type = $current_post_id ? get_post_type($current_post_id) : null;

        $projekt_id_der_aufrufenden_seite = $current_post_id ? ud_get_current_project_context($current_post_id) : null;

        // =========================
        // Queries vorbereiten
        // =========================
        $query_sticky     = null;
        $query_non_sticky = null;


        if ($art_des_posts === 'projekt') {
            // Sticky-Projekte
            $args_sticky = [
                'post_type'      => 'projekt',
                'posts_per_page' => $anz_posts,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'meta_key'       => '_oben_gehalten',
                'meta_value'     => '1',
            ];
            $query_sticky = new WP_Query($args_sticky);

            // Non-Sticky-Projekte
            $args_non_sticky = [
                'post_type'      => 'projekt',
                'posts_per_page' => $anz_posts,
                'meta_query'     => [
                    'relation' => 'OR',
                    ['key' => '_oben_gehalten', 'compare' => 'NOT EXISTS'],
                    ['key' => '_oben_gehalten', 'value' => '1', 'compare' => '!='],
                ],
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];
            $query_non_sticky = new WP_Query($args_non_sticky);
        } elseif ($art_des_posts === 'post') {
            // Magazin (Posts)
            $args_non_sticky = [
                'post_type'      => 'post',
                'posts_per_page' => $anz_posts,
                'post__not_in'   => get_option('sticky_posts'),
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];

            // Nur verknüpfte Beiträge filtern
            if ($nur_verknuepfte) {
                if ($projekt_id_der_aufrufenden_seite) {
                    // Projekt-Kontext vorhanden → filtern
                    $args_non_sticky['meta_query'] = [
                        [
                            'key'     => 'ud_projekt_verknuepfen',
                            'value'   => (int) $projekt_id_der_aufrufenden_seite,
                            'compare' => '=',
                            'type'    => 'NUMERIC',
                        ],
                    ];
                } elseif (is_admin()) {
                    // Hinweis nur im Editor, wenn Filter aktiv aber kein Kontext
                    return '<div class="components-notice is-warning">
                        <p>' . esc_html__('⚠ Filter aktiv, aber kein Projekt-Kontext vorhanden. 
                        Im Frontend auf Beitragsseiten wird korrekt gefiltert.', 'ud-loop-block') . '</p>
                    </div>';
                }
            }

            $query_non_sticky = new WP_Query($args_non_sticky);
        } elseif ($art_des_posts === 'erleben') {
            // "Erleben" = Seiten mit Template
            $args_non_sticky = [
                'post_type'      => 'page',
                'posts_per_page' => $anz_posts,
                'meta_query'     => [
                    [
                        'key'     => '_wp_page_template',
                        'value'   => 'wp-custom-template-muota-erleben',
                        'compare' => '=',
                    ],
                ],
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];
            $query_non_sticky = new WP_Query($args_non_sticky);
        }

        // Posts zusammenstellen
        if ($art_des_posts === 'projekt') {
            $posts = array_slice(
                array_merge(
                    $query_sticky ? $query_sticky->posts : [],
                    $query_non_sticky ? $query_non_sticky->posts : []
                ),
                0,
                $anz_posts
            );
        } else {
            $posts = $query_non_sticky ? array_slice($query_non_sticky->posts, 0, $anz_posts) : [];
        }

        // =========================
        // Ausgabe
        // =========================
        ob_start();

        // Titel nur im Frontend ausgeben
        if (! is_admin() && $title !== '') {
            echo '<h2 class="ud-loop-block-title above_loop">' . esc_html($title) . '</h2>';
        }

        // Sticky-Ausgabe (nur Magazin-Ansicht)
        if ($art_des_posts === 'post') {
            $sticky_ids = get_option('sticky_posts');

            if (! empty($sticky_ids) && is_array($sticky_ids)) {
                $sticky_output = '';
                ob_start();
                foreach ($sticky_ids as $spid) {
                    if (ud_should_render_post((int) $spid, $art_des_posts, $projekt_id_der_aufrufenden_seite, $attributes)) {
                        ud_render_post_item((int) $spid, $art_des_posts, $art_der_darstellung, true);
                    }
                }
                $sticky_output = ob_get_clean();

                if ($sticky_output !== '') {
                    echo '<div class="post_loop featured ' . esc_attr($classname . ' ' . $art_der_darstellung) . '">';
                    echo $sticky_output;
                    echo '</div>';
                }
            }
        }

        // Reguläre Ausgabe
        $regular_output = '';
        ob_start();

        foreach ($posts as $p) {
            $pid = (int) $p->ID;
            if (ud_should_render_post($pid, $art_des_posts, $projekt_id_der_aufrufenden_seite, $attributes)) {
                $is_featured = ($art_des_posts === 'projekt') && (get_post_meta($pid, '_oben_gehalten', true) === '1');
                ud_render_post_item($pid, $art_des_posts, $art_der_darstellung, $is_featured);
            }
        }
        $regular_output = ob_get_clean();

        if ($regular_output !== '') {
            echo '<div class="post_loop ' . esc_attr($classname . ' ' . $art_der_darstellung) . '">';
            echo $regular_output;
            echo '</div>';
        } else {
            echo '<div class="hidden_on_frontend">Keine Beiträge vorhanden</div>';
        }

        wp_reset_postdata();
        return ob_get_clean();
    }
}


/*
if (! function_exists('ud_loop_block_render')) {
    function ud_loop_block_render($attributes, $content, $block) {

        // =========================
        // Attribute sicher auslesen
        // =========================
        $tax_allowed  = ['projekt', 'post', 'erleben'];
        $view_allowed = ['ausfuhrlich', 'kompakt'];

        $art_des_posts       = in_array($attributes['taxonomie'] ?? 'projekt', $tax_allowed, true) ? $attributes['taxonomie'] : 'projekt';
        $art_der_darstellung = in_array($attributes['vorschau'] ?? 'ausfuhrlich', $view_allowed, true) ? $attributes['vorschau'] : 'ausfuhrlich';
        $anz_posts           = (isset($attributes['loop']) && is_numeric($attributes['loop']) && (int) $attributes['loop'] > 0) ? (int) $attributes['loop'] : 6;

        // Switch robust auswerten
        $nur_verknuepfte_raw = $attributes['nurVerknuepfteMagazinBeitraege'] ?? false;
        $nur_verknuepfte     = ($nur_verknuepfte_raw === true || $nur_verknuepfte_raw === 1 || $nur_verknuepfte_raw === '1');

        $title = isset($attributes['title']) ? trim($attributes['title']) : '';

        // CSS-Mapping
        $classname = match ($art_des_posts) {
            'post'    => 'magazin',
            'erleben' => 'erleben',
            default   => 'projekte',
        };

        // =========================================
        // Kontext: aktuelle Seite / Projektbezug
        // =========================================
        $current_post_id = isset($block->context['postId']) ? (int) $block->context['postId'] : (int) get_the_ID();
        $current_post_type = $current_post_id ? get_post_type($current_post_id) : null;

        $projekt_id_der_aufrufenden_seite = $current_post_id ? ud_get_current_project_context($current_post_id) : null;

        // =========================
        // Queries vorbereiten
        // =========================
        $query_sticky     = null;
        $query_non_sticky = null;

        if ($art_des_posts === 'projekt') {
            // Sticky-Projekte
            $args_sticky = [
                'post_type'      => 'projekt',
                'posts_per_page' => $anz_posts,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'meta_key'       => '_oben_gehalten',
                'meta_value'     => '1',
            ];
            $query_sticky = new WP_Query($args_sticky);

            // Non-Sticky-Projekte
            $args_non_sticky = [
                'post_type'      => 'projekt',
                'posts_per_page' => $anz_posts,
                'meta_query'     => [
                    'relation' => 'OR',
                    ['key' => '_oben_gehalten', 'compare' => 'NOT EXISTS'],
                    ['key' => '_oben_gehalten', 'value' => '1', 'compare' => '!='],
                ],
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];
            $query_non_sticky = new WP_Query($args_non_sticky);
        } elseif ($art_des_posts === 'post') {

            // Magazin (Posts)
            $args_non_sticky = [
                'post_type'      => 'post',
                'posts_per_page' => $anz_posts,
                'post__not_in'   => get_option('sticky_posts'),
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];

            // Nur verknüpfte Beiträge filtern
            if ($nur_verknuepfte && $projekt_id_der_aufrufenden_seite) {
                $args_non_sticky['meta_query'] = [
                    [
                        'key'     => 'ud_projekt_verknuepfen',
                        'value'   => (int) $projekt_id_der_aufrufenden_seite,
                        'compare' => '=',
                        'type'    => 'NUMERIC',
                    ],
                ];
            }

            $query_non_sticky = new WP_Query($args_non_sticky);
        } elseif ($art_des_posts === 'erleben') {
            // "Erleben" = Seiten mit Template
            $args_non_sticky = [
                'post_type'      => 'page',
                'posts_per_page' => $anz_posts,
                'meta_query'     => [
                    [
                        'key'     => '_wp_page_template',
                        'value'   => 'wp-custom-template-muota-erleben',
                        'compare' => '=',
                    ],
                ],
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];
            $query_non_sticky = new WP_Query($args_non_sticky);
        }

        // Posts zusammenstellen
        if ($art_des_posts === 'projekt') {
            $posts = array_slice(
                array_merge(
                    $query_sticky ? $query_sticky->posts : [],
                    $query_non_sticky ? $query_non_sticky->posts : []
                ),
                0,
                $anz_posts
            );
        } else {
            $posts = $query_non_sticky ? array_slice($query_non_sticky->posts, 0, $anz_posts) : [];
        }

        // =========================
        // Ausgabe
        // =========================
        ob_start();
        // Titel nur im Frontend ausgeben
        if (! is_admin() && $title !== '') {
            echo '<h2 class="ud-loop-block-title above_loop">' . esc_html($title) . '</h2>';
        }
        // Sticky-Ausgabe (nur Magazin-Ansicht)
        if ($art_des_posts === 'post') {
            $sticky_ids = get_option('sticky_posts');

            if (! empty($sticky_ids) && is_array($sticky_ids)) {
                $sticky_output = '';
                ob_start();
                foreach ($sticky_ids as $spid) {
                    if (ud_should_render_post((int) $spid, $art_des_posts, $projekt_id_der_aufrufenden_seite, $attributes)) {
                        ud_render_post_item((int) $spid, $art_des_posts, $art_der_darstellung, true);
                    }
                }
                $sticky_output = ob_get_clean();

                if ($sticky_output !== '') {
                    echo '<div class="post_loop featured ' . esc_attr($classname . ' ' . $art_der_darstellung) . '">';
                    echo $sticky_output;
                    echo '</div>';
                }
            }
        }

        // Reguläre Ausgabe
        $regular_output = '';
        ob_start();




        foreach ($posts as $p) {
            $pid = (int) $p->ID;
            if (ud_should_render_post($pid, $art_des_posts, $projekt_id_der_aufrufenden_seite, $attributes)) {
                $is_featured = ($art_des_posts === 'projekt') && (get_post_meta($pid, '_oben_gehalten', true) === '1');
                ud_render_post_item($pid, $art_des_posts, $art_der_darstellung, $is_featured);
            }
        }
        $regular_output = ob_get_clean();

        if ($regular_output !== '') {

            echo '<div class="post_loop ' . esc_attr($classname . ' ' . $art_der_darstellung) . '">';
            echo $regular_output;
            echo '</div>';
        } else {
            echo '<div class="hidden_on_frontend">Keine Beiträge vorhanden</div>';
        }

        wp_reset_postdata();
        return ob_get_clean();
    }
}
*/