<?php
class ActionQueue_model {
	/* Fields */
	public $aq_id;
	public $aq_attempts;
	public $aq_date;
	public $service_id;
	public $domain_id;
	public $action_type;
	public $aq_target;
	public $aq_arg1;
	public $aq_arg2;
	public $aq_arg3;
	public $aq_complete;

	/* Referenced tables */
	public $Service;
	public $ListDomain;
	public $ListActionType;

	/**
	 * Load all related models.
	*/
	public static function init() {
		Auth::loadClass("Database");
		Auth::loadClass("Service_model");
		Auth::loadClass("ListDomain_model");
		Auth::loadClass("ListActionType_model");
	}

	/**
	 * Create new ActionQueue based on a row from the database.
	 * @param array $row The database row to use.
	*/
	public function ActionQueue_model(array $row = array()) {
		$this -> aq_id       = isset($row['aq_id'])       ? $row['aq_id']      : '';
		$this -> aq_attempts = isset($row['aq_attempts']) ? $row['aq_attempts']: '';
		$this -> aq_date     = isset($row['aq_date'])     ? $row['aq_date']    : '';
		$this -> service_id  = isset($row['service_id'])  ? $row['service_id'] : '';
		$this -> domain_id   = isset($row['domain_id'])   ? $row['domain_id']  : '';
		$this -> action_type = isset($row['action_type']) ? $row['action_type']: '';
		$this -> aq_target   = isset($row['aq_target'])   ? $row['aq_target']  : '';
		$this -> aq_arg1     = isset($row['aq_arg1'])     ? $row['aq_arg1']    : '';
		$this -> aq_arg2     = isset($row['aq_arg2'])     ? $row['aq_arg2']    : '';
		$this -> aq_arg3     = isset($row['aq_arg3'])     ? $row['aq_arg3']    : '';
		$this -> aq_complete = isset($row['aq_complete']) ? $row['aq_complete']: '';

		/* Fields from related tables */
		$this -> Service = new Service_model($row);
		$this -> ListDomain = new ListDomain_model($row);
		$this -> ListActionType = new ListActionType_model($row);
	}

	public static function get($aq_id) {
		$sql = "SELECT * FROM ActionQueue LEFT JOIN Service ON ActionQueue.service_id = Service.service_id LEFT JOIN ListDomain ON ActionQueue.domain_id = ListDomain.domain_id LEFT JOIN ListActionType ON ActionQueue.action_type = ListActionType.action_type LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type WHERE ActionQueue.aq_id='%s'";
		$res = Database::retrieve($sql, array($aq_id));
		if($row = Database::get_row($res)) {
			return new ActionQueue_model($row);
		}
		return false;
	}

	public static function list_by_service_id($service_id) {
		$sql = "SELECT * FROM ActionQueue LEFT JOIN Service ON ActionQueue.service_id = Service.service_id LEFT JOIN ListDomain ON ActionQueue.domain_id = ListDomain.domain_id LEFT JOIN ListActionType ON ActionQueue.action_type = ListActionType.action_type LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type WHERE ActionQueue.service_id='%s';";
		$res = Database::retrieve($sql, array($service_id));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new ActionQueue_model($row);
		}
		return $ret;
	}

	public static function list_by_domain_id($domain_id) {
		$sql = "SELECT * FROM ActionQueue LEFT JOIN Service ON ActionQueue.service_id = Service.service_id LEFT JOIN ListDomain ON ActionQueue.domain_id = ListDomain.domain_id LEFT JOIN ListActionType ON ActionQueue.action_type = ListActionType.action_type LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type WHERE ActionQueue.domain_id='%s';";
		$res = Database::retrieve($sql, array($domain_id));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new ActionQueue_model($row);
		}
		return $ret;
	}

	public static function list_by_action_type($action_type) {
		$sql = "SELECT * FROM ActionQueue LEFT JOIN Service ON ActionQueue.service_id = Service.service_id LEFT JOIN ListDomain ON ActionQueue.domain_id = ListDomain.domain_id LEFT JOIN ListActionType ON ActionQueue.action_type = ListActionType.action_type LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type WHERE ActionQueue.action_type='%s';";
		$res = Database::retrieve($sql, array($action_type));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new ActionQueue_model($row);
		}
		return $ret;
	}

	public function insert() {
		$sql = "INSERT INTO ActionQueue(aq_attempts, aq_date, service_id, domain_id, action_type, aq_target, aq_arg1, aq_arg2, aq_arg3, aq_complete) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');";
		return Database::insert($sql, array($this -> aq_attempts, $this -> aq_date, $this -> service_id, $this -> domain_id, $this -> action_type, $this -> aq_target, $this -> aq_arg1, $this -> aq_arg2, $this -> aq_arg3, $this -> aq_complete));
	}

	public function update() {
		$sql = "UPDATE ActionQueue SET aq_attempts ='%s', aq_date ='%s', service_id ='%s', domain_id ='%s', action_type ='%s', aq_target ='%s', aq_arg1 ='%s', aq_arg2 ='%s', aq_arg3 ='%s', aq_complete ='%s' WHERE aq_id ='%s';";
		return Database::update($sql, array($this -> aq_attempts, $this -> aq_date, $this -> service_id, $this -> domain_id, $this -> action_type, $this -> aq_target, $this -> aq_arg1, $this -> aq_arg2, $this -> aq_arg3, $this -> aq_complete, $this -> aq_id));
	}

	public function delete() {
		$sql = "DELETE FROM ActionQueue WHERE aq_id ='%s';";
		return Database::delete($sql, array($this -> aq_id));
	}

	/* Non-generated functions */
	public function get_overview() {
		$sql = "SELECT Service.*, ListDomain.*, ActionQueue.aq_id, ActionQueue.aq_id, ActionQueue.aq_attempts, ActionQueue.aq_date, ActionQueue.service_id, ActionQueue.domain_id, ActionQueue.action_type, ActionQueue.aq_target FROM ActionQueue LEFT JOIN Service ON ActionQueue.service_id = Service.service_id LEFT JOIN ListDomain ON ActionQueue.domain_id = ListDomain.domain_id LEFT JOIN ListActionType ON ActionQueue.action_type = ListActionType.action_type LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type WHERE ActionQueue.aq_complete='0' ORDER BY aq_date;";
		$res = Database::retrieve($sql, array());
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new ActionQueue_model($row);
		}
		return $ret;
	}
	
	public static function get_next() {
		$sql = "SELECT * FROM ActionQueue LEFT JOIN Service ON ActionQueue.service_id = Service.service_id LEFT JOIN ListDomain ON ActionQueue.domain_id = ListDomain.domain_id LEFT JOIN ListActionType ON ActionQueue.action_type = ListActionType.action_type LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type WHERE ActionQueue.aq_complete='0' AND ActionQueue.aq_date < CURRENT_TIMESTAMP ORDER BY ActionQueue.aq_date, ActionQueue.aq_id LIMIT 0, 1;";
		$res = Database::retrieve($sql, array());
		if($row = Database::get_row($res)) {
			return new ActionQueue_model($row);
		}
		return false;
	}
	
	public static function count() {
		$sql = "SELECT count(aq_id) as c FROM ActionQueue WHERE ActionQueue.aq_complete='0' AND ActionQueue.aq_date < CURRENT_TIMESTAMP;";
		$res = Database::retrieve($sql, array());
		if($row = Database::get_row($res)) {
			return (int)$row['c'];
		}
		return 0;
	}
}
?>
