# Simple Role-Based Access control library for the Lithium framework.

## Installation

Checkout the code to either of your library directories. It's always a good idea to add it as a sub-module:

	cd libraries
	git clone https://github.com/SayB/li3_simple_rbac.git

	OR if you want to add it as a sub-module

	git submodule add https://github.com/SayB/li3_simple_rbac.git libraries/li3_simple_rbac


Include the library in in your `/app/config/bootstrap/libraries.php`

	Libraries::add('li3_simple_rbac');

## Introduction

This plugin attempts to make writing access rules simpler. This is inspired by Tom Maiaroto's `li3_access` plugin
for the `Lithium` framework.

	https://github.com/tmaiaroto/li3_access

Since this plugin attempts a simpler approach to write rules for something like the `AuthRbac` adapter of `li3_access`
therefore, your configuration keys must be identical to the configuration keys that you set in Auth::config().

## Usage

Let's say you have set your Auth coniguration keys like:

	Auth::config(array(
		'default' => array(
			'adapter' => 'Form',
			'model' => 'User',
			'fields' => array(
				'email', 'password'
			),
			'validators' => array(
				'password' => function ($form, $data) {
					return $form == $data;
				}
			),
			'scope' => array(
				'active' => 1,
				'role' => 'member'
			)
		),
		'administrator' => array(
			'adapter' => 'Form',
			'model' => 'User',
			'fields' => array(
				'email', 'password'
			),
			'validators' => array(
				'password' => function ($form, $data) {
					return $form == $data;
				}
			),
			'scope' => array(
				'active' => 1,
				'role' => 'admin'
			)
		)
	));

Here there are two configuration keys, `default` that authenticates users with role == 'member' and `administrator` for users with
role == 'admin'.

Since this is role-based access control and authentication is also handled within, therefore, you should set up your rules in
SimpleRbac configurations like this:

	use li3_simple_rbac\security\SimpleRbac;

	SimpleRbac::config(array(
		// access is allowed for all prefixes and libraries for all
		// controllers and actions for the `administrator`
		'administrator' => array(
			'rules' => array(
				'*::*'
			)
		),
		'default' => array(
			'rules' => array(
				array(
					'prefix' => 'admin',
					'allow' => false
				),
				array(
					'controller' => 'users',
					'action' => 'login',
					'prefix' => 'admin',
					'allow' => false,
					'message' => 'Please logout first and then log back in as an administrator'
				),
				array(
					'controller' => 'tests',
					'action' => 'run',
					'library' => 'test_suite', // you can also allow "Library"
					'allow' => false,
					'message' => 'Please logout first and then log back in as an administrator'
				),
				'!Users::check_admin'
			),
			'settings' => array(
				'message' => 'Bad Request',
				'redirect' => '/'
			)
		),
		'visitor' => array(	// special configuration - applies to any non-authenticated users
			'rules' => array(
				'!*::*',
				'Users::login',
				array(
					'prefix' => 'admin',
					'message' => 'Please login to access this area.',
					'redirect' => '/admin/users/login',
					'allow' => false
				),
				array(
					'controller' => 'users',
					'action' => 'login',
					'prefix' => 'admin'
				),
				array(
					'controller' => 'pages',
					'action' => '*'
				),
				array(
					'library' => 'li3_simple_rbac',
					'controller' => 'tests',
					'action' => '*',
					'allow' => false
				),
			),
			'settings' => array(
				'message' => 'Please login first',
				'redirect' => '/users/login',
				'visitorsKey' => 'visitor'
			)
		)
	));

If you observe how the rules are written then the rule arrays I hope are simple to understand. All you need to do is specify
the `controller` and `action` and optionally, the `prefix` and the `library`.

These rules are calculated from top-to-bottom. So if access is allowed for a rule preceding a rule for which access is denied,
then you will get access denied result. This is similar to `li3_access`.

You will also notice that there are simple `string-based` rules also present. These are identical to how Lithium's `\net\http\Router`
class parses string based urls.

If you need to deny string based rule, you should put `!` before it and it will tell the plugin to deny access to the corresponding
controller::action pair. Otherwise controller / action pair is allowed.

When writing string based rules you won't be able to specify the `prefix` and / or the `library`. However, there is a special rule
that acts as deny all and allow all.

	Deny all: '!*::*' - denies access to all controller / action pairs for all prefixes and libraries.
	Allow all: '*::*' - allows access to all controller / action pairs for all prefixes and libraries.

In the array based rules, let's say you need to allow access to all actions or a controller, then this rule would suffice:

	array(
		'controller' => 'users',
		'action' => '*'
	)

This rule would allow access to all actions of the users controller. However, if there is a prefix or a library involved, this rule
won't work. To have it allow access to all actions of the `users` controller of all prefixes and / or libraries, you can write something
like this:

	array(
		'controller' => 'users',
		'action' => '*',
		'prefix' => '*',
		'library' => '*'
	)

So '*' denotes "all".

If you want to deny all actions of all prefixes and libraries of the `users` controller, simply:

	array(
		'controller' => 'users',
		'action' => '*',
		'prefix' => '*',
		'library' => '*',
		'allow' => false
	)

And viola ! - No access granted !

One more important thing to note here is that you must write all rules within the `rules` key of the configuration. The reason
for this is that it was an easy way for me to make it compatible with Lithium's `Adapter` class. Otherwise, running Adapter::config()
returned the first rule in the array.

Now for the special `visitor` key :) ... It was a constant nag for me handle non-authenticated users' access. This key solves this problem.
You can specify the rules for "visitors" of your site in this configuration. And if for some odd reason you don't want to use the
`visitor` key, simply change it's name in the `settings` configuration key `visitorKey`. The best way to do that would be to simply:

	SimpleRbac::settings(array(
		'visitorKey' => 'public'
	));

Before or after SimpleRbac::config().

## Credits

### Sohaib Muneer

The original author of this library.

Github: [SayB](https://github.com/SayB/li3_simple_rbac)

Website: [Sohaib Muneer](http://www.sohaibmuneer.com)

## Tom Maiaroto

The author of `li3_access` that served as the inspiration behind this `li3_simple_rbac` plugin.

Github: [tmaiaroto](https://github.com/tmaiaroto/li3_access)

Website: [Shift8 Creative](http://www.shift8creative.com)

## Note

I will enhance the documentation for this library soon. Hopefully observing how I have the rules set up will simple enough
to understand.