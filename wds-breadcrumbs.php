<?php
/*
Plugin Name: WDS Breadcrumbs
Plugin URI: http://webdevstudios.com
Description: Simple breadcrumbs for WDS7
Version: 1.0
Author: WebDevStudios
Author URI: http://webdevstudios.com
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WDS_Breadcrumbs {

	/**
	 * @var string the separator
	 */
	protected $separator = ' &raquo; ';

	/**
	 * @var string the homepage text
	 */
	protected $homepage_text = 'WebDevStudios.com';

	/**
	 * @var integer Post ID whose breadcrumbs we should display
	 */
	protected $post_id = 0;

	/**
	 * @var integer Post type whose breadcrumbs we should display
	 */
	protected $post_type = 'post';

	/**
	 * Bake our bread and leave a trail.
	 *
	 * @return string  the breadcrumbs
	 */
	public function do_breadcrumbs( $post_id = 0 ) {
		$this->post_id = $post_id ? $post_id : get_the_ID();
		$this->post    = get_post( $post_id );

		// Start baking
		$output = '';

		/**
		 * Frst Breadcrumb
		 */
		$output .= $this->homepage_crumb();

		/**
		 * Secondary Breadcrumbs
		 */
		if ( is_singular() ) {

			if ( is_page() ) {

				$output .= $this->page_crumbs();

			} elseif ( is_single() ) {

				$output .= $this->post_crumb();

			}

			/**
			 * Final Breadcrumb
			 */
			$output .= get_the_title();

		}

		elseif ( is_day() ) {
			$output .= $this->day_crumbs();
		}

		elseif ( is_month() ) {
			$output .= $this->month_crumbs();
		}

		elseif ( is_year() ) {
			$output .= get_the_time( 'Y' );
		}

		elseif ( is_author() ) {
			$output .= get_the_author();
		}

		elseif ( is_search() ) {
			$output .= __( 'Search results', 'wds7' );
		}

		elseif ( is_post_type_archive() ) {
			$output .= $this->post_type_singular_name();
		}

		elseif ( is_tag() || is_category() || is_archive() ) {
			$output .= single_term_title( '', false );
		}

		// When all else fails, we're probably on index.php
		else {

			if ( ! is_home() || ! is_front_page() ) {
				$output .= $this->post_type_singular_name();
			}
		}

		// Return the concantonated string of breadcrumbs!
		return $output;
	}

	/**
	 * HTML markup wrapper for breadcrumb link.
	 *
	 * @param  string  $link  the link
	 * @param  string  $text  the linked text
	 * @return string         complete url makrup
	 */
	private function link_wrap( $link = '', $text = '' ) {
		return $link ? '<a class="breadcrumb-link" href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>' . $this->maybe_do_seperator( $link ) : '';
	}

	/**
	 * The homepage breadcrumb.
	 */
	private function homepage_crumb() {
		return $this->link_wrap( home_url(), $this->homepage_text );
	}

	/**
	 * Build the page ancestors breadcrumbs.
	 *
	 * @return string the page breadcrumb
	 */
	private function page_crumbs() {
		// Get an array of post ancestors
		$parents = get_post_ancestors( $this->post_id );
		$crumbs = '';

		// No parents? Then bail...
		if ( empty( $parents ) || ! is_array( $parents ) ) {
			return $crumbs;
		}

		foreach ( array_reverse( $parents ) as $parent ) {
			$crumbs .= $this->link_wrap( get_permalink( $parent ), get_the_title( $parent ) );
		}

		return $crumbs;
	}

	/**
	 * Build the single post breadcrumb.
	 *
	 * @return string the post breadcrumb
	 */
	private function post_crumb() {
		return $this->link_wrap( $this->post_type_archive_link(), $this->post_type_singular_name() );
	}

	/**
	 * Build the day archive breadcrumb.
	 *
	 * @return string the post breadcrumb
	 */
	private function day_crumbs() {
		$year = get_the_time( 'Y' );
		$output = $this->link_wrap( get_year_link( $year ), $year );
		$output .= $this->link_wrap( get_month_link( $year, get_the_time( 'm' ) ), get_the_time( 'F' ) );
		$output .= get_the_time( 'jS' );

		return $output;
	}

	/**
	 * Build the month archive breadcrumb.
	 *
	 * @return string the post breadcrumb
	 */
	private function month_crumbs() {
		$year = get_the_time( 'Y' );
		$output = $this->link_wrap( get_year_link( $year ), $year );
		$output .= get_the_time( 'F' );

		return $output;
	}

	/**
	 * Maybe get post type singuar name.
	 *
	 * Will get a singluar name, or you can set a custom name.
	 *
	 * @return string the post type singular name
	 */
	private function post_type_singular_name() {

		if ( isset( $this->post->singular_name ) ) {
			return $this->post->singular_name;
		}

		// Set a custom name based on post type, or just use the singular name
		switch ( $this->post->post_type ) {
			case 'post':
				$this->post->singular_name = 'Blog';
				break;
			case 'work-portfolio':
				$this->post->singular_name = 'Portfolio';
				break;
			case 'plugins':
				$this->post->singular_name = 'Plugins';
				break;
			case 'books':
				$this->post->singular_name = 'Books';
				break;
			case 'events':
				$this->post->singular_name = 'Events';
				break;
			default:
				// Get the current post type object
				$post_type_object = get_post_type_object( $this->post->post_type );
				$this->post->singular_name = $post_type_object->labels->singular_name;
				break;
		}

		return $this->post->singular_name;
	}

	/**
	 * Maybe get the post type archive link.
	 *
	 * Will get the arcvhive link, or you can set a custom link.
	 *
	 * @return string the post type archive url
	 */
	private function post_type_archive_link() {

		if ( isset( $this->post->archive_link ) ) {
			return $this->post->archive_link;
		}

		// Set a custom link, or just the default archive link
		switch ( $this->post->post_type ) {
			case 'post':
				$this->post->archive_link = '/blog/';
				break;
			case 'work-portfolio':
				$this->post->archive_link = '/portfolio/';
				break;
			case 'team':
				$this->post->archive_link = '/about/team/';
				break;
			case 'plugins':
				$this->post->archive_link = '/plugins/';
				break;
			case 'events':
				$this->post->archive_link = '/events/';
				break;
			default:
				$this->post->archive_link = get_post_type_archive_link( $this->post->post_type );
				break;
		}

		return $this->post->archive_link;
	}

	/**
	 * Maybe display a separator.
	 *
	 * Only return a seperator if there is a parent link.
	 *
	 * @param  string  $link  a link to the parent item
	 * @return string         maybe a seperator...maybe not
	 */
	private function maybe_do_seperator( $link ) {
		return ( $link ) ? $this->separator : '';
	}

}
