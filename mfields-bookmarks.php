<?php
/*
Plugin Name:       Mfields Bookmarks
Description:       Enables a custom post_type for bookmarks.
Version:           0.2
Author:            Michael Fields
Author URI:        http://wordpress.mfields.org/
License:           GPLv2 or later

Copyright 2011     Michael Fields  michael@mfields.org

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

Changelog
0.2
	- Flush rewrite on activation and deactivation.
	- Created Mfields_Bookmark_Post_Type class.
0.1
	- Initial plugin
*/


class Mfields_Bookmark_Post_Type {
	/**
	 * Constructor.
	 */
	 function Mfields_Bookmark_Post_Type() {
		add_action( 'init', array( &$this, 'register_post_type' ), 0 );
		add_action( 'init', array( &$this, 'register_taxonomies' ), 1 );
		add_action( 'admin_head-post-new.php', array( &$this, 'process_bookmarklet' ) );
		add_filter( 'the_content', array( &$this, 'append_link_to_content' ), 0 );
	}
	/**
	 * Activation.
	 */
	function activate() {
		$this->register_post_type();
		flush_rewrite_rules();
	}
	/**
	 * Deactivation.
	 */
	function deactivate() {
		flush_rewrite_rules();
	}
	/**
	 * Register post_type.
	 */
	function register_post_type() {
		if ( isset( $_REQUEST['action'] ) && 'deactivate' == $_REQUEST['action'] ) {
			return;
		}
		register_post_type( 'mfields_bookmark', array(
			'public'        => true,
			'can_export'    => true,
			'has_archive'   => 'bookmarks',
			'rewrite'       => array( 'slug' => 'bookmark', 'with_front' => false ),
			'menu_position' => 3,
			'supports' => array(
				'title',
				'editor',
				'comments',
				'thumbnail',
				'trackbacks',
				'custom-fields',
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
	 */
	function register_taxonomies() {
		register_taxonomy( 'mfields_bookmark_type', 'mfields_bookmark', array(
			'hierarchical'          => true,
			'query_var'             => 'bookmark_type',
			'rewrite'               => array( 'slug' => 'bookmark_type' ),
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
		register_taxonomy( 'mfields_bookmark_source', 'mfields_bookmark', array(
			'hierarchical'          => true,
			'query_var'             => 'source',
			'rewrite'               => array( 'slug' => 'source' ),
			'show_tagcloud'         => true,
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
		register_taxonomy_for_object_type( 'topics', 'mfields_bookmark' );
	}
	/**
	 * Append Bookmark link to the content on multiple views.
	 */
	function append_link_to_content( $content ) {
		if ( ! is_singular() && 'mfields_bookmark' === get_post_type() ) {
			$url = esc_url( get_post_meta( get_the_ID(), 'bookmark_url', true ) );
			if ( !empty( $url ) ) {
				$content .= ' <a href="' . $url . '" rel="external">Visit Site</a>';
			}
		}
		return $content;
	}
	/**
	 * jQuery to process bookmarklet requests on post-new.php
	 */
	function process_bookmarklet() {
		if ( isset( $_GET['mfields_bookmark_url'] ) && 'mfields_bookmark' === get_post_type() ) {
			$url = esc_url( $_GET['mfields_bookmark_url'] );
			print <<< EOF
			<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				$( '#metakeyselect' ).val( 'resource_url' );
				$( '#metavalue' ).text( '{$url}' );
			} );
			</script>
EOF;
		}
	}
}
$mfields_bookmark_post_type = new Mfields_Bookmark_Post_Type();

register_activation_hook( __FILE__, array( &$mfields_bookmark_post_type, 'activate' ) );
register_deactivation_hook( __FILE__, array( &$mfields_bookmark_post_type, 'deactivate' ) );