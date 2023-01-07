<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2023
 * @package Base
 * @subpackage View
 */


namespace Aimeos\Base\View\Helper;


/**
 * Common abstract class for all view helper classes.
 *
 * @method string|array config(string $name = null, string|array $default = null) Returns the config value for the given key
 * @method \Aimeos\Base\View\Helper\Iface csrf() Returns the CSRF helper object
 * @method string date(string $date) Returns the formatted date
 * @method \Aimeos\Base\View\Helper\Iface encoder() Returns the encoder helper object
 * @method string formparam(string|array $names) Returns the name for the HTML form parameter
 * @method \Aimeos\Base\Mail\Message\Iface mail() Returns the e-mail message object
 * @method string number(integer|float|decimal $number, integer $decimals = 2) Returns the formatted number
 * @method string|array param(string|null $name, string|array $default) Returns the parameter value
 * @method string partial(string $filepath, array $params = [] ) Renders the partial template
 * @method \Aimeos\Base\View\Helper\Iface request() Returns the request helper object
 * @method string translate(string $domain, string $singular, string $plural = '', integer $number = 1) Returns the translated string or the original one if no translation is available
 * @method string url(string|null $target, string|null $controller = null, string|null $action = null, array $params = [], array $trailing = [], array $config = []) Returns the URL assembled from the given arguments
 *
 * @package Base
 * @subpackage View
 */
abstract class Base
{
	private $view;


	/**
	 * Initializes the view helper classes.
	 *
	 * @param \Aimeos\Base\View\Iface $view View instance with registered view helpers
	 */
	public function __construct( \Aimeos\Base\View\Iface $view )
	{
		$this->view = $view;
	}


	/**
	 * Calls the view helper with the given name and arguments and returns it's output.
	 *
	 * @param string $name Name of the view helper
	 * @param array $args Arguments passed to the view helper
	 * @return mixed Output depending on the view helper
	 */
	public function __call( string $name, array $args )
	{
		return call_user_func_array( array( $this->view, $name ), $args );
	}


	/**
	 * Sets a new view object for changing views afterwards
	 *
	 * @param \Aimeos\Base\View\Iface $view View object
	 * @return \Aimeos\Base\View\Helper\Iface Helper object for method chaining
	 */
	public function setView( \Aimeos\Base\View\Iface $view ) : Iface
	{
		$this->view = $view;
		return $this;
	}


	/**
	 * Returns the view object.
	 *
	 * @return \Aimeos\Base\View\Iface View object
	 */
	protected function view() : \Aimeos\Base\View\Iface
	{
		return $this->view;
	}
}
