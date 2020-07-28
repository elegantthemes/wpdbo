<?php

namespace ET\DBO;

use function \et_;
use \{WP_Post, WP_Query};


class PostQuery {

	/**
	 * Whether or not to negate the next query arg that is set. Default 'false'.
	 *
	 * @since ??
	 */
	protected bool $_should_negate = false;

	/**
	 * The query result.
	 *
	 * @since ??
	 * @var   WP_Post|WP_Post[]
	 */
	protected $_query_result;

	/**
	 * The args that will be passed to {@see WP_Query} the next time {@see self::run()} is called.
	 *
	 * @since ??
	 */
	protected array $_wp_query_args;

	/**
	 * The name of the primary category-style taxonomy for this post type.
	 *
	 * @since 3.0.99
	 * @var   string
	 */
	public string $category_tax;

	/**
	 * The post type (slug) for this instance.
	 *
	 * @since 3.0.99
	 * @var   string
	 */
	public string $post_type;

	/**
	 * The name of the primary tag-style taxonomy for this post type.
	 *
	 * @since 3.0.99
	 * @var   string
	 */
	public string $tag_tax;

	/**
	 * ET_Core_Post_Query constructor.
	 *
	 * @since ??
	 *
	 * @param string $post_type    See {@see self::$post_type}
	 * @param string $category_tax See {@see self::$category_tax}
	 * @param string $tag_tax      See {@see self::$tag_tax}
	 */
	public function __construct( string $post_type = '', string $category_tax = '', string $tag_tax = '' ) {
		$this->post_type    ??= $post_type;
		$this->category_tax ??= $category_tax;
		$this->tag_tax      ??= $tag_tax;

		$this->_wp_query_args = array(
			'post_type'      => $this->post_type,
			'posts_per_page' => -1,
		);
	}

	/**
	 * Adds a meta query to the WP Query args for this instance.
	 *
	 * @since ??
	 *
	 * @param string  $key            The meta key.
	 * @param ?mixed  $value          The meta value.
	 * @param bool    $should_negate  Whether or not to negate this meta query.
	 */
	protected function _addMetaQuery( string $key, $value, bool $should_negate ): void {
		if ( ! isset( $this->_wp_query_args['meta_query'] ) ) {
			$this->_wp_query_args['meta_query'] = [];
		}

		if ( is_null( $value ) ) {
			$compare = $should_negate ? 'NOT EXISTS' : 'EXISTS';
		} else if ( is_array( $value ) ) {
			$compare = $should_negate ? 'NOT IN' : 'IN';
		} else {
			$compare = $should_negate ? '!=' : '=';
		}

		$query = [
			'key'     => $key,
			'compare' => $compare,
		];

		if ( ! is_null( $value ) ) {
			$query['value'] = $value;
		}

		if ( '!=' === $compare ) {
			$query = [
				'relation' => 'OR',
				[
					'key'     => $key,
					'compare' => 'NOT EXISTS',
				],
				$query,
			];
		}

		$this->_wp_query_args['meta_query'][] = $query;
	}

	/**
	 * Adds a tax query to the WP Query args for this instance.
	 *
	 * @since ??
	 *
	 * @param string  $taxonomy       The taxonomy name.
	 * @param array   $terms          Taxonomy terms.
	 * @param bool    $should_negate  Whether or not to negate this tax query.
	 */
	protected function _addTaxQuery( string $taxonomy, array $terms, bool $should_negate ): void {
		if ( ! isset( $this->_wp_query_args['tax_query'] ) ) {
			$this->_wp_query_args['tax_query'] = [];
		}

		$operator = $should_negate ? 'NOT IN' : 'IN';
		$field    = is_int( $terms[0] ) ? 'term_id' : 'name';

		$query = [
			'taxonomy' => $taxonomy,
			'field'    => $field,
			'terms'    => $terms,
			'operator' => $operator,
		];

		if ( $should_negate ) {
			$query = [
				'relation' => 'OR',
				[
					'taxonomy' => $taxonomy,
					'operator' => 'NOT EXISTS',
				],
				$query,
			];
		}

		$this->_wp_query_args['tax_query'][] = $query;
	}

	/**
	 * Resets {@see self::$_should_negate} to default then returns the previous value.
	 *
	 * @since ??
	 */
	protected function _resetNegation(): bool {
		$negate = $this->_should_negate;

		$this->_should_negate = false;

		return $negate;
	}

	/**
	 * Adds a tax query to this instance's WP Query args for it's category taxonomy.
	 *
	 * @since ??
	 *
	 * @param mixed ...$categories Variable number of category arguments where each arg can be
	 *                             a single category name or ID or an array of names or IDs.
	 */
	public function inCategory( ...$categories ): self {
		$negate = $this->_resetNegation();

		if ( ! $this->category_tax ) {
			wp_die( 'A category taxonomy has not been set for this query!' );
		}

		if ( ! $categories = et_()->arrayFlatten( $categories ) ) {
			return $this;
		}

		$this->_addTaxQuery( $this->category_tax, $categories, $negate );

		return $this;
	}

	/**
	 * Negates the next query arg that is set.
	 *
	 * @since ??
	 */
	public function not(): self {
		$this->_should_negate = true;

		return $this;
	}

	/**
	 * Performs a new WP Query using the instance's current query params and then returns the
	 * results. Typically, this method is the last method call in a set of chained calls to other
	 * methods on this class during which various query params are set.
	 *
	 * Examples:
	 *
	 *     $cpt_query
	 *         ->in_category( 'some_cat' )
	 *         ->with_tag( 'some_tag' )
	 *         ->run();
	 *
	 *     $cpt_query
	 *         ->with_tag( 'some_tag' )
	 *         ->not()->in_category( 'some_cat' )
	 *         ->run();
	 *
	 * @since ??
	 *
	 * @param array $args Optional. Additional arguments for {@see WP_Query}.
	 *
	 * @return WP_Post[] $posts
	 */
	public function run( array $args = [] ): array {
		if ( ! is_null( $this->_query_result ) ) {
			return $this->_query_result;
		}

		$name = $this->post_type;

		if ( $args ) {
			$this->_wp_query_args = array_merge_recursive( $this->_wp_query_args, $args );
		}

		/**
		 * Filters the WP Query args for a custom post type query. The dynamic portion of
		 * the filter name, $name, refers to the name of the custom post type.
		 *
		 * @since ??
		 *
		 * @param array $args {@see WP_Query::__construct()}
		 */
		$this->_wp_query_args = apply_filters( "et_cloud_cpt_{$name}_query_args", $this->_wp_query_args );

		$query = new WP_Query( $this->_wp_query_args );

		return $this->_query_result = $query->posts;
	}

	/**
	 * Adds a meta query to this instance's WP Query args.
	 *
	 * @since ??
	 *
	 * @param string $key   The meta key.
	 * @param ?mixed $value Optional. The meta value to compare. When `$value` is not provided,
	 *                      the comparison will be 'EXISTS' or 'NOT EXISTS' (when negated).
	 *                      When `$value` is an array, comparison will be 'IN' or 'NOT IN'.
	 *                      When `$value` is not an array, comparison will be '=' or '!='.
	 */
	public function withMeta( string $key, $value = null ): self {
		$this->_addMetaQuery( $key, $value, $this->_resetNegation() );

		return $this;
	}

	/**
	 * Adds a tax query to this instance's WP Query args for it's primary tag-like taxonomy.
	 *
	 * @since ??
	 *
	 * @param mixed ...$tags Variable number of tag arguments where each arg can be
	 *                       a single tag name or ID, or an array of tag names or IDs.
	 */
	public function withTag( ...$tags ): self {
		$negate = $this->_resetNegation();

		if ( ! $this->tag_tax ) {
			wp_die( 'A tag taxonomy has not been set for this query!' );
		}

		if ( ! $tags = et_()->arrayFlatten( $tags ) ) {
			return $this;
		}

		$this->_addTaxQuery( $this->tag_tax, $tags, $negate );

		return $this;
	}
}
