<?php
/*
Plugin Name:       Mfields Bookmarks
Description:       Enables a custom post_type for bookmarks.
Version:           0.3
Author:            Michael Fields
Author URI:        http://wordpress.mfields.org/
License:           GPLv2 or later

Copyright 2011-2012 Michael Fields michael@mfields.org

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License version 2 or later
as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

Mfields_Bookmarks::init();

class Mfields_Bookmarks {

	const prefix    = 'Mfields_Bookmarks::';
	const post_type = 'mfields_bookmark';
	const meta_url  = '_mfields_bookmark_url';
	const meta_text = '_mfields_bookmark_link_text';

	/**
	 * Constructor.
	 *
	 * Hook into WordPress.
	 *
	 * @return     void
	 * @since      2011-02-20
	 */
	static public function init() {
		register_activation_hook( __file__,    self::prefix . 'activate' );
		register_deactivation_hook( __file__,  self::prefix . 'deactivate' );
		add_action( 'init',                    self::prefix . 'register_post_type', 0 );
		add_action( 'init',                    self::prefix . 'register_taxonomies', 2 );
		add_action( 'admin_menu',              self::prefix . 'register_meta_boxen' );
		add_action( 'admin_head-post-new.php', self::prefix . 'process_bookmarklet' );
		add_action( 'save_post',               self::prefix . 'meta_save', 10, 2 );
		add_filter( 'the_content',             self::prefix . 'append_link_to_content', 0 );

		/* Integrate with the Nighthawk theme. */
		add_filter( 'nighthawk_table_columns',        self::prefix . 'nighthawk_table_columns' );
		add_filter( 'nighthawk_archive_meta_strings', self::prefix . 'nighthawk_archive_meta_strings' );
	}

	/**
	 * Activation.
	 *
	 * When a user activates this plugin the public pages
	 * for both custom taxonomies and post_types will need
	 * to be immediately available. To ensure that this happens
	 * both post_types and taxonomies need to be registered at
	 * activation so that their rewrite rules will be present
	 * when new rules are added to the database during flush.
	 *
	 * @return     void
	 * @since      2011-02-20
	 */
	static public function activate() {
		self::register_post_type();
		self::register_taxonomies();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation.
	 *
	 * When a user chooses to deactivate bookmarks it is
	 * important to remove all custom object rewrites from
	 * the database.
	 *
	 * @return     void
	 * @since      2011-02-20
	 */
	static public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Register post_type.
	 *
	 * Registers custom post_type 'mfields_bookmark' with
	 * WordPress.
	 *
	 * This method is hooked into the init action and should
	 * fire everywhere save the deactivation hook. When this
	 * plugin is deactivated this method will return early.
	 * This makes it easy for the deactivation() method to do
	 * its job.
	 *
	 * @return     void
	 * @since      2011-02-20
	 */
	static public function register_post_type() {
		if ( isset( $_REQUEST['action'] ) && 'deactivate' == $_REQUEST['action'] ) {
			return;
		}
		register_post_type( self::post_type, array(
			'public'        => true,
			'can_export'    => true,
			'has_archive'   => 'bookmarks',
			'rewrite'       => array( 'slug' => 'bookmark', 'with_front' => false ),
			'menu_position' => 3,
			'supports' => array(
				'title',
				'editor',
				),
			'labels' => array(
				'name'               => 'Bookmarks',
				'singular_name'      => 'Bookmark',
				'add_new'            => 'Add New',
				'add_new_item'       => 'Add New Bookmark',
				'edit_item'          => 'Edit Bookmark',
				'new_item'           => 'New Bookmark',
				'view_item'          => 'View Bookmark',
				'search_items'       => 'Search Bookmark',
				'not_found'          => 'No Bookmarks found',
				'not_found_in_trash' => 'No Bookmarks found in Trash'
				),
			'decription' => 'I created this section to store webpages that I have read, found interesting or may need to reference in the future. Not all bookmarks found here directly pertain to WordPress.'
			)
		);
	}

	/**
	 * Register taxonomies.
	 *
	 * Register all taxonomy's for bookmarks including:
	 * "Type", "Source", and "Topic".
	 *
	 * This function will look for an already installed taxonomy
	 * named "topic" and, if found, will register it with the
	 * 'mfields_bookmark' object type. On installations not having
	 * a global "topic" taxonomy, a new one will be registered.
	 *
	 * @return     void
	 * @since      2011-02-20
	 */
	static public function register_taxonomies() {
		if ( isset( $_REQUEST['action'] ) && 'deactivate' == $_REQUEST['action'] ) {
			return;
		}
		register_taxonomy( self::post_type . '_type', self::post_type, array(
			'hierarchical'          => true,
			'query_var'             => 'bookmark_type',
			'rewrite'               => array( 'slug' => 'bookmark-type' ),
			'show_tagcloud'         => false,
			'update_count_callback' => '_update_post_term_count',
			'labels' => array(
				'name'              => 'Types',
				'singular_name'     => 'Type',
				'search_items'      => 'Search Types',
				'all_items'         => 'All Types',
				'parent_item'       => 'Parent Type',
				'parent_item_colon' => 'Parent Type:',
				'edit_item'         => 'Edit Type',
				'update_item'       => 'Update Type',
				'add_new_item'      => 'Add a New Type',
				'new_item_name'     => 'New Type Name'
			)
		) );
		register_taxonomy( self::post_type . '_source', self::post_type, array(
			'hierarchical'          => true,
			'query_var'             => 'bookmark_source',
			'rewrite'               => array( 'slug' => 'bookmark-source' ),
			'show_tagcloud'         => false,
			'update_count_callback' => '_update_post_term_count',
			'labels' => array(
				'name'              => 'Sources',
				'singular_name'     => 'Source',
				'search_items'      => 'Search Sources',
				'all_items'         => 'All Sources',
				'parent_item'       => 'Parent Source',
				'parent_item_colon' => 'Parent Source:',
				'edit_item'         => 'Edit Source',
				'update_item'       => 'Update Source',
				'add_new_item'      => 'Add a New Source',
				'new_item_name'     => 'New Source'
			)
		) );
		register_taxonomy( self::post_type . '_topic', 'post', array(
			'hierarchical'          => true,
			'query_var'             => 'bookmark_topic',
			'rewrite'               => array( 'slug' => 'bookmark-topic' ),
			'show_tagcloud'         => false,
			'update_count_callback' => '_update_post_term_count',
			'labels' => array(
				'name'              => 'Topics',
				'singular_name'     => 'Topic',
				'search_items'      => 'Search Topics',
				'all_items'         => 'All Topics',
				'parent_item'       => 'Parent Topic',
				'parent_item_colon' => 'Parent Topic:',
				'edit_item'         => 'Edit Topic',
				'update_item'       => 'Update Topic',
				'add_new_item'      => 'Add a New Topic',
				'new_item_name'     => 'New Topic Name'
			)
		) );
	}

	/**
	 * Append Bookmark link to the content.
	 *
	 * @since      unknown
	 */
	static public function append_link_to_content( $content ) {
		if ( self::post_type != get_post_type() ) {
			return $content;
		}

		$meta = array(
			'text' => (string) get_post_meta( get_the_ID(), self::meta_text, true ),
			'url'  => (string) esc_url( get_post_meta( get_the_ID(), self::meta_url, true ) ),
			);

		$text = 'Visit Site';
		if ( ! empty( $meta['text'] ) ) {
			$text = $meta['text'];
		}

		if ( ! empty( $meta['url'] ) ) {
			$content .= ' <a href="' . esc_url( $meta['url'] ) . '" rel="external">' . esc_html( $text ) . '</a>';
		}

		return $content;
	}

	/**
	 * jQuery to process bookmarklet requests on post-new.php
	 *
	 * @since      unknown
	 */
	static public function process_bookmarklet() {
		if ( isset( $_GET[ self::post_type . '_url'] ) && self::post_type === get_post_type() ) {
			$url = esc_url( $_GET[ self::post_type . '_url'] );
			print <<< EOF
			<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				$( '#{self::post_type}_url' ).val( 'resource_url' );
			} );
			</script>
EOF;
		}
	}

	/**
	 * Register Metaboxen.
	 *
	 * @uses       Mfields_Bookmarks::meta_box()
	 * @since      2011-03-12
	 */
	static public function register_meta_boxen() {
		add_meta_box( self::post_type . '_meta', 'Bookmark Data', array( __class__, 'meta_box' ), self::post_type, 'side', 'high' );
	}

	/**
	 * Meta Box.
	 *
	 * @since      2011-03-12
	 */
	static public function meta_box() {

		/* URL. */
		$url = get_post_meta( get_the_ID(), self::meta_url, true );
		print "\n\t" . '<p><label for="' . esc_attr( self::meta_url ) . '">URL</label>';
		print "\n\t" . '<input id="' . esc_attr( self::meta_url ) . '" type="text" class="widefat" name="' . esc_attr( self::meta_url ) . '" value="' . esc_url( $url ) . '" /></p>';

		/* Link Text. */
		$text = get_post_meta( get_the_ID(), self::meta_text, true );
		print "\n\t" . '<p><label for="' . esc_attr( self::meta_text ) . '">Link Text</label>';
		print "\n\t" . '<input id="' . esc_attr( self::meta_text ) . '" type="text" class="widefat" name="' . esc_attr( self::meta_text ) . '" value="' . esc_attr( $text ) . '" /></p>';

		/* Nonce field. */
		print "\n" . '<input type="hidden" name="' . self::post_type . '_meta_nonce" value="' . esc_attr( wp_create_nonce( 'update-' . self::post_type . '-meta-for-' . get_the_ID() ) ) . '" />';
	}

	/**
	 * Save Meta Data.
	 *
	 * @since      2011-03-12
	 */
	static public function meta_save( $ID, $post ) {
		/* Local variables. */
		$ID               = absint( $ID );
		$unique           = self::post_type . '_url';
		$meta_key         = '_' . $unique;
		$post_type        = get_post_type();
		$post_type_object = get_post_type_object( $post_type );
		$capability       = '';
		$url              = '';

		/* Do nothing on auto save. */
		if ( defined( 'DOING_AUTOSAVE' ) && true === DOING_AUTOSAVE ) {
			return;
		}

		/* Return early if custom value is not present in POST request. */
		if ( ! isset( $_POST[self::meta_url] ) || ! isset( $_POST[self::meta_text] ) ) {
			return;
		}

		/* This function only applies to the following post_types. */
		if ( ! in_array( $post_type, array( self::post_type ) ) ) {
			return;
		}

		/* Terminate script if accessed from outside the administration panels. */
		check_admin_referer( 'update-' . self::post_type . '-meta-for-' . $ID, self::post_type . '_meta_nonce' );

		/* Find correct capability from post_type arguments. */
		if ( isset( $post_type_object->cap->edit_posts ) ) {
			$capability = $post_type_object->cap->edit_posts;
		}

		/* Return if current user cannot edit this post. */
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		/* Save post meta. */
		update_post_meta( $ID, self::meta_url, esc_url_raw( $_POST[self::meta_url] ) );
		update_post_meta( $ID, self::meta_text, esc_html( $_POST[self::meta_text] ) );
	}

	public static function nighthawk_table_columns( $columns ) {
		if ( is_post_type_archive( self::post_type ) ) {
			$columns = array(
				array(
					'label'    => __( 'Post Title', 'nighthawk' ),
					'class'    => 'post-title',
					'callback' => 'Nighthawk::td_title',
				),
				array(
					'label'    => __( 'Source', 'nighthawk' ),
					'class'    => 'bookmark-source',
					'callback' => self::prefix . 'nighthawk_td_bookmark_source',
				),
				array(
					'label'    => __( 'Permalink', 'nighthawk' ),
					'class'    => 'permalink',
					'callback' => 'Nighthawk::td_permalink_icon',
				),
			);
		} else if ( is_tax( self::post_type . '_source' ) ) {
			$columns = array(
				array(
					'label'    => __( 'Post Title', 'nighthawk' ),
					'class'    => 'post-title',
					'callback' => 'Nighthawk::td_title',
				),
				array(
					'label'    => __( 'Permalink', 'nighthawk' ),
					'class'    => 'permalink',
					'callback' => 'Nighthawk::td_permalink_icon',
				),
			);
		} else if ( is_tax( self::post_type . '_type' ) ) {
			$columns = array(
				array(
					'label'    => __( 'Post Title', 'nighthawk' ),
					'class'    => 'post-title',
					'callback' => 'Nighthawk::td_title',
				),
				array(
					'label'    => __( 'Source', 'nighthawk' ),
					'class'    => 'bookmark-source',
					'callback' => self::prefix . 'nighthawk_td_bookmark_source',
				),
				array(
					'label'    => __( 'Permalink', 'nighthawk' ),
					'class'    => 'permalink',
					'callback' => 'Nighthawk::td_permalink_icon',
				),
			);
		}
		return $columns;
	}

	function nighthawk_archive_meta_strings( $strings ) {
		if ( is_post_type_archive( self::post_type ) ) {
			$strings['count']      = _n_noop( 'There is %1$s bookmark in this section.', 'There are %1$s bookmarks in this section.' );
			$strings['feed_text']  = __( 'Subscribe', 'mfields_bookmarks' );
			$strings['feed_title'] = __( 'Get updated whenever new bookmarks are added.', 'mfields_bookmarks' );
		}
		return $strings;
	}

	function nighthawk_td_bookmark_source( $column = array() ) {
		$taxonomy = self::post_type . '_source';
		$sources = get_the_terms( get_the_ID(), $taxonomy );

		if ( is_wp_error( $sources ) || empty( $sources ) )
			return;

		$source = current( (array) $sources );

		$link = esc_html( $source->name );
		if ( 1 < absint( $source->count ) )
			$link = '<a href="' . esc_url( get_term_link( $source, $taxonomy ) ) . '">' . $link . '</a>';

		echo "\n\t" . '<td class="' . esc_attr( $column['class'] ) . '">' . $link . '</td>';
	}
}