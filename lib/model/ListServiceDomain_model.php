<?php
namespace Auth\model;

use Auth\Auth;
use Auth\misc\Database;
use Auth\model\ListDomain_model;
use Auth\model\ListServiceDomain_model;
use Auth\model\Service_model;

class ListServiceDomain_model {
	/* Fields */
	public $service_id;
	public $domain_id;
	public $sd_root;
	public $sd_secondary;

	/* Referenced tables */
	public $Service;
	public $ListDomain;

	/**
	 * Load all related models.
	*/
	public static function init() {
		Auth::loadClass("Database");
		Auth::loadClass("Service_model");
		Auth::loadClass("ListDomain_model");
	}

	/**
	 * Create new ListServiceDomain based on a row from the database.
	 * @param array $row The database row to use.
	*/
	public function __construct(array $row = array()) {
		$this -> service_id   = isset($row['service_id'])   ? $row['service_id']  : '';
		$this -> domain_id    = isset($row['domain_id'])    ? $row['domain_id']   : '';
		$this -> sd_root      = isset($row['sd_root'])      ? $row['sd_root']     : '';
		$this -> sd_secondary = isset($row['sd_secondary']) ? $row['sd_secondary']: '';

		/* Fields from related tables */
		$this -> Service = new Service_model($row);
		$this -> ListDomain = new ListDomain_model($row);
	}

	public static function get($service_id, $domain_id) {
		$sql = "SELECT * FROM ListServiceDomain LEFT JOIN Service ON ListServiceDomain.service_id = Service.service_id LEFT JOIN ListDomain ON ListServiceDomain.domain_id = ListDomain.domain_id LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type WHERE ListServiceDomain.service_id='%s' AND ListServiceDomain.domain_id='%s'";
		$res = Database::retrieve($sql, array($service_id, $domain_id));
		if($row = Database::get_row($res)) {
			return new ListServiceDomain_model($row);
		}
		return false;
	}

	public static function list_by_domain_id($domain_id) {
		$sql = "SELECT * FROM ListServiceDomain LEFT JOIN Service ON ListServiceDomain.service_id = Service.service_id LEFT JOIN ListDomain ON ListServiceDomain.domain_id = ListDomain.domain_id LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type WHERE ListServiceDomain.domain_id='%s';";
		$res = Database::retrieve($sql, array($domain_id));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new ListServiceDomain_model($row);
		}
		return $ret;
	}

	public static function list_by_service_id($service_id) {
		$sql = "SELECT * FROM ListServiceDomain LEFT JOIN Service ON ListServiceDomain.service_id = Service.service_id LEFT JOIN ListDomain ON ListServiceDomain.domain_id = ListDomain.domain_id LEFT JOIN ListServiceType ON Service.service_type = ListServiceType.service_type WHERE ListServiceDomain.service_id='%s';";
		$res = Database::retrieve($sql, array($service_id));
		$ret = array();
		while($row = Database::get_row($res)) {
			$ret[] = new ListServiceDomain_model($row);
		}
		return $ret;
	}

	public function insert() {
		$sql = "INSERT INTO ListServiceDomain(service_id, domain_id, sd_root, sd_secondary) VALUES ('%s', '%s', '%s', '%s');";
		return Database::insert($sql, array($this -> service_id, $this -> domain_id, $this -> sd_root, $this -> sd_secondary));
	}

	public function update() {
		$sql = "UPDATE ListServiceDomain SET sd_root ='%s', sd_secondary ='%s' WHERE service_id ='%s' AND domain_id ='%s';";
		return Database::update($sql, array($this -> sd_root, $this -> sd_secondary, $this -> service_id, $this -> domain_id));
	}

	public function delete() {
		$sql = "DELETE FROM ListServiceDomain WHERE service_id ='%s' AND domain_id ='%s';";
		return Database::delete($sql, array($this -> service_id, $this -> domain_id));
	}
}
?>