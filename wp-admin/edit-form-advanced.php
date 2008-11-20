<?php
/**
 * Post advanced form for inclusion in the administration panels.
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Post ID global
 * @name $post_ID
 * @var int
 */
if ( ! isset( $post_ID ) )
	$post_ID = 0;
else
	$post_ID = (int) $post_ID;

$action = isset($action) ? $action : '';
if ( isset($_GET['message']) )
	$_GET['message'] = absint( $_GET['message'] );
$messages[1] = sprintf( __( 'Post updated. Continue editing below or <a href="%s">go back</a>.' ), attribute_escape( stripslashes( ( isset( $_GET['_wp_original_http_referer'] ) ? $_GET['_wp_original_http_referer'] : '') ) ) );
$messages[2] = __('Custom field updated.');
$messages[3] = __('Custom field deleted.');
$messages[4] = __('Post updated.');
$messages[6] = sprintf(__('Post published. <a href="%s">View post</a>'), get_permalink($post_ID));
$messages[7] = __('Post saved.');

if ( isset($_GET['revision']) )
	$messages[5] = sprintf( __('Post restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) );

$notice = false;
$notices[1] = __( 'There is an autosave of this post that is more recent than the version below.  <a href="%s">View the autosave</a>.' );

if ( 0 == $post_ID ) {
	$form_action = 'post';
	$temp_ID = -1 * time(); // don't change this formula without looking at wp_write_post()
	$form_extra = "<input type='hidden' id='post_ID' name='temp_ID' value='$temp_ID' />";
	$autosave = false;
} else {
	$form_action = 'editpost';
	$form_extra = "<input type='hidden' id='post_ID' name='post_ID' value='$post_ID' />";
	$autosave = wp_get_post_autosave( $post_ID );

	// Detect if there exists an autosave newer than the post and if that autosave is different than the post
	if ( $autosave && mysql2date( 'U', $autosave->post_modified_gmt ) > mysql2date( 'U', $post->post_modified_gmt ) ) {
		foreach ( _wp_post_revision_fields() as $autosave_field => $_autosave_field ) {
			if ( normalize_whitespace( $autosave->$autosave_field ) != normalize_whitespace( $post->$autosave_field ) ) {
				$notice = sprintf( $notices[1], get_edit_post_link( $autosave->ID ) );
				break;
			}
		}
		unset($autosave_field, $_autosave_field);
	}
}

// All meta boxes should be defined and added before the first do_meta_boxes() call (or potentially during the do_meta_boxes action).

/**
 * Display post submit form fields.
 *
 * @since 2.7.0
 *
 * @param object $post
 */
function post_submit_meta_box($post) {
	global $action;

	$can_publish = current_user_can('publish_posts');
?>
<div class="submitbox" id="submitpost">

<div id="minor-publishing">

<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
<div style="display:none;">
<input type="submit" name="save" value="<?php echo attribute_escape( __('Save') ); ?>" />
</div>

<div id="minor-publishing-actions">
<div id="save-action">
<?php if ( 'publish' != $post->post_status && 'future' != $post->post_status && 'pending' != $post->post_status )  { ?>
<input <?php if ( 'private' == $post->post_status ) { ?>style="display:none"<?php } ?> type="submit" name="save" id="save-post" value="<?php echo attribute_escape( __('Save Draft') ); ?>" tabindex="4" class="button button-highlighted" />
<?php } elseif ( 'pending' == $post->post_status && $can_publish ) { ?>
<input type="submit" name="save" id="save-post" value="<?php echo attribute_escape( __('Save as Pending') ); ?>" tabindex="4" class="button button-highlighted" />
<?php } ?>
</div>

<div id="preview-action">
<?php $preview_link = 'publish' == $post->post_status ? clean_url(get_permalink($post->ID)) : clean_url(apply_filters('preview_post_link', add_query_arg('preview', 'true', get_permalink($post->ID)))); ?>

<a class="preview button" href="<?php echo $preview_link; ?>" target="wp-preview" id="post-preview" tabindex="4"><?php _e('Preview'); ?></a>
<input type="hidden" name="wp-preview" id="wp-preview" value="" />
</div>

<div class="clear"></div>
</div><?php // /minor-publishing-actions ?>

<div id="misc-publishing-actions">

<div class="misc-pub-section<?php if ( !$can_publish ) { echo '  misc-pub-section-last'; } ?>"><label for="post_status"><?php _e('Status:') ?></label>
<b><span id="post-status-display">
<?php
switch ( $post->post_status ) {
	case 'private':
		_e('Privately Published');
		break;
	case 'publish':
		_e('Published');
		break;
	case 'future':
		_e('Scheduled');
		break;
	case 'pending':
		_e('Pending Review');
		break;
	case 'draft':
		_e('Draft');
		break;
}
?>
</span></b>
<?php if ( 'publish' == $post->post_status || 'private' == $post->post_status || $can_publish ) { ?>
<a href="#post_status" <?php if ( 'private' == $post->post_status ) { ?>style="display:none;" <?php } ?>class="edit-post-status hide-if-no-js" tabindex='4'><?php _e('Edit') ?></a>

<div id="post-status-select" class="hide-if-js">
<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo $post->post_status; ?>" />
<select name='post_status' id='post_status' tabindex='4'>
<?php if ( 'publish' == $post->post_status ) : ?>
<option<?php selected( $post->post_status, 'publish' ); ?> value='publish'><?php _e('Published') ?></option>
<?php elseif ( 'private' == $post->post_status ) : ?>
<option<?php selected( $post->post_status, 'private' ); ?> value='publish'><?php _e('Privately Published') ?></option>
<?php elseif ( 'future' == $post->post_status ) : ?>
<option<?php selected( $post->post_status, 'future' ); ?> value='future'><?php _e('Scheduled') ?></option>
<?php endif; ?>
<option<?php selected( $post->post_status, 'pending' ); ?> value='pending'><?php _e('Pending Review') ?></option>
<option<?php selected( $post->post_status, 'draft' ); ?> value='draft'><?php _e('Draft') ?></option>
</select>
 <a href="#post_status" class="save-post-status hide-if-no-js button"><?php _e('OK'); ?></a>
 <a href="#post_status" class="cancel-post-status hide-if-no-js"><?php _e('Cancel'); ?></a>
</div>

<?php } ?>
</div><?php // /misc-pub-section ?>

<div class="misc-pub-section " id="visibility">
<?php _e('Visibility:'); ?> <b><span id="post-visibility-display"><?php

if ( !empty( $post->post_password ) ) {
	$visibility = 'password';
	$visibility_trans = __('Password protected');
} elseif ( 'private' == $post->post_status ) {
	$visibility = 'private';
	$visibility_trans = __('Private');
} elseif ( is_sticky( $post->ID ) ) {
	$visibility = 'public';
	$visibility_trans = __('Public, Sticky');
} else {
	$visibility = 'public';
	$visibility_trans = __('Public');
}

?><?php echo wp_specialchars( $visibility_trans ); ?></span></b> <?php if ( $can_publish ) { ?> <a href="#visibility" class="edit-visibility hide-if-no-js"><?php _e('Edit'); ?></a>

<div id="post-visibility-select" class="hide-if-js">
<input type="hidden" name="hidden_post_password" id="hidden-post-password" value="<?php echo attribute_escape($post->post_password); ?>" />
<input type="checkbox" style="display:none" name="hidden_post_sticky" id="hidden-post-sticky" value="sticky" <?php checked(is_sticky($post->ID), true); ?> />
<input type="hidden" name="hidden_post_visibility" id="hidden-post-visibility" value="<?php echo attribute_escape( $visibility ); ?>" />


<input type="radio" name="visibility" id="visibility-radio-public" value="public" <?php checked( $visibility, 'public' ); ?> /> <label for="visibility-radio-public" class="selectit"><?php _e('Public'); ?></label><br />
<span id="sticky-span"><input id="sticky" name="sticky" type="checkbox" value="sticky" <?php checked(is_sticky($post->ID), true); ?> tabindex="4" /> <label for="sticky" class="selectit"><?php _e('Stick this post to the front page') ?></label><br /></span>
<input type="radio" name="visibility" id="visibility-radio-password" value="password" <?php checked( $visibility, 'password' ); ?> /> <label for="visibility-radio-password" class="selectit"><?php _e('Password protected'); ?></label><br />
<span id="password-span"><label for="post_password"><?php _e('Password:'); ?></label> <input type="text" name="post_password" id="post_password" value="<?php echo attribute_escape($post->post_password); ?>" /><br /></span>
<input type="radio" name="visibility" id="visibility-radio-private" value="private" <?php checked( $visibility, 'private' ); ?> /> <label for="visibility-radio-private" class="selectit"><?php _e('Private'); ?></label><br />

<p>
 <a href="#visibility" class="save-post-visibility hide-if-no-js button"><?php _e('OK'); ?></a>
 <a href="#visibility" class="cancel-post-visibility hide-if-no-js"><?php _e('Cancel'); ?></a>
</p>
</div>
<?php } ?>

</div><?php // /misc-pub-section ?>


<?php
$datef = _c( 'M j, Y @ G:i|Publish box date format');
if ( 0 != $post->ID ) {
	if ( 'future' == $post->post_status ) { // scheduled for publishing at a future date
		$stamp = __('Scheduled for: <b>%1$s</b>');
	} else if ( 'publish' == $post->post_status || 'private' == $post->post_status ) { // already published
		$stamp = __('Published on: <b>%1$s</b>');
	} else if ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
		$stamp = __('Publish <b>immediately</b>');
	} else if ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
		$stamp = __('Schedule for: <b>%1$s</b>');
	} else { // draft, 1 or more saves, date specified
		$stamp = __('Publish on: <b>%1$s</b>');
	}
	$date = date_i18n( $datef, strtotime( $post->post_date ) );
} else { // draft (no saves, and thus no date specified)
	$stamp = __('Publish <b>immediately</b>');
	$date = date_i18n( $datef, strtotime( current_time('mysql') ) );
}
?>
<?php if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
<div class="misc-pub-section curtime misc-pub-section-last">
	<span id="timestamp">
	<?php printf($stamp, $date); ?></span>
	<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" tabindex='4'><?php _e('Edit') ?></a>
	<div id="timestampdiv" class="hide-if-js"><?php touch_time(($action == 'edit'),1,4); ?></div>
</div><?php // /misc-pub-section ?>
<?php endif; ?>

</div>
<div class="clear"></div>
</div>

<div id="major-publishing-actions">
<?php do_action('post_submitbox_start'); ?>
<div id="delete-action">
<?php
if ( ( 'edit' == $action ) && current_user_can('delete_post', $post->ID) ) { ?>
<a class="submitdelete deletion" href="<?php echo wp_nonce_url("post.php?action=delete&amp;post=$post->ID", 'delete-post_' . $post->ID); ?>" onclick="if ( confirm('<?php echo js_escape(sprintf( ('draft' == $post->post_status) ? __("You are about to delete this draft '%s'\n  'Cancel' to stop, 'OK' to delete.") : __("You are about to delete this post '%s'\n  'Cancel' to stop, 'OK' to delete."), $post->post_title )); ?>') ) {return true;}return false;"><?php _e('Delete'); ?></a>
<?php } ?>
</div>

<div id="publishing-action">
<?php
if ( !in_array( $post->post_status, array('publish', 'future', 'private') ) || 0 == $post->ID ) { ?>
<?php if ( current_user_can('publish_posts') ) : ?>
	<?php if ( !empty($post->post_date_gmt) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) : ?>
		<input name="original_publish" type="hidden" id="original_publish" value="<?php _e('Schedule') ?>" />
		<input name="publish" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php _e('Schedule') ?>" />
	<?php else : ?>
		<input name="original_publish" type="hidden" id="original_publish" value="<?php _e('Publish') ?>" />
		<input name="publish" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php _e('Publish') ?>" />
	<?php endif; ?>
<?php else : ?>
	<input name="original_publish" type="hidden" id="original_publish" value="<?php _e('Submit for Review') ?>" />
	<input name="publish" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php _e('Submit for Review') ?>" />
<?php endif; ?>
<?php } else { ?>
	<input name="original_publish" type="hidden" id="original_publish" value="<?php _e('Update Post') ?>" />
	<input name="save" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php _e('Update Post') ?>" />
<?php } ?>
</div>
<div class="clear"></div>
</div>
</div>

<?php
}
add_meta_box('submitdiv', __('Publish'), 'post_submit_meta_box', 'post', 'side', 'core');

/**
 * Display post tags form fields.
 *
 * @since 2.6.0
 *
 * @param object $post
 */
function post_tags_meta_box($post) {
?>
<p id="jaxtag"><label class="hidden" for="newtag"><?php _e('Tags'); ?></label><input type="text" name="tags_input" class="tags-input" id="tags-input" size="40" tabindex="3" value="<?php echo get_tags_to_edit( $post->ID ); ?>" /></p>
<div id="tagchecklist"></div>
<p id="tagcloud-link" class="hide-if-no-js"><a href='#'><?php _e( 'Choose from the most popular tags' ); ?></a></p>
<?php
}
add_meta_box('tagsdiv', __('Tags'), 'post_tags_meta_box', 'post', 'side', 'core');

/**
 * Display post categories form fields.
 *
 * @since 2.6.0
 *
 * @param object $post
 */
function post_categories_meta_box($post) {
?>
<ul id="category-tabs">
	<li class="ui-tabs-selected"><a href="#categories-all" tabindex="3"><?php _e( 'All Categories' ); ?></a></li>
	<li class="hide-if-no-js"><a href="#categories-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
</ul>

<div id="categories-pop" class="ui-tabs-panel" style="display: none;">
	<ul id="categorychecklist-pop" class="categorychecklist form-no-clear" >
		<?php $popular_ids = wp_popular_terms_checklist('category'); ?>
	</ul>
</div>

<div id="categories-all" class="ui-tabs-panel">
	<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
		<?php wp_category_checklist($post->ID, false, false, $popular_ids) ?>
	</ul>
</div>

<?php if ( current_user_can('manage_categories') ) : ?>
<div id="category-adder" class="wp-hidden-children">
	<h4><a id="category-add-toggle" href="#category-add" class="hide-if-no-js" tabindex="3"><?php _e( '+ Add New Category' ); ?></a></h4>
	<p id="category-add" class="wp-hidden-child">
		<label class="hidden" for="newcat"><?php _e( 'Add New Category' ); ?></label><input type="text" name="newcat" id="newcat" class="form-required form-input-tip" value="<?php _e( 'New category name' ); ?>" tabindex="3" aria-required="true"/>
		<label class="hidden" for="newcat_parent"><?php _e('Parent category'); ?>:</label><?php wp_dropdown_categories( array( 'hide_empty' => 0, 'name' => 'newcat_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => __('Parent category'), 'tab_index' => 3 ) ); ?>
		<input type="button" id="category-add-sumbit" class="add:categorychecklist:category-add button" value="<?php _e( 'Add' ); ?>" tabindex="3" />
		<?php wp_nonce_field( 'add-category', '_ajax_nonce', false ); ?>
		<span id="category-ajax-response"></span>
	</p>
</div>
<?php
endif;

}
add_meta_box('categorydiv', __('Categories'), 'post_categories_meta_box', 'post', 'side', 'core');

/**
 * Display post password form fields.
 *
 * @since 2.6.0
 *
 * @param object $post
 */
function post_password_meta_box($post) {
?>
<p>
	<label for="post_status_private" class="selectit"><input id="post_status_private" name="post_status" type="checkbox" value="private" <?php checked($post->post_status, 'private'); ?> tabindex="4" /> <?php _e('Keep this post private') ?></label>
</p>
<h4><?php _e( 'Post Password' ); ?></h4>
<p><label class="hidden" for="post_password"><?php _e('Password Protect This Post') ?></label><input name="post_password" type="text" size="25" id="post_password" value="<?php the_post_password(); ?>" /></p>
<p><?php _e('Setting a password will require people who visit your blog to enter the above password to view this post and its comments.'); ?></p>
<?php
}
// add_meta_box('passworddiv', __('Privacy Options'), 'post_password_meta_box', 'post', 'side', 'core');

/**
 * Display post excerpt form fields.
 *
 * @since 2.6.0
 *
 * @param object $post
 */
function post_excerpt_meta_box($post) {
?>
<label class="hidden" for="excerpt"><?php _e('Excerpt') ?></label><textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt"><?php echo $post->post_excerpt ?></textarea>
<p><?php _e('Excerpts are optional hand-crafted summaries of your content. You can <a href="http://codex.wordpress.org/Template_Tags/the_excerpt" target="_blank">use them in your template</a>'); ?></p>
<?php
}
add_meta_box('postexcerpt', __('Excerpt'), 'post_excerpt_meta_box', 'post', 'normal', 'core');

/**
 * Display trackback links form fields.
 *
 * @since 2.6.0
 *
 * @param object $post
 */
function post_trackback_meta_box($post) {
	$form_trackback = '<input type="text" name="trackback_url" id="trackback_url" tabindex="7" value="'. attribute_escape( str_replace("\n", ' ', $post->to_ping) ) .'" />';
	if ('' != $post->pinged) {
		$pings = '<p>'. __('Already pinged:') . '</p><ul>';
		$already_pinged = explode("\n", trim($post->pinged));
		foreach ($already_pinged as $pinged_url) {
			$pings .= "\n\t<li>" . wp_specialchars($pinged_url) . "</li>";
		}
		$pings .= '</ul>';
	}

?>
<p><label for="trackback_url"><?php _e('Send trackbacks to:'); ?></label> <?php echo $form_trackback; ?><br /> (<?php _e('Separate multiple URLs with spaces'); ?>)</p>
<p><?php _e('Trackbacks are a way to notify legacy blog systems that you&#8217;ve linked to them. If you link other WordPress blogs they&#8217;ll be notified automatically using <a href="http://codex.wordpress.org/Introduction_to_Blogging#Managing_Comments" target="_blank">pingbacks</a>, no other action necessary.'); ?></p>
<?php
if ( ! empty($pings) )
	echo $pings;
}
add_meta_box('trackbacksdiv', __('Send Trackbacks'), 'post_trackback_meta_box', 'post', 'normal', 'core');

/**
 * Display custom fields for the post form fields.
 *
 * @since 2.6.0
 *
 * @param object $post
 */
function post_custom_meta_box($post) {
?>
<div id="postcustomstuff">
<div id="ajax-response"></div>
<?php
$metadata = has_meta($post->ID);
list_meta($metadata);
meta_form();
?>
</div>
<p><?php _e('Custom fields can be used to add extra metadata to a post that you can <a href="http://codex.wordpress.org/Using_Custom_Fields" target="_blank">use in your theme</a>.'); ?></p>
<?php
}
add_meta_box('postcustom', __('Custom Fields'), 'post_custom_meta_box', 'post', 'normal', 'core');

do_action('dbx_post_advanced');

/**
 * Display comment status for post form fields.
 *
 * @since 2.6.0
 *
 * @param object $post
 */
function post_comment_status_meta_box($post) {
	global $wpdb, $post_ID;
?>
<input name="advanced_view" type="hidden" value="1" />
<p class="meta-options">
	<label for="comment_status" class="selectit"> <input name="comment_status" type="checkbox" id="comment_status" value="open" <?php checked($post->comment_status, 'open'); ?> /> <?php _e('Allow comments on this post') ?></label><br />
	<label for="ping_status" class="selectit"><input name="ping_status" type="checkbox" id="ping_status" value="open" <?php checked($post->ping_status, 'open'); ?> /> <?php _e('Allow <a href="http://codex.wordpress.org/Introduction_to_Blogging#Managing_Comments" target="_blank">trackbacks and pingbacks</a> on this post') ?></label>
</p>
<?php
	$total = $wpdb->get_var($wpdb->prepare("SELECT count(1) FROM $wpdb->comments WHERE comment_post_ID = '%d' AND ( comment_approved = '0' OR comment_approved = '1')", $post_ID));

	if ( !$post_ID || $post_ID < 0 || 1 > $total )
		return;

wp_nonce_field( 'get-comments', 'add_comment_nonce', false );
?>

<table class="widefat comments-box fixed" cellspacing="0" style="display:none;">
<thead>
	<tr>
    <th scope="col" class="column-comment"><?php _e('Comment') ?></th>
    <th scope="col" class="column-author"><?php _e('Author') ?></th>
    <th scope="col" class="column-date"><?php _e('Submitted') ?></th>
  </tr>
</thead>
<tbody id="the-comment-list" class="list:comment">
</tbody>
</table>
<p class="hide-if-no-js"><a href="#commentstatusdiv" id="show-comments" onclick="commentsBox.get(<?php echo $total; ?>);return false;"><?php _e('Show comments'); ?></a> <img class="waiting" style="display:none;" src="images/loading.gif" alt="" /></p>
<?php
	$hidden = (array) get_user_option( "meta-box-hidden_post" );
	if ( ! in_array('commentstatusdiv', $hidden) ) { ?>
		<script type="text/javascript">commentsBox.get(<?php echo $total; ?>, 10);</script>
<?php
	}
}
add_meta_box('commentstatusdiv', __('Discussion'), 'post_comment_status_meta_box', 'post', 'normal', 'core');

/**
 * Display post slug form fields.
 *
 * @since 2.6.0
 *
 * @param object $post
 */
function post_slug_meta_box($post) {
?>
<label class="hidden" for="post_name"><?php _e('Post Slug') ?></label><input name="post_name" type="text" size="13" id="post_name" value="<?php echo attribute_escape( $post->post_name ); ?>" />
<?php
}
if ( !( 'pending' == $post->post_status && !current_user_can( 'publish_posts' ) ) )
	add_meta_box('slugdiv', __('Post Slug'), 'post_slug_meta_box', 'post', 'normal', 'core');

$authors = get_editable_user_ids( $current_user->id ); // TODO: ROLE SYSTEM
if ( $post->post_author && !in_array($post->post_author, $authors) )
	$authors[] = $post->post_author;
if ( $authors && count( $authors ) > 1 ) :
/**
 * Display form field with list of authors.
 *
 * @since 2.6.0
 *
 * @param object $post
 */
function post_author_meta_box($post) {
	global $current_user, $user_ID;
	$authors = get_editable_user_ids( $current_user->id ); // TODO: ROLE SYSTEM
	if ( $post->post_author && !in_array($post->post_author, $authors) )
		$authors[] = $post->post_author;
?>
<label class="hidden" for="post_author_override"><?php _e('Post Author'); ?></label><?php wp_dropdown_users( array('include' => $authors, 'name' => 'post_author_override', 'selected' => empty($post->ID) ? $user_ID : $post->post_author) ); ?>
<?php
}
add_meta_box('authordiv', __('Post Author'), 'post_author_meta_box', 'post', 'normal', 'core');
endif;

if ( 0 < $post_ID && wp_get_post_revisions( $post_ID ) ) :
/**
 * Display list of post revisions.
 *
 * @since 2.6.0
 *
 * @param object $post
 */
function post_revisions_meta_box($post) {
	wp_list_post_revisions();
}
add_meta_box('revisionsdiv', __('Post Revisions'), 'post_revisions_meta_box', 'post', 'normal', 'core');
endif;

do_action('do_meta_boxes', 'post', 'normal', $post);
do_action('do_meta_boxes', 'post', 'advanced', $post);
do_action('do_meta_boxes', 'post', 'side', $post);

require_once('admin-header.php');

?>

<?php if ( (isset($mode) && 'bookmarklet' == $mode) || isset($_GET['popupurl']) ): ?>
<input type="hidden" name="mode" value="bookmarklet" />
<?php endif; ?>

<div class="wrap">
<h2><?php echo wp_specialchars( $title ); ?></h2>
<?php if ( $notice ) : ?>
<div id="notice" class="error"><p><?php echo $notice ?></p></div>
<?php endif; ?>
<?php if (isset($_GET['message'])) : ?>
<div id="message" class="updated fade"><p><?php echo $messages[$_GET['message']]; ?></p></div>
<?php endif; ?>
<form name="post" action="post.php" method="post" id="post">
<?php

if ( 0 == $post_ID)
	wp_nonce_field('add-post');
else
	wp_nonce_field('update-post_' .  $post_ID);

?>

<input type="hidden" id="user-id" name="user_ID" value="<?php echo (int) $user_ID ?>" />
<input type="hidden" id="hiddenaction" name="action" value="<?php echo $form_action ?>" />
<input type="hidden" id="originalaction" name="originalaction" value="<?php echo $form_action ?>" />
<input type="hidden" id="post_author" name="post_author" value="<?php echo attribute_escape( $post->post_author ); ?>" />
<input type="hidden" id="post_type" name="post_type" value="<?php echo $post->post_type ?>" />
<input type="hidden" id="original_post_status" name="original_post_status" value="<?php echo $post->post_status ?>" />
<input name="referredby" type="hidden" id="referredby" value="<?php echo clean_url(stripslashes(wp_get_referer())); ?>" />
<?php if ( 'draft' != $post->post_status ) wp_original_referer_field(true, 'previous'); ?>

<?php echo $form_extra ?>

<div id="poststuff" class="metabox-holder">

<div id="side-info-column" class="inner-sidebar">

<?php do_action('submitpost_box'); ?>

<?php $side_meta_boxes = do_meta_boxes('post', 'side', $post); ?>
</div>

<div id="post-body" class="<?php echo $side_meta_boxes ? 'has-sidebar' : ''; ?>">
<div id="post-body-content" class="has-sidebar-content">
<div id="titlediv">
<div id="titlewrap">
	<input type="text" name="post_title" size="30" tabindex="1" value="<?php echo attribute_escape($post->post_title); ?>" id="title" autocomplete="off" />
</div>
<div class="inside">
<?php $sample_permalink_html = get_sample_permalink_html($post->ID); ?>
<?php if ( !( 'pending' == $post->post_status && !current_user_can( 'publish_posts' ) ) ) { ?>
	<div id="edit-slug-box">
<?php if ( ! empty($post->ID) && ! empty($sample_permalink_html) ) :
	echo $sample_permalink_html;
endif; ?>
	</div>
<?php } ?>
</div>
</div>

<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">

<?php the_editor($post->post_content); ?>

<div id="post-status-info">
	<span id="wp-word-count" class="alignleft"></span>
	<span class="alignright">
	<span id="autosave">&nbsp;</span>
<?php
	if ( $post_ID ) {
		echo '<span id="last-edit">';
		if ( $last_id = get_post_meta($post_ID, '_edit_last', true) ) {
			$last_user = get_userdata($last_id);
			printf(__('Last edited by %1$s on %2$s at %3$s'), wp_specialchars( $last_user->display_name ), mysql2date(get_option('date_format'), $post->post_modified), mysql2date(get_option('time_format'), $post->post_modified));
		} else {
			printf(__('Last edited on %1$s at %2$s'), mysql2date(get_option('date_format'), $post->post_modified), mysql2date(get_option('time_format'), $post->post_modified));
		}
		echo '</span>';
	}
?>
	</span>
	<br class="clear" />
</div>


<?php wp_nonce_field( 'autosave', 'autosavenonce', false ); ?>
<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
<?php wp_nonce_field( 'getpermalink', 'getpermalinknonce', false ); ?>
<?php wp_nonce_field( 'samplepermalink', 'samplepermalinknonce', false ); ?>
<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
</div>

<?php

do_meta_boxes('post', 'normal', $post);

do_action('edit_form_advanced');

do_meta_boxes('post', 'advanced', $post);

do_action('dbx_post_sidebar');

?>

</div>
</div>
<br class="clear" />
</div><!-- /poststuff -->
</form>
</div>

<?php wp_comment_reply(); ?>

<?php if ((isset($post->post_title) && '' == $post->post_title) || (isset($_GET['message']) && 2 > $_GET['message'])) : ?>
<script type="text/javascript">
try{document.post.title.focus();}catch(e){}
</script>
<?php endif; ?>
