<?php
/**
 * Functions File
 *
 * @package Facebook Recent Comments
 * @author  Bishoy A. <hi@bishoy.me>
 * @since   1.0
 */

/**
 * Get all posts IDs Limited to 10000
 *
 * We need to loop all posts to check Facebook API for comments
 * Using links. That's the only available approach at this time.
 * @return array
 */
function frc_get_all_posts_ids() {
	$results = get_transient( 'frc_allposts_ids' );

	if ( empty( $results ) ) {
		
		global $wpdb;

		$query = 'SELECT ID FROM ' . $wpdb->base_prefix . 'posts WHERE post_status = "publish" AND post_type != "attachment" AND post_type != "revision" AND post_type != "nav_menu_item" ORDER BY `ID` DESC LIMIT 10000';
		$query_results = $wpdb->get_results( $query, ARRAY_N );

		$results = array();
		foreach ( $query_results as $id_array ) {
			$results[] = $id_array[0];
		}

		set_transient( 'frc_allposts_ids', $results, HOUR_IN_SECONDS );
	}

	return $results;
}

/**
 * Calls Facebook API and gets all comments and save to transient
 * @param  integer $max_count
 * @return array
 */
function frc_get_fb_comments( $max_count = 5 ) {	

	$comments_data = get_transient( 'frc_recent_' . $max_count . '_fb_count' );
	if ( empty( $comments_data ) ) {
		$posts_ids = frc_get_all_posts_ids();
		if ( empty( $posts_ids ) ) return array();

		$commets_data = array();
		$i = 0;

		foreach ( $posts_ids as $id ) {
			$title = get_the_title( $id );
			$link  = get_permalink( $id );

			if ( empty( $title ) || empty( $link ) ) continue;

			$fb_json = frc_curl_something( 'http://graph.facebook.com/comments?id=' . $link );
			$fb_data = json_decode( $fb_json );

			if ( empty( $fb_data->data ) ) continue;

			foreach( $fb_data->data as $comment ) {
				$comments_data[$i]['post_title']   = $title;
				$comments_data[$i]['post_link']    = $link;
				$comments_data[$i]['comment_data'] = $comment;	
			}
			
			$i++;
			if ( count( $comments_data ) == $max_count ) break;
		}

		arsort( $comments_data, 'frc_sort_by_created_time' );

		if ( empty( $comments_data ) ) return array();

		set_transient( 'frc_recent_' . $max_count . '_fb_count', $comments_data, 15 * MINUTE_IN_SECONDS );

		do_action( 'frc_updated_recent_' . $max_count . '_fb_count', $max_count );
	}
	
	return $comments_data;
}

/**
 * PHP Array sort function
 *
 * Used to sort comments with Facebook created_time
 * @param  array $a element
 * @param  array $b element
 * @return sort
 */
function frc_sort_by_created_time( $a, $b ) {
    return $a['created_time'] - $b['created_time'];
}

/**
 * Makes a Curl Call and returns the output
 * @param  string $url
 * @return mixed
 */
function frc_curl_something( $url ) {
 
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/1.0" );
    curl_setopt( $ch, CURLOPT_HEADER, 0 );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_TIMEOUT, 20 );

    $output = curl_exec( $ch );
    curl_close( $ch );
 
    return $output;
}

/**
 * Truncate Text
 * @param  string   $input     
 * @param  integer  $maxWords
 * @param  integer  $maxChars
 * @param  integer  $postID
 * @param  boolean  $betPost
 * @param  string   $more_replace
 * @return string
 */
function fbrc_truncate_text( $input, $maxWords = 20, $maxChars = 60, $more_replace = '' ) {
	$words = preg_split( '/[\s]+/', $input );
	$words = array_slice( $words, 0, $maxWords );
	$words = array_reverse( $words );

	$chars = 0;
	$truncated = array();

	while( count( $words ) > 0 ) {
		$fragment = trim( array_pop( $words ) );
		$chars += strlen( $fragment );

		if ( $chars > $maxChars ) break;

		$truncated[] = $fragment;
	}

	$result = implode( $truncated, ' ' );

	$result = force_balance_tags( $result );

	return $result . ( $input == $result ? '' : ' ...<em><a href="' . get_permalink( $postID ) . '">More</a></em>' );
}

/**
 * Not used
 *
 * We're looping all post types instead because
 * Facebook comments can be implemented on any page.
 * 
 * @return array
 */
function frc_get_comments_post_types() {
	$post_types = get_post_types();

	$supported_types = array();

	foreach ( $post_types as $post_type ) {

		if ( ! post_type_supports( $post_type, 'comments' ) || $post_type == 'attachment' )
			continue;

		$supported_types[] = $post_type;
	}
}