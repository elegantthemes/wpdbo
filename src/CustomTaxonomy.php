<?php

namespace ET\DBO;

use \{WP_Error, WP_Term};


abstract class CustomTaxonomy extends BaseObject {
	/**
	 * Taxonomy key.
	 *
	 * @since ??
	 */
	public static string $name;

	/**
	 * The post types to which this taxonomy applies.
	 *
	 * @since ??
	 */
	public array $post_types;

	/**
	 * This taxonomy's terms.
	 *
	 * @since ??
	 * @var WP_Term[]
	 */
	public array $terms;

	/**
	 * @inheritDoc
	 */
	public static string $wp_type = 'taxonomy';

	/**
	 * Taxonomy constructor.
	 *
	 * @since ??
	 */
	public function __construct() {
		parent::__construct();

		$name = static::$name;

		/**
		 * Filters the supported post types for a custom taxonomy. The dynamic portion of the
		 * filter name, $name, refers to the name of the custom taxonomy.
		 *
		 * @since ??
		 *
		 * @param array
		 */
		$this->post_types = apply_filters( "et_core_taxonomy_{$name}_post_types", $this->post_types );
	}

	/**
	 * Get the terms for this taxonomy.
	 *
	 * @since ??
	 *
	 * @return array|int|WP_Error|WP_Term[]
	 */
	public function get() {
		if ( is_null( $this->terms ) ) {
			$this->terms = get_terms( static::$name, array( 'hide_empty' => false ) );
		}

		return $this->terms;
	}
}
