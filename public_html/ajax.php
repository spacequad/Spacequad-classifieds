<?php
/**
 *  Common AJAX functions
 *  @author     Lee Garner <lee@leegarner.com>
 *  @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
 *  @package    classifieds
 *  @version    0.3.1
 *  @license    http://opensource.org/licenses/gpl-2.0.php 
 *  GNU Public License v2 or later
 *  @filesource
 */

/**
 *  Include required glFusion common functions
 */
require_once '../lib-common.php';

// This is for administrators only
if (!SEC_hasRights('classifieds.admin')) {
    exit;
}

$base_url = $_CONF['site_url'];

switch ($_GET['action']) {
case 'toggleEnabled':
    $newval = $_REQUEST['newval'] == 1 ? 1 : 0;

    switch ($_GET['type']) {
    case 'adtype':
        USES_classifieds_class_adtype();
        $newval = AdType::toggleEnabled($newval, $_GET['id']);
        break;

     default:
        exit;
    }

    header('Content-Type: text/xml');
    header("Cache-Control: no-cache, must-revalidate");
    //A date in the past
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

    echo '<?xml version="1.0" encoding="ISO-8859-1"?>
    <info>'. "\n";
    echo "<newval>$newval</newval>\n";
    echo "<id>{$_REQUEST['id']}</id>\n";
    echo "<type>{$_REQUEST['type']}</type>\n";
    echo "<baseurl>{$base_url}</baseurl>\n";
    echo "</info>\n";
    break;

}

?>
