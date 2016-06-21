<?php
require_once('class.ilDBPdoFieldDefinition.php');

/**
 * Class ilDBPdoPostgresFieldDefinition
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilDBPdoPostgresFieldDefinition extends ilDBPdoFieldDefinition {

	/**
	 * @var array
	 */
	protected $reserved = array(
		"ALL",
		"ANALYSE",
		"ANALYZE",
		"AND",
		"ANY",
		"ARRAY",
		"AS",
		"ASC",
		"ASYMMETRIC",
		"AUTHORIZATION",
		"BETWEEN",
		"BINARY",
		"BOTH",
		"CASE",
		"CAST",
		"CHECK",
		"COLLATE",
		"COLUMN",
		"CONSTRAINT",
		"CREATE",
		"CROSS",
		"CURRENT_DATE",
		"CURRENT_ROLE",
		"CURRENT_TIME",
		"CURRENT_TIMESTAMP",
		"CURRENT_USER",
		"DEFAULT",
		"DEFERRABLE",
		"DESC",
		"DISTINCT",
		"DO",
		"ELSE",
		"END",
		"EXCEPT",
		"FALSE",
		"FOR",
		"FOREIGN",
		"FREEZE",
		"FROM",
		"FULL",
		"GRANT",
		"GROUP",
		"HAVING",
		"ILIKE",
		"IN",
		"INITIALLY",
		"INNER",
		"INTERSECT",
		"INTO",
		"IS",
		"ISNULL",
		"JOIN",
		"LEADING",
		"LEFT",
		"LIKE",
		"LIMIT",
		"LOCALTIME",
		"LOCALTIMESTAMP",
		"NATURAL",
		"NEW",
		"NOT",
		"NOTNULL",
		"NULL",
		"OFF",
		"OFFSET",
		"OLD",
		"ON",
		"ONLY",
		"OR",
		"ORDER",
		"OUTER",
		"OVERLAPS",
		"PLACING",
		"PRIMARY",
		"REFERENCES",
		"RETURNING",
		"RIGHT",
		"SELECT",
		"SESSION_USER",
		"SIMILAR",
		"SOME",
		"SYMMETRIC",
		"TABLE",
		"THEN",
		"TO",
		"TRAILING",
		"TRUE",
		"UNION",
		"UNIQUE",
		"USER",
		"USING",
		"VERBOSE",
		"WHEN",
		"WHERE",
		"WITH",
	);


	/**
	 * @param $field
	 * @return string
	 */
	public function getTypeDeclaration($field) {
		$db = $this->getDBInstance();

		switch ($field['type']) {
			case 'text':
				return 'TEXT';
				$length = !empty($field['length']) ? $field['length'] : $db->options['default_text_field_length'];
				$fixed = !empty($field['fixed']) ? $field['fixed'] : false;

				return $fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(' . $db->options['default_text_field_length']
				                                                     . ')') : ($length ? 'VARCHAR(' . $length . ')' : 'TEXT');
			case 'clob':
				return 'TEXT';
			case 'blob':
				return 'BYTEA';
			case 'integer':
				if (!empty($field['autoincrement'])) {
					if (!empty($field['length'])) {
						$length = $field['length'];
						if ($length > 4) {
							return 'BIGSERIAL PRIMARY KEY';
						}
					}

					return 'SERIAL PRIMARY KEY';
				}
				if (!empty($field['length'])) {
					$length = $field['length'];
					if ($length <= 2) {
						return 'SMALLINT';
					} elseif ($length == 3 || $length == 4) {
						return 'INT';
					} elseif ($length > 4) {
						return 'BIGINT';
					}
				}

				return 'INT';
			case 'boolean':
				return 'BOOLEAN';
			case 'date':
				return 'DATE';
			case 'time':
				return 'TIME without time zone';
			case 'timestamp':
				return 'TIMESTAMP without time zone';
			case 'float':
				return 'FLOAT8';
			case 'decimal':
				$length = !empty($field['length']) ? $field['length'] : 18;
				$scale = !empty($field['scale']) ? $field['scale'] : $db->options['decimal_places'];

				return 'NUMERIC(' . $length . ',' . $scale . ')';
		}
	}


	/**
	 * @param $name
	 * @param $field
	 * @return string
	 * @throws \ilDatabaseException
	 */
	protected function getIntegerDeclaration($name, $field) {
		$db = $this->getDBInstance();

		if (!empty($field['unsigned'])) {
			$db->warnings[] = "unsigned integer field \"$name\" is being declared as signed integer";
		}
		if (!empty($field['autoincrement'])) {
			$name = $db->quoteIdentifier($name, true);

			return $name . ' ' . $this->getTypeDeclaration($field);
		}
		$default = '';
		if (array_key_exists('default', $field)) {
			if ($field['default'] === '') {
				$field['default'] = empty($field['notnull']) ? null : 0;
			}
			$default = ' DEFAULT ' . $this->quote($field['default'], 'integer');
		} elseif (empty($field['notnull'])) {
			$default = ' DEFAULT NULL';
		}

		$notnull = empty($field['notnull']) ? '' : ' NOT NULL';
		$name = $db->quoteIdentifier($name, true);

		return $name . ' ' . $this->getTypeDeclaration($field) . $default . $notnull;
	}


	/**
	 * @param $field
	 * @return array
	 * @throws \ilDatabaseException
	 */
	protected function mapNativeDatatypeInternal($field) {
		$db_type = strtolower($field['type']);
		$length = $field['length'];
		$type = array();
		$unsigned = $fixed = null;
		switch ($db_type) {
			case 'smallint':
			case 'int2':
				$type[] = 'integer';
				$unsigned = false;
				$length = 2;
				if ($length == '2') {
					$type[] = 'boolean';
					if (preg_match('/^(is|has)/', $field['name'])) {
						$type = array_reverse($type);
					}
				}
				break;
			case 'int':
			case 'int4':
			case 'integer':
			case 'serial':
			case 'serial4':
				$type[] = 'integer';
				$unsigned = false;
				$length = 4;
				break;
			case 'bigint':
			case 'int8':
			case 'bigserial':
			case 'serial8':
				$type[] = 'integer';
				$unsigned = false;
				$length = 8;
				break;
			case 'bool':
			case 'boolean':
				$type[] = 'boolean';
				$length = null;
				break;
			case 'text':
			case 'varchar':
				$fixed = false;
			case 'unknown':
			case 'char':
			case 'bpchar':
				$type[] = 'text';
				if ($length == '1') {
					$type[] = 'boolean';
					if (preg_match('/^(is|has)/', $field['name'])) {
						$type = array_reverse($type);
					}
				} elseif (strstr($db_type, 'text')) {
					$type[] = 'clob';
				}
				if ($fixed !== false) {
					$fixed = true;
				}
				break;
			case 'date':
				$type[] = 'date';
				$length = null;
				break;
			case 'datetime':
			case 'timestamp':
				$type[] = 'timestamp';
				$length = null;
				break;
			case 'time':
				$type[] = 'time';
				$length = null;
				break;
			case 'float':
			case 'float8':
			case 'double':
			case 'real':
				$type[] = 'float';
				break;
			case 'decimal':
			case 'money':
			case 'numeric':
				$type[] = 'decimal';
				if ($field['scale']) {
					$length = $length . ',' . $field['scale'];
				}
				break;
			case 'tinyblob':
			case 'mediumblob':
			case 'longblob':
			case 'blob':
			case 'bytea':
				$type[] = 'blob';
				$length = null;
				break;
			case 'oid':
				$type[] = 'blob';
				$type[] = 'clob';
				$length = null;
				break;
			case 'year':
				$type[] = 'integer';
				$type[] = 'date';
				$length = null;
				break;
			default:
				throw new ilDatabaseException('unknown database attribute type: ' . $db_type);
		}

		if ((int)$length <= 0) {
			$length = null;
		}

		return array( $type, $length, $unsigned, $fixed );
	}
}