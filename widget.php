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
        parent::__construct( 'fb_recent_comments_widget', __( 'Facebook Recent Comments', 'frc' ), array( 'description' => __( 'Facebook recent comments widget.', 'frc' ) ) );
        $this->alt_option_name = 'widget_fbrc';
    }
    
	public function flush_widget_cache() {
		wp_cache_delete( 'fb_recent_comments_widget', 'widget' );
	}

    public function enqueue_fb_comments_styles() {
    	wp_register_style( 'frc_styles', plugins_url( 'css/style.css', __FILE__ ) );
    	wp_enqueue_style( 'frc_styles' );
    }

    public function widget( $args, $instance ) {

    	if ( empty( $instance['comments_count'] ) ) {
    		$comments_count = apply_filters( 'fbrc_default_count', 5 );
    	} else {
    		$comments_count = $instance['comments_count'];
    	}

    	add_action( 'frc_updated_recent_' . $comments_count . '_fb_count', array( $this, 'flush_widget_cache' ) );

    	$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get('fb_recent_comments_widget', 'widget');
		}
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

    	$comments = frc_get_fb_comments( $comments_count );

    	if ( ! empty( $comments ) ) {

    		$output = '';
    		$title = apply_filters( 'widget_title', $instance['title'] );
    			$output .= $args['before_widget'];
				        $output .= '<div id="fb_recent_comments_widget" class="widget fb_recent_comments">';
					        if ( ! empty( $title ) ) $output .= $args['before_title'] . $title . $args['after_title'];
					        $output .= '<div class="fb-commets-widg-container">';
						        $output .= '<ul class="fb-comments-list">';
						        	$i = 1;
						        	foreach ( $comments as $comment ) {
						        		$output .= '<li>';
						        			$output .= '<div class="comment-content">';
						        				$output .= '&ldquo;'. apply_filters( 'fbrc_comment_text', fbrc_truncate_text( $comment['comment_data']->message ) ) . '&rdquo;';
						        			$output .= '</div>';
						        			$output .= '<div class="comment-meta">';
						        			if ( ! empty( $instance['comment_show_profile'] ) ) {
							        			$output .= '&mdash; <a href="http://facebook.com/profile.php?id='. $comment['comment_data']->from->id .'" class="frc-commenter-link" target="_blank">';
							        				$output .= $comment['comment_data']->from->name;
							        			$output .= '</a>';
						        			} else {
						        				$output .= '<span class="frc-commenter-name">' . $comment['comment_data']->from->name . '</span>';
						        			}
					        					$output .= 'on <a href="'.$comment['post_link'] .'#comments">';
					        						$output .= $comment['post_title'];
					        					$output .= '</a> - ' . apply_filters( 'fbrc_comment_date', date_i18n( get_option( 'date_format' ), strtotime( $comment['comment_data']->created_time ) ) );
						        			$output .= '</div>';
						        		$output .= '</li>';
						        	$i++; }
						        $output .= '</ul>';
					        $output .= '</div>';
						$output .= '</div>';
			$output .= $args['after_widget'];

			echo $output;

			if ( ! $this->is_preview() ) {
				$cache[ $args['widget_id'] ] = $output;
				wp_cache_set( 'fb_recent_comments_widget', $cache, 'widget', 3 * HOUR_IN_SECONDS );
			}
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
            $comments_count = apply_filters( 'fbrc_default_count', 5 );
        }  

        $comment_show_profile = isset( $instance['comment_show_profile'] ) ? (bool) $instance['comment_show_profile'] : false;

        ?>
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
			<p><input class="checkbox" id="<?php
			        echo $this->get_field_id( 'comment_show_profile' ); ?>" name="<?php
			        echo $this->get_field_name( 'comment_show_profile' ); ?>" type="checkbox" <?php checked( $comment_show_profile ); ?> />
				<label for="<?php
			        echo $this->get_field_id( 'comment_show_profile' ); ?>"><?php _e( 'Display profile link?' ); ?></label>
			</p>
        <?php
    }
    
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['comments_count'] = ( ! empty( $new_instance['comments_count'] ) ) ? $new_instance['comments_count'] : '';
        $instance['comment_show_profile'] = isset( $new_instance['comment_show_profile'] ) ? (bool) $new_instance['comment_show_profile'] : false;

        $this->flush_widget_cache();

        $alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_fbrc']) )
			delete_option('widget_fbrc');
        return $instance;
    }
}

function frc_load_widgets() {
    register_widget( 'fb_recent_comments_widget' );
}
add_action('widgets_init', 'frc_load_widgets');