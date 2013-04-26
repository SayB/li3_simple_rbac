<?php
/**
 * li3_simple_rbac [simple role-based access control] plugin for Lithium: the most rad php framework.
 *
 * @author        Sohaib Muneer
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_simple_rbac\security;

use lithium\core\ConfigException;
use lithium\security\Auth;
use li3_simple_rbac\security\RbacAuth;

class SimpleRbac extends \lithium\core\Adaptable {
	/**
	 * Stores configurations for various authentication adapters.
	 *
	 * @var object `Collection` of authentication configurations.
	 */
	protected static $_configurations = array();
	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.security.simple_rbac';
	/**
	 * Default settings. The special visitors key is named "visitor" by default. You should change it
	 * if you choose to change the visitor configuration name.
	 *
	 * @var array Default setting values.
	 */
	protected static $_settings = array(
		'visitorsKey' => 'visitor', // name of authentication configuration for non-authenticated users. Default is "visitors" but you can change this using SimpleRbac::settings()
		'message' => 'You are not allowed to access this area.',
		'redirect' => '/'
	);
	/**
	 * Change and / or get default settings.
	 *
	 * @param array $settings
	 * @return array
	 */
	public static function settings($settings = array()) {
		static::$_settings = $settings + static::$_settings;
		return static::$_settings;
	}
	/**
	 * Set the rules by calling this method. The configuration keys should match
	 * the Auth::config() keys.
	 *
	 * This method also sets 'Rbac' as the adapter. You can certainly enhance this method
	 * if you want ti write your own adapter.
	 *
	 * @param array $config
	 * @return array
	 */
	public static function config($config = array()) {
		foreach ($config as $k => $v) {
			if (empty($config[$k]['adapter'])) {
				$config[$k]['adapter'] = 'Rbac';
			}
		}

		return parent::config($config);
	}
	/**
	 * Sets per-key settings as default settings
	 *
	 * @param type $name
	 * @return type
	 */
	protected static function _config($name) {
		$config = parent::_config($name);
		if (!empty($config['settings'])) {
			static::settings($config['settings']);
		}

		return $config;
	}
	/**
	 * Performs Authorization check and if it succeeds, it performs an access check
	 * against the specified configuration, and returns an empty array upon success.
	 *
	 * If authorization fails, it will return boolean FALSE.
	 *
	 * If access is denied it will return an array with `message` and `redirect` keys.
	 * You can use the values of these keys to redirect the user to the desired location
	 * and possibly set a session flash message or may be even log it or both.
	 *
	 * @param string $key The name of the `Access` configuration matching the Auth configuration.
	 * @param object $request Lithium's default `Request` object. Or any type of object with `params`
	 *        key set which is similar to the params key of Lithium's `Request` object.
	 * @return mixed Boolean FALSE if Auth::check() fails and an empty array if access is allowed and
	 *         an array with reasons for denial if denied.
	 */
	public static function check($key, $request) {
		if ($key != static::$_settings['visitorsKey']) {
			if (!Auth::check($key)) {
				return false;
			}
		}

		if (($config = static::_config($key)) === null) {
			throw new ConfigException("Configuration `{$key}` has not been defined.");
		}

		$params = array(
			'request' => $request,
			'rules' => $config['rules'],
			'settings' => static::$_settings
		);

		$filter = function ($self, $params) use ($key) {
			$result = $self::adapter($key)->check(
				$params['rules'], $params['request']
			);

			$settings = $params['settings'];

			if (!empty($result)) { // means access was denied
				if (empty($result['message'])) {
					$result['message'] = $settings['message'];
				}
				if (empty($result['redirect'])) {
					$result['redirect'] = $settings['redirect'];
				}
			}

			return $result;
		};

		return static::_filter(__FUNCTION__, $params, $filter);
	}
	/**
	 * Performs Authorization check (Auth::check()) on all authorization config keys.
	 * If all fail to authorize, returns access for special `visitor` key. The `visitor`
	 * key configuration should hold rules for non-authenticated users, i.e. visitors
	 * to your site.
	 *
	 * @param object $request Lithium's `Request` object.
	 * @return array An empty array if access is allowed and an array with reasons for denial
	 *         if denied.
	 */
	public static function checkAll($request) {
		$authKeys = RbacAuth::configKeys();
		foreach ($authKeys as $key) {
			$auth = Auth::check($key);
			if (!empty($auth)) {
				return static::check($key, $request);
			}
		}

		// now this would mean the user is a visitor
		$settings = static::settings();
		return static::check($settings['visitorsKey'], $request);
	}
}