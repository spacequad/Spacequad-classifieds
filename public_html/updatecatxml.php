<?php
/**
*   Provide ajax functions for updating form fields based on category selection.
*   
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    classifieds
*   @version    0.3
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/** Import core glFusion libraries */
require_once '../lib-common.php';

$id = intval($_GET['q']);
if ($id <= 0) exit;
$A = array();

$sql = "SELECT keywords, perm_anon
    FROM {$_TABLES['ad_category']}
    WHERE cat_id=$id";
//echo $sql;
$result = DB_query($sql);
if (!$result || DB_numRows($result) != 1) {
    $A['keywords'] = '';
    $A['perm_anon'] = 0;
} else {
    $A = DB_fetchArray($result);
}

if ($A['perm_anon'] < 2) $A['perm_anon'] = 0;
if ($A['keywords'] == '') $A['keywords'] = 'none';

header('Content-Type: text/xml');
header("Cache-Control: no-cache, must-revalidate");
//A date in the past
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

echo '<?xml version="1.0" encoding="ISO-8859-1"?>
<category>'. "\n";
echo "<keywords>" . $A['keywords'] . "</keywords>";
echo "<perm_anon>" . $A['perm_anon'] . "</perm_anon>\n";
echo "</category>\n";
?>
