<?php

class tinyRelated_Widget extends WP_Widget {

  public function __construct() {
    parent::__construct(
      'tinyrelated_widget',
      /* translators: widget title */
      _x( 'Related Posts', 'widget', 'tinyrelated' ),
      array( 'description' => __( 'List of related posts. Only shows on single posts.', 'tinyrelated' ) , )
    );
  }

  public function widget( $args, $instance ) {
    if (is_single()) {
      $post_id = get_queried_object_id();
      $options = array(
        'title' => false
      );
      if ( isset($instance['count']) && $instance['count'] > 0 ) {
        $options['count'] = $instance['count'];
      }
      echo $args['before_widget'];
      if ( ! empty( $instance['title'] ) ) {
        echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
      }
      echo tinyRelated::build_list($post_id,$options);
      echo $args['after_widget'];
    }
  }

  public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) ) {
      $title = $instance[ 'title' ];
    }
    else {
      /* translators: widget default title */
      $title = _x( 'Related posts', 'title', 'tinyrelated' );
    }
    if ( !isset( $instance['count'] ) || !$instance['count'] ) {
      $instance['count'] = tinyRelated::$options['general_count'];
    }
    ?>
    <p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'tinyrelated' ); ?>:</label> 
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
    </p>
    <p>
    <label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Number of related posts', 'tinyrelated'); ?>:</label> 
    <input class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="number" min="0" max="10" step="1" value="<?php echo esc_attr( $instance['count'] ); ?>">
    </p>
    <?php 
  }

  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    $instance['count'] = ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : '';
    return $instance;  
  }
}
?>