<?php

namespace ET\DBO\Post\Post;

use function et_;
use WP_Post_Type;
use WP_Taxonomy;


abstract class BaseObject {

	/**
	 * Current instances of this class organized by type.
	 *
	 * @since 1.0.0
	 * @var   array[] {
	 *
	 * @type BaseObject[] $type {
	 *
	 * @type BaseObject   $name Instance.
	 *         ...
	 *     }
	 *     ...
	 * }
	 */
	protected static array $_instances = [];

	/**
	 * The `$args` array used when registering this entity.
	 *
	 * @since 1.0.0
	 */
	protected array $_args;

	/**
	 * Whether or not the object has been registered.
	 *
	 * @since 1.0.0
	 */
	protected bool $_is_registered = false;

	/**
	 * The WP object for this instance.
	 *
	 * @since 1.0.0
	 * @var   WP_Post_Type|WP_Taxonomy
	 */
	protected $_wp_object;

	/**
	 * Post object key.
	 *
	 * @since 1.0.0
	 */
	public static string $name;

	/**
	 * Post object type. Accepts 'cpt', 'taxonomy'.
	 *
	 * @since 1.0.0
	 */
	public static string $wp_type;

	/**
	 * BaseObject constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->_args           = $this->_args();
		$this->_args['labels'] = $this->_labels();

		$this->_applyFilters();
		$this->_sanityCheck();

		if ( empty( self::$_instances ) ) {
			add_action( 'init', [__CLASS__, 'registerAll'] );
		}
	}

	/**
	 * Applies filters to the instance's filterable properties.
	 *
	 * @since 1.0.0
	 */
	protected function _applyFilters(): void {
		$name = static::$name;
		$type = static::$wp_type;

		/**
		 * Filters the `$args` for a custom post type or taxonomy. The dynamic portions of the
		 * filter are:
		 *   - `$type` Will be `cpt` or `taxonomy`.
		 *   - `$name` Refers to the name/key of the post type or taxonomy being registered.
		 *
		 * @since 1.0.0
		 *
		 * @param  array  $args  {@see register_post_type()} and {@see register_taxonomy()}
		 */
		$this->_args = apply_filters( "et_wpdbo_{$type}_{$name}_args", $this->_args );
	}

	/**
	 * Returns the args for the instance. See {@see register_post_type()} or
	 * {@see register_taxonomy()}.
	 *
	 * @since 1.0.0
	 */
	abstract protected function _args(): array;

	/**
	 * This method is called right before registering the object. It is intended to be
	 * overridden by child classes as needed.
	 *
	 * @since 1.0.0
	 */
	protected function _beforeRegister(): void {}

	/**
	 * Returns labels for the instance. See {@see register_post_type()} or {@see register_taxonomy()}.
	 *
	 * @since 1.0.0
	 */
	abstract protected function _labels(): array;

	/**
	 * Checks for required properties and existing instances.
	 *
	 * @since 1.0.0
	 */
	protected function _sanityCheck(): void {
		if ( ! $this->_args || ! static::$name || ! static::$wp_type ) {
			wp_die( 'Missing required properties!' );

		} else if ( isset( self::$_instances[ static::$wp_type ][ static::$name ] ) ) {
			wp_die( 'Multiple instances are not allowed!' );
		}
	}

	/**
	 * Get a derived class instance.
	 *
	 * @since 1.0.0
	 */
	public static function instance(): self {
		if ( $instance = et_()->arrayGet( self::$_instances, [ static::$wp_type, static::$name ], null ) ) {
			return $instance;
		}

		$instance = new static;

		et_()->arraySet( self::$_instances, [ static::$wp_type, static::$name ], $instance );

		return $instance;
	}

	/**
	 * Calls either {@see register_post_type} or {@see register_taxonomy} for each instance.
	 *
	 * @since 1.0.0
	 */
	public static function registerAll(): void {
		if ( empty( self::$_instances ) ) {
			return;
		}

		global $wp_taxonomies;

		foreach ( self::$_instances['taxonomy'] as $instance ) {
			if ( $instance->_is_registered ) {
				continue;
			}

			$instance->_beforeRegister();

			register_taxonomy( $instance::$name, $instance->post_types, $instance->_args );

			$instance->_wp_object     = $wp_taxonomies[ $instance::$name ];
			$instance->_is_registered = true;
		}

		foreach ( self::$_instances['cpt'] as $instance ) {
			if ( $instance->_is_registered ) {
				continue;
			}

			$instance->_beforeRegister();

			$instance->_wp_object     = register_post_type( $instance::$name, $instance->_args );
			$instance->_is_registered = true;
		}
	}
}
