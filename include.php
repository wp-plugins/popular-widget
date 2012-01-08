<?php 
/**
*PopularWidgetFunctions
*
*@Popular Widget
*@author Hafid Trujillo
*@copyright 20010-2011
*@since 1.0.1
*/

class PopularWidgetFunctions {
	
	 function PopularWidgetProFunctions() {
    	// with no instructions, does nothing
	 }
	
	/**
	 * Limit the words in a string
	 *
	 * @param string $string
	 * @param unit $word_limit
	 * @return string
	 * @since 1.0.0
	 */
	function limit_words($string, $word_limit){
		$words = explode(" ",$string);
		if((str_word_count($string)) > $word_limit) return implode(" ",array_splice($words,0,$word_limit))."...";
		else return implode(" ",array_splice($words,0,$word_limit));
	}
	
	/**
	 * Get the first 
	 * image attach to the post
	 *
	 * @param unit $post_id
	 * @return string|void
	 * @since 1.0.0
	 */
	function get_post_image($post_id, $size){
		$images = get_posts(array( 
			'order'   => 'ASC',
			'numberposts' => 1,
			'orderby' => 'menu_order',
			'post_parent' => $post_id,
			'post_type' => 'attachment',
			'post_mime_type'  => 'image',
		));
		if(empty($images)) return false;
		foreach($images as $image)
			return wp_get_attachment_image($image->ID, $size);
	}
	
	/**
	 *get the latest comments
	 *
	 *@return void
	 *@since 1.0.0
	*/
	function get_comments( $instance ){
		
		global $wpdb; 
		extract( $instance );
		
		$join = '';
		$output  = '';
		$where = isset( $where ) ? $where : '';
		$time = date('Y-m-d H:i:s',strtotime("-{$lastdays} days",current_time('timestamp')));
		
		if( !empty($cats) ){
			$join = 
			"INNER JOIN $wpdb->term_relationships tr ON c.comment_post_ID = tr.object_id
			INNER JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id 
			INNER JOIN $wpdb->terms t ON tt.term_id = t.term_id ";
		}
		
		$comments = wp_cache_get( "pop_comments_{$number}", 'pop_cache' );
		
		if( $comments == false ) {
			$comments = $wpdb->get_results(
				"SELECT DISTINCT comment_content,comment_ID,comment_author,user_id,comment_author_email,comment_date
				FROM $wpdb->comments c $join WHERE comment_date >= '$time' AND comment_approved = 1 AND comment_type = '' 
				$where ORDER BY comment_date DESC LIMIT $limit"
			);
			wp_cache_set( "pop_comments_{$number}", $comments, 'pop_cache' );
		}
		
		$count = 1;
		foreach($comments as $comment){
			$comment_author = ($comment->comment_author) ? $comment->comment_author : "Anonymous";
			$title = ($tlength && (strlen($comment_author) > $tlength)) 
			? substr($comment_author,0,$tlength) . " ..." : $comment_author;
			$output .= '<li><a href="'.get_comment_link($comment->comment_ID).'">';
			
			if( !empty($thumb ))
				$image = get_avatar($comment->comment_author_email, 100); 
			
			$output .= isset($image) ? $image.'<span class="pop-overlay">':'<span class="pop-text">';
			$output .= '<span class="pop-title">'.$title.'</span> ';
			if( !empty( $excerpt )){
				if($comment->comment_content && $excerptlength) $output .= '<p>'.self::limit_words(strip_tags($comment->comment_content),$words).'</p>';
				else $output .= '<p>'.self::limit_words(strip_tags($comment->comment_content),$words).'</p>';
			}
			$output .= '</span></a><div class="pop-cl"></div></li>';  $count++;
		}
		return $output .= ($comments[0]) ? '' : '<li></li>' ;
	}
	
	/**
	 *get commented results
	 *
	 *@return void
	 *@since 1.0.0
	*/
	function get_most_commented( $instance ){
		
		global $wpdb; 
		extract($instance);
		
		$join = '';
		$output  = '';
		$where = isset( $where ) ? $where : '';
		$time = date('Y-m-d H:i:s',strtotime("-{$lastdays} days",current_time('timestamp')));
		
		$commented = wp_cache_get("pop_commented_{$number}", 'pop_cache');
		if( $commented == false ){
			$commented = $wpdb->get_results(
				"SELECT DISTINCT comment_count,ID,post_title,post_content,post_excerpt,post_date 
				FROM $wpdb->posts p $join WHERE post_date >= '$time' AND post_status = 'publish' AND comment_count != 0 
				AND post_type IN ($types) $where ORDER BY comment_count DESC LIMIT $limit"
			);
			wp_cache_set("pop_commented_{$number}", $commented, 'pop_cache');
		}
		
		$count = 1;
		foreach($commented as $post){
			$title = ($tlength && (strlen($post->post_title) > $tlength)) 
			? substr($post->post_title,0,$tlength) . " ..." : $post->post_title;
			$output .= '<li><a href="'.get_permalink($post->ID).'">';
			
			if( !empty($thumb ))
				$image = (has_post_thumbnail($post->ID)) ? 
				get_the_post_thumbnail($post->ID,$imgsize) : 
				self::get_post_image($post->ID,$imgsize);
				
			$output .= isset($image) ? $image.'<span class="pop-overlay">':'<span class="pop-text">';
			//$output .= '<span class="pop-rating">'.$count.'</span> ';
			$output .= '<span class="pop-title">'.$title.'</span> ';
			if( !empty( $counter ))
				$output .= '<span class="pop-count">('.preg_replace("/(?<=\d)(?=(\d{3})+(?!\d))/"," ",$post->comment_count).')</span>';
			if( !empty( $excerpt )){
				if($post->post_excerpt && $excerptlength) $output .= '<p>'.self::limit_words(strip_tags($post->post_content),$words).'</p>';
				else $output .= '<p>'.self::limit_words(strip_tags($post->post_content),$words).'</p>';
			}
			$output .= '</span></a><div class="pop-cl"></div></li>';  $count++;
		}
		return $output .= ($commented[0]) ? '' : '<li></li>' ;
	}

	/**
	 *get viewed results
	 *
	 *@return void
	 *@since 1.0.0
	*/
	function get_most_viewed( $instance ){
	
		global $wpdb; 
		extract( $instance );
		
		$join = '';
		$output  = '';
		$where = isset( $where ) ? $where : '';
		$time = date('Y-m-d H:i:s',strtotime("-{$lastdays} days",current_time('timestamp')));
		
		$viewed = wp_cache_get( "pop_viewed_{$number}", 'pop_cache' );
		if( $viewed == false) {
			$viewed = $wpdb->get_results(
				"SELECT ID,post_title,post_date,post_content,post_excerpt,meta_value as views
				FROM $wpdb->posts p JOIN $wpdb->postmeta pm ON p.ID = pm.post_id $join
				WHERE meta_key = '_popular_views' AND meta_value != '' AND post_date >= '$time'
				AND post_status = 'publish' AND post_type IN ($types) $where
				ORDER BY (meta_value+0) DESC LIMIT $limit"
			);
			wp_cache_set( "pop_viewed_{$number}", $viewed, 'pop_cache' );
		}
		
		$count=1;
		foreach($viewed as $post){
			$title = ($tlength && (strlen($post->post_title) > $tlength)) 
			? substr($post->post_title,0,$tlength) . " ..." : $post->post_title;
			$output .= '<li><a href="'.get_permalink($post->ID).'">';
					
			if( !empty($thumb ))
				$image = (has_post_thumbnail($post->ID)) ? 
				get_the_post_thumbnail($post->ID,$imgsize) : 
				self::get_post_image($post->ID,$imgsize);
			
			$output .= isset($image) ? $image.'<span class="pop-overlay">':'<span class="pop-text">';
			//$output .= '<span class="pop-rating">'.$count.'</span> ';
			$output .= '<span class="pop-title">'.$title.'</span> ';
			if( !empty( $counter ))
				$output .= '<span class="pop-count">('.preg_replace("/(?<=\d)(?=(\d{3})+(?!\d))/"," ",$post->views).')</span>';
			if( !empty( $excerpt )){
				if($post->post_excerpt && $excerptlength) $output .= '<p>'.self::limit_words(strip_tags($post->post_content),$words).'</p>';
				else $output .= '<p>'.self::limit_words(strip_tags($post->post_content),$words).'</p>';
			}
			$output .= '</span></a><div class="pop-cl"></div></li>'; $count++;
		}
		return $output .= ($viewed[0]) ? '' : '<li></li>' ;
	}
	
}

?>