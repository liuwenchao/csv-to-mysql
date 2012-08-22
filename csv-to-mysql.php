<?php

/**
 * CSV to MySQL
 *
 * The Short Version:
 * Converts a CSV file into an SQL file containing the CREATE TABLE syntax as
 * well as any INSERT statements to populate the generated table.
 *
 * The Long Version:
 * Loops through all the lines in a CSV file (assumes the first row contains
 * the field names) and attempts to figure out what sort of data we're working
 * with and how long the maximum length is (specifically for VARCHAR fields).
 *
 * @author    Josh Sherman <josh@crowdsavings.com>
 * @copyright Copyright 2012, CrowdSavings.com, LLC
 * @license   http://www.opensource.org/licenses/mit-license.html
 * @link      https://github.com/crowdsavings/csv-to-mysql
 * @usage     php csv-to-mysql.php /path/to/file.csv /path/to/file.sql
 */

ini_set('memory_limit',  -1);
ini_set('date.timezone', 'America/New_York');

if ($argc < 3)
{
	exit('Usage: php csv-to-mysql.php /path/to/file.csv /path/to/file.sql');
}
else
{
	$lines = array();

	if (($handle = fopen($argv[1], "r")) !== false)
	{
		while (($data = fgetcsv($handle, 0, ',')) !== false)
		{
			if (isset($fields))
			{
				$lines[] = $data;
			}
			else
			{
				$fields = $data;
			}
		}

		fclose($handle);
	}

	$types = array_fill(0, count($fields), array());;

	foreach ($lines as $line_number => $line)
	{
		foreach ($line as $key => $value)
		{
			// Detects INT
			if (preg_match('/^-?[0-9]+$/', $value))
			{
				$type = array(
					'type'     => 'INT',
					'size'     => '1',
					'unsigned' => $value > 0,
				);
			}
			// Detects DECIMAL
			elseif (preg_match('/^-?[0-9]+\.([0-9])+$/', $value))
			{
				// This is to preserve 2 decimal points even when the
				// post-decimal value is "00" as preg_match will only return
				// "0" when passing in a "matches" array
				$pieces = explode('.', $value);

				$type = array(
					'type'     => 'DECIMAL',
					'size'     => strlen($pieces[0]),
					'decimal'  => strlen($pieces[1]),
					'unsigned' => $value > 0,
				);
			}
			// Detects DATETIME
			elseif ($time = strtotime($value))
			{
				$type = array('type' => 'DATETIME');
			}
			// Fails over to VARCHAR
			else
			{
				$value_length = strlen($value);

				if ($value_length == 0)
				{
					if (!isset($types[$key]['type']))
					{
						$type = array('type' => 'CHAR', 'size' => '1');
					}
				}
				else
				{
					$type = array('type' => 'VARCHAR', 'size' => $value_length);
				}
			}

			// Types don't match
			if ($types[$key] != $type)
			{
				// Checks both size variables and consolidates for DECIMAL
				if (isset($type['size'], $type['decimal']))
				{
					if ($types[$key] != array())
					{
						if ($type['size'] < $types[$key]['size'])
						{
							$type['size'] = $types[$key]['size'];
						}

						if ($type['decimal'] < $types[$key]['decimal'])
						{
							$type['decimal'] = $types[$key]['decimal'];
						}
					}

					$type['size'] = $type['size'];
					$types[$key]  = $type;
				}
				else
				{
					// Type hasn't been set or new type is larger (bigger's better in this scenario)
					if (!isset($types[$key]['size']) || (isset($type['size']) && $types[$key]['size'] < $type['size']))
					{
						$types[$key] = $type;
					}
				}
			}
		}
	}

	// Sniffs out any large VARCHAR and changes to TEXT
	foreach ($types as $key => $type)
	{
		if ($type['type'] == 'VARCHAR' && $type['size'] > 1000)
		{
			$types[$key] = array('type' => 'TEXT');
		}
	}

	$file_info  = pathinfo($argv[2]);
	$table_name = basename($argv[2], '.' . $file_info['extension']);

	$sql = 'DROP TABLE IF EXISTS `' . $table_name . '`;' . "\n\n" . 'CREATE TABLE `' . $table_name . '` (' . "\n";

	foreach ($fields as $key => $field)
	{
		$type = $types[$key];
		$sql .= "\t" . '`' . $field . '` ' . $type['type'];


		if (isset($type['size']))
		{
			$sql .= '(' . $type['size'] . (isset($type['decimal']) ? ', ' . $type['decimal'] : '') . ')';
		}

		if (isset($type['unsigned']) && $type['unsigned'])
		{
			$sql .= ' unsigned';
		}

		// Assumes first field is the primary key
		if ($key == 0)
		{
			$sql        .= ' NOT NULL AUTO_INCREMENT';
			$primary_key = $field;
		}

		$sql .= ',' . "\n";
	}

	$sql .= "\t" . 'PRIMARY KEY (`' . $primary_key . '`)' . "\n";
	$sql .= ') ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;' . "\n\n";

	$sql .= 'INSERT INTO `' . $table_name . '` (' . implode($fields, ', ') . ') VALUES' . "\n";

	file_put_contents($argv[2], $sql);

	foreach ($lines as $line_number => $line)
	{
		$sql = '';

		if ($line_number > 0)
		{
			$sql .= ',' . "\n";
		}

		foreach ($line as $key => $field)
		{
			$line[$key] = '"' . str_replace('"', '\"', nl2br($field)) . '"';
		}

		$sql .= '(' . implode($line, ', ') . ')';

		file_put_contents($argv[2], $sql, FILE_APPEND);
	}

	file_put_contents($argv[2], ';', FILE_APPEND);
}

?>
