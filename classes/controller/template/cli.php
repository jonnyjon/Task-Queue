<?php defined('SYSPATH') or die('No direct script access.');

/*
 * Extend this controller to make controllers accessible by CLI only
 */

class Controller_Template_CLI extends Controller {

	public function before()
	{
		parent::before();

		if ( ! Kohana::$is_cli)
		{
			exit;
			// Deny none CLI access
			/*throw new Kohana_Exception('The requested route does not exist: :route',
				array(':route' => $this->request->route->uri()));
			*/
		}
	}
}