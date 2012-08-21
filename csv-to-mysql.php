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

// @todo Stay calm and code on.

?>
