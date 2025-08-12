<?php
/**
 * Plugin Name: WPGraphQL Elementor Support Extended
 * Description: Расширенная поддержка контента Elementor для REST и GraphQL.
 * Version: 1.0
*/

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Получаем HTML Elementor
*/
function get_elementor_page_html( $post_id ) {
    if ( ! $post_id || ! class_exists( '\Elementor\Plugin' ) ) {
        return new WP_Error( 'no_page', 'Страница не найдена', [ 'status' => 404 ] );
    }

    global $post;
    $post = get_post( $post_id );

    if ( ! $post || $post->post_status !== 'publish' ) {
        return new WP_Error( 'no_page', 'Страница не найдена или не опубликована', [ 'status' => 404 ] );
    }

    setup_postdata( $post );

    wp_enqueue_script( 'jquery' );
    if ( method_exists( \Elementor\Plugin::$instance->frontend, 'enqueue_styles' ) ) {
        \Elementor\Plugin::$instance->frontend->enqueue_styles();
    }
    if ( method_exists( \Elementor\Plugin::$instance->frontend, 'enqueue_scripts' ) ) {
        \Elementor\Plugin::$instance->frontend->enqueue_scripts();
    }

    if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
        if ( wp_style_is( 'elementor-pro-frontend', 'registered' ) ) {
            wp_enqueue_style( 'elementor-pro-frontend' );
        }
        if ( wp_script_is( 'elementor-pro-frontend', 'registered' ) ) {
            wp_enqueue_script( 'elementor-pro-frontend' );
        }
    }

    wp_enqueue_script( 'imagesloaded' );
    wp_enqueue_script( 'swiper' );
    wp_enqueue_script( 'jquery-waypoints' );

    ob_start();

    echo '<!DOCTYPE html><html ';
    language_attributes();
    echo '><head>';
    wp_head();
    echo '</head><body ';
    body_class();
    echo '>';

    if ( \Elementor\Plugin::$instance->documents->get( $post_id )->is_built_with_elementor() ) {
        echo \Elementor\Plugin::$instance->frontend->get_builder_content( $post_id, true );
    } else {
        echo apply_filters( 'the_content', $post->post_content );
    }

    if ( method_exists( \Elementor\Plugin::$instance->frontend, 'get_settings' ) ) {
        $frontend_settings = \Elementor\Plugin::$instance->frontend->get_settings();
        echo "<script>var elementorFrontendConfig = " . wp_json_encode( $frontend_settings ) . ";</script>";
    }

    wp_footer();
    echo '</body></html>';

    $html = ob_get_clean();
    wp_reset_postdata();

    $kit_id     = get_option('elementor_active_kit');
    $body_class = $kit_id ? "elementor-kit-" . intval($kit_id) : "";

    return [
        'head'      => get_between_tags( $html, '<head>', '</head>' ),
        'body'      => get_between_tags( $html, '<body>', '</body>' ),
        'footer'    => get_after_tag( $html, '</body>' ),
        'full'      => $html,
        'bodyClass' => $body_class,
        'seo'       => [
            'title'       => get_the_title( $post_id ),
            'description' => get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ) ?: '',
            'og_image'    => get_the_post_thumbnail_url( $post_id, 'full' ) ?: '',
            'permalink'   => get_permalink( $post_id ),
        ]
    ];
}

/**
 * Вспомогательные функции
*/
function get_between_tags( $html, $start, $end ) {
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    if ($start === '<body>' && $end === '</body>') {
        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body) {
            $innerHTML = '';
            foreach ($body->childNodes as $child) {
                $innerHTML .= $doc->saveHTML($child);
            }
            return $innerHTML;
        }
    }

    if ($start === '<head>' && $end === '</head>') {
        $head = $doc->getElementsByTagName('head')->item(0);
        if ($head) {
            $innerHTML = '';
            foreach ($head->childNodes as $child) {
                $innerHTML .= $doc->saveHTML($child);
            }
            return $innerHTML;
        }
    }

    return '';
}

function get_after_tag( $string, $tag ) {
    $pos = strrpos( $string, $tag );
    if ( $pos === false ) return "";
    return substr( $string, $pos + strlen( $tag ) );
}

function get_elementor_template_content( $post_id ) {
    if ( ! $post_id || ! class_exists( '\Elementor\Plugin' ) ) {
        return new WP_Error( 'no_page', 'Страница не найдена', [ 'status' => 404 ] );
    }

    $document = \Elementor\Plugin::$instance->documents->get( $post_id );
    if ( ! $document || ! $document->is_built_with_elementor() ) {
        return new WP_Error( 'no_elementor', 'Шаблон не построен с Elementor', [ 'status' => 404 ] );
    }

    // Возвращаем чистый HTML контент (без enqueue и wp_head/wp_footer)
    return \Elementor\Plugin::$instance->frontend->get_builder_content( $post_id, true );
}


/**
 * Получаем ID Elementor-шаблона по slug
*/
function get_elementor_template_id_by_slug( $slug ) {
    if ( empty( $slug ) ) {
        return null;
    }

    $post = get_page_by_path( $slug, OBJECT, ['hfe_template', 'elementor-hf'] );
    return $post ? $post->ID : null;
}


/**
 * Регистрируем REST API
*/
add_action( 'rest_api_init', function() {
    $header_slug = 'header';
    $footer_slug = 'footer';


    // Страница по slug
    register_rest_route( 'elementor/v1', '/page/(?P<slug>[a-zA-Z0-9-]+)', [
        'methods'  => 'GET',
        'callback' => function( $request ) {
            $slug = sanitize_title( $request['slug'] );
            $post = get_page_by_path( $slug, OBJECT, 'page' );
            if (!$post) {
                return new WP_Error( 'no_page', 'Страница не найдена', [ 'status' => 404 ] );
            }
            return get_elementor_page_html( $post->ID );
        },
        'permission_callback' => '__return_true'
    ]);

    // Header
    register_rest_route( 'elementor/v1', '/header', [
        'methods'  => 'GET',
        'callback' => function() use ( $header_slug ) {
            $id = get_elementor_template_id_by_slug( $header_slug );
            if ( ! $id ) {
                return new WP_Error( 'no_header', 'Хедер не найден', [ 'status' => 404 ] );
            }
            return get_elementor_template_content( $id );
        },
        'permission_callback' => '__return_true'
    ]);

    // Footer
    register_rest_route( 'elementor/v1', '/footer', [
        'methods'  => 'GET',
        'callback' => function() use ( $footer_slug ) {
            $id = get_elementor_template_id_by_slug( $footer_slug );
            if ( ! $id ) {
                return new WP_Error( 'no_footer', 'Футер не найден', [ 'status' => 404 ] );
            }
            return get_elementor_template_content( $id );
        },
        'permission_callback' => '__return_true'
    ]);
});

/**
 * GraphQL: добавляем поле elementorContent
*/
add_action( 'graphql_register_types', function() {
    register_graphql_object_type( 'ElementorPageSeo', [
        'fields' => [
            'title'       => [ 'type' => 'String' ],
            'description' => [ 'type' => 'String' ],
            'og_image'    => [ 'type' => 'String' ],
            'permalink'   => [ 'type' => 'String' ],
        ]
    ]);

    register_graphql_object_type( 'ElementorPageContent', [
        'fields' => [
            'head'      => [ 'type' => 'String' ],
            'body'      => [ 'type' => 'String' ],
            'footer'    => [ 'type' => 'String' ],
            'bodyClass' => [ 'type' => 'String' ],
            'seo'       => [ 'type' => 'ElementorPageSeo' ],
        ]
    ]);

    register_graphql_field( 'Page', 'elementorContent', [
        'type' => 'ElementorPageContent',
        'resolve' => function( $page ) {
            $post_id = $page->databaseId ?? null;
            if ( ! $post_id ) return null;

            $data = get_elementor_page_html( $post_id );
            if ( is_wp_error( $data ) ) return null;

            return [
                'head'      => $data['head'] ?? '',
                'body'      => $data['body'] ?? '',
                'footer'    => $data['footer'] ?? '',
                'bodyClass' => $data['bodyClass'] ?? '',
                'seo'       => $data['seo'] ?? null,
            ];
        }
    ]);
});
