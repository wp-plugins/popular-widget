<?php 
/*
Plugin Name: Popular Widget
Plugin URI: http://xparkmedia.com/plugins/popular-widget/
Description: Display most viewed, most commented and tags in one widget (with tabs)
Author: Hafid R. Trujillo Huizar
Version: 1.1.0
Author URI: http://www.xparkmedia.com
Requires at least: 3.0.0
Tested up to: 3.3.1

Copyright 2011-2012 by Hafid Trujillo http://www.xparkmedia.com

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
		
		$this->version = "1.0.1";
		$this->domain  = "pop-wid";
		$this->load_text_domain();
		
		$this->functions = new PopularWidgetFunctions();
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
	function widget( $args, $instance ) {
		global $wpdb;
		
		extract($args); extract($instance); 

		$instance['limit']	= ($limit) ? $limit : 5;
		$instance['days']	= ($lastdays) ? $lastdays : 365;
		$instance['imgsize']= ($imgsize)? $imgsize : 'thumbnail';
		$instance['words']	= ($excerptlength) ? $excerptlength : 15;
		
		$posttypes = (empty($posttypes)) ? $posttypes = array('post'=>'on') : $posttypes;
		foreach($posttypes as $type => $val) $types[] = "'$type'"; $instance['types'] = implode(',',$types);
		
		if($cats){
			$join = 
			"INNER JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id
			INNER JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id 
			INNER JOIN $wpdb->terms t ON tt.term_id = t.term_id ";
			$where = "AND t.term_id IN (".trim($cats,',').") AND taxonomy = 'category'";
		}
		
		$tabs = (empty($nocommented ) && empty( $noviewed ) 
		&& empty( $notags ) && empty( $nocomments )) 
		? ' class="pop-widget-tabs pop-tabs-all"': 'class="pop-widget-tabs"';
		
		if( isset( $title ) )
			echo $before_title. $title . $after_title . "\n";
			
		// start widget //
		$output  = $before_widget."\n";
		$output .= '<div class="pop-layout-v">';
		$output .= '<ul id="pop-widget-tabs-'.$this->number.'"'.$tabs.'>';
		
		if( empty( $nocomments )) 
			$output .= '<li><a href="#comments" rel="nofollow">'.__('<span>Recent</span> Comments',$this->domain).'</a></li>';
		if( empty( $nocommented )) 
			$output .= '<li><a href="#commented" rel="nofollow">'.__('<span>Most</span> Commented',$this->domain).'</a></li>';
		if( empty( $noviewed )) 
			$output .= '<li><a href="#viewed" rel="nofollow">'.__('<span>Most</span> Viewed',$this->domain).'</a></li>';
		if( empty( $notags )) 
			$output .= '<li><a href="#tags" rel="nofollow">'.__('Tags',$this->domain).'</a></li>';

		$output .= '</ul><div class="pop-inside-'.$this->number.' pop-inside">';
		
		$instance['number'] = $this->number;
		
		//most comments
		if( empty( $nocomments )){
			$output .= '<ul id="pop-widget-comments-'.$this->number.'">';
			$output .= $this->functions->get_comments( $instance );
			$output .= '</ul>';
		}
		
		//most commented
		if( empty( $nocommented )) {
			$output .= '<ul id="pop-widget-commented-'.$this->number.'">';
			$output .= $this->functions->get_most_commented( $instance );
			$output .= '</ul>';
		}
		
		//most viewed
		if( empty( $noviewed )) {
			$output .= '<ul id="pop-widget-viewed-'.$this->number.'">';
			$output .= $this->functions->get_most_viewed( $instance );
			$output .= '</ul>';
		}
		
		//tags
		if( empty( $notags )) 
			$output .= wp_tag_cloud(array('smallest'=>'8','largest'=>'22','format'=>"list",'echo'=>false));
		
		$output .= '<div class="pop-cl"></div></div></div>';
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
	function form( $instance ) {
		
		$default = array(
			'nocomments' => false, 'nocommented' => false, 'noviewed' => false,
			'imgsize' => 'thumbnail', 'counter' => false, 'excerptlength' => '', 'tlength' => '',
			'calculate' => 'visits', 'title' => '', 'limit'=> 5, 'cats'=>'', 'lastdays' => 30,
			'posttypes' => array('post'=>'on'), 'thumb' => false, 'excerpt' => false,'notags'=> false,
		); $instance = wp_parse_args( $instance, $default );
		
		$post_types = get_post_types(array('public'=>true),'names','and');
		extract( $instance );
		
	 	?>
		<p>
	 		<label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Title', $this->domain ) ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title') ?>" name="<?php echo $this->get_field_name( 'title' ) ?>" type="text" value="<?php echo $title ?>" /></label>
		</p>
		
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
			<label for="<?php echo $this->get_field_id('imgsize')?>"><?php _e('Image Size',$this->domain)?>
			<select id="<?php echo $this->get_field_id('imgsize') ?>" name="<?php echo $this->get_field_name('imgsize') ?>">
			<?php foreach( get_intermediate_image_sizes() as $size):?>
				<option value="<?php echo $size?>" <?php selected($size,$imgsize)?>><?php echo $size?></option>
			<?php endforeach;?>
			</select>
			</label>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('counter')?>"><input id="<?php echo $this->get_field_id('counter')?>" name="<?php echo $this->get_field_name('counter')?>" type="checkbox" <?php echo($counter)?'checked="checked"':''; ?> /> <?php _e('Display count',$this->domain)?></label><br />		
		<label for="<?php echo $this->get_field_id('thumb')?>"><input id="<?php echo $this->get_field_id('thumb')?>" name="<?php echo $this->get_field_name('thumb')?>" type="checkbox" <?php echo($thumb)?'checked="checked"':''; ?> /> <?php _e('Display thumbnail',$this->domain)?></label><br />

		<label for="<?php echo $this->get_field_id('excerpt')?>"><input id="<?php echo $this->get_field_id('excerpt')?>" name="<?php echo $this->get_field_name('excerpt')?>" type="checkbox" <?php echo($excerpt)?'checked="checked"':''; ?> /> <?php _e('Display post excerpt',$this->domain)?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('excerptlength')?>"><?php _e('Excerpt length',$this->domain)?> <input id="<?php echo $this->get_field_id('excerptlength')?>" name="<?php echo $this->get_field_name('excerptlength')?>" size="5" type="text" value="<?php echo $excerptlength?>"/> <?php _e('Words',$this->domain)?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('tlength')?>"><?php _e('Title length',$this->domain)?> <input id="<?php echo $this->get_field_id('tlength')?>" name="<?php echo $this->get_field_name('tlength')?>" size="4" type="text" value="<?php echo $tlength?>"/> <?php _e('characters',$this->domain)?></label>
		</p>
		<p>
		<?php _e('Calculate:',$this->domain)?><br />
		<label for="<?php echo $this->get_field_id('calculate-views')?>"><input id="<?php echo $this->get_field_id('calculate-views')?>" name="<?php echo $this->get_field_name('calculate')?>" value="views" type="radio" <?php checked($calculate,'views') ?> /> <abbr title="Every time the user views the page"><?php _e('Views',$this->domain)?></abbr></label> <br />
<small><?php _e('Every time user views the post.',$this->domain ) ?></small><br />

		<label for="<?php echo $this->get_field_id('calculate-visits')?>"><input id="<?php echo $this->get_field_id('calculate-visits')?>" name="<?php echo $this->get_field_name('calculate')?>" value="visits" type="radio" <?php checked($calculate,'visits') ?> /> <abbr title="Every time the user visits the site"><?php _e('Visits',$this->domain)?></abbr></label><br />
			<small><?php _e('Ads one to the post only once per website visitt.',$this->domain ) ?></small>
		</p>
		
		<p>
			<label><?php _e('Post Types:',$this->domain)?></label><br />
			<?php foreach ( $post_types  as $post_type ) { ?>
			<label for="<?php echo $this->get_field_id( $post_type )?>"><input id="<?php echo $this->get_field_id($post_type)?>" name="<?php echo $this->get_field_name('posttypes')."[$post_type]"?>" type="checkbox" <?php echo( isset($posttypes[$post_type]) ) ? 'checked="checked"' :''; ?> /> <?php echo $post_type?></label><br />
			<?php }?>
		</p>
		
		<p><?php _e('Disable:',$this->domain)?><br />
			<label for="<?php echo $this->get_field_id( 'nocomments' )?>"><input id="<?php echo $this->get_field_id('nocomments')?>" name="<?php echo $this->get_field_name('nocomments')?>" type="checkbox" <?php echo( $nocomments ) ? 'checked="checked"':''; ?> /> <?php _e('Recent Comments',$this->domain)?></label><br />
			<label for="<?php echo $this->get_field_id('nocommented')?>"><input id="<?php echo $this->get_field_id('nocommented')?>" name="<?php echo $this->get_field_name('nocommented')?>" type="checkbox" <?php echo($nocommented)?'checked="checked"':''; ?> /> <?php _e('Most Commented',$this->domain)?></label><br />
			<label for="<?php echo $this->get_field_id('noviewed')?>"><input id="<?php echo $this->get_field_id('noviewed')?>" name="<?php echo $this->get_field_name('noviewed')?>" type="checkbox" <?php echo($noviewed)?'checked="checked"':''; ?> /> <?php _e('Most Viewed',$this->domain)?></label><br />
			<label for="<?php echo $this->get_field_id('notags')?>"><input id="<?php echo $this->get_field_id('notags')?>" name="<?php echo $this->get_field_name('notags')?>" type="checkbox" <?php echo($notags)?'checked="checked"':''; ?> /> <?php _e('Tags',$this->domain)?></label>
		</p>
		<a href="http://xparkmedia.com/popular-widget/"><?php _e('New! Popular Widget Pro',$this->domain)?></a>&nbsp; | &nbsp;
		<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8SJEQXK5NK4ES"><?php _e('Donate',$this->domain)?></a> 
		 <?php
	}
	
	
	/**
	 * Add postview count.
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function set_post_view( ) {
		global $post;
		
		$widgets = get_option($this->option_name);
		$instance = $widgets[$this->number];
		
		if((is_single() || is_page() || is_singular()) && $instance['calculate'] == 'visits'){
			if(!isset($_COOKIE['popular_views_'.COOKIEHASH]) && setcookie("pop-test", "1", time() + 360)){
				update_post_meta($post->ID,'_popular_views',get_post_meta($post->ID,'_popular_views',true)+1);
				setcookie('popular_views_'.COOKIEHASH,"$post->ID|",0,COOKIEPATH);
			}else{
				$views = explode("|",$_COOKIE['popular_views_'.COOKIEHASH]);
				foreach( $views as $post_id ){ 
					if( $post->ID == $post_id ) 
						$exist = true;
				}
				if( !$exist ){
					$views[] = $post->ID;
					update_post_meta($post->ID,'_popular_views',get_post_meta($post->ID,'_popular_views',true)+1);
					setcookie('popular_views_'.COOKIEHASH,implode("|",$views),0,COOKIEPATH);
				}
			}
		}elseif(is_single() || is_page() || is_singular()){
			update_post_meta($post->ID,'_popular_views',get_post_meta($post->ID,'_popular_views',true)+1);
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
include(dirname(__FILE__)."/include.php");
add_action('widgets_init',create_function('','return register_widget("PopularWidget");'));
?>