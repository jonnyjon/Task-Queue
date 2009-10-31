<?php defined('SYSPATH') or die('No direct script access.');

class task_queue {
	
	public static function get_db() {
		return Database::instance('default');
	}
	
    public static function add_task($route='default', $controller, $action, $parameters = null, $priority = 5) {
        if ($priority < 1 OR $priority > 10) $priority = 5;
        //Kohana::$log->add('error', 'Queue. Could not spawn child task process.');
		ORM::factory('task')->values(array(
			'route' => $route,
			'uri'   => array(
				'controller' => $controller,
				'action'     => $action,
				'id'         => $parameters
			),
			'priority' => $priority,
			'created' => time()
		))->save();
    }

	public static function remove_task($id) {
		task_queue::close_db_connections();
		//
		ORM::factory('task', $id)->delete();
		//
		task_queue::close_db_connections();
	}

	// force closes any and all connections with the database
	public static function close_db_connections() {
		foreach(Database::$instances as $db) {
			$db->disconnect();
			unset($db);
		}
		Database::$instances = array();
	}

    public static function get_next_task() {
		$result = null;
		//
        task_queue::close_db_connections();
		//
		$db = task_queue::get_db();
		//
		$db->query(Database::INSERT, 'SET AUTOCOMMIT=0;', FALSE);
		$db->query(Database::INSERT, 'START TRANSACTION;', FALSE);
		//$result = $db->query(Database::SELECT, 'SELECT * from tasks WHERE e=0 ORDER BY priority DESC LIMIT 1', true);
		$result = ORM::factory('task')->where('e', '=', 0)->order_by('priority','desc')->order_by('created','asc')->limit(1)->find();
		$db->query(Database::INSERT, 'COMMIT;', FALSE);
		$db->query(Database::INSERT, 'SET AUTOCOMMIT=1', FALSE);
		//
		//if($row = $result->current()) {
		if($result->loaded()) {
            task_queue::close_db_connections();
			unset($db);
			//
			$db = task_queue::get_db();
			//
			$db->query(Database::INSERT, 'SET AUTOCOMMIT=0;', FALSE);
			$db->query(Database::INSERT, 'START TRANSACTION;', FALSE);
			$db->query(Database::UPDATE, 'UPDATE tasks SET e = 1 WHERE id= ' . $result->id . ' LIMIT 1', false);
			$db->query(Database::INSERT, 'COMMIT;', FALSE);
			$db->query(Database::INSERT, 'SET AUTOCOMMIT=1', FALSE);
        } else {
			$result = null;	
		}
		// force close the db conns
		task_queue::close_db_connections();
        //
        return $result;
    }
	
	public static function clean_failed_tasks($mins=null, $delete=false) {
		//
		if($mins) $diff = time()-($mins*60);
		//
		task_queue::close_db_connections();
		//
		$db = task_queue::get_db();
		//
		$db->query(Database::INSERT, 'SET AUTOCOMMIT=0;', FALSE);
		$db->query(Database::INSERT, 'START TRANSACTION;', FALSE);
		if($delete) {
			$db->query(Database::DELETE, 'DELETE from tasks WHERE e = 0 ' . ($mins ? 'AND created < ' . $diff : ''), false);
		} else {
			$db->query(Database::UPDATE, 'UPDATE tasks SET e = 0 ' . ($mins ? 'AND created < ' . $diff : ''), false);
		}
		$db->query(Database::INSERT, 'COMMIT;', FALSE);
		$db->query(Database::INSERT, 'SET AUTOCOMMIT=1', FALSE);
		//
		task_queue::close_db_connections();
	}

/*
    public static function isEmpty() {
        Database::$instances = array();
        $db = new Database();
        $db->query("SET AUTOCOMMIT=0;");
        $db->query("START TRANSACTION;");
        $count = $db->count_records("tasks") == 0;
        $db->query("COMMIT;");
        $db->query("SET AUTOCOMMIT=1");
        Database::$instances = array();
        return $count;
    }
	*/
	// php /var/www/html/socialmention.com/index.php --uri=daemon > /dev/null &
	// http://forum.kohanaphp.com/comments.php?DiscussionID=3737&page=1#Item_25
	public static function daemon_start($no_dup=false, $debug=false) {
		// don't start a new daemon if there is a duplicate deamon already
		if($no_dup) {
			$pids = task_queue::get_pid('--uri=daemon');
			if(sizeof($pids) > 0) return;
		}
		// echo 'php '.DOCROOT.'index.php --uri=daemon ' . ($debug ? '' : '> /dev/null &');
		$output = shell_exec('php '.DOCROOT.'index.php --uri=daemon ' . ($debug ? '' : '> /dev/null &'));
		if($debug) Kohana::$log->add('add', 'Daemon Start: ' . $output);
		//exit;
	}

	public static function daemon_restart() {
		// stops all daemons
		task_queue::daemon_stop();
		// reset any tasks
		task_queue::clean_failed_tasks(null, false);
		//
		task_queue::daemon_start(true, false);
	}

	public static function daemon_stop() {
		$pids = task_queue::get_pid('--uri=daemon');
		foreach($pids as $pid) {
			echo 'kill ' . $pid .'<br/>';
			shell_exec('kill ' . $pid);
		}
	}
	
	// ps -aef | grep daemon
	public static function get_pid($process_name) {
		$output = array();
		$str = shell_exec('ps -aef | grep daemon');
		$array = preg_split('/[\n]+/', strtolower($str));
		foreach($array as $line) {
			$pos = strpos($line, $process_name);
			if (!$pos === false) {
				$array2 = preg_split('/[ ]+/', $line);
				if(isset($array2[1])) {
					$output[] = $array2[1];
				}
			}
		}
		return $output;
	}
}