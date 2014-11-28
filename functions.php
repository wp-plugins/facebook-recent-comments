<?php
/**
 * Functions File
 *
 * @package Facebook Recent Comments
 * @author  Bishoy A. <hi@bishoy.me>
 * @since   1.0
 */

/**
 * Get all posts IDs Limited to 2000
 * @return array
 */
function frc_get_all_posts_ids() {
	$results = get_transient( 'frc_allposts_ids' );

	if ( empty( $results ) ) {
		global $wpdb;
		$query = 'SELECT ID FROM ' . $wpdb->base_prefix . 'posts WHERE post_status = "publish" AND post_type != "attachment" AND post_type != "revision" AND post_type != "nav_menu_item" LIMIT 2000';
		$query_results = $wpdb->get_results( $query );
		$results = array_map( 'reset', $query_results );
		set_transient( 'frc_allposts_ids', $results, 5 * 60 );	
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
			$article = get_post( $id );
			if ( empty( $article ) ) continue;
			
			$link    = get_permalink( $id );
			$fb_json = frc_curl_something( 'http://graph.facebook.com/comments?id=' . $link );
			$fb_data = json_decode( $fb_json );

			if ( empty( $fb_data->data ) ) continue;

			foreach( $fb_data->data as $comment ) {
				$comments_data[$i]['post_title']   = $article->post_title;
				$comments_data[$i]['post_link']    = $link;
				$comments_data[$i]['comment_data'] = $comment;	
			}
			
			$i++;
			if ( count( $comments_data ) == $max_count ) break;
		}

		if ( empty( $comments_data ) ) return array();

		set_transient( 'frc_recent_' . $max_count . '_fb_count', $comments_data, 5 * 60 );
	}
	
	return $comments_data;
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
function truncate_text( $input, $maxWords = 20, $maxChars = 60, $more_replace = '' ) {
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

	if ( ! $betPost ) {
		if ( ! empty( $more_replace ) ) {
			return $result . ( $input == $result ? '' : $more_replace );
		} else {
		return $result . ( $input == $result ? '' : ' ...<em><a href="#more-pop" class="open-popup-link bet-more-link popup-with-move-anim" data-post-id="' . $postID . '">More</a></em>' );
		}
	} else {
		return $result . ( $input == $result ? '' : ' ...<em><a href="' . get_permalink( $postID ) . '">More</a></em>' );
	}
}