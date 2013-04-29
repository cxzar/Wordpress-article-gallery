<?php
/*PHP Article Gallery Shortcode*/
function wp_article_gallery_shortcode($atts, $content=null){
	$pluginURL   = plugins_url('wp_article_gallery');
	$myposts = get_posts('');
	if(!empty($myposts)){
		$wag         =  '<div class="wagwep-container"><ul id="portfolio-filter" class="nav nav-tabs"></ul><ul id="og-grid" class="og-grid">';

		foreach ($myposts as $post) :
			$post_title = $post->post_title;
			$post_content = $post->post_content;
			$url = $post->guid;
			$first_img = catch_that_image($post_content);
			$thumb = '<img src="'.$pluginURL.'/timthumb.php?src='.$first_img.'&a=r&w=250&h=250"/>';
			$wag .=	'<li data-tags="">';
			$wag .=	'<a href="'.$url.'" data-largesrc="'.$first_img.'"';
			$wag .=	'	data-title="'.$post_title.'"';
			$wag .=	'	data-description="'.wp_article_excerpt(strip_tags($post_content), '').'">';
			$wag .=	$thumb;
			$wag .=	'</a>';
			$wag .=	'</li>';

		endforeach;

		$wag .=	'</ul></div>';

		return $wag;
	}

	return null;
}
add_shortcode('wp_article_gallery', 'wp_article_gallery_shortcode');

/*---------------------------------
* FRONT PAGE SECTION
*---------------------------------/

/*Check the post content - if have wp_article_gallery shortcode, do include necessary js*/
function include_article_gallery_js_to_footer() {

 	$pluginURL   = plugins_url('wp_article_gallery');
    if ( strstr( get_the_content(), '[wp_article_gallery]' ) ) {
		$script 	= '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>';
		$script    .= '<script src="'.$pluginURL.'/js/jquery.browser.min.js"></script>';
		$script    .= '<script src="'.$pluginURL.'/js/modernizr.custom.js"></script>';
		$script    .= '<script src="'.$pluginURL.'/js/grid.js"></script>';
		$script    .= '<script src="'.$pluginURL.'/js/auto_gallery.js"></script>';
		echo $script;
    }
}
add_action('wp_footer', 'include_article_gallery_js_to_footer');

/*Check the post content - if have wp_article_gallery shortcode, do include necessary css*/
function include_article_gallery_css_to_header() {
	global $wp_query;
	$content = $wp_query->post->post_content;
 	$pluginURL   = plugins_url('wp_article_gallery');
    if ( strstr( $content, '[wp_article_gallery]' ) ) {
	    $stylesheet = '<link rel="stylesheet" type="text/css" href="'.$pluginURL.'/css/component.css" />';
		echo $stylesheet;
    }
}
add_action('wp_head', 'include_article_gallery_css_to_header');
?>