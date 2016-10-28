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
	 * @var object
	 */
	protected $post;

	/**
	 * Used for the navigation meta for structured data.
	 * @var int
	 */
	private $content_pos = 0;

	/**
	 * Allow for filtering of the separator value
	 */
	public function do_separator() {
		return apply_filters( 'wds_breadcrumbs_separator', $this->separator );
	}

	/**
	 * Allow for filtering of the separator value
	 */
	public function do_homepage_text() {
		return apply_filters( 'wds_breadcrumbs_homepage_text', $this->homepage_text );
	}

	private function _itemprop_pos() {
		return sprintf( '<meta itemprop="position" content="%d" />', $this->content_pos );
	}

	/**
	 * Wraps content/links in spans for SEO purposes
	 *
	 * @param string $content
	 * @param string $link
	 * @link https://developers.google.com/search/docs/data-types/breadcrumbs
	 *
	 * @author JayWood
	 * @return string HTML output
	 */
	public function build_list_item_data( $content = '', $link = '' ) {
		// Positions are base 1, not 0.
		$this->content_pos++;

		if ( ! empty( $link ) ) {
			$output = '<li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
						<a class="breadcrumb-link" href="%3$s" itemscope itemtype="http://schema.org/Thing" itemprop="item">
							<span itemprop="name">%1$s</span>
						</a>
						' . $this->_itemprop_pos() . '
					%2$s</li>';
		} else {
			// End of the line crumb... no link for you!
			$output = '<li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
						<span itemscope itemtype="http://schema.org/Thing" itemprop="item">
							<span itemprop="name">%1$s</span>
						</span>
						' . $this->_itemprop_pos() . '
					%2$s</li>';
		}

		return sprintf( $output, $content, $this->maybe_do_seperator( $link ), $link );
	}

	/**
	 * Bake our bread and leave a trail.
	 *
	 * @return string  the breadcrumbs
	 */
	public function do_breadcrumbs( $post_id = 0 ) {
		$this->post_id = $post_id ? $post_id : get_the_ID();
		$this->post    = get_post( $post_id );

		/**
		 * Override output of breadcrumbs before the addtional logic
		 *
		 * Filter to override breadcrumbs output before running through logic
		 *
		 * @since 1.1
		 *
		 * @param         null override for breadcrumb output
		 * @param         int    ID for the current post
		 * @param  		  WP_Post post object for the current post
		 */
		$override = apply_filters( 'wds_breadcrumbs_output_override', null, $this->post_id, $this->post );

		if ( ! ( null === $override ) ) {
			return $override;
		}

		// Start baking
		$output = '<ul itemscope itemtype="http://schema.org/BreadcrumbList">';

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
			$output .= $this->build_list_item_data( get_the_title() );
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

		elseif ( is_category() ) {
			$output .= $this->category_crumbs();
		}

		elseif ( is_tag() || is_category() || is_archive() ) {
			if ( is_tax() ) {
				$output .= $this->taxonomy_archive_links();
			} else {
				$output .= single_term_title( '', false );
			}

		}

		// When all else fails, we're probably on index.php
		else {

			if ( ! is_home() || ! is_front_page() ) {
				$output .= $this->post_type_singular_name();
			}
		}

		$output .= '</ul>';

		/**
		 * Filter returned concantonated string of breadcrumbs!
		 *
		 * Filter breadcrumbs for the post
		 *
		 * @since 1.1
		 *
		 * @param         string generated breadcrumbs
		 * @param         int    ID for the current post
		 * @param  		  WP_Post post object for the current post
		 */
		return apply_filters( 'wds_breadcrumbs_output', $output, $this->post_id, $this->post );
	}

	/**
	 * HTML markup wrapper for breadcrumb link.
	 *
	 * @param  string  $link  the link
	 * @param  string  $text  the linked text
	 * @return string         complete url makrup
	 */
	private function link_wrap( $link = '', $text = '' ) {

		if ( $link ) {
			return $this->build_list_item_data( $text, $link );
		}

		return '';
	}

	/**
	 * The homepage breadcrumb.
	 */
	public function homepage_crumb() {
		return apply_filters( 'wds_breadcrumbs_homepage_crumb', $this->link_wrap( home_url(), $this->do_homepage_text() ) );
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
			return apply_filters( 'wds_page_crumbs', $crumbs, $this->post_id );
		}

		foreach ( array_reverse( $parents ) as $parent ) {
			$crumbs .= $this->link_wrap( get_permalink( $parent ), get_the_title( $parent ) );
		}

		return apply_filters( 'wds_page_crumbs', $crumbs, $this->post_id );
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

	private function category_crumbs() {
		if ( get_option( 'show_on_front' ) != 'page' ) {
			return single_term_title( '', false );
		} else {
			$id = get_option( 'page_for_posts' );
			$page = get_post( (int) $id );
			$output = $this->link_wrap( get_permalink( $page->ID ), $page->post_title );
			$output .= single_term_title( '', false );
			return $output;
		}
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

		if ( ! isset( $this->post->post_type ) ) {
			return '';
		}
		// Set a custom name based on post type, or just use the singular name
		$name = '';
		switch ( $this->post->post_type ) {
			case 'post':
				$name = 'Blog';
				break;
			default:
				// Get the current post type object
				$post_type_object = get_post_type_object( $this->post->post_type );
				if ( ! is_null( $post_type_object ) && isset( $post_type_object->labels ) && isset( $post_type_object->labels->singular_name ) ) {
					$name = apply_filters( 'wds_breadcrumbs_singular_name', $post_type_object->labels->singular_name, $post_type_object );
				}
				break;
		}

		$this->post->singular_name = $name;
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
		// bail early if archive link is already available
		if ( isset( $this->post->archive_link ) ) {
			return $this->post->archive_link;
		}

		// Set a custom link, or just the default archive link
		switch ( $this->post->post_type ) {
			case 'post':
				$this->post->archive_link = get_post_type_archive_link( 'post' );
				break;
			default:
				$this->post->archive_link = apply_filters( 'wds_breadcrumbs_post_type_archive_link', get_post_type_archive_link( $this->post->post_type ), $this->post );
				break;
		}

		return $this->post->archive_link;
	}

	/**
	 * Maybe get the taxonomy archive links.
	 *
	 * @return string list of taxonomy archive links
	 */
	protected function taxonomy_archive_links() {
		global $wp_query;

		// bail early if no taxonomy
		if ( empty( $wp_query->queried_object->term_id ) ) {
			return;
		}

		// hold link output
		$output = '';

		// get ancestors for term
		$ancestors = get_ancestors( $wp_query->queried_object->term_id, $wp_query->queried_object->taxonomy );

		// fill ancestors if available
		if ( ! empty( $ancestors ) ) {
			// make sure they're in order
			$ancestors = array_reverse( $ancestors );

			// add terms to breadcrumbs
			foreach ( (array) $ancestors as $ancestor ) {
				$term = get_term_by( 
					'id', 
					$ancestor, 
					$wp_query->queried_object->taxonomy
				);

				// skip if no term
				if ( empty( $term ) ) {
					continue;
				}

				// add term breadcrumb 
				$output .= $this->build_list_item_data(
					$term->name,
					get_term_link( $term->term_id )
				);
			}
		}

		// add term breadcrumb 
		$output .= $this->build_list_item_data( single_term_title( '', false ) );

		return $output;
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
		return ( $link ) ? $this->do_separator() : '';
	}

}
