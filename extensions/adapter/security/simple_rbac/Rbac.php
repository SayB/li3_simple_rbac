<?php

namespace li3_simple_rbac\extensions\adapter\security\simple_rbac;

class Rbac extends \lithium\core\Object {

	protected $_rules = array();

	public function __construct(array $config = array()) {
		$defaults = array(
			'rules' => array()
		);
		$config += $defaults;
		parent::__construct($config);
		$this->_rules = $config['rules'];
	}

	public function check($rules, $request) {
		$default = array(
			'controller' => '*',
			'action' => '*',
			'prefix' => null,
			'library' => null,
			'allow' => true,
			'message' => null,
			'redirect' => null
		);

		$params = $request->params + array(
			'prefix' => null,
			'library' => null
		);
		$params['controller'] = strtolower($params['controller']);
		$allowed = array();

		foreach ($rules as $r) {
			$_r = $r;
			if (is_string($r)) {
				$_r = $this->_parseString($r);
			}

			$_r = array_merge($default, $_r);
			$match = $this->_match($_r, $params);

			if ($match) {
				$allowed = array();
				if (!$_r['allow']) {
					$allowed = array(
						'message' => $_r['message'],
						'redirect' => $_r['redirect']
					);
				}
			}
		}

		return $allowed;
	}

	protected function _match($rule, $params) {
		$match = false;
		if ($rule['controller'] == '*' || $rule['controller'] == $params['controller']) {
			if ($rule['action'] == '*' || $rule['action'] == $params['action']) {
				$match = true;
			}
		}

		if ($rule['prefix'] != '*' && $rule['prefix'] != $params['prefix']) {
			$match = false;
		}

		if ($rule['library'] != '*' && $rule['library'] != $params['library']) {
			$match = false;
		}

		return $match;
	}

	protected function _parseString($rule) {
		$_rule = $rule;
		$allow = true;
		if (substr($rule, 0, 1) == '!') {
			$_rule = str_replace('!', '', $rule); // clean the rule so that it can be parsed nicely :)
			$allow = false;
		}

		if (!preg_match('/^[A-Za-z0-9_|*]+::[A-Za-z0-9_|*]+$/', $_rule)) {
			return false; // bad rule - there should be an Exception thrown here
		}

		list($controller, $action) = explode('::', $_rule);
		$controller = strtolower($controller);
		$action = strtolower($action);
		$prefix = null;
		$library = null;

		if ($controller == '*' && $action == '*') {
			$prefix = '*';
			$library = '*';
		}

		return compact('controller', 'action', 'prefix', 'library', 'allow');
	}
}