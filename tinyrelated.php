<?php
/*
Plugin Name: tinyRelated
Plugin URI: http://wordpress.org/plugins/tinyrelated/
Text Domain: tinyrelated
Domain Path: /languages
Description: A tiny and simple related posts plugin.
Author: Arūnas Liuiza
Version: 1.0.1
Author URI: http://klausk.aruno.lt/
License: GPL2

    Copyright 2014  Arūnas Liuiza  (email : klausk@aruno.lt)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// block direct access to plugin file
defined('ABSPATH') or die( __("No script kiddies please!", 'tinyrelated' ) );

// activation hook
register_activation_hook( __FILE__, array( 'tinyRelated', 'activate' ) );
// uninstall hook
register_uninstall_hook( __FILE__, array( 'tinyRelated', 'uninstall' ) );
// init tinyRelated
add_action( 'plugins_loaded', array( 'tinyRelated', 'init' ) );

// main plugin class
class tinyRelated {
  // options
  public static $options = array(
    'general_count'  => 5,
    'general_show'   => 'before',  // before | after | false 
    'general_widget' => true,
    'general_random' => true
  );

  public static function activate() {
    self::init_options();
  }

  public static function uninstall() {
    // delete plugin options
    delete_option( 'tinyrelated_options' );
    // delete plugin meta data from posts
    delete_metadata( 'post', null, '_tinyrelated_count', null, true );
    delete_metadata( 'post', null, '_tinyrelated_list',  null, true );
    delete_metadata( 'post', null, '_tinyrelated_hide',  null, true );
  }

  public static function init( ) {
    self::init_options();
    if ( is_admin() ) {
      require_once ( plugin_dir_path( __FILE__ ).'includes/options.php' );
      add_action( 'admin_menu', array( 'tinyRelated', 'init_settings' ) );
    }
    // metabox filters
    add_action( 'add_meta_boxes_post',  array( 'tinyRelated', 'add_metabox' ) ) ;
    add_action( 'save_post',            array( 'tinyRelated', 'save_metabox' ) );
    // add filter to automatically show related posts after post content
    add_filter( 'the_content', array( 'tinyRelated', 'append_list') );
    // a shortcode
    add_shortcode( 'tinyrelated', array( 'tinyRelated', 'shortcode' ) );
    // a widget
    if ( self::$options['general_widget'] ) {
      require_once ( plugin_dir_path( __FILE__ ).'includes/widget.php' );
      add_action( 'widgets_init', array( 'tinyRelated', 'widget' ) );
    }
    load_plugin_textdomain( 'tinyrelated', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
  }

  public static function init_settings() {
    $settings = array(
      'id'          => 'tinyrelated_options',
      'title'       => __( 'tinyRelated Options', 'tinyrelated' ),
      'menu_title'  => __( 'tinyRelated', 'tinyrelated' ),
      'fields'      => array(
        "general" => array(
          'title' => '',
          'callback' => '',
          'options' => array(
            'count' => array(
              'title'=> __( 'Number of related posts', 'tinyrelated' ),
              'callback' => 'number',
              'args' => array(
                'min'  => 1,
                'max'  => 10,
                'step' => 1, 
              )
            ),
            'show' => array(
              'title'=> __( 'List location', 'tinyrelated' ),
              'callback' => 'select',
              'args' => array(
                'values' => array(
                  'false'  => __( '- do not show automatically -', 'tinyrelated' ),
                  'before' => __( 'Before post content', 'tinyrelated' ),
                  'after'  => __( 'After post content', 'tinyrelated' ),
                )
              )
            ),
            'widget' => array(
              'title'=> __( 'Enable widget', 'tinyrelated' ),
              'callback' => 'checkbox',
            ),
            'random' => array(
              'title'=> __( 'Display random posts if not set', 'tinyrelated' ),
              'callback' => 'checkbox',
            ),
          )
        )
      )
    );
    tinyRelated_Options::init( $settings );
  }

  public static function init_options() {
    $options = get_option( 'tinyrelated_options' );
    if ( !$options ) {
      add_option( 'tinyrelated_options', self::$options );
    }
    self::$options = wp_parse_args( $options, self::$options );
  }

  // add list to the end of post_content
  public static function append_list( $content ) {
    if (is_singular('post')) {
      if ( self::$options['general_show'] ) {
        $post_id = get_the_id();
        if ( get_post_meta( $post_id, '_tinyrelated_hide') ) {
          return $content;
        }
        $related = self::build_list($post_id);
        switch ( self::$options['general_show'] ) {
          case 'after' :
            $content .= "\r\n".$related;
          break;
          case 'before' :
            $content = $related . "\r\n" . $content;
          break;
        }
      }
    }
    return $content;
  }  
  // build html for related post list
  public static function build_list($post, $options = array()) {
    if (is_numeric($post)) {
      $post = get_post($post);
    }
    $count = get_post_meta( $post->ID, '_tinyrelated_count', true );
    $count = $count > 0 ? $count : self::$options['general_count'];
    $defaults = array(
      /* translators: list default title */
      'title' => _x( 'Related posts', 'title', 'tinyrelated' ),
      'class' => 'tinyrelated',
      'count' => $count,
      'list'  => false 
    );
    $options = shortcode_atts( $defaults, $options );
    $options = apply_filters( 'tinyrelated_list_options', $options );
    if ( ! $options['list'] ) {
      $posts = tinyRelated::get_list( $post, $options );
    } else {
      $posts = $options['list'];
      if ( !is_array( $posts ) ) {
        $posts = explode( ',', $posts );
      }
      $posts = array_splice( $posts, 0, $options['count'] );
    }
    $return = '';
    if ( $options['title'] ) {
      $return .= apply_filters( 'tinyrelated_list_title', '<h3>'.$options['title'].'</h3>' );
    }
    $return .= apply_filters( 'tinyrelated_list_start', '<ul class="'.$options['class'].'">' );
    foreach ($posts as $related_post) {
      if (is_numeric($related_post)) {
        $related_post = get_post($related_post);
      }
      if (!$related_post || $related_post->post_status != 'publish') {
        continue;
      }
      $return .= sprintf(
        apply_filters( 'tinyrelated_list_item', '<li><a href="%1$s">%2$s</a></li>' ), 
        get_permalink($related_post),
        get_the_title($related_post)
      );
    }
    $return .= apply_filters( 'tinyrelated_list_end', '</ul>' );
    return $return;
  }
  // get array of related posts - from post_meta, generate random, if none found
  public static function get_list($post, $options = array() ) {
    if (is_numeric($post)) {
      $post = get_post( $post );
    }
    $count = $options['count'];
    $count = $count > 0 ? $count : get_post_meta( $post->ID, '_tinyrelated_count', true );
    $count = $count > 0 ? $count : self::$options['general_count'];
    $return = get_post_meta( $post->ID, '_tinyrelated_list', true );
    if ( $return ) {
      $return = explode( ',', $return );
      $return = array_splice( $return, 0, $count );
      foreach ( $return as $key => $item ) {
        $return[$key] = get_post( $item );
      }
    } else if (self::$options['general_random']) {
      $args = array(
        'post_type'       => $post->post_type,
        'post_status'     => 'publish',
        'posts_per_page'  => $count,
        'orderby'         => 'rand',
        'no_found_rows'   => true,
      );
      $return = new WP_Query($args);
      $return = $return->posts;
    } else {
      $return = array();
    }
    $return = apply_filters( 'tinyrelated_get_list', $return, $post );
    return $return;
  }

  // Metabox
  public static function add_metabox(){
    add_meta_box(
      'tinyrelated_metabox',
      /* translators: Metabox title */
      _x( 'Related posts', 'metabox', 'tinyrelated' ),
      array('tinyRelated','print_metabox'),
      'post'
    );
  }
  public static function print_metabox($post) {
    // Set nonce
    wp_nonce_field( 'tinyrelated_metabox', 'tinyrelated_metabox_nonce' );
    echo '<table class="form-table"><tbody>';
    echo self::metabox_field( array(
      'id'    => 'tinyrelated_hide',
      'name'  => 'tinyrelated_hide',
      'label' => __( 'Hide related posts', 'tinyrelated' ),
      'value' => get_post_meta($post->ID, '_tinyrelated_hide', true),
      'type'  => 'checkbox'
    ));
    echo self::metabox_field( array(
      'id'    => 'tinyrelated_count',
      'name'  => 'tinyrelated_count',
      'label' => __( 'Number of related posts', 'tinyrelated' ),
      'value' => get_post_meta($post->ID, '_tinyrelated_count', true),
      'type'  => 'number'
    ));
    $list = get_post_meta($post->ID, '_tinyrelated_list', true);
    $list = explode( ',', $list );
    echo self::metabox_field( array(
      'id'    => 'tinyrelated_list',
      'name'  => 'tinyrelated_list',
      /* translators: select title */
      'label' => _x( 'Related posts', 'select', 'tinyrelated' ),
      'value' => $list,
      'type'  => 'select'
    ));
    echo '</tbody></table>';
  }
  public static function save_metabox( $post_id ) {
    // Check if our nonce is set.
    if ( ! isset( $_POST['tinyrelated_metabox_nonce'] ) ) {
      return;
    }
    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['tinyrelated_metabox_nonce'], 'tinyrelated_metabox' ) ) {
      return;
    }
    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
      return;
    }
    // Check permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
      return;
    }  
    // Sanitize user input
    $hide = isset($_POST['tinyrelated_hide'])?1:false;
    $count = isset($_POST['tinyrelated_count'])?$_POST['tinyrelated_count']*1:false;
    $list = implode( ',', array_filter($_POST['tinyrelated_list']) );
    if ($hide) {
      update_post_meta( $post_id, '_tinyrelated_hide', $hide );
    } else {
      delete_post_meta( $post_id, '_tinyrelated_hide' );
    }
    if ($count) {
      update_post_meta( $post_id, '_tinyrelated_count', $count );
    } else {
      delete_post_meta( $post_id, '_tinyrelated_count' );
    }
    if ($list) {
      update_post_meta( $post_id, '_tinyrelated_list', $list );
    } else {
      delete_post_meta( $post_id, '_tinyrelated_list' );
    }
  }
  private static function metabox_field( $args=array() ) {
    switch ($args['type']) {
      case 'select' :
        $return = '
          <tr>
          <th scope="row"><label for="'.$args['id'].'">'.$args['label'].'</label></th>
          <td>';
        for ( $i = 0; $i < 10; ++$i ) {        
          $return .= self::dropdown( array(
            'selected'          => isset( $args['value'][$i] )?$args['value'][$i]:false,
            'name'              => $args['name'].'['.$i.']',
            'id'                => $args['id'].'_'.$i,
            'post_type'         => 'post',
            'echo'              => 0,
            'show_option_none'  => '- none -',
          ) ).'<br/>';
        }
        $return .= '
          </td>
          </tr>
        ';
      break;
      case 'checkbox' :
        if (! isset($args['description']) ) {
          $args['description'] = false;
        }
        $return = '
          <tr>
          <th scope="row">'.$args['label'].'</th>
          <td> <fieldset><legend class="screen-reader-text"><span>'.$args['label'].'</span></legend><label for="'.$args['id'].'">
          <input name="'.$args['name'].'" type="checkbox" id="id" value="1" '.checked( $args['value'], true, false ).'>
          '.$args['description'].'</label>
          </fieldset></td>
          </tr>
          ';
      break;
      case 'text' :
      default :
        $return = '
          <tr>
          <th scope="row"><label for="'.$args['id'].'">'.$args['label'].'</label></th>
          <td><input name="'.$args['name'].'" type="'.$args['type'].'" id="'.$args['id'].'" value="'.$args['value'].'" class="regular-text"></td>
          </tr>
          ';
      break;
    }
    return $return;
  }
  private static function dropdown( $args = '' ) {
    $defaults = array(
      'depth' => 0, 'child_of' => 0,
      'selected' => 0, 'echo' => 1,
      'name' => 'page_id', 'id' => '',
      'show_option_none' => '', 'show_option_no_change' => '',
      'option_none_value' => ''
    );

    $r = wp_parse_args( $args, $defaults );

    $pages = get_posts( 'posts_per_page=1000' );
    $output = '';
    // Back-compat with old system where both id and name were based on $name argument
    if ( empty( $r['id'] ) ) {
      $r['id'] = $r['name'];
    }

    if ( ! empty( $pages ) ) {
      $output = "<select name='" . esc_attr( $r['name'] ) . "' id='" . esc_attr( $r['id'] ) . "'>\n";
      if ( $r['show_option_no_change'] ) {
        $output .= "\t<option value=\"-1\">" . $r['show_option_no_change'] . "</option>\n";
      }
      if ( $r['show_option_none'] ) {
        $output .= "\t<option value=\"" . esc_attr( $r['option_none_value'] ) . '">' . $r['show_option_none'] . "</option>\n";
      }
      $output .= walk_page_dropdown_tree( $pages, $r['depth'], $r );
      $output .= "</select>\n";
    }

    /**
     * Filter the HTML output of a list of pages as a drop down.
     *
     * @since 2.1.0
     *
     * @param string $output HTML output for drop down list of pages.
     */
    $html = apply_filters( 'wp_dropdown_posts', $output );

    if ( $r['echo'] ) {
      echo $html;
    }
    return $html;
  }

  // Shortcode
  public static function shortcode( $args, $content = '' ) {
    $return = self::build_list( get_the_id(), $args );
    return $return;
  }

  public static function widget() {
    register_widget( 'tinyRelated_Widget' );
  }
}

function the_related_posts( $post_id = false, $options = array() ) {
  if ( !$post_id ) {
    $post_id = get_the_id();
  }
  echo tinyRelated::build_list( $post_id, $options );
}

function get_related_posts( $post_id = false, $options = array() ) {
  if ( !$post_id ) {
    $post_id = get_the_id();
  }
  return tinyRelated::get_list( $post_id, $options );
}

?>