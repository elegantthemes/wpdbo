<?php

namespace ET\DBO;


abstract class CustomPostType extends BaseObject {

	/**
	 * The name of the primary category-style taxonomy for this post type.
	 *
	 * @since ??
	 */
	protected string $_category_tax = '';

	/**
	 * The name of the primary tag-style taxonomy for this post type.
	 *
	 * @since ??
	 */
	protected string $_tag_tax = '';

	/**
	 * Post type key.
	 *
	 * @since ??
	 */
	public static string $name;

	/**
	 * @inheritDoc
	 */
	public static string $wp_type = 'cpt';

	/**
	 * Returns a new {@see Query} instance for this post type.
	 *
	 * @since ??
	 */
	public function query(): PostQuery {
		return new PostQuery( static::$name, $this->_category_tax, $this->_tag_tax );
	}
}
