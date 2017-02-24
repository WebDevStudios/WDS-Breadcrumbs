<?php
/**
 * Plugin Name: WDS Breadcrumbs
 * Plugin URI: http://webdevstudios.com
 * Description: Simple breadcrumbs for WDS7
 * Version: 1.1
 * Author: WebDevStudios
 * Author URI: http://webdevstudios.com
 *
 * @package  WDS Breadcrumbs
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to generate breadcrumbs for the current post/archive/page.
 */
class WDS_Breadcrumbs {

	/**
	 * Default homepage separator.
	 *
	 * @var string the separator
	 */
	protected $separator = ' &gt; ';

	/**
	 * Default homepage text.
	 *
	 * @var string the homepage text
	 */
	protected $homepage_text = 'WebDevStudios.com';

	/**
	 * The current post id.
	 *
	 * @var integer Post ID whose breadcrumbs we should display
	 */
	protected $post_id = 0;

	/**
	 * The curent post type.
	 *
	 * @var string Post type whose breadcrumbs we should display.
	 */
	protected $post_type = 'post';

	/**
	 * The current post object.
	 *
	 * @var object
	 */
	protected $post;

	/**
	 * Used for the navigation meta for structured data.
	 *
	 * @var int
	 */
	protected $content_pos = 0;

	/**
	 * Allow for filtering of the separator value.
	 *
	 * @return  string Breadcrumb separator.
	 */
	public function do_separator() {
		/**
		 * Adjust the breadcrumb separator.
		 *
		 * Filter to update the separator between breadcrumbs.
		 *
		 * @since 1.0
		 *
		 * @param         string Default homepage text.
		 */
		return apply_filters( 'wds_breadcrumbs_separator', $this->separator );
	}

	/**
	 * Allow for filtering of the separator value.
	 *
	 * @return string Homepage Text.
	 */
	public function do_homepage_text() {
		/**
		 * Adjust the breadcrumb homepage text.
		 *
		 * Filter to update the text for the homepage breadcrumb
		 *
		 * @since 1.0
		 *
		 * @param         string Default homepage text.
		 */
		return apply_filters( 'wds_breadcrumbs_homepage_text', $this->homepage_text );
	}

	/*
	 * Define meta itemprop content.
	 * Add metadata for the breadcrumb.
	 *
	 * @since 1.0
	 *
	 * @return string Markup for metadata.
	 */
	protected function _itemprop_pos() {
		return sprintf( '<meta itemprop="position" content="%d" />', $this->content_pos );
	}

	/**
	 * Wraps content/links in spans for SEO purposes.
	 *
	 * @param string $content Text for the breadcrumb.
	 * @param string $link 	  URL for the breadcrumb.
	 * @link https://developers.google.com/search/docs/data-types/breadcrumbs
	 *
	 * @author JayWood.
	 * @return string HTML output.
	 */
	public function build_list_item_data( $content = '', $link = '' ) {
		// Positions are base 1, not 0.
		++$this->content_pos;

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
						<span class="current" itemscope itemtype="http://schema.org/Thing" itemprop="item">
							<span itemprop="name">%1$s</span>
						</span>
						' . $this->_itemprop_pos() . '
					%2$s</li>';
		}

		return sprintf( $output, $content, $this->maybe_do_separator( $link ), $link );
	}

	/**
	 * Bake our bread and leave a trail.
	 *
	 * @param  int $post_id The ID of the post to build the breadcrumb(s) for.
	 * @return string  the breadcrumbs
	 */
	public function do_breadcrumbs( $post_id = 0 ) {
		$this->post_id = $post_id ? $post_id : get_the_ID();
		$this->post    = get_post( $post_id );

		/**
		 * Override output of breadcrumbs before the additional logic.
		 *
		 * Filter to override breadcrumbs output before running through logic.
		 *
		 * @since 1.1
		 *
		 * @param null    Override for breadcrumb output.
		 * @param int     ID for the current post.
		 * @param WP_Post Post object for the current post.
		 */
		$override = apply_filters( 'wds_breadcrumbs_output_override', null, $this->post_id, $this->post );

		// Bail early breadcrumbs are being overridden.
		if ( ! ( null === $override ) ) {
			return $override;
		}

		// Start baking.
		$output = '<ul class="site-breadcrumbs" itemscope itemtype="http://schema.org/BreadcrumbList">';

		// First Breadcrumb.
		$output .= $this->homepage_crumb();

		// Secondary Breadcrumbs.
		if ( is_singular() ) {

			if ( is_page() ) {
				$output .= $this->page_crumbs();
			} elseif ( is_single() ) {
				$output .= $this->post_crumb();
			}

			 // Final Breadcrumb.
			$output .= $this->build_list_item_data( get_the_title() );

		} elseif ( is_home() ) {
			$blog_title = get_the_title( get_option( 'page_for_posts', true ) );
			$output .= $this->build_list_item_data( $blog_title );

		} elseif ( is_day() ) {
			$output .= $this->day_crumbs();

		} elseif ( is_month() ) {
			$output .= $this->month_crumbs();

		} elseif ( is_year() ) {
			$output .= get_the_time( 'Y' );

		} elseif ( is_author() ) {
			$author = get_the_author();
			$output .= $this->build_list_item_data( $author );

		} elseif ( is_search() ) {
			$search_results = __( 'Searched For: ', 'wds8' ) . get_search_query();
			$output .= $this->build_list_item_data( $search_results );

		} elseif ( is_post_type_archive() ) {
			$output .= $this->post_type_singular_name();

		} elseif ( is_day() ) {
			$output .= $this->day_crumbs();

		} elseif ( is_month() ) {
			$output .= $this->month_crumbs();

		} elseif ( is_year() ) {
			$output .= get_the_time( 'Y' );

		} elseif ( is_author() ) {
			$output .= get_the_author();

		} elseif ( is_search() ) {
			$search_results = __( 'Searched For: ', 'wds8' ) . get_search_query();
			$output .= $this->build_list_item_data( $search_results );

		} elseif ( is_post_type_archive() ) {
			$output .= $this->post_type_singular_name();

		} elseif ( is_tag() || is_category() || is_archive() ) {
			if ( is_tax() ) {
				$output .= $this->taxonomy_archive_links();
			} else {
				$output .= single_term_title( '', false );
			}
		} elseif ( is_404() ) {
			// Do nothing on 404s.
			$output = '';
		} else {
			// When all else fails, we're probably on index.php.
			if ( ! is_home() || ! is_front_page() ) {
				$output .= $this->post_type_singular_name();
			}
		}

		// Close the breadcrumbs.
		$output .= '</ul>';

		/**
		 * Filter returned concatenated string of breadcrumbs!
		 *
		 * Filter breadcrumbs for the post.
		 *
		 * @since 1.1
		 *
		 * @param string  Generated breadcrumbs.
		 * @param int     ID for the current post.
		 * @param WP_Post Post object for the current post.
		 */
		return apply_filters( 'wds_breadcrumbs_output', $output, $this->post_id, $this->post );
	}

	/**
	 * HTML markup wrapper for breadcrumb link.
	 *
	 * @param  string $link  The link.
	 * @param  string $text  The linked text.
	 * @return string        Complete url markup.
	 */
	protected function link_wrap( $link = '', $text = '' ) {
		// Bail early if no link.
		if ( empty( $link ) ) {
			return '';
		}

		return $this->build_list_item_data( $text, $link );
	}

	/**
	 * The homepage breadcrumb.
	 *
	 * @return string Markup for the homepage crumb.
	 */
	public function homepage_crumb() {
		/**
		 * Modify the homepage crumb markup.
		 *
		 * Filter markup for the homepage crumb.
		 *
		 * @since 1.1
		 *
		 * @param string Current homepage crumb markup.
		 */
		return apply_filters( 'wds_breadcrumbs_homepage_crumb', $this->link_wrap( home_url(), $this->do_homepage_text() ) );
	}

	/**
	 * Build the page ancestors breadcrumbs.
	 *
	 * @return string the page breadcrumb.
	 */
	public function page_crumbs() {
		// Get an array of post ancestors.
		$parents = get_post_ancestors( $this->post_id );
		$crumbs = '';

		// No parents? Then bail...
		if ( empty( $parents ) || ! is_array( $parents ) ) {
			/**
			 * Modify the page breadcrumb markup.
			 *
			 * Filter markup for the page breadcrumbs.
			 *
			 * @since 1.0
			 *
			 * @param string Current page crumb markup.
			 * @param int    ID of the current post.
			 */
			return apply_filters( 'wds_page_crumbs', $crumbs, $this->post_id );
		}

		// Loop through parents and add to crumbs.
		foreach ( array_reverse( $parents ) as $parent ) {
			$crumbs .= $this->link_wrap( get_permalink( $parent ), get_the_title( $parent ) );
		}

		/**
		 * Modify the page breadcrumb markup.
		 *
		 * Filter markup for the page breadcrumbs.
		 *
		 * @since 1.0
		 *
		 * @param string Current page crumb markup.
		 * @param int    ID of the current post.
		 */
		return apply_filters( 'wds_page_crumbs', $crumbs, $this->post_id );
	}

	/**
	 * Build the single post breadcrumb.
	 *
	 * @return string the post breadcrumb.
	 */
	protected function post_crumb() {
		return $this->link_wrap( $this->post_type_archive_link(), $this->post_type_singular_name() );
	}

	/**
	 * Build the day archive breadcrumb.
	 *
	 * @return string the post breadcrumb.
	 */
	protected function day_crumbs() {
		$year = get_the_time( 'Y' );
		$output = $this->link_wrap( get_year_link( $year ), $year );
		$output .= $this->link_wrap( get_month_link( $year, get_the_time( 'm' ) ), get_the_time( 'F' ) );
		$output .= get_the_time( 'jS' );

		return $output;
	}

	/**
	 * Build the month archive breadcrumb.
	 *
	 * @return string the post breadcrumb.
	 */
	public function month_crumbs() {
		$year = get_the_time( 'Y' );
		$output = $this->link_wrap( get_year_link( $year ), $year );
		$output .= get_the_time( 'F' );

		return $output;
	}

	/**
	 * Build the category breadcrumb
	 *
	 * @return string the category breadcrumb.
	 */
	public function category_crumbs() {
		if ( ! ( 'page' === get_option( 'show_on_front' ) ) ) {
			return single_term_title( '', false );
		} else {
			$id = get_option( 'page_for_posts' );

			$page = get_post( (int) $id );
			$output = $this->link_wrap( get_permalink( $page->ID ), $page->post_title );
			$output .= $this->build_list_item_data( single_term_title( '', false ) );
			return $output;
		}
	}

	/**
	 * Maybe get post type singuar name.
	 *
	 * Will get a singular name, or you can set a custom name.
	 *
	 * @return string the post type singular name.
	 */
	protected function post_type_singular_name() {
		// Bail early if singular name is available.
		if ( isset( $this->post->singular_name ) ) {
			return $this->post->singular_name;
		}

		// Bail early is post_type is no available.
		if ( ! isset( $this->post->post_type ) ) {
			return '';
		}

		// Set a custom name based on post type, or just use the singular name.
		$name = '';

		switch ( $this->post->post_type ) {
			case 'post':
				$name = 'Blog';
				break;
			default:
				// Get the current post type object.
				$post_type_object = get_post_type_object( $this->post->post_type );
				if ( ! is_null( $post_type_object ) && isset( $post_type_object->labels ) && isset( $post_type_object->labels->singular_name ) ) {
					$name = apply_filters( 'wds_breadcrumbs_singular_name', $post_type_object->labels->singular_name, $post_type_object );
				}
				break;
		}

		/**
		 * Update the name for a post type in breadcrumbs.
		 *
		 * Filter post type name in breadcrumbs.
		 *
		 * @since 1.1
		 *
		 * @param         string  The name for the post type.
		 * @param         int     ID for the current post.
		 * @param  		  WP_Post Post object for the current post.
		 */
		$name = apply_filters( 'wds_breadcrumbs_post_type_name', $name, $this->post_id, $this->post );

		$this->post->singular_name = $name;
		return $this->post->singular_name;
	}

	/**
	 * Maybe get the post type archive link.
	 *
	 * Will get the archive link, or you can set a custom link.
	 *
	 * @return string the post type archive url.
	 */
	public function post_type_archive_link() {
		// Bail early if archive link is already available.
		if ( isset( $this->post->archive_link ) ) {
			return $this->post->archive_link;
		}

		// Set a custom link, or just the default archive link.
		switch ( $this->post->post_type ) {
			case 'post':
				$this->post->archive_link = get_post_type_archive_link( 'post' );
				break;
			default:
				/**
				 * Update the post type archive link.
				 *
				 * Filter post type archive link in breadcrumbs.
				 *
				 * @since 1.0
				 *
				 * @param         string  Link for the post type archive.
				 * @param  		  WP_Post Post object for the current post.
				 */
				$this->post->archive_link = apply_filters(
					'wds_breadcrumbs_post_type_archive_link',
					get_post_type_archive_link( $this->post->post_type ),
					$this->post
				);

				break;
		}

		return $this->post->archive_link;
	}

	/**
	 * Maybe get the taxonomy archive links.
	 *
	 * @return string list of taxonomy archive links.
	 */
	public function taxonomy_arcshive_links() {
		global $wp_query;

		// Bail early if no taxonomy.
		if ( empty( $wp_query->queried_object->term_id ) ) {
			return;
		}

		// Hold link output.
		$output = '';

		// Get ancestors for term.
		$ancestors = get_ancestors( $wp_query->queried_object->term_id, $wp_query->queried_object->taxonomy );

		// Fill ancestors if available.
		if ( ! empty( $ancestors ) ) {
			// Make sure they're in order.
			$ancestors = array_reverse( $ancestors );

			// Add terms to breadcrumbs.
			foreach ( (array) $ancestors as $ancestor ) {
				$term = get_term_by(
					'id',
					$ancestor,
					$wp_query->queried_object->taxonomy
				);

				// Skip if no term.
				if ( empty( $term ) ) {
					continue;
				}

				// Add ancestor term breadcrumb.
				$output .= $this->build_list_item_data(
					$term->name,
					get_term_link( $term->term_id )
				);
			}
		}

		// Add original term breadcrumb.
		$output .= $this->build_list_item_data( single_term_title( '', false ) );

		return $output;
	}

	/**
	 * Maybe display a separator.
	 *
	 * Only return a separator if there is a parent link.
	 *
	 * @param  string $link  Link to the parent item.
	 * @return string         maybe a seperator...maybe not
	 */
	protected function maybe_do_seperator( $link ) {
		return ( $link ) ? $this->do_separator() : '';
	}
}
