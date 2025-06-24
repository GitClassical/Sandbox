<?php
// access globals
global $post, $commentpress_core;
// init output
$_page_comments_output = '';
// is it commentable?
$_is_commentable = commentpress_is_commentable();
// if a commentable post
if ( $_is_commentable AND ! post_password_required() ) {
	// get singular post type label
	$current_type = get_post_type();
	$post_type = get_post_type_object( $current_type );
	/**
	 * Assign name of post type.
	 *
	 * @since 3.8.10
	 *
	 * @param str $singular_name The singular label for this post type
	 * @param str $current_type The post type identifier
	 * @return str $singular_name The modified label for this post type
	 */
	$post_type_name = apply_filters( 'commentpress_lexia_post_type_name', $post_type->labels->singular_name, $current_type );
	// construct recent comments phrase
	$_paragraph_text = sprintf( __( 'Recent Comments on this %s', 'commentpress-core' ), $post_type_name );
	// set default
	$page_comments_title = apply_filters(
		'cp_activity_tab_recent_title_page',
		$_paragraph_text
	);
	// get page comments
	$_page_comments_output = commentpress_get_comment_activity( 'post' );
}
// set default
$_all_comments_title = apply_filters(
	'cp_activity_tab_recent_title_blog',
	__( 'Recent Comments in this Document', 'commentpress-core' )
);
// get all comments
$_all_comments_output = commentpress_get_comment_activity( 'all' );
// set maximum number to show - put into option?
$_max_members = 10;
?><!-- activity_sidebar.php -->
<div id="activity_sidebar" class="sidebar_container">
<div class="sidebar_header">
<h2><?php _e( 'Activity', 'commentpress-core' ); ?></h2>
</div>
<div class="sidebar_minimiser">
<div class="sidebar_contents_wrapper">
<div class="comments_container">
<?php 
/* global $wp_query;
$args = array( 'post_type' => 'vocabulary', 'posts_per_page' => 10 );
$loop = new WP_Query( $args );
while ( $loop->have_posts() ) : $loop->the_post();
  the_title();
  echo '<div class="entry-content">';
  the_content();
  echo '</div>';
endwhile; */
//the_meta();
 echo get_post_meta($post->ID, 'Vocabulary', true);     
 ?>
</div><!-- /comments_container -->
</div><!-- /sidebar_contents_wrapper -->
</div><!-- /sidebar_minimiser -->
</div><!-- /activity_sidebar -->



