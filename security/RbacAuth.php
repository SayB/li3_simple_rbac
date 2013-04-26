<?php

namespace li3_simple_rbac\security;

class RbacAuth extends \lithium\security\Auth {
	/**
	 * Returns an array of Lithium's `Auth` configuration keys
	 * @return array Array of `Auth` configuration keys.
	 */
	public static function configKeys() {
		return is_array(static::$_configurations) ? array_keys(static::$_configurations) : array();
	}
}