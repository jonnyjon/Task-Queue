<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Template_Application extends Controller_Template {
	// Set the name of the template to use
	public $template = 'layout';
	public $auto_render = TRUE; //defaults to true, renders the template after the controller method is done

    public function before()
    {
        parent::before(); // This must be included
 		
		// start session if not started
		if(!isset($_SESSION)) session_start();
		
		// cache
		$this->cache = Cache::instance();
		
		// include helpers
		include_once APPPATH.'/classes/helpers/email.php';
		include_once APPPATH.'/classes/helpers/flash.php';
		include_once APPPATH.'/classes/helpers/cli.php';
		include_once APPPATH.'/classes/library/task_queue.php';
		
		// set default layout props
		$this->template->title = 'Social Mention';
		$this->template->content = '';			
        //$this->db = Database::instance();
        //$this->session = Session::instance();

		// load user auth class
		include_once APPPATH.'/classes/library/authorize.php';
		$this->auth = new Authorize();
		// user details
		$this->user_id = $this->auth->get_user();
		$this->_user_id = $this->auth->get_user();
		$this->_user = false;
		if($this->user_id) {
			$this->_user = $this->auth->get_user_details();
		}
		
		//
		if($this->user_id) $this->set_projects();
    }

	// populates the project list and set's the user's current project
	public function get_languages($use_cache=true) {
		// cache
		$key = 'search_phrase_languages';
		$data = $this->cache->get($key);

		if (!$data || $use_cache == false) {
			$table = Model::factory('user');
			$tmp = $table->select_languages();
			$data = array();
			$data[''] = 'All';
			// if there are projects
			if($tmp->current()) {
				// build the select drop down
				foreach($tmp as $tmp2) {
					$data[$tmp2->code] = $tmp2->language;
				}
			}
			// set cache for 24 hours
			$this->cache->set($key, $data, NULL, 86400);
		}
		return $data;
	}

	public function clear_projects_cache() {
		//
		$key = $this->_user_id.'::project_list';
		$this->cache->delete($key);
	}

	// populates the project list and set's the user's current project
	public function set_projects() {
		// cache
		$key = $this->_user_id.'::project_list';
		$data = $this->cache->get($key);

		if (!$data) {
			$project_table = Model::factory('project');
			$tmp = $project_table->select_all_user_projects($this->_user_id);
			$data['_projects'] = array();
			// if there are projects
			if($tmp->current()) {
				// build the select drop down
				foreach($tmp as $tmp2) {
					$data['_projects'][$tmp2->id] = $tmp2->name;
				}
			}
			// set cache for 24 hours
			$this->cache->set($key, $data, NULL, 86400);
		}
		// set the currently selected project_id if it is not set
		if(!isset($_SESSION['project_id_current']) || !isset($data['_projects'][$_SESSION['project_id_current']])) {
			// set it to the first project id in the list
			foreach($data['_projects'] as $k=>$v) {
				$_SESSION['project_id_current']	= $k;
				break;
			}
		}
		// set current project id
		$data['_project_id_selected'] = $_SESSION['project_id_current'];
		$this->_project_id_selected = $data['_project_id_selected'];
		// bind the data the template
		$this->template->bind('data', $data);	
	}
	
	public function set_project_current($project_id) {
		$_SESSION['project_id_current'] = $project_id;
	}

	public function must_be_logged_in() {
		if(!$this->user_id) {
			$this->auto_render = FALSE;
			header("Location: /");
			exit;	
		}	
	}

	public function after()
	{	
		// binds this to the outer template (layout_app)
		$this->template->bind('_user', $this->_user);
		// binds to the inner template
		$this->template->content->bind('_flash', $flash);
		$flash = Flash::get();
		//
		
		//
		parent::after();
	}
}
?>