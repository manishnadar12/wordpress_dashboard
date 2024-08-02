<?php
/*-------------------------------------------------------
 * Divi Child Theme Functions.php
------------------ ADD YOUR PHP HERE ------------------*/
 
function divichild_enqueue_styles() {
  
 $parent_style = 'parent-style';
  
 wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
 wp_enqueue_style( 'child-style',
 get_stylesheet_directory_uri() . '/style.css',
 array( $parent_style ),
 wp_get_theme()->get('Version')
 );
}
add_action( 'wp_enqueue_scripts', 'divichild_enqueue_styles' );

// require_once('custom_development_functions.php');

// Location 
function location_post_type() {

    // Set UI labels for Custom Post Type
        $labels = array(
            'name'                => _x( 'Locations', 'Post Type General Name', 'divi' ),
            'singular_name'       => _x( 'Location', 'Post Type Singular Name', 'divi' ),
            'menu_name'           => __( 'Locations', 'divi' ),
            'parent_item_colon'   => __( 'Parent Location', 'divi' ),
            'all_items'           => __( 'All Locations', 'divi' ),
            'view_item'           => __( 'View Location', 'divi' ),
            'add_new_item'        => __( 'Add New Location', 'divi' ),
            'add_new'             => __( 'Add New', 'divi' ),
            'edit_item'           => __( 'Edit Location', 'divi' ),
            'update_item'         => __( 'Update Location', 'divi' ),
            'search_items'        => __( 'Search Location', 'divi' ),
            'not_found'           => __( 'Not Found', 'divi' ),
            'not_found_in_trash'  => __( 'Not found in Trash', 'divi' ),
        );
         
    // Set other options for Custom Post Type
         
        $args = array(
            'label'               => __( 'Locations', 'divi' ),
            'description'         => __( 'Location Page', 'divi' ),
            'labels'              => $labels,
            'supports'            => array( 'title', 'editor'  ),
            'taxonomies'          => array( 'genres' ),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 5,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'show_in_rest' => true,
     
        );
         
        // Registering your Custom Post Type
        register_post_type( 'location', $args );
     
    }
    
    add_action( 'init', 'location_post_type', 0 );


function my_enqueue_location_features( $hook_suffix ){

    $posts = get_posts( array (  
        'numberposts' => -1,
        'post_type'   => 'location'
    ) );
    
    foreach ( $posts as $post ) {
    
        $new_slug = sanitize_title( $post->post_title );
        if ( $post->post_name != $new_slug )
        {
            wp_update_post(
                array (
                    'ID'        => $post->ID,
                    'post_name' => $new_slug
                )
            );
        }
    }
    }
    add_action( 'admin_enqueue_scripts', 'my_enqueue_location_features');
    

//Allow Shortcode in Title
add_filter( 'the_title', 'do_shortcode' );
