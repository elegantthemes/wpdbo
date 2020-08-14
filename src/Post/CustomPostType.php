<?php

namespace ET\DBO\Post;


abstract class CustomPostType extends BaseObject {

	/**
	 * The name of the primary category-style taxonomy for this post type.
	 *
	 * @since 1.0.0
	 */
	protected string $_category_tax = '';

	/**
	 * The name of the primary tag-style taxonomy for this post type.
	 *
	 * @since 1.0.0
	 */
	protected string $_tag_tax = '';

	/**
	 * Post type key.
	 *
	 * @since 1.0.0
	 */
	public static string $name;

	/**
	 * @inheritDoc
	 */
	public static string $wp_type = 'cpt';

	/**
	 * Returns a new {@see Query} instance for this post type.
	 *
	 * @since 1.0.0
	 */
	public function query(): PostQuery {
		return new PostQuery( static::$name, $this->_category_tax, $this->_tag_tax );
	}
}
