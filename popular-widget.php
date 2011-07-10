<?php 
/*
Plugin Name: Popular Widget
Plugin URI: http://imstore.xparkmedia.com/popular-widget/
Description: Display most viewed, most commented and tags in one widget (with tabs)
Author: Hafid R. Trujillo Huizar
Version: 0.5.3
Author URI: http://www.xparkmedia.com
Requires at least: 3.0.0
Tested up to: 3.2.0

Copyright 2011 by Hafid Trujillo http://www.xparkmedia.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License,or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not,write to the Free Software
Foundation,Inc.,51 Franklin St,Fifth Floor,Boston,MA 02110-1301 USA
*/ 


// Stop direct access of the file
if(preg_match('#'.basename(__FILE__).'#',$_SERVER['PHP_SELF'])) 
	die();
	
class PopularWidget extends WP_Widget {
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function PopularWidget() {
		
		ob_start(); //header sent problems
		if(!defined('POPWIDGET_URL')) 
			define('POPWIDGET_URL',WP_PLUGIN_URL."/".plugin_basename(dirname(__FILE__))."/");
		
		$this->version = "0.5.3";
		$this->domain  = "pop-wid";
		$this->load_text_domain();
		
		add_action('template_redirect',array(&$this,'set_post_view'));
		add_action('wp_enqueue_scripts',array(&$this,'load_scripts_styles'));
		
		$widget_ops = array('classname' => 'popular-widget','description' => __("Display most popular posts and tags",$this->domain));
		$this->WP_Widget('popular-widget',__('Popular Widget',$this->domain),$widget_ops);
	}
	
	/**
	* Register localization/language file
	*
	* @return void
	* @since 0.5.0 
	*/
	function load_text_domain(){
		if(function_exists('load_plugin_textdomain')){
			$plugin_dir = basename(dirname(__FILE__)).'/langs/';
			load_plugin_textdomain($this->domain,WP_CONTENT_DIR.'/plugins/'.$plugin_dir,$plugin_dir);
		}
	}
	
	/**
	 * Load frontend js/css
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function load_scripts_styles(){
		if(is_admin()) return;
		wp_enqueue_style('popular-widget',POPWIDGET_URL.'_css/pop-widget.css',NULL,$this->version);
		wp_enqueue_script('popular-widget',POPWIDGET_URL.'_js/pop-widget.js',array('jquery'),$this->version,true); 	
	}
	
	
	/**
	 * Display widget.
	 *
	 * @param array $args
	 * @param array $instance
	 * @return void
	 * @since 0.5.0
	 */
	function widget($args,$instance) {
		global $wpdb;
		
		extract($args); extract($instance); 
		$limit	= ($limit) ? $limit : 5;
		$days	= ($lastdays) ? $lastdays : 365;
		$words	= ($excerptlength) ? $excerptlength : 15;
		
		$posttypes = (empty($posttypes)) ? $posttypes = array('post'=>'on') : $posttypes;
		foreach($posttypes as $type => $val) $types[] = "'$type'"; $types = implode(',',$types);
		
		if($cats){
			$join = 
			"INNER JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id
			INNER JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id 
			INNER JOIN $wpdb->terms t ON tt.term_id = t.term_id ";
			$where = "AND t.term_id IN (".trim($cats,',').") AND taxonomy = 'category'";
		}
		
		if(!$nocommented){
		$commented = $wpdb->get_results(
		"SELECT DISTINCT comment_count,ID,post_title,post_content,post_excerpt,post_date 
			FROM $wpdb->posts p $join WHERE  post_date >= '" . date('Y-m-d', current_time('timestamp')-($days*86400)) . "' 
			AND post_status = 'publish' AND comment_count != 0 AND post_type IN ($types) $where
			ORDER BY comment_count DESC LIMIT $limit"
		);}
		
		if(!$noviewed){
		$viewed = $wpdb->get_results(
			"SELECT ID,post_title,post_date,post_content,post_excerpt,meta_value as views
			FROM $wpdb->posts p JOIN $wpdb->postmeta pm ON p.ID = pm.post_id $join
			WHERE meta_key = '_popular_views' AND meta_value != ''
			AND post_date >= '" . date('Y-m-d', current_time('timestamp')-($days*86400)) . "' 
			AND post_status = 'publish' AND post_type IN ($types) $where
			ORDER BY (meta_value+0) DESC LIMIT $limit"
		);}
		
		$tabs = (!$nocommented && !$noviewed && !$notags) ? ' class="pop-tabs-all"': '';
		
		// start widget //
		$output  = $before_widget."\n";
		$output .= '<ul id="pop-widget-tabs"'.$tabs.'>';
		if(!$nocommented) $output .= '<li><a href="#commented" rel="nofollow">'.__('Most Commented',$this->domain).'</a></li>';
		if(!$noviewed) $output .= '<li><a href="#viewed" rel="nofollow">'.__('Most Viewed',$this->domain).'</a></li>';
		if(!$notags) $output .= '<li><a href="#tags" rel="nofollow">'.__('Tags',$this->domain).'</a></li>';
		$output .= '</ul><div class="pop-inside">';

		
		//most commented
		if(!$nocommented){
			$output .= '<ul id="pop-widget-commented">';
			foreach($commented as $post){
				$title = ($tlength && (strlen($post->post_title) > $tlength)) 
				? substr($post->post_title,0,$tlength) . " ..." : $post->post_title;
				$output .= '<li>';
				$output .= '<a href="'.get_permalink($post->ID).'">'.$title.'</a> ';
				if($count) $output .= '<span class="pop-count">('.$post->comment_count.')</span>';
				if($excerpt){
					if($post->post_excerpt) $output .= '<p>'.$post->post_excerpt.'</p>';
					elseif($post->post_excerpt && $excerptlength) $output .= '<p>'.$this->limit_words($post->post_excerpt,$words).'</p>';
					else $output .= '<p>'.$this->limit_words($post->post_content,$words).'</p>';
				}
				$output .= '</li>';
			}
			$output .= ($commented[0]) ? '' : '<li></li>' ;
			$output .= '</ul>';
		}
		
		//most viewed
		if(!$noviewed){
			$output .= '<ul id="pop-widget-viewed">';
			foreach($viewed as $post){
				$title = ($tlength && (strlen($post->post_title) > $tlength)) 
				? substr($post->post_title,0,$tlength) . " ..." : $post->post_title;
				$output .= '<li>';
				$output .= '<a href="'.get_permalink($post->ID).'">'.$title.'</a> ';
				if($count) $output .= '<span class="pop-count">('.$post->views.')</span>';
				if($excerpt){
					if($post->post_excerpt) $output .= '<p>'.$post->post_excerpt.'</p>';
					elseif($post->post_excerpt && $excerptlength) $output .= '<p>'.$this->limit_words($post->post_excerpt,$words).'</p>';
					else $output .= '<p>'.$this->limit_words($post->post_content,$words).'</p>';
				}
				$output .= '</li>';
			}
			$output .= ($viewed[0]) ? '' : '<li></li>' ;
			$output .= '</ul>';
		}
		
		//tags
		if(!$notags) $output .= wp_tag_cloud(array('smallest'=>'8','largest'=>'22','format'=>"list",'echo'=>false));
		$output .= '</div>';
		echo $output .=  $after_widget."\n";
		
		// end widget //
	}
	
	
	/**
	 * Configuration form.
	 *
	 * @param array $instance
	 * @return void
	 * @since 0.5.0
	 */
	function form($instance) {
		extract($instance);
		$post_types = get_post_types(array('public'=>true),'names','and');
		$posttypes = (empty($posttypes)) ? $posttypes = array('post'=>'on') : $posttypes;
	 	?>
		<p>
			<label for="<?php echo $this->get_field_id('limit')?>"><?php _e('Show how many posts?',$this->domain)?> <input id="<?php echo $this->get_field_id('limit')?>" name="<?php echo $this->get_field_name('limit')?>" size="4" type="text" value="<?php echo $limit?>"/></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('cats')?>"><?php _e('In categories',$this->domain)?> <input id="<?php echo $this->get_field_id('cats')?>" name="<?php echo $this->get_field_name('cats')?>" size="20" type="text" value="<?php echo $cats?>"/></label><br /><small><?php _e('comma-separated category IDs',$this->domain)?> </small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('lastdays')?>"><?php _e('In the last',$this->domain)?> <input id="<?php echo $this->get_field_id('lastdays')?>" name="<?php echo $this->get_field_name('lastdays')?>" size="4" type="text" value="<?php echo $lastdays?>"/> <?php _e('Days',$this->domain)?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('tlength')?>"><?php _e('Title length',$this->domain)?> <input id="<?php echo $this->get_field_id('tlength')?>" name="<?php echo $this->get_field_name('tlength')?>" size="4" type="text" value="<?php echo $tlength?>"/> <?php _e('characters',$this->domain)?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('count')?>"><input id="<?php echo $this->get_field_id('count')?>" name="<?php echo $this->get_field_name('count')?>" type="checkbox" <?php echo($count)?'checked="checked"':''; ?> /> <?php _e('Display count',$this->domain)?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('excerpt')?>"><input id="<?php echo $this->get_field_id('excerpt')?>" name="<?php echo $this->get_field_name('excerpt')?>" type="checkbox" <?php echo($excerpt)?'checked="checked"':''; ?> /> <?php _e('Display post excerpt',$this->domain)?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('excerptlength')?>"><?php _e('Excerpt length',$this->domain)?> <input id="<?php echo $this->get_field_id('excerptlength')?>" name="<?php echo $this->get_field_name('excerptlength')?>" size="5" type="text" value="<?php echo $excerptlength?>"/> <?php _e('words',$this->domain)?></label>
		</p>
		<p>
			<label><?php _e('Post Types',$this->domain)?></label><br />
			<?php foreach ($post_types  as $post_type) { ?>
			<label for="<?php echo $this->get_field_id($post_type)?>"><input id="<?php echo $this->get_field_id($post_type)?>" name="<?php echo $this->get_field_name('posttypes')."[$post_type]"?>" type="checkbox" <?php echo($posttypes[$post_type])?'checked="checked"':''; ?> /> <?php echo $post_type?></label><br />
			<?php }?>
		</p>
		<p><?php _e('Disable:',$this->domain)?></p>
		<p>
			<label for="<?php echo $this->get_field_id('nocommented')?>"><input id="<?php echo $this->get_field_id('nocommented')?>" name="<?php echo $this->get_field_name('nocommented')?>" type="checkbox" <?php echo($nocommented)?'checked="checked"':''; ?> /> <?php _e('Most Commented',$this->domain)?></label><br />
			<label for="<?php echo $this->get_field_id('noviewed')?>"><input id="<?php echo $this->get_field_id('noviewed')?>" name="<?php echo $this->get_field_name('noviewed')?>" type="checkbox" <?php echo($noviewed)?'checked="checked"':''; ?> /> <?php _e('Most Viewed',$this->domain)?></label><br />
			<label for="<?php echo $this->get_field_id('notags')?>"><input id="<?php echo $this->get_field_id('notags')?>" name="<?php echo $this->get_field_name('notags')?>" type="checkbox" <?php echo($notags)?'checked="checked"':''; ?> /> <?php _e('Tags',$this->domain)?></label>
		</p>
		<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8SJEQXK5NK4ES"><?php _e('Donate',$this->domain)?></a>
		 <?php
	}
	
	
	/**
	 * Add postview count.
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function set_post_view() {
		if(is_single() || is_page()){
			global $post;
			if(!isset($_COOKIE['popular_views_'.COOKIEHASH]) && setcookie("test", "test", time() + 360)){
				update_post_meta($post->ID,'_popular_views',get_post_meta($post->ID,'_popular_views',true)+1);
				setcookie('popular_views_'.COOKIEHASH,"$post->ID|",0,COOKIEPATH);	
			}elseif(isset($_COOKIE['popular_views_'.COOKIEHASH])){
				$views = explode("|",$_COOKIE['popular_views_'.COOKIEHASH]);
				foreach($views as $post_id) 
					if($post->ID == $post_id) $exist = true;
				if(!$exist){
					$views[] = $post->ID;
					update_post_meta($post->ID,'_popular_views',get_post_meta($post->ID,'_popular_views',true)+1);
					setcookie('popular_views_'.COOKIEHASH,implode("|",$views),0,COOKIEPATH);
				}
			}
		}
	}
	
	/**
	 * Limit the words in a string
	 *
	 * @param string $string
	 * @param unit $word_limit
	 * @return string
	 * @since 0.5.0
	 */
	function limit_words($string, $word_limit){
   		$words = explode(" ",$string);
   		return implode(" ",array_splice($words,0,$word_limit));
	}


}
add_action('widgets_init',create_function('','return register_widget("PopularWidget");'));
?>