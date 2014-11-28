<?php
/**
 * Facebook Recent Comments Widget
 *
 * @package Facebook Recent Comments
 * @author  Bishoy A. <hi@bishoy.me>
 * @since   1.0
 */

class fb_recent_comments_widget extends WP_Widget {
    
    function __construct() {
        parent::__construct( 'fb_recent_comments_widget', __( 'Facebook Recent Comments', 'frc' ), array( 'description' => __( '125x125 Multiple Advertisements', 'frc' ) ) );
    }
    
    public function enqueue_fb_comments_styles() {
    	wp_register_style( 'frc_styles', plugins_url( 'css/style.css', __FILE__ ) );
    	wp_enqueue_style( 'frc_styles' );
    }

    public function widget( $args, $instance ) {

    	if ( empty( $instance['comments_count'] ) ) {
    		$comments_count = 5;
    	} else {
    		$comments_count = $instance['comments_count'];
    	}

    	$comments = frc_get_fb_comments( $comments_count );

    	if ( ! empty( $comments ) ) {
    		add_action( 'wp_footer', array( $this, 'enqueue_fb_comments_styles' ) );
    		$title = apply_filters( 'widget_title', $instance['title'] );
    			echo $args['before_widget']; ?>
				        <div id="fb_recent_comments_widget" class="widget fb_recent_comments">
					        <?php if ( ! empty( $title ) ) echo $args['before_title'] . $title . $args['after_title']; ?>
					        <div class="fb-commets-widg-container">
						        <ul class="fb-comments-list">
						        	<?php foreach ( $comments as $comment ) { ?>
						        		<li>
						        			<div class="comment-content">
						        				&ldquo;<?php echo truncate_text( $comment['comment_data']->message ); ?>&rdquo;
						        			</div>
						        			<div class="comment-meta">
						        			&mdash; <a href="http://facebook.com/profile.php?id=<?php echo $comment['comment_data']->from->id; ?>" target="_blank">
						        						<?php echo $comment['comment_data']->from->name; ?>
						        					</a> on <a href="<?php echo $comment['post_link']; ?>#comments">
						        								<?php echo $comment['post_title']; ?>
						        							</a> - <?php echo date_i18n( get_option( 'date_format' ), strtotime( $comment['comment_data']->created_time ) ); ?>
						        			</div>
						        		</li>
						        	<?php } ?>
						        </ul>
					        </div>
						</div>
	<?php
		echo $args['after_widget'];
    	}
    }
    
    public function form( $instance ) {
        if ( isset( $instance['title'] ) ) {
            $title = $instance['title'];
        } else {
            $title = __('Recent Comments', 'frc');
        } 

		if ( isset( $instance['comments_count'] ) ) {
            $comments_count = absint( $instance['comments_count'] );
        } else {
            $comments_count = 5;
        }  ?>
        	<p>
			<label for="<?php
			        echo $this->get_field_id( 'title' ); ?>"><?php
			        _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php
			        echo $this->get_field_id( 'title' ); ?>" name="<?php
			        echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php
			        echo esc_attr( $title ); ?>" />
			</p>
			<p>
				<label for="<?php
			        echo $this->get_field_id( 'comments_count' ); ?>"><?php _e( 'Number of comments' ); ?></label>
				<input id="<?php
			        echo $this->get_field_id( 'comments_count' ); ?>" name="<?php
			        echo $this->get_field_name( 'comments_count' ); ?>" class="widefat" type="text" value="<?php
			        echo esc_attr( $comments_count ); ?>" />
			        <small>Default 5</small>
			</p>

        <?php
    }
    
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['comments_count'] = ( ! empty( $new_instance['comments_count'] ) ) ? $new_instance['comments_count'] : '';
        return $instance;
    }
}

function frc_load_widgets() {
    register_widget( 'fb_recent_comments_widget' );
}
add_action('widgets_init', 'frc_load_widgets');