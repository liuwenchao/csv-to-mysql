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

if ($argc < 3)
{
	exit('Usage: php csv-to-mysql.php /path/to/file.csv /path/to/file.sql');
}
else
{
	$input = file_get_contents($argv[1]);
	$lines = explode("\n", $input);

	$fields = str_getcsv(array_shift($lines));
	$types  = array_fill(0, count($fields), array());;

	foreach ($lines as $line)
	{
		if (trim($line) != '')
		{
			$line = str_getcsv($line);

			foreach ($line as $key => $value)
			{
				if (preg_match('/^[0-9]+$/', $value))
				{
					$type = array('type' => 'INT', 'size' => '1');

					if ($value > 0)
					{
						$type['unsigned'] = true;
					}
				}
				else
				{
					$type = array('type' => 'VARCHAR', 'size' => strlen($value));
				}

				if ($types[$key] != $type)
				{
					$types[$key] = $type;
				}
			}

			print_r($types);
			exit;
		}
	}

	file_put_contents($argv[2], $sql);
}

?>
