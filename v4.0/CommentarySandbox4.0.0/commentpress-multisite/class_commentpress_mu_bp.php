<?php

/**
 * CommentPress Core Multisite BuddyPress Class.
 *
 * This class encapsulates BuddyPress compatibility.
 *
 * @since 3.3
 */
class Commentpress_Multisite_Buddypress {

	/**
	 * Plugin object.
	 *
	 * @since 3.0
	 * @access public
	 * @var object $parent_obj The plugin object
	 */
	public $parent_obj;

	/**
	 * Database interaction object.
	 *
	 * @since 3.0
	 * @access public
	 * @var object $db The database object
	 */
	public $db;

	/**
	 * CommentPress Core enabled on all groupblogs flag.
	 *
	 * @since 3.3
	 * @access public
	 * @var str $force_commentpress The CommentPress Core enabled on all groupblogs flag
	 */
	public $force_commentpress = '0';

	/**
	 * Default theme stylesheet for groupblogs (WP3.4+).
	 *
	 * @since 3.3
	 * @access public
	 * @var str $groupblog_theme The default theme stylesheet
	 */
	public $groupblog_theme = 'commentpress-modern';

	/**
	 * Default theme stylesheet for groupblogs (pre-WP3.4).
	 *
	 * @since 3.3
	 * @access public
	 * @var str $groupblog_theme_name The default theme stylesheet
	 */
	public $groupblog_theme_name = 'CommentPress Default Theme';

	/**
	 * Groupblog privacy flag.
	 *
	 * @since 3.3
	 * @access public
	 * @var bool $groupblog_privacy True if private groups have private groupblogs
	 */
	public $groupblog_privacy = 1;

	/**
	 * Require login to leave comments on groupblogs flag.
	 *
	 * @since 3.3
	 * @access public
	 * @var bool $require_comment_registration True if login required
	 */
	public $require_comment_registration = 1;



	/**
	 * Initialises this object.
	 *
	 * @since 3.3
	 *
	 * @param object $parent_obj a reference to the parent object
	 */
	function __construct( $parent_obj = null ) {

		// store reference to "parent" (calling obj, not OOP parent)
		$this->parent_obj = $parent_obj;

		// store reference to database wrapper (child of calling obj)
		$this->db = $this->parent_obj->db;

		// init
		$this->_init();

	}



	/**
	 * Set up all items associated with this object.
	 *
	 * @return void
	 */
	public function initialise() {

	}



	/**
	 * If needed, destroys all items associated with this object.
	 *
	 * @return void
	 */
	public function destroy() {

	}



//##############################################################################



	/**
	 * -------------------------------------------------------------------------
	 * Public Methods
	 * -------------------------------------------------------------------------
	 */



	/**
	 * Enqueue any styles and scripts needed by our public page.
	 *
	 * @return void
	 */
	public function add_frontend_styles() {

		// dequeue BP Tempate Pack CSS, even if queued
		wp_dequeue_style( 'bp' );

	}



	/**
	 * Allow HTML comments and content in Multisite blogs.
	 *
	 * @return void
	 */
	public function allow_html_content() {

		// using publish_posts for now - means author+
		if ( current_user_can( 'publish_posts' ) ) {

			/**
			 * Remove html filtering on content.
			 *
			 * Note - this has possible consequences.
			 *
			 * @see http://wordpress.org/extend/plugins/unfiltered-mu/
			 */
			kses_remove_filters();

		}

	}



	/**
	 * Allow HTML in Activity items.
	 *
	 * @param array $activity_allowedtags The existing array of allowed tags
	 * @return array $activity_allowedtags The modified array of allowed tags
	 */
	public function activity_allowed_tags( $activity_allowedtags ) {

		// pretty pointless not to allow p tags when we encourage the use of TinyMCE!
		$activity_allowedtags['p'] = array();

		// --<
		return $activity_allowedtags;

	}



	/**
	 * Add capability to edit own comments.
	 *
	 * @see: http://scribu.net/wordpress/prevent-blog-authors-from-editing-comments.html
	 *
	 * @param array $caps The existing capabilities array for the WordPress user
	 * @param str $cap The capability in question
	 * @param int $user_id The numerical ID of the WordPress user
	 * @param array $args The additional arguments
	 * @return array $caps The modified capabilities array for the WordPress user
	 */
	public function enable_comment_editing( $caps, $cap, $user_id, $args ) {

		// only apply this to queries for edit_comment cap
		if ( 'edit_comment' == $cap ) {

			// get comment
			$comment = get_comment( $args[0] );

			// is the user the same as the comment author?
			if ( $comment->user_id == $user_id ) {

				//$caps[] = 'moderate_comments';
				$caps = array('edit_posts');

			}

		}

		// --<
		return $caps;

	}



	/**
	 * Override capability to comment based on group membership.
	 *
	 * @param bool $approved True if the comment is approved, false otherwise
	 * @param array $commentdata The comment data
	 * @return bool $approved Modified approval value. True if the comment is approved, false otherwise
	 */
	public function pre_comment_approved( $approved, $commentdata ) {

		// do we have groupblogs?
		if ( function_exists( 'get_groupblog_group_id' ) ) {

			// get current blog ID
			$blog_id = get_current_blog_id();

			// check if this blog is a group blog
			$group_id = get_groupblog_group_id( $blog_id );

			// when this blog is a groupblog
			if ( isset( $group_id ) AND is_numeric( $group_id ) ) {

				// is this user a member?
				if ( groups_is_user_member( $commentdata['user_id'], $group_id ) ) {

					// allow un-moderated commenting
					return 1;

				}

			}

		}

		// pass through
		return $approved;

	}



	/*
	// a nicer way?
	add_action( 'preprocess_comment', 'my_check_comment', 1 );

	public function my_check_comment( $commentdata ) {

		// Get the user ID of the comment author.
		$user_id = absint( $commentdata['user_ID'] );

		// If comment author is a registered user, approve the comment.
		if ( 0 < $user_id )
			add_filter( 'pre_comment_approved', 'my_approve_comment' );
		else
			add_filter( 'pre_comment_approved', 'my_moderate_comment' );

		return $commentdata;
	}

	public function my_approve_comment( $approved ) {
		$approved = 1;
		return $approved;
	}

	public function my_moderate_comment( $approved ) {
		if ( 'spam' !== $approved )
			$approved = 0;
		return $approved;
	}
	*/



	/**
	 * Add pages to the post_types that BuddyPress records published activity for.
	 *
	 * @param array $post_types The existing array of post types
	 * @return array $post_types The modified array of post types
	 */
	public function record_published_pages( $post_types ) {

		// if not in the array already
		if ( ! in_array( 'page', $post_types ) ) {

			// add page post_type
			$post_types[] = 'page';

		}

		// --<
		return $post_types;

	}



	/**
	 * Add pages to the post_types that BuddyPress records comment activity for.
	 *
	 * @param array $post_types The existing array of post types
	 * @return array $post_types The modified array of post types
	 */
	public function record_comments_on_pages( $post_types ) {

		// if not in the array already
		if ( ! in_array( 'page', $post_types ) ) {

			// add page post_type
			$post_types[] = 'page';

		}

		// --<
		return $post_types;

	}



	/**
	 * Override "publicness" of groupblogs so that we can set the hide_sitewide
	 * property of the activity item (post or comment) depending on the group's
	 * setting.
	 *
	 * Do we want to test if they are CommentPress Core-enabled?
	 *
	 * @param bool $blog_public_option True if blog is public, false otherwise
	 * @return bool $blog_public_option True if blog is public, false otherwise
	 */
	public function is_blog_public( $blog_public_option ) {

		// do we have groupblogs?
		if ( function_exists( 'get_groupblog_group_id' ) ) {

			// get current blog ID
			$blog_id = get_current_blog_id();

			// check if this blog is a group blog
			$group_id = get_groupblog_group_id( $blog_id );

			// when this blog is a groupblog
			if ( isset( $group_id ) AND is_numeric( $group_id ) ) {

				// always true - so that activities are registered
				return 1;

			}

		}

		// fallback
		return $blog_public_option;

	}



	/**
	 * Disable comment sync because parent activity items may not be in the same
	 * group as the comment. Furthermore, CommentPress Core comments should be
	 * read in context rather than appearing as if globally attached to the post
	 * or page.
	 *
	 * @param bool $is_disabled The BuddyPress setting that determines blogforum sync
	 * @return bool $is_disabled The modified value that determines blogforum sync
	 */
	public function disable_blogforum_comments( $is_disabled ) {

		// don't mess with admin
		if ( is_admin() ) return $is_disabled;

		// get current blog ID
		$blog_id = get_current_blog_id();

		// if it's CommentPress Core-enabled, disable sync
		if ( $this->db->is_commentpress( $blog_id ) ) return 1;

		// pass through
		return $is_disabled;

	}



	/**
	 * Record the blog activity for the group.
	 *
	 * Amended from bp_groupblog_set_group_to_post_activity()
	 *
	 * @param object $activity The existing activity object
	 * @return object $activity The modified activity object
	 */
	public function group_custom_comment_activity( $activity ) {

		// only deal with comments
		if ( ( $activity->type != 'new_blog_comment' ) ) return;

		// init vars
		$is_groupblog = false;
		$is_groupsite = false;
		$is_working_paper = false;

		// get groupblog status
		$is_groupblog = $this->_is_commentpress_groupblog();

		// if on a CommentPress Core-enabled groupblog
		if ( $is_groupblog ) {

			// which blog?
			$blog_id = $activity->item_id;

			// get the group ID
			$group_id = get_groupblog_group_id( $blog_id );

			// kick out if not groupblog
			if ( ! $group_id ) return $activity;

			// set activity type
			$type = 'new_groupblog_comment';

		} else {

			// get group site status
			$is_groupsite = $this->_is_commentpress_groupsite();

			// if on a CommentPress Core-enabled group site
			if ( $is_groupsite ) {

				// get group ID from POST
				global $bp_groupsites;
				$group_id = $bp_groupsites->activity->get_group_id_from_comment_form();

				// kick out if not a comment in a group
				if ( false === $group_id ) return $activity;

				// set activity type
				$type = 'new_groupsite_comment';

			} else {

				// do we have the function we need to call?
				if ( function_exists( 'bpwpapers_is_working_paper' ) ) {

					// which blog?
					$blog_id = $activity->item_id;

					// only on working papers
					if ( ! bpwpapers_is_working_paper( $blog_id ) ) return $activity;

					// get the group ID for this blog
					$group_id = bpwpapers_get_group_by_blog_id( $blog_id );

					// sanity check
					if ( $group_id === false ) return $activity;

					// set activity type
					$type = 'new_working_paper_comment';

					// working paper is active
					$is_working_paper = true;

				}

			}

		}

		// sanity check
		if ( ! $is_groupblog AND ! $is_groupsite AND ! $is_working_paper ) return $activity;

		// okay, let's get the group object
		$group = groups_get_group( array( 'group_id' => $group_id ) );

		// see if we already have the modified activity for this blog post
		$id = bp_activity_get_activity_id( array(
			'user_id' => $activity->user_id,
			'type' => $type,
			'item_id' => $group_id,
			'secondary_item_id' => $activity->secondary_item_id
		) );

		// if we don't find a modified item
		if ( ! $id ) {

			// see if we have an unmodified activity item
			$id = bp_activity_get_activity_id( array(
				'user_id' => $activity->user_id,
				'type' => $activity->type,
				'item_id' => $activity->item_id,
				'secondary_item_id' => $activity->secondary_item_id
			) );

		}

		// If we found an activity for this blog comment then overwrite that to avoid having
		// multiple activities for every blog comment edit
		if ( $id ) $activity->id = $id;

		// get the comment
		$comment = get_comment( $activity->secondary_item_id );

		// get the post
		$post = get_post( $comment->comment_post_ID );

		// was it a registered user?
		if ($comment->user_id != '0') {

			// get user details
			$user = get_userdata( $comment->user_id );

			// construct user link
			$user_link = bp_core_get_userlink( $activity->user_id );

		} else {

			// show anonymous user
			$user_link = '<span class="anon-commenter">' . __( 'Anonymous', 'commentpress-core' ) . '</span>';

		}

		// if on a CommentPress Core-enabled groupblog
		if ( $is_groupblog ) {

			// allow plugins to override the name of the activity item
			$activity_name = apply_filters(
				'cp_activity_post_name',
				__( 'post', 'commentpress-core' )
			);

		}

		// if on a CommentPress Core-enabled group site
		if ( $is_groupsite ) {

			// respect BP Group Sites filter for the name of the activity item
			$activity_name = apply_filters(
				'bpgsites_activity_post_name',
				__( 'post', 'commentpress-core' ),
				$post
			);

		}

		// if on a CommentPress Core-enabled working paper
		if ( $is_working_paper ) {

			// respect BP Working Papers filter for the name of the activity item
			$activity_name = apply_filters(
				'bpwpapers_activity_post_name',
				__( 'post', 'commentpress-core' ),
				$post
			);

		}

		// set key
		$key = '_cp_comment_page';

		// if the custom field has a value, we have a subpage comment
		if ( get_comment_meta( $comment->comment_ID, $key, true ) != '' ) {

			// get comment's page from meta
			$page_num = get_comment_meta( $comment->comment_ID, $key, true );

			// get the url for the comment
			$link = commentpress_get_post_multipage_url( $page_num ) . '#comment-' . $comment->comment_ID;

			// amend the primary link
			$activity->primary_link = $link;

			// init target link
			$target_post_link = '<a href="' . commentpress_get_post_multipage_url( $page_num, $post ) . '">' .
									esc_html( $post->post_title ) .
								'</a>';

		} else {

			// init target link
			$target_post_link = '<a href="' . get_permalink( $post->ID ) . '">' .
									esc_html( $post->post_title ) .
								'</a>';

		}

		// construct links
		$comment_link = '<a href="' . $activity->primary_link . '">' . __( 'comment', 'commentpress-core' ) . '</a>';
		$group_link = '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_html( $group->name ) . '</a>';

		// Replace the necessary values to display in group activity stream
		$activity->action = sprintf(
			__( '%s left a %s on a %s %s in the group %s:', 'commentpress-core' ),
			$user_link,
			$comment_link,
			$activity_name,
			$target_post_link,
			$group_link
		);

		// allow plugins to override this
		$activity->action = apply_filters(
			'commentpress_comment_activity_action', // hook
			$activity->action, // default
			$activity,
			$user_link,
			$comment_link,
			$activity_name,
			$target_post_link,
			$group_link
		);

		// apply group id
		$activity->item_id = (int)$group_id;

		// change to groups component
		$activity->component = 'groups';

		// having marked all groupblogs as public, we need to hide activity from them if the group is private
		// or hidden, so they don't show up in sitewide activity feeds.
		if ( 'public' != $group->status ) {
			$activity->hide_sitewide = true;
		} else {
			$activity->hide_sitewide = false;
		}

		// set unique type
		$activity->type = $type;

		// note: BuddyPress seemingly runs content through wp_filter_kses (sad face)

		// prevent from firing again
		remove_action( 'bp_activity_before_save', array( $this, 'group_custom_comment_activity' ) );

		// --<
		return $activity;

	}



	/**
	 * Add some meta for the activity item - bp_activity_after_save doesn't seem to fire.
	 *
	 * @param object $activity The existing activity object
	 * @return object $activity The modified activity object
	 */
	public function groupblog_custom_comment_meta( $activity ) {

		// only deal with comments
		if ( ( $activity->type != 'new_groupblog_comment' ) ) return $activity;

		// only do this on CommentPress Core-enabled groupblogs
		if ( ( false === $this->_is_commentpress_groupblog() ) ) return $activity;

		// set a meta value for the blog type of the post
		$meta_value = $this->_get_groupblog_type();
		$result = bp_activity_update_meta( $activity->id, 'groupblogtype', 'groupblogtype-' . $meta_value );

		// prevent from firing again
		remove_action( 'bp_activity_after_save', array( $this, 'groupblog_custom_comment_meta' ) );

		// --<
		return $activity;

	}



	/**
	 * Record the blog post activity for the group.
	 *
	 * Adapted from code by Luiz Armesto.
	 *
	 * Since the updates to BP Groupblog, a second argument is passed to this
	 * method which, if present, means that we don't need to check for an
	 * existing activity item. This code needs to be streamlined in the light
	 * of the changes.
	 *
	 * @see bp_groupblog_set_group_to_post_activity( $activity )
	 *
	 * @param object $activity The existing activity object
	 * @param array $args {
	 *     Optional. Handy if you've already parsed the blog post and group ID.
	 *     @type WP_Post $post The WP post object.
	 *     @type int $group_id The group ID.
	 * }
	 * @return object $activity The modified activity object
	 */
	public function groupblog_custom_post_activity( $activity, $args = array() ) {

		// sanity check
		if ( ! bp_is_active( 'groups' ) ) return $activity;

		// only on new blog posts
		if ( ( $activity->type != 'new_blog_post' ) ) return $activity;

		// only on CommentPress Core-enabled groupblogs
		if ( ( false === $this->_is_commentpress_groupblog() ) ) return $activity;

		// clarify data
		$blog_id = $activity->item_id;
		$post_id = $activity->secondary_item_id;
		$post = get_post( $post_id );

		// get group id
		$group_id = get_groupblog_group_id( $blog_id );
		if ( ! $group_id ) return $activity;

		// get group
		$group = groups_get_group( array( 'group_id' => $group_id ) );

		// see if we already have the modified activity for this blog post
		$id = bp_activity_get_activity_id( array(
			'user_id' => $activity->user_id,
			'type' => 'new_groupblog_post',
			'item_id' => $group_id,
			'secondary_item_id' => $activity->secondary_item_id
		) );

		// if we don't find a modified item
		if ( ! $id ) {

			// see if we have an unmodified activity item
			$id = bp_activity_get_activity_id( array(
				'user_id' => $activity->user_id,
				'type' => $activity->type,
				'item_id' => $activity->item_id,
				'secondary_item_id' => $activity->secondary_item_id
			) );

		}

		// If we found an activity for this blog post then overwrite that to avoid
		// having multiple activities for every blog post edit
		if ( $id ) {
			$activity->id = $id;
		}

		// allow plugins to override the name of the activity item
		$activity_name = apply_filters(
			'cp_activity_post_name',
			__( 'post', 'commentpress-core' )
		);

		// default to standard BuddyPress author
		$activity_author = bp_core_get_userlink( $post->post_author );

		// compat with Co-Authors Plus
		if ( function_exists( 'get_coauthors' ) ) {

			// get multiple authors
			$authors = get_coauthors();

			// if we get some
			if ( ! empty( $authors ) ) {

				// we only want to override if we have more than one
				if ( count( $authors ) > 1 ) {

					// use the Co-Authors format of "name, name, name and name"
					$activity_author = '';

					// init counter
					$n = 1;

					// find out how many author we have
					$author_count = count( $authors );

					// loop
					foreach( $authors AS $author ) {

						// default to comma
						$sep = ', ';

						// if we're on the penultimate
						if ( $n == ($author_count - 1) ) {

							// use ampersand
							$sep = __( ' &amp; ', 'commentpress-core' );

						}

						// if we're on the last, don't add
						if ( $n == $author_count ) { $sep = ''; }

						// add name
						$activity_author .= bp_core_get_userlink( $author->ID );

						// and separator
						$activity_author .= $sep;

						// increment
						$n++;

					}

				}

			}

		}

		// if we're replacing an item, show different message
		if ( $id ) {

			// replace the necessary values to display in group activity stream
			$activity->action = sprintf(
				__( '%s updated a %s %s in the group %s:', 'commentpress-core' ),
				$activity_author,
				$activity_name,
				'<a href="' . get_permalink( $post->ID ) . '">' . esc_attr( $post->post_title ) . '</a>',
				'<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>'
			);

		} else {

			// replace the necessary values to display in group activity stream
			$activity->action = sprintf(
				__( '%s wrote a new %s %s in the group %s:', 'commentpress-core' ),
				$activity_author,
				$activity_name,
				'<a href="' . get_permalink( $post->ID ) . '">' . esc_attr( $post->post_title ) . '</a>',
				'<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>'
			);

		}

		$activity->item_id = (int)$group_id;
		$activity->component = 'groups';

		// having marked all groupblogs as public, we need to hide activity from them if the group is private
		// or hidden, so they don't show up in sitewide activity feeds.
		if ( 'public' != $group->status ) {
			$activity->hide_sitewide = true;
		} else {
			$activity->hide_sitewide = false;
		}

		// CMW: assume groupblog_post is intended
		$activity->type = 'new_groupblog_post';

		// prevent from firing again
		remove_action( 'bp_activity_before_save', array( $this, 'groupblog_custom_post_activity' ) );

		// using this function outside BP's save routine requires us to manually save
		if ( ! empty( $args['post'] ) ) {
			$activity->save();
		}

		// --<
		return $activity;

	}



	/**
	 * Detects a post edit and modifies the activity entry if found.
	 *
	 * This is needed for BuddyPress 2.2+. Older versions of BuddyPress continue
	 * to use the {@link bp_groupblog_set_group_to_post_activity()} function.
	 *
	 * This is copied from BP Groupblog and amended to suit.
	 *
	 * @see bp_groupblog_catch_transition_post_type_status()
	 *
	 * @since 3.8.5
	 *
	 * @param str $new_status New status for the post.
	 * @param str $old_status Old status for the post.
	 * @param object $post The post data.
	 * @return void
	 */
	public function transition_post_type_status( $new_status, $old_status, $post ) {

		// only needed for >= BP 2.2
		if ( ! function_exists( 'bp_activity_post_type_update' ) ) return;

		// bail if not a blog post
		if ( 'post' !== $post->post_type ) return;

		// is this an edit?
		if ( $new_status === $old_status ) {

			// an edit of an existing post should update the existing activity item
			if ( $new_status == 'publish' ) {

				// get group ID
				$group_id = get_groupblog_group_id( get_current_blog_id() );

				// get existing activity ID
				$id = bp_activity_get_activity_id( array(
					'component'         => 'groups',
					'type'              => 'new_groupblog_post',
					'item_id'           => $group_id,
					'secondary_item_id' => $post->ID
				) );

				// bail if we don't have one
				if ( empty( $id ) ) return;

				// retrieve activity item and modify some properties
				$activity = new BP_Activity_Activity( $id );
				$activity->content = $post->post_content;
				$activity->date_recorded = bp_core_current_time();

				// we currently have to fool `$this->groupblog_custom_post_activity()`
				$activity->type = 'new_blog_post';

				// pass activity to our edit function
				$this->groupblog_custom_post_activity( $activity, array(
					'group_id' => $group_id,
					'post'     => $post,
				) );

			}

		}

	}



	/**
	 * Add some meta for the activity item. (DISABLED)
	 *
	 * @param object $activity The existing activity object
	 * @return object $activity The modified activity object
	 */
	public function groupblog_custom_post_meta( $activity ) {

		// only on new blog posts
		if ( ( $activity->type != 'new_groupblog_post' ) ) return;

		// only on CommentPress Core-enabled groupblogs
		if ( ( false === $this->_is_commentpress_groupblog() ) ) return;

		// set a meta value for the blog type of the post
		$meta_value = $this->_get_groupblog_type();
		bp_activity_update_meta( $activity->id, 'groupblogtype', 'groupblogtype-' . $meta_value );

		// --<
		return $activity;

	}



	/**
	 * Check if a group has a CommentPress Core-enabled groupblog.
	 *
	 * @param int $group_id The numeric ID of the BuddyPress group
	 * @return boolean True if group has CommentPress Core groupblog, false otherwise
	 */
	public function group_has_commentpress_groupblog( $group_id = null ) {

		// do we have groupblogs enabled?
		if ( function_exists( 'get_groupblog_group_id' ) ) {

			// did we get a specific group passed in?
			if ( is_null( $group_id ) ) {

				// use BuddyPress API
				$group_id = bp_get_current_group_id();

				// unlikely, but if we don't get one
				if ( empty( $group_id ) ) {

					// try and get ID from BuddyPress
					global $bp;

					if ( isset( $bp->groups->current_group->id ) ) {
						$group_id = $bp->groups->current_group->id;
					}

				}

			}

			// yes, is this blog a groupblog?
			if ( ! empty( $group_id ) AND is_numeric( $group_id ) ) {

				// is it CommentPress Core-enabled?

				// get group blogtype
				$groupblog_type = groups_get_groupmeta( $group_id, 'groupblogtype' );

				// did we get one?
				if ( $groupblog_type ) {

					// yes
					return true;

				}

			}

		}

		// --<
		return false;

	}



	/**
	 * Add a filter option to the filter select box on group activity pages.
	 *
	 * @return void
	 */
	public function groupblog_comments_filter_option() {

		// default name
		$comment_name = __( 'CommentPress Comments', 'commentpress-core' );

		// allow plugins to override the name of the option
		$comment_name = apply_filters( 'cp_groupblog_comment_name', $comment_name );

		// construct option
		$option = '<option value="new_groupblog_comment">' . $comment_name . '</option>' . "\n";

		// print
		echo $option;

	}



	/**
	 * Override the name of the filter item.
	 *
	 * @return void
	 */
	public function groupblog_posts_filter_option() {

		// default name
		$name = __( 'CommentPress Posts', 'commentpress-core' );

		// allow plugins to override the name of the option
		$name = apply_filters( 'cp_groupblog_post_name', $name );

		// construct option
		$option = '<option value="new_groupblog_post">' . $name . '</option>' . "\n";

		// print
		echo $option;

	}



	/**
	 * For group blogs, override the avatar with that of the group.
	 *
	 * @param str $avatar The existing HTML for displaying an avatar
	 * @param int $blog_id The numeric ID of the WordPress blog
	 * @param array $args Additional arguments
	 * @return str $avatar The modified HTML for displaying an avatar
	 */
	public function get_blog_avatar( $avatar, $blog_id = '', $args ){

		// do we have groupblogs?
		if ( function_exists( 'get_groupblog_group_id' ) ) {

			// get the group id
			$group_id = get_groupblog_group_id( $blog_id );

		}

		// did we get a group for which this is the group blog?
		if ( isset( $group_id ) ) {

			// --<
			return bp_core_fetch_avatar( array( 'item_id' => $group_id, 'object' => 'group' ) );

		} else {

			// --<
			return $avatar;

		}

	}



	/**
	 * Override the name of the sub-nav item.
	 *
	 * @param str $name The existing name of a "blog"
	 * @return str $name The modified name of a "blog"
	 */
	public function filter_blog_name( $name ) {

		// get group blogtype
		$groupblog_type = groups_get_groupmeta( bp_get_current_group_id(), 'groupblogtype' );

		// did we get one?
		if ( $groupblog_type ) {

			// yes, it's a CommentPress Core-enabled groupblog
			return apply_filters( 'cpmu_bp_groupblog_subnav_item_name', __( 'Document', 'commentpress-core' ) );

		}

		// --<
		return $name;

	}



	/**
	 * Override the slug of the sub-nav item.
	 *
	 * @param str $slug The existing slug of a "blog"
	 * @return str $slug The modified slug of a "blog"
	 */
	public function filter_blog_slug( $slug ) {

		// get group blogtype
		$groupblog_type = groups_get_groupmeta( bp_get_current_group_id(), 'groupblogtype' );

		// did we get one?
		if ( $groupblog_type ) {

			// yes, it's a CommentPress Core-enabled groupblog
			return apply_filters( 'cpmu_bp_groupblog_subnav_item_slug', 'document' );

		}

		// --<
		return $slug;

	}



	/**
	 * Override CommentPress Core "Title Page".
	 *
	 * @param str $title The existing title of a "blog"
	 * @return str $title The modified title of a "blog"
	 */
	public function filter_nav_title_page_title( $title ) {

		// bail if main BuddyPress site
		if ( bp_is_root_blog() ) return $title;

		// override default link name
		return apply_filters( 'cpmu_bp_nav_title_page_title', __( 'Document Home Page', 'commentpress-core' ) );

	}



	/**
	 * Remove group blogs from blog list.
	 *
	 * @param bool $b True if there are blogs, false otherwise
	 * @param object $blogs The existing blogs object
	 * @return object $blogs The modified blogs object
	 */
	public function remove_groupblog_from_loop( $b, $blogs ) {

		// loop through them
		foreach ( $blogs->blogs as $key => $blog ) {

			// exclude if it's a group blog
			if ( function_exists( 'groupblog_group_id' ) ) {

				// get group id
				$group_id = get_groupblog_group_id( $blog->blog_id );

				// did we get one?
				if ( is_numeric( $group_id ) ) {

					// exclude
					unset( $blogs->blogs[$key] );

					// recalculate global values
					$blogs->blog_count = $blogs->blog_count - 1;
					$blogs->total_blog_count = $blogs->total_blog_count - 1;
					$blogs->pag_num = $blogs->pag_num - 1;

				}

			}

		}

		// renumber the array keys to account for missing items
		$blogs_new = array_values( $blogs->blogs );
		$blogs->blogs = $blogs_new;

		return $blogs;

	}



	/**
	 * Override the name of the button on the BuddyPress "blogs" screen.
	 *
	 * @param array $button The existing blogs button data
	 * @return array $button The existing blogs button data
	 */
	public function get_blogs_visit_blog_button( $button ) {

		/*
		[id] => visit_blog
		[component] => blogs
		[must_be_logged_in] =>
		[block_self] =>
		[wrapper_class] => blog-button visit
		[link_href] => http://domain/site-slug/
		[link_class] => blog-button visit
		[link_text] => Visit Site
		[link_title] => Visit Site
		*/

		// init
		$blogtype = 'blog';

		// access global
		global $blogs_template;

		// do we have groupblogs enabled?
		if ( function_exists( 'get_groupblog_group_id' ) ) {

			// get group id
			$group_id = get_groupblog_group_id( $blogs_template->blog->blog_id );

			// yes, is this blog a groupblog?
			if ( is_numeric( $group_id ) ) {

				// is it CommentPress Core-enabled?

				// get group blogtype
				$groupblog_type = groups_get_groupmeta( $group_id, 'groupblogtype' );

				// did we get one?
				if ( $groupblog_type ) {

					// yes
					$blogtype = 'commentpress-groupblog';

				} else {

					// standard groupblog
					$blogtype = 'groupblog';

				}

			}

		} else {

			// TODO: is this blog CommentPress Core-enabled?
			// we cannot do this without switch_to_blog at the moment
			$blogtype = 'blog';

		}

		// switch by blogtype
		switch ( $blogtype ) {

			// standard sub-site
			case 'blog':
				$label = __( 'View Site', 'commentpress-core' );
				$button['link_text'] = $label;
				$button['link_title'] = $label;
				break;

			// CommentPress Core sub-site
			case 'commentpress':
				$label = __( 'View Document', 'commentpress-core' );
				$button['link_text'] = apply_filters( 'cp_get_blogs_visit_blog_button', $label );
				$button['link_title'] = apply_filters( 'cp_get_blogs_visit_blog_button', $label );
				break;

			// standard groupblog
			case 'groupblog':
				$label = __( 'View Group Blog', 'commentpress-core' );
				$button['link_text'] = $label;
				$button['link_title'] = $label;
				break;

			// CommentPress Core sub-site
			case 'commentpress-groupblog':
				$label = __( 'View Document', 'commentpress-core' );
				$button['link_text'] = apply_filters( 'cp_get_blogs_visit_groupblog_button', $label );
				$button['link_title'] = apply_filters( 'cp_get_blogs_visit_groupblog_button', $label );
				break;

		}

		// --<
		return $button;

	}



	/**
	 * Override the name of the type dropdown label.
	 *
	 * @param str $name The existing name of the label
	 * @return str $name The modified name of the label
	 */
	public function blog_type_label( $name ) {

		return apply_filters( 'cp_class_commentpress_formatter_label', __( 'Default Text Format', 'commentpress-core' ) );

	}



	/**
	 * Define the "types" of groupblog.
	 *
	 * @param array $existing_options The existing types of groupblog
	 * @return array $existing_options The modified types of groupblog
	 */
	public function blog_type_options( $existing_options ) {

		// define types
		$types = array(
			__( 'Prose', 'commentpress-core' ), // types[0]
			__( 'Poetry', 'commentpress-core' ), // types[1]
		);

		// --<
		return apply_filters( 'cp_class_commentpress_formatter_types', $types );

	}



	/**
	 * Enable workflow.
	 *
	 * @param bool $exists True if "workflow" is enabled, false otherwise
	 * @return bool $exists True if "workflow" is enabled, false otherwise
	 */
	public function blog_workflow_exists( $exists ) {

		// switch on, but allow overrides
		return apply_filters( 'cp_class_commentpress_workflow_enabled', true );

	}



	/**
	 * Override the name of the workflow checkbox label.
	 *
	 * @param str $name The existing singular name of the label
	 * @return str $name The modified singular name of the label
	 */
	public function blog_workflow_label( $name ) {

		// set label, but allow overrides
		return apply_filters( 'cp_class_commentpress_workflow_label', __( 'Enable Translation Workflow', 'commentpress-core' ) );

	}



	/**
	 * Amend the group meta if workflow is enabled.
	 *
	 * @param str $blog_type The existing numerical type of the blog
	 * @return str $blog_type The modified numerical type of the blog
	 */
	public function group_meta_set_blog_type( $blog_type, $blog_workflow ) {

		// if the blog workflow is enabled, then this is a translation group
		if ( $blog_workflow == '1' ) {

			// translation is type 2
			$blog_type = '2';

		}

		/**
		 * Allow plugins to override the blog type - for example if workflow
		 * is enabled, it might become a new blog type as far as BuddyPress
		 * is concerned.
		 *
		 * @param int $blog_type The numeric blog type
		 * @param bool $blog_workflow True if workflow enabled, false otherwise
		 */
		return apply_filters( 'cp_class_commentpress_workflow_group_blogtype', $blog_type, $blog_workflow );

	}



	/**
	 * Hook into the group blog signup form.
	 *
	 * @param array $errors The errors generated previously
	 * @return void
	 */
	public function signup_blogform( $errors ) {

		// apply to group blog signup form?
		if ( bp_is_groups_component() ) {

			// hand off to private method
			$this->_create_groupblog_options();

		} else {

			// hand off to private method
			$this->_create_blog_options();

		}

	}



	/**
	 * Hook into wpmu_new_blog and target plugins to be activated.
	 *
	 * @param int $blog_id The numeric ID of the WordPress blog
	 * @param int $user_id The numeric ID of the WordPress user
	 * @param str $domain The domain of the WordPress blog
	 * @param str $path The path of the WordPress blog
	 * @param int $site_id The numeric ID of the WordPress parent site
	 * @param array $meta The meta data of the WordPress blog
	 * @return void
	 */
	public function wpmu_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

		// test for presence of our checkbox variable in _POST
		if ( isset( $_POST['cpbp-groupblog'] ) AND $_POST['cpbp-groupblog'] == '1' ) {

			// hand off to private method
			$this->_create_groupblog( $blog_id, $user_id, $domain, $path, $site_id, $meta );

		} else {

			// test for presence of our checkbox variable in _POST
			if ( isset( $_POST['cpbp-new-blog'] ) AND $_POST['cpbp-new-blog'] == '1' ) {

				// hand off to private method
				$this->_create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta );

			}

		}

	}



	/**
	 * Override the title of the "Create a new document" link.
	 *
	 * @return str Ther overridden name of the link
	 */
	public function user_links_new_site_title() {

		// override default link name
		return apply_filters( 'cpmu_bp_create_new_site_title', __( 'Create a New Site', 'commentpress-core' ) );

	}



	/**
	 * Check if a non-public group is being accessed by a user who is not a
	 * member of the group.
	 *
	 * Adapted from code in mahype's fork of BuddyPress Groupblog plugin, but not
	 * accepted because there may be cases where private groups have public
	 * groupblogs. Ours is not such a case.
	 *
	 * @see groupblog_privacy_check()
	 *
	 * @return void
	 */
	public function groupblog_privacy_check() {

		// allow network admins through regardless
		if ( is_super_admin() ) return;

		// check our site option
		if ( $this->db->option_get( 'cpmu_bp_groupblog_privacy' ) != '1' ) return;

		global $blog_id, $current_user;

		// if is not the main blog but we do have a blog ID
		if( ! is_main_site() AND isset( $blog_id ) AND is_numeric( $blog_id ) ) {

			// do we have groupblog active?
			if ( function_exists( 'get_groupblog_group_id' ) ) {

				// get group ID for this blog
				$group_id = get_groupblog_group_id( $blog_id );

				// if we get one
				if( is_numeric( $group_id ) ) {

					// get the group object
					$group = new BP_Groups_Group( $group_id );

					// if group is not public
					if( $group->status != 'public' ) {

						// is the groupblog CommentPress Core enabled?
						if ( $this->group_has_commentpress_groupblog( $group->id ) ) {

							// is the current user a member of the blog?
							if ( ! is_user_member_of_blog( $current_user->ID, $blog_id ) ) {

								// no - redirect to network home, but allow overrides
								wp_redirect( apply_filters( 'bp_groupblog_privacy_redirect_url', network_site_url() ) );
								exit;

							}

						}

					}

				}

			}

		}

	}



//##############################################################################



	/**
	 * -------------------------------------------------------------------------
	 * Private Methods
	 * -------------------------------------------------------------------------
	 */



	/**
	 * Object initialisation.
	 *
	 * @return void
	 */
	function _init() {

		// register hooks
		$this->_register_hooks();

	}



	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	function _register_hooks() {

		// enable html comments and content for authors
		add_action( 'init', array( $this, 'allow_html_content' ) );

		// check for the privacy of a groupblog
		add_action( 'init', array( $this, 'groupblog_privacy_check' ) );

		// add some tags to the allowed tags in activities
		add_filter( 'bp_activity_allowed_tags', array( $this, 'activity_allowed_tags' ), 20 );

		// allow comment authors to edit their own comments
		add_filter( 'map_meta_cap', array( $this, 'enable_comment_editing' ), 10, 4 );

		// amend comment activity
		add_filter( 'pre_comment_approved', array( $this, 'pre_comment_approved' ), 99, 2 );
		//add_action( 'preprocess_comment', 'my_check_comment', 1 );

		// add pages to the post_types that BuddyPress records comment activity for
		add_filter( 'bp_blogs_record_comment_post_types', array( $this, 'record_comments_on_pages' ), 10, 1 );

		// add pages to the post_types that BuddyPress records published activity for
		//add_filter( 'bp_blogs_record_post_post_types', array( $this, 'record_published_pages' ), 10, 1 );

		// make sure "Allow activity stream commenting on blog and forum posts" is disabled
		add_action( 'bp_disable_blogforum_comments', array( $this, 'disable_blogforum_comments' ), 20, 1 );

		// override "publicness" of groupblogs
		add_filter( 'bp_is_blog_public', array( $this, 'is_blog_public' ), 20, 1 );

		// amend BuddyPress group activity (after class Commentpress_Core does)
		add_action( 'bp_setup_globals', array( $this, '_group_activity_mods' ), 1001 );

		// get group avatar when listing groupblogs
		add_filter( 'bp_get_blog_avatar', array( $this, 'get_blog_avatar' ), 20, 3 );

		// filter bp-groupblog defaults
		add_filter( 'bp_groupblog_subnav_item_name', array( $this, 'filter_blog_name' ), 20 );
		add_filter( 'bp_groupblog_subnav_item_slug', array( $this, 'filter_blog_slug' ), 20 );

		// override CommentPress Core "Title Page"
		add_filter( 'cp_nav_title_page_title', array( $this, 'filter_nav_title_page_title' ), 20 );

		// override the name of the button on the BuddyPress "blogs" screen
		// to override this, just add the same filter with a priority of 21 or greater
		add_filter( 'bp_get_blogs_visit_blog_button', array( $this, 'get_blogs_visit_blog_button' ), 20 );

		// we can remove groupblogs from the blog list, but cannot update the total_blog_count_for_user
		// that is displayed on the tab *before* the blog list is built - hence filter disabled for now
		//add_filter( 'bp_has_blogs', array( $this, 'remove_groupblog_from_loop' ), 20, 2 );

		/**
		 * Duplicated from 'class_commentpress_formatter.php' because CommentPress Core need
		 * not be active on the main blog, but we still need the options to appear
		 * in the Groupblog Create screen
		 */

		// set blog type options
		add_filter( 'cp_blog_type_options', array( $this, 'blog_type_options' ), 21 );

		// set blog type options label
		add_filter( 'cp_blog_type_label', array( $this, 'blog_type_label' ), 21 );

		// ---------------------------------------------------------------------

		/**
		 * Duplicated from 'class_commentpress_workflow.php' because CommentPress Core need
		 * not be active on the main blog, but we still need the options to appear
		 * in the Groupblog Create screen
		 */

		// enable workflow
		add_filter( 'cp_blog_workflow_exists', array( $this, 'blog_workflow_exists' ), 21 );

		// override label
		add_filter( 'cp_blog_workflow_label', array( $this, 'blog_workflow_label' ), 21 );

		// override blog type if workflow is on
		add_filter( 'cp_get_group_meta_for_blog_type', array( $this, 'group_meta_set_blog_type' ), 21, 2 );

		// ---------------------------------------------------------------------

		// add form elements to groupblog form
		add_action( 'signup_blogform', array( $this, 'signup_blogform' ) );

		// activate blog-specific CommentPress Core plugin
		// added @ priority 20 because BuddyPress Groupblog adds its action at the default 10 and
		// we want it to have done its stuff before we do ours
		add_action( 'wpmu_new_blog', array( $this, 'wpmu_new_blog' ), 20, 6 );

		// register any public styles
		add_action( 'wp_enqueue_scripts', array( $this, 'add_frontend_styles' ), 20 );

		// override CommentPress Core "Create New Document" text
		add_filter( 'cp_user_links_new_site_title', array( $this, 'user_links_new_site_title' ), 21 );
		add_filter( 'cp_site_directory_link_title', array( $this, 'user_links_new_site_title' ), 21 );
		add_filter( 'cp_register_new_site_page_title', array( $this, 'user_links_new_site_title' ), 21 );

		// override groupblog theme, if the bp-groupblog default theme is not a CommentPress Core one
		add_filter( 'cp_forced_theme_slug', array( $this, '_get_groupblog_theme' ), 20, 1 );
		add_filter( 'cp_forced_theme_name', array( $this, '_get_groupblog_theme' ), 20, 1 );

		// is this the back end?
		if ( is_admin() ) {

			// anything specifically for WP Admin

			// add options to network settings form
			add_filter( 'cpmu_network_options_form', array( $this, '_network_admin_form' ), 20 );

			// add options to reset array
			add_filter( 'cpmu_db_bp_options_get_defaults', array( $this, '_get_default_settings' ), 20, 1 );

			// hook into Network BuddyPress form update
			add_action( 'cpmu_db_options_update', array( $this, '_buddypress_admin_update' ), 20 );

		} else {

			// anything specifically for Front End

			// add filter options for the post and comment activities as late as we can
			// so that bp-groupblog's action can be removed
			add_action( 'bp_setup_globals', array( $this, '_groupblog_filter_options' ) );

		}

	}



	/**
	 * Add a filter actions once BuddyPress is loaded.
	 *
	 * @return void
	 */
	function _groupblog_filter_options() {

		// kick out if this group does not have a CommentPress Core groupblog
		if ( ! $this->group_has_commentpress_groupblog() ) return;

		// remove bp-groupblog's contradictory option
		remove_action( 'bp_group_activity_filter_options', 'bp_groupblog_posts' );

		// add our consistent one
		add_action( 'bp_activity_filter_options', array( $this, 'groupblog_posts_filter_option' ) );
		add_action( 'bp_group_activity_filter_options', array( $this, 'groupblog_posts_filter_option' ) );
		add_action( 'bp_member_activity_filter_options', array( $this, 'groupblog_posts_filter_option' ) );

		// add our comments
		add_action( 'bp_activity_filter_options', array( $this, 'groupblog_comments_filter_option' ) );
		add_action( 'bp_group_activity_filter_options', array( $this, 'groupblog_comments_filter_option' ) );
		add_action( 'bp_member_activity_filter_options', array( $this, 'groupblog_comments_filter_option' ) );

	}



	/**
	 * Amend Activity methods once BuddyPress is loaded.
	 *
	 * @return void
	 */
	function _group_activity_mods() {

		// don't mess with hooks unless the blog is CommentPress Core-enabled
		if ( ( false === $this->_is_commentpress_groupblog() ) ) return;

		// allow lists in activity content
		add_action( 'bp_activity_allowed_tags', array( $this, '_activity_allowed_tags' ), 20, 1 );

		// drop the bp-groupblog post activity actions
		remove_action( 'bp_activity_before_save', 'bp_groupblog_set_group_to_post_activity' );
		remove_action( 'transition_post_status', 'bp_groupblog_catch_transition_post_type_status' );

		// implement our own post activity (with Co-Authors compatibility)
		add_action( 'bp_activity_before_save', array( $this, 'groupblog_custom_post_activity' ), 20, 1 );
		add_action( 'transition_post_status', array( $this, 'transition_post_type_status' ), 20, 3 );

		// CommentPress Core needs to know the sub-page for a comment, therefore:

		// drop the bp-group-sites comment activity action, if present
		global $bp_groupsites;
		if ( ! is_null( $bp_groupsites ) AND is_object( $bp_groupsites ) ) {
			remove_action( 'bp_activity_before_save', array( $bp_groupsites->activity, 'custom_comment_activity' ) );
		}

		// drop the bp-working-papers comment activity action, if present
		global $bp_working_papers;
		if ( ! is_null( $bp_working_papers ) AND is_object( $bp_working_papers ) ) {
			remove_action( 'bp_activity_before_save', array( $bp_working_papers->activity, 'custom_comment_activity' ) );
		}

		// add our own custom comment activity
		add_action( 'bp_activity_before_save', array( $this, 'group_custom_comment_activity' ), 20, 1 );

		// these don't seem to fire to allow us to add our meta values for the items
		// instead, I'm trying to store the blog_type as group meta data
		//add_action( 'bp_activity_after_save', array( $this, 'groupblog_custom_comment_meta' ), 20, 1 );
		//add_action( 'bp_activity_after_save', array( $this, 'groupblog_custom_post_meta' ), 20, 1 );

	}



	/**
	 * Allow our TinyMCE comment markup in activity content.
	 *
	 * @param array $activity_allowedtags The array of tags allowed in an activity item
	 * @param array $activity_allowedtags The modified array of tags allowed in an activity item
	 */
	function _activity_allowed_tags( $activity_allowedtags ) {

		// lists
		$activity_allowedtags['ul'] = array();
		$activity_allowedtags['ol'] = array();
		$activity_allowedtags['li'] = array();

		// bold
		$activity_allowedtags['strong'] = array();

		// italic
		$activity_allowedtags['em'] = array();

		// underline
		$activity_allowedtags['span']['style'] = array();

		// --<
		return $activity_allowedtags;

	}



	/**
	 * Hook into the groupblog create screen.
	 *
	 * @return void
	 */
	function _create_groupblog_options() {

		global $bp, $groupblog_create_screen;

		$blog_id = get_groupblog_blog_id();

		if ( ! $groupblog_create_screen && $blog_id != '' ) {

			// existing blog and group - do we need to present any options?

		} else {

			// creating a new group - no groupblog exists yet
			// NOTE: need to check that our context is right

			// get force option
			$forced = $this->db->option_get( 'cpmu_bp_force_commentpress' );

			// are we force-enabling CommentPress Core?
			if ( $forced ) {

				// set hidden element
				$forced_html = '
				<input type="hidden" value="1" id="cpbp-groupblog" name="cpbp-groupblog" />
				';

				// define text, but allow overrides
				$text = apply_filters(
					'cp_groupblog_options_signup_text_forced',
					__( 'Select the options for your new CommentPress-enabled blog. Note: if you choose an existing blog as a group blog, setting these options will have no effect.', 'commentpress-core' )
				);

			} else {

				// set checkbox
				$forced_html = '
				<div class="checkbox">
					<label for="cpbp-groupblog"><input type="checkbox" value="1" id="cpbp-groupblog" name="cpbp-groupblog" /> ' . __( 'Enable CommentPress', 'commentpress-core' ) . '</label>
				</div>
				';

				// define text, but allow overrides
				$text = apply_filters(
					'cp_groupblog_options_signup_text',
					__( 'When you create a group blog, you can choose to enable it as a CommentPress blog. This is a "one time only" option because you cannot disable CommentPress from here once the group blog is created. Note: if you choose an existing blog as a group blog, setting this option will have no effect.', 'commentpress-core' )
				);

			}

			// off by default
			$has_workflow = false;

			// init output
			$workflow_html = '';

			// allow overrides
			$has_workflow = apply_filters( 'cp_blog_workflow_exists', $has_workflow );

			// if we have workflow enabled, by a plugin, say
			if ( $has_workflow !== false ) {

				// define workflow label
				$workflow_label = __( 'Enable Custom Workflow', 'commentpress-core' );

				// allow overrides
				$workflow_label = apply_filters( 'cp_blog_workflow_label', $workflow_label );

				// show it
				$workflow_html = '

				<div class="checkbox">
					<label for="cp_blog_workflow"><input type="checkbox" value="1" id="cp_blog_workflow" name="cp_blog_workflow" /> ' . $workflow_label . '</label>
				</div>

				';

			}

			// assume no types
			$types = array();

			// init output
			$type_html = '';

			// but allow overrides for plugins to supply some
			$types = apply_filters( 'cp_blog_type_options', $types );

			// if we got any, use them
			if ( ! empty( $types ) ) {

				// define blog type label
				$type_label = __( 'Document Type', 'commentpress-core' );

				// allow overrides
				$type_label = apply_filters( 'cp_blog_type_label', $type_label );

				// construct options
				$type_option_list = array();
				$n = 0;
				foreach( $types AS $type ) {
					$type_option_list[] = '<option value="' . $n . '">' . $type . '</option>';
					$n++;
				}
				$type_options = implode( "\n", $type_option_list );

				// show it
				$type_html = '

				<div class="dropdown">
					<label for="cp_blog_type">' . $type_label . '</label> <select id="cp_blog_type" name="cp_blog_type">

					' . $type_options . '

					</select>
				</div>

				';

			}

			// construct form
			$form = '

			<br />
			<div id="cp-multisite-options">

				<h3>' . __( 'CommentPress Options', 'commentpress-core' ) . '</h3>

				<p>' . $text . '</p>

				' . $forced_html . '

				' . $workflow_html . '

				' . $type_html . '

			</div>

			';

			echo $form;

		}

	}



	/**
	 * Create a blog that is a groupblog.
	 *
	 * @param int $blog_id The numeric ID of the WordPress blog
	 * @param int $user_id The numeric ID of the WordPress user
	 * @param str $domain The domain of the WordPress blog
	 * @param str $path The path of the WordPress blog
	 * @param int $site_id The numeric ID of the WordPress parent site
	 * @param array $meta The meta data of the WordPress blog
	 * @return void
	 */
	function _create_groupblog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

		// get group id before switch
		$group_id = isset( $_COOKIE['bp_new_group_id'] )
					? $_COOKIE['bp_new_group_id']
					: bp_get_current_group_id();

		// wpmu_new_blog calls this *after* restore_current_blog, so we need to do it again
		switch_to_blog( $blog_id );

		// activate CommentPress Core
		$this->db->install_commentpress();

		// access core
		global $commentpress_core;

		// TODO: create admin page settings for WordPress options

		// show posts by default (allow plugin overrides)
		$posts_or_pages = apply_filters( 'cp_posts_or_pages_in_toc', 'post' );
		$commentpress_core->db->option_set( 'cp_show_posts_or_pages_in_toc', $posts_or_pages );

		// if we opted for posts
		if ( $posts_or_pages == 'post' ) {

			// TOC shows extended posts by default (allow plugin overrides)
			$extended_toc = apply_filters( 'cp_extended_toc', 1 );
			$commentpress_core->db->option_set( 'cp_show_extended_toc', $extended_toc );

		}

		// get blog type (saved already)
		$cp_blog_type = $commentpress_core->db->option_get( 'cp_blog_type' );

		// get workflow (saved already)
		$cp_blog_workflow = $commentpress_core->db->option_get( 'cp_blog_workflow' );

		// did we get a group id before we switched blogs?
		if ( isset( $group_id ) ) {

			/**
			 * Allow plugins to override the blog type - for example if workflow
			 * is enabled, it might become a new blog type as far as BuddyPress
			 * is concerned.
			 *
			 * @param int $cp_blog_type The numeric blog type
			 * @param bool $cp_blog_workflow True if workflow enabled, false otherwise
			 */
			$blog_type = apply_filters( 'cp_get_group_meta_for_blog_type', $cp_blog_type, $cp_blog_workflow );

			// set the type as group meta info
			// we also need to change this when the type is changed from the CommentPress Core admin page
			groups_update_groupmeta( $group_id, 'groupblogtype', 'groupblogtype-' . $blog_type );

		}

		// save
		$commentpress_core->db->options_save();

		// ---------------------------------------------------------------------
		// WordPress Internal Configuration
		// ---------------------------------------------------------------------

		// get commenting option
		$anon_comments = $this->db->option_get( 'cpmu_bp_require_comment_registration' ) == '1' ? 1 : 0;

		/**
		 * Allow overrides for anonymous commenting.
		 *
		 * This may be overridden by an option in the Network Admin settings screen.
		 *
		 * @param bool $anon_comments Value of 1 requires registration, 0 does not
		 */
		$anon_comments = apply_filters( 'cp_require_comment_registration', $anon_comments );

		// update wp option
		update_option( 'comment_registration', $anon_comments );

		// get all network-activated plugins
		$active_sitewide_plugins = maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );

		// did we get any?
		if ( is_array( $active_sitewide_plugins ) AND count( $active_sitewide_plugins ) > 0 ) {

			// loop through them
			foreach( $active_sitewide_plugins AS $plugin_path => $plugin_data ) {

				// if we've got BuddyPress Group Email Subscription network-installed
				if ( false !== strstr( $plugin_path, 'bp-activity-subscription.php' ) ) {

					// switch comments_notify off
					update_option( 'comments_notify', 0 );

					// no need to carry on
					break;

				}

			}

		}

		/**
		 * Allow plugins to add their own config.
		 *
		 * @since 3.8.5
		 *
		 * @param int $blog_id The numeric ID of the WordPress blog
		 * @param int $cp_blog_type The numeric blog type
		 * @param bool $cp_blog_workflow True if workflow enabled, false otherwise
		 */
		do_action( 'cp_new_groupblog_created', $blog_id, $cp_blog_type, $cp_blog_workflow );

		// switch back
		restore_current_blog();

	}



	/**
	 * Hook into the blog create screen on registration page.
	 *
	 * @return void
	 */
	function _create_blog_options() {

		// get force option
		$forced = $this->db->option_get( 'cpmu_force_commentpress' );

		// are we force-enabling CommentPress Core?
		if ( $forced ) {

			// set hidden element
			$forced_html = '
			<input type="hidden" value="1" id="cpbp-new-blog" name="cpbp-new-blog" />
			';

			// define text
			$text = __( 'Select the options for your new CommentPress document.', 'commentpress-core' );

		} else {

			// set checkbox
			$forced_html = '
			<div class="checkbox">
				<label for="cpbp-new-blog"><input type="checkbox" value="1" id="cpbp-new-blog" name="cpbp-new-blog" /> ' . __( 'Enable CommentPress', 'commentpress-core' ) . '</label>
			</div>
			';

			// define text
			$text = __( 'Do you want to make the new site a CommentPress document?', 'commentpress-core' );

		}

		// off by default
		$has_workflow = false;

		// init output
		$workflow_html = '';

		// allow overrides
		$has_workflow = apply_filters( 'cp_blog_workflow_exists', $has_workflow );

		// if we have workflow enabled, by a plugin, say
		if ( $has_workflow !== false ) {

			// define workflow label
			$workflow_label = __( 'Enable Custom Workflow', 'commentpress-core' );

			// allow overrides
			$workflow_label = apply_filters( 'cp_blog_workflow_label', $workflow_label );

			// show it
			$workflow_html = '

			<div class="checkbox">
				<label for="cp_blog_workflow"><input type="checkbox" value="1" id="cp_blog_workflow" name="cp_blog_workflow" /> ' . $workflow_label . '</label>
			</div>

			';

		}

		// assume no types
		$types = array();

		// init output
		$type_html = '';

		// but allow overrides for plugins to supply some
		$types = apply_filters( 'cp_blog_type_options', $types );

		// if we got any, use them
		if ( ! empty( $types ) ) {

			// define blog type label
			$type_label = __( 'Document Type', 'commentpress-core' );

			// allow overrides
			$type_label = apply_filters( 'cp_blog_type_label', $type_label );

			// construct options
			$type_option_list = array();
			$n = 0;
			foreach( $types AS $type ) {
				$type_option_list[] = '<option value="' . $n . '">' . $type . '</option>';
				$n++;
			}
			$type_options = implode( "\n", $type_option_list );

			// show it
			$type_html = '

			<div class="dropdown cp-workflow-type">
				<label for="cp_blog_type">' . $type_label . '</label> <select id="cp_blog_type" name="cp_blog_type">

				' . $type_options . '

				</select>
			</div>

			';

		}

		// construct form
		$form = '

		<br />
		<div id="cp-multisite-options">

			<h4>' . __( 'CommentPress Options', 'commentpress-core' ) . '</h4>

			<p>' . $text . '</p>

			' . $forced_html . '

			' . $workflow_html . '

			' . $type_html . '

		</div>

		';

		echo $form;

	}



	/**
	 * Create a blog that is not a groupblog.
	 *
	 * @param int $blog_id The numeric ID of the WordPress blog
	 * @param int $user_id The numeric ID of the WordPress user
	 * @param str $domain The domain of the WordPress blog
	 * @param str $path The path of the WordPress blog
	 * @param int $site_id The numeric ID of the WordPress parent site
	 * @param array $meta The meta data of the WordPress blog
	 * @return void
	 */
	function _create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

		// wpmu_new_blog calls this *after* restore_current_blog, so we need to do it again
		switch_to_blog( $blog_id );

		// activate CommentPress Core
		$this->db->install_commentpress();

		// switch back
		restore_current_blog();

	}



	/**
	 * Utility to wrap is_groupblog().
	 *
	 * Note that this only tests the current blog and cannot be used to discover
	 * if a specific blog is a CommentPress Core groupblog.
	 *
	 * Also note that this method only functions after 'bp_setup_globals' has
	 * fired with priority 1000.
	 *
	 * @return bool True if current blog is CommentPress Core-enabled, false otherwise
	 */
	function _is_commentpress_groupblog() {

		// check if this blog is a CommentPress Core groupblog
		global $commentpress_core;
		if (
			! is_null( $commentpress_core ) AND
			is_object( $commentpress_core ) AND
			$commentpress_core->is_groupblog()
		) {

			return true;

		}

		return false;

	}



	/**
	 * Utility to discover if this is a BP Group Site.
	 *
	 * @return bool True if current blog is a BP Group Site, false otherwise
	 */
	function _is_commentpress_groupsite() {

		// check if this blog is a CommentPress Core groupsite
		if (
			function_exists( 'bpgsites_is_groupsite' ) AND
			bpgsites_is_groupsite( get_current_blog_id() )
		) {

			return true;

		}

		return false;

	}



	/**
	 * Utility to get blog_type.
	 *
	 * @return mixed String if there is a blog type, false otherwise
	 */
	function _get_groupblog_type() {

		global $commentpress_core;

		// if we have the plugin
		if ( ! is_null( $commentpress_core ) AND is_object( $commentpress_core ) ) {

			// --<
			return $commentpress_core->db->option_get( 'cp_blog_type' ) ;
		}

		// --<
		return false;

	}



	/**
	 * Add our options to the network admin form.
	 *
	 * @return void
	 */
	function _network_admin_form() {

		// define admin page
		$admin_page = '
		<div id="cpmu_bp_admin_options">

		<h3>' . __( 'BuddyPress &amp; Groupblog Settings', 'commentpress-core' ) . '</h3>

		<p>' . __( 'Configure how CommentPress interacts with BuddyPress and BuddyPress Groupblog.', 'commentpress-core' ) . '</p>

		<table class="form-table">

			<tr valign="top">
				<th scope="row"><label for="cpmu_bp_reset">' . __( 'Reset BuddyPress settings', 'commentpress-core' ) . '</label></th>
				<td><input id="cpmu_bp_reset" name="cpmu_bp_reset" value="1" type="checkbox" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="cpmu_bp_force_commentpress">' . __( 'Make all new Groupblogs CommentPress-enabled', 'commentpress-core' ) . '</label></th>
				<td><input id="cpmu_bp_force_commentpress" name="cpmu_bp_force_commentpress" value="1" type="checkbox"' . ( $this->db->option_get( 'cpmu_bp_force_commentpress' ) == '1' ? ' checked="checked"' : '' ) . ' /></td>
			</tr>

			' . $this->_get_commentpress_themes() . '

			<tr valign="top">
				<th scope="row"><label for="cpmu_bp_groupblog_privacy">' . __( 'Private Groups must have Private Groupblogs', 'commentpress-core' ) . '</label></th>
				<td><input id="cpmu_bp_groupblog_privacy" name="cpmu_bp_groupblog_privacy" value="1" type="checkbox"' . ( $this->db->option_get( 'cpmu_bp_groupblog_privacy' ) == '1' ? ' checked="checked"' : '' ) . ' /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="cpmu_bp_require_comment_registration">' . __( 'Require user login to post comments on Groupblogs', 'commentpress-core' ) . '</label></th>
				<td><input id="cpmu_bp_require_comment_registration" name="cpmu_bp_require_comment_registration" value="1" type="checkbox"' . ( $this->db->option_get( 'cpmu_bp_require_comment_registration' ) == '1' ? ' checked="checked"' : '' ) . ' /></td>
			</tr>

			' . $this->_additional_buddypress_options() . '

		</table>

		</div>
		';

		// --<
		return $admin_page;

	}



	/**
	 * Get all CommentPress Core themes.
	 *
	 * @return str $element The HTML form element
	 */
	function _get_commentpress_themes() {

		// get all themes
		if ( function_exists( 'wp_get_themes' ) ) {

			// get theme data the WP3.4 way
			$themes = wp_get_themes(
				false,     // only error-free themes
				'network', // only network-allowed themes
				0          // use current blog as reference
			);

			// get currently selected theme
			$current_theme = $this->db->option_get('cpmu_bp_groupblog_theme');

		} else {

			// pre WP3.4 functions
			$themes = get_themes();

			// get currently selected theme
			$current_theme = $this->db->option_get('cpmu_bp_groupblog_theme_name');

		}

		// init
		$options = array();
		$element = '';

		// we must get *at least* one (the Default), but let's be safe
		if ( ! empty( $themes ) ) {

			// loop
			foreach( $themes AS $theme ) {

				// is it a CommentPress Core Groupblog theme?
				if (
					in_array( 'commentpress', (array) $theme['Tags'] ) AND
					in_array( 'groupblog', (array) $theme['Tags'] )
				) {

					// is this WP3.4+?
					if ( function_exists( 'wp_get_themes' ) ) {

						// use stylesheet as theme data
						$theme_data = $theme->get_stylesheet();

					} else {

						// use name as theme data
						$theme_data = $theme['Title'];

					}

					// is it the currently selected theme?
					$selected = ( $current_theme == $theme_data ) ? ' selected="selected"' : '';

					// add to array
					$options[] = '<option value="' . $theme_data . '" ' . $selected . '>' . $theme['Title'] . '</option>';

				}

			}

			// did we get any?
			if ( ! empty( $options ) ) {

				// implode
				$opts = implode( "\n", $options );

				// define element
				$element = '

				<tr valign="top">
					<th scope="row"><label for="cpmu_bp_groupblog_theme">' . __( 'Select theme for CommentPress Groupblogs', 'commentpress-core' ) . '</label></th>
					<td><select id="cpmu_bp_groupblog_theme" name="cpmu_bp_groupblog_theme">
						' . $opts . '
						</select>
					</td>
				</tr>

				';

			}

		}

		// --<
		return $element;

	}



	/**
	 * Get Groupblog theme as defined in Network BuddyPress admin.
	 *
	 * @param str $default_theme The existing theme
	 * @return str $theme The modified theme
	 */
	function _get_groupblog_theme( $default_theme ) {

		// get the theme we've defined as the default for groupblogs
		$theme = $this->db->option_get( 'cpmu_bp_groupblog_theme' );

		// --<
		return $theme;

	}



	/**
	 * Allow other plugins to hook into our multisite admin options.
	 *
	 * @return str Empty string, but plugins may send content back
	 */
	function _additional_buddypress_options() {

		// return whatever plugins send back
		return apply_filters( 'cpmu_network_buddypress_options_form', '' );

	}



	/**
	 * Get default BuddyPress-related settings.
	 *
	 * @param array $existing_options The existing options data aray
	 * @return array $options The modified options data aray
	 */
	function _get_default_settings( $existing_options ) {

		// is this WP3.4+?
		if ( function_exists( 'wp_get_themes' ) ) {

			// use stylesheet as theme data
			$theme_data = $this->groupblog_theme;

		} else {

			// use name as theme data
			$theme_data = $this->groupblog_theme_name;

		}

		// define BuddyPress and BuddyPress Groupblog defaults
		$defaults = array(
			'cpmu_bp_force_commentpress' => $this->force_commentpress,
			'cpmu_bp_groupblog_privacy' => $this->groupblog_privacy,
			'cpmu_bp_require_comment_registration' => $this->require_comment_registration,
			'cpmu_bp_groupblog_theme' => $theme_data
		);

		// return defaults, but allow overrides and additions
		return apply_filters( 'cpmu_buddypress_options_get_defaults', $defaults );

	}



	/**
	 * Hook into Network BuddyPress form update.
	 *
	 * @return void
	 */
	function _buddypress_admin_update() {

		// init
		$cpmu_bp_force_commentpress = '0';
		$cpmu_bp_groupblog_privacy = '0';
		$cpmu_bp_require_comment_registration = '0';

		// get variables
		extract( $_POST );

		// force CommentPress Core to be enabled on all groupblogs
		$cpmu_bp_force_commentpress = esc_sql( $cpmu_bp_force_commentpress );
		$this->db->option_set( 'cpmu_bp_force_commentpress', ( $cpmu_bp_force_commentpress ? 1 : 0 ) );

		// groupblog privacy synced to group privacy
		$cpmu_bp_groupblog_privacy = esc_sql( $cpmu_bp_groupblog_privacy );
		$this->db->option_set( 'cpmu_bp_groupblog_privacy', ( $cpmu_bp_groupblog_privacy ? 1 : 0 ) );

		// default groupblog theme
		$cpmu_bp_groupblog_theme = esc_sql( $cpmu_bp_groupblog_theme );
		$this->db->option_set( 'cpmu_bp_groupblog_theme', $cpmu_bp_groupblog_theme );

		// anon comments on groupblogs
		$cpmu_bp_require_comment_registration = esc_sql( $cpmu_bp_require_comment_registration );
		$this->db->option_set( 'cpmu_bp_require_comment_registration', ( $cpmu_bp_require_comment_registration ? 1 : 0 ) );

	}



//##############################################################################



} // class ends



