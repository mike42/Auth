#!/usr/bin/php
<?
/* This is an extremely crude model generator.
 * 	Works with phpmyadmin exports of this specific database.
 * 	Will probably not work on other datbases, other exports of
 *	the same database, etc. */

$sql = file_get_contents(dirname(__FILE__)."/auth.sql");
$lines = explode("\n", $sql);
$table = array();

$create = "CREATE TABLE";
$alter = "ALTER TABLE";
$primary = "PRIMARY KEY";
$key = "KEY";
$unique = "UNIQUE KEY";
$constraint = "ADD CONSTRAINT";

$action = "";
$inTable = "";

/* Parse SQL file */
foreach($lines as $line) {
	$line = trim($line);
	if($line == "") {
		$action = "";
	} elseif($action == $create) {
		/* Look for fields and keys */
		$part = explode("`", $line);
		$key = "";
		if(isset($part[1])) {
			$field = $part[1];
			$fields = array();
			if(substr($line,0,1) == "`") {
				/* Just pull list of fields */
				$dtype = trim($part[2]);
				if(!(strpos($dtype, ' ') === false)) {
					$dtype = substr($dtype, 0,strpos($dtype, ' '));
				}
				/* Full MySQL data type */
				$table[$inTable]['field'][$field]['type'] = $dtype;
				/* Length, or 0 if no length: */
				$table[$inTable]['field'][$field]['insert'] = strpos($line, "AUTO_INCREMENT") === false  && (strpos($field, "_created") === false);
				/* Also set to false for PK fields later*/
				$table[$inTable]['field'][$field]['update'] = (strpos($line, "ON UPDATE") === false) && (strpos($field, "_created") === false);
				$table[$inTable]['field'][$field]['null'] = (strpos($line, "NOT NULL") === false);
				
			} elseif(substr($line,0,strlen($primary)) == $primary) {
				for($i = 1; $i < count($part); $i += 2) {
					$fields[] = $part[$i];
				}
				$table[$inTable]['index']['primary'] = $fields;
				foreach($fields as $field) {
					/* Don't try to update a primary key */
					$table[$inTable]['field'][$field]['update'] = false;
				}
			} elseif(substr($line,0,strlen($unique)) == $unique || substr($line,0,strlen($key)) == $key) {
				$key = "unique";
				for($i = 3; $i < count($part); $i += 2) {
					$fields[] = $part[$i];
				}
				$table[$inTable]['index']['index'][$part[1]] = $fields;
				if(substr($line,0,strlen($unique)) == $unique) {
					$table[$inTable]['index']['unique'][$part[1]] = true;
				}
			}
		}
	} elseif($action == $alter && substr($line,0,strlen($constraint)) == $constraint) {
		$part = explode("(", $line);
		$sub_part = explode("`",$part[1]);
		$field = $sub_part[1];
		$dest_table = $sub_part[3];
		$sub_part = explode("`",$part[2]);
		$dest_key = $sub_part[1];
		
		$table[$inTable]['references'][$dest_table]['local'] = $field;
		$table[$inTable]['references'][$dest_table]['remote'] = $dest_key;
		
		$table[$dest_table]['referenced'][$inTable]['local'] = $dest_key;
		$table[$dest_table]['referenced'][$inTable]['remote'] = $field;
	} else {
		/* Look for an action to start */
		if(substr($line,0,strlen($create)) == $create) {
			$action = $create;
			$part = explode("`", $line);
			$inTable = $part[1];
		} elseif(substr($line,0,strlen($alter)) == $alter) {
			$action = $alter;
			$part = explode("`", $line);
			$inTable = $part[1];
		}
	}
}

/* Output models */
$outp_folder = dirname(__FILE__)."/../../lib/model/";
@mkdir($outp_folder);
foreach($table as $name => $current) {
	unset($isrelated);
	$str = "<?php\n";
	$str .= "class $name"."_model {\n";
	$max = 0;
	
	/* Fields */
	$str .= "\t/* Fields */\n";
	foreach($current['field'] as $field => $exists) {
		if(strlen($field) > $max) {
			$max = strlen($field);
		}
		$str .= "\tpublic $".$field.";\n";
	}
	
	/* Tables which we hold keys for */
	if(isset($current['references'])) {
		$str .= "\n\t/* Referenced tables */\n";
		foreach($current['references'] as $other_table => $fields) {
			$str .=  "\tpublic \$$other_table;\n";
			$isrelated[$other_table] = true;
		}
	}
	
	/* Tables which point here */
	if(isset($current['referenced'])) {
		$str .= "\n\t/* Tables which reference this */\n";
		foreach($current['referenced'] as $other_table => $fields) {
			$str .=  "\tpublic \$list_".str_pad($other_table, 20, " ")." = array();\n";
			$isrelated[$other_table] = true;
		}
	}
	
	/* Init for dependencies if needed*/
	if(isset($isrelated)) {
		$str .= "\n".blockComment(1, array("Load all related models."));
		$str .= "\tpublic static function init() {\n";
		$str .= "\t\tsjcAuth::loadClass(\"Database\");\n";
		foreach($isrelated as $related_table => $true) {
			$str .= "\t\tsjcAuth::loadClass(\"" . $related_table . "_model\");\n";
		}
		$str .= "\t}\n";
	}
		
	/* Constructor */
	$comment = array("Create new $name based on a row from the database.");
	$comment[] = "@param array \$row The database row to use.";
	$str .= "\n".blockComment(1, $comment);
	$str .= "\tpublic function $name"."_model(array \$row = array()) {\n";
	foreach($current['field'] as $field => $exists) {
		$str .= "\t\t\$this -> " . str_pad($field, $max, " ") . " = isset(\$row['$field'])".str_pad('', ($max - strlen($field)), ' ')." ? \$row['$field']".str_pad('', ($max - strlen($field)), ' ').": '';\n";
	}

	if(isset($current['references'])) {
		$str .= "\n\t\t/* Fields from related tables */\n";
		foreach($current['references'] as $references => $fields) {
			$str .= "\t\t\$this -> " . $references . " = new $references"."_model(\$row);\n";
		}
	}
	$str .= "\t}\n";
		
	if(!isset($current['index']['primary'])) {
		/* PK check */
		die("\nERROR: No primary key defined on $name\n");
	}
	
	/* Get by primary key and other unique keys */
	$str .= get($table, "get", $name, $current, $current['index']['primary']);
	if(isset($current['index']['unique'])) {
		foreach($current['index']['unique'] as $fname => $index) {
			$str .= get($table, "get_by_$fname", $name, $current, $current['index']['index'][$fname]);
		}
	}

	/* List by non-unique keys */
	if(isset($current['index']['index'])) {
		foreach($current['index']['index'] as $fname => $index) {
			if(!isset($current['index']['unique'][$fname])) {
				$str .= listBy($table, "list_by_$fname", $name, $current, $index);
			}
		}
	}
		
	/* Find related records */
	if(isset($current['referenced'])) {
		foreach($current['referenced'] as $referenced => $fields) {
			$str .= "\n\tpublic function populate_list_$referenced() {\n";
			$str .= "\t\t\$this -> list_$referenced = $referenced"."_model::list_by_".$fields['remote']."(\$this -> ".$fields['local'].");\n";
			$str .= "\t}\n";
		}
	}
	
	/* Insert and update functions */
	$insertFields_name = $updateFields_name = $deleteFields_name = array();
	foreach($current['field'] as $field_name => $field) {
		if($field['insert']) {
			$insertFields_name[] = $field_name;
		}
		if($field['update']) {
			$updateFields_name[] = $field_name;
		}
	}
	
	/* Construct INSERT query */
	$str .= "\n\tpublic function insert() {\n";
	$str .= "\t\t\$sql = \"INSERT INTO $name(" . implode(", ", $insertFields_name) . ") VALUES (";
	for($i = 0; $i < count($insertFields_name); $i++) {
		$str .= (($i == 0) ? "" : ", ") . "'%s'";
	}
	$str .= ");\";\n";
	$str .= "\t\treturn Database::insert(\$sql, array(".implode(", ", thisify($insertFields_name))."));\n";
	$str .= "\t}\n";

	/* Construct UPDATE query */
	$str .= "\n\tpublic function update() {\n";
	$updateFields_line = array();
	foreach($updateFields_name as $key => $field) {
		$updateFields_line[$key] = "$field ='%s'";
	}
	$str .= "\t\t\$sql = \"UPDATE $name SET ".implode(", ", $updateFields_line);
	$updateKey = array();
	foreach($current['index']['primary'] as $field) {
		$updateKey[] = $field . " ='%s'";
		$updateFields_name[] = $field;
	}
	$str .= " WHERE " . implode(" AND ", $updateKey) . ";\";\n";
	$str .= "\t\treturn Database::update(\$sql, array(".implode(", ", thisify($updateFields_name))."));\n";
	
	$str .= "\t}\n";

	/* Construct DELETE query */
	$str .= "\n\tpublic function delete() {\n";
	$deleteKey = array();
	foreach($current['index']['primary'] as $field) {
		$deleteKey[] = $field . " ='%s'";
		$deleteFields_name[] = $field;
	}
	$str .= "\t\t\$sql = \"DELETE FROM $name";
	$str .= " WHERE " . implode(" AND ", $deleteKey) . ";\";\n";
	$str .= "\t\treturn Database::delete(\$sql, array(".implode(", ", thisify($deleteFields_name))."));\n";
	$str .= "\t}\n";

	$filename = $outp_folder.$name."_model.php";
	$non_generated = "/* Non-generated functions */";
	$has_non_generated = false;
	if(file_exists($filename) && $current = file_get_contents($filename)) {
		/* Add back non-generated functions */
		$current = rtrim($current);
		$current = explode($non_generated, $current);
		if(count($current) == 2) {
			$str .= "\n\t".$non_generated."\n\t".trim($current[1])."\n";
			$has_non_generated = true;
		}
	}
	if(!$has_non_generated) {
		$str .= "}\n";
		$str .= "?>";
	}
	
	file_put_contents($filename, $str);
}

function get($table, $fname, $name, $current, $index) {
	$fieldlist = $fieldlist_sql = array();
	foreach($index as $field) {
		$fieldlist[] = "\$".$field;
		$fieldlist_sql[] = $name.".".$field . "='%s'";
	}
	$str = "\n\tpublic static function $fname(".implode(", ", $fieldlist).") {\n";
	$str .= "\t\t\$sql = \"".build_w_references($table, $name, $current)." WHERE " . implode(" AND ", $fieldlist_sql)."\";\n";	
	$str .= "\t\t\$res = Database::retrieve(\$sql, array(".implode(", ", $fieldlist)."));\n";
	$str .= "\t\tif(\$row = Database::get_row(\$res)) {\n";
	$str .= "\t\t\treturn new $name"."_model(\$row);\n";
	$str .= "\t\t}\n";
	$str .= "\t\treturn false;\n";
	$str .= "\t}\n";
	return $str;
}

function listBy($table, $fname, $name, $current, $index) {
	$fieldlist = $fieldlist_sql = array();
	foreach($index as $field) {
		$fieldlist[] = "\$".$field;
		$fieldlist_sql[] = $name.".".$field . "='%s'";
	}
	$str = "\n\tpublic static function $fname(".implode(", ", $fieldlist).") {\n";
	$str .= "\t\t\$sql = \"".build_w_references($table, $name, $current)." WHERE " . implode(" AND ", $fieldlist_sql).";\";\n";
	$str .= "\t\t\$res = Database::retrieve(\$sql, array(".implode(", ", $fieldlist)."));\n";
	$str .= "\t\t\$ret = array();\n";
	$str .= "\t\twhile(\$row = Database::get_row(\$res)) {\n";
	$str .= "\t\t\t\$ret[] = new $name"."_model(\$row);\n";
	$str .= "\t\t}\n";
	$str .= "\t\treturn \$ret;\n";
	$str .= "\t}\n";
	return $str;
}

function build_w_references($table, $name, $current) {
	$q = "SELECT * FROM ".$name;
	if(!isset($current['references'])) {
		return $q;
	}	

	/* Build up a queue of other tables to join */
	$jointo = array();
	$joined = array();
	foreach($current['references'] as $join => $field) {
		$jointo[] = array('from' => $name, 'join' => $join, 'field' => $field);
	}

	while(count($jointo) > 0) {
		$next = array_shift($jointo);
		$join = $next['join'];
		if(!isset($joined[$join]) && $join != $name) {
			$field = $next['field'];
			$from = $next['from'];
			$q .= " LEFT JOIN $join ON $from.".$field['local']. " = $join.".$field['remote'];
			$joined[$join] = true;

			/* And add other joining fields */
			if(isset($table[$join]['references'])) {
				foreach($table[$join]['references'] as $to => $field) {
					$jointo[] = array('from' => $join, 'join' => $to, 'field' => $field);
				}
			}
		}
	}

	return $q;
}

/**
 * Insert a block comment
 * @param int   $indent  Number of tabs to prefix lines with
 * @param array $lines   Lines to add to comment
 */
function blockComment($indent, $lines) {
	$tab = str_repeat("\t", $indent);
	$str = "";
	$str .= $tab ."/**\n";
	foreach($lines as $line) {
		$str .= $tab . " * " . $line . "\n";
	}
	return $str . $tab . "*/\n";
}

/**
 * Change an array of "foo, bar" to "$this -> foo, $this -> bar"
 */
function thisify(array $in) {
	foreach($in as $key => $val) {
		$in[$key] = "\$this -> $val";
	}
	return $in;
}
?>
