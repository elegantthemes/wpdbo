<?php

namespace ET\DBO\User;

use WP_Role;
use function add_role, et_, et_wrong;



abstract class UserRole {

	/**
	 * See {@see WP_Role::name}
	 */
	public string $name;

	/**
	 * See {@see add_role()} `$display_name` param
	 */
	public string $display_name;

	/**
	 * See {@see add_role()} `$capabilities` param
	 */
	public array $capabilities;

	/**
	 * UserRole constructor
	 */
	public function __construct() {
		if ( et_()->all( [$this->name, $this->display_name, $this->capabilities] ) ) {
			add_action( 'init', [$this, 'maybeAddRole'] );
		} else {
			$class = static::class;
			et_wrong( "{$class}::\$capabilities, {$class}::\$display_name, and {$class}::\$name should not be empty." );
		}
	}

	public function maybeAddRole(): void {
		$role       = get_role( $this->name );
		$should_add = is_null( $role );

		if ( $role && ( $role->capabilities != $this->capabilities ) ) {
			$should_add = true;

			remove_role( $this->name );
		}

		if ( $should_add ) {
			add_role( $this->name, $this->display_name, $this->capabilities );
		}
	}
}
