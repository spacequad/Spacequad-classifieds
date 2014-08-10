<?php
/**
*   Create administration lists for ad elements
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    classifieds
*   @version    1.0.2
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/


// Include required admin functions
USES_lib_admin();


/**
*   Ad type management - return the display version for a single field
*   @param string   $fieldname  Name of the field
*   @param string   $fieldvalue Value to be displayed
*   @param array    $A          Associative array of all values available
*   @param array    $icon_arr   Array of icons available for display
*   @return string              Complete HTML to display the field
*/
function plugin_getListField_AdTypes($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_CONF_ADVT, $LANG24, $LANG_ADVT;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval = COM_createLink(
            $icon_arr['edit'],
            CLASSIFIEDS_ADMIN_URL . 
                "/index.php?mode=editadtype&amp;type_id={$A['id']}"
            );
        break;

    case 'enabled':
        if ($fieldvalue == 1) {
            $retval = COM_createImage($_CONF['layout_url'] . '/images/admin/check.png',
                'Enabled');
        }
        if ($fieldvalue == 1) {
            $chk = ' checked="checked" ';
            $enabled = 1;
        } else {
            $chk = '';
            $enabled = 0;
        }
        $fld_id = $fieldname . '_' . $A['id'];
        $retval = 
                "<input name=\"{$fld_id}\" id=\"{$fld_id}\" " .
                "type=\"checkbox\" $chk " .
                "onclick='ADVTtoggleEnabled(this, \"{$A['id']}\", \"adtype\", \"{$_CONF['site_url']}\");' ".
                ">\n";
        break;

    case 'delete':
        $retval .= '&nbsp;&nbsp;' . COM_createLink(
            COM_createImage($_CONF['layout_url'] . '/images/admin/delete.png',
                'Delete this item',
                array('title' => 'Delete this item', 
                    'class' => 'gl_mootip',
                    'onclick' => "return confirm('Do you really want to delete this item?');",
                )),
            CLASSIFIEDS_ADMIN_URL . 
                "/index.php?mode=deleteadtype&amp;type_id={$A['id']}"
            );

        /*if ($A['enabled'] == 1) {
            $img = 'on.png';
            $title = 'Disable this item';
            $newval = 0;
        } else {
            $img = 'off.png';
            $title = 'Enable this item';
            $newval = 1;
        }
        $retval .= '&nbsp;&nbsp; ' . 
                "<span id=\"ena{$A['id']}\">\n" .
                "<img src=\"" .
                    "{$_CONF['site_url']}/classifieds/images/{$img}\" " .
                    "border=\"0\" width=\"16\" height=\"16\" " .
                    "onclick='ADVTtoggleEnabled({$newval}, \"{$A['id']}\", \"adtype\", \"{$_CONF['site_url']}\");'>\n".
                "</span>\n";

/*            COM_createLink(
            COM_createImage($_CONF['site_url'] . '/' .
                $_CONF_ADVT['pi_name'] . '/images/'. $img,
                'Delete this item',
                array('title' => $title, 'class' => 'gl_mootip')),
            "{$_CONF['site_admin_url']}/plugins/{$_CONF_ADVT['pi_name']}/index.php?mode=toggleadtype&amp;id={$A['id']}&enabled=$newval"
            );*/
        break;

    default:
        $retval = $fieldvalue;
        break;
    }

    return $retval;

}


/**
*   Create admin list of Ad Types
*   @return string  HTML for admin list
*/
function CLASSIFIEDS_adminAdTypes()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ACCESS, $_CONF_ADVT, $LANG_ADVT;

    $retval = '';

    $header_arr = array(      # display 'text' and use table field 'field'
        array('text' => $LANG_ADVT['edit'], 'field' => 'edit', 
            'sort' => false, 'align' => 'center'),
        array('text' => $LANG_ADVT['description'], 'field' => 'descrip', 
            'sort' => true),
        array('text' => $LANG_ADVT['enabled'], 'field' => 'enabled', 
            'sort' => false, 'align' => 'center'),
        array('text' => $LANG_ADVT['delete'], 'field' => 'delete', 
            'sort' => false, 'align' => 'center'),
    );

    $defsort_arr = array('field' => 'descrip', 'direction' => 'asc');

    $text_arr = array( 
        'has_extras' => true,
        'form_url' => CLASSIFIEDS_ADMIN_URL . '/index.php',
    );

    $query_arr = array('table' => 'ad_types',
        'sql' => "SELECT * FROM {$_TABLES['ad_types']} ", 
        'query_fields' => array(),
        'default_filter' => ''
    );

    $retval .= ADMIN_list('classifieds', 'plugin_getListField_AdTypes', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, '', '', '', $form_arr);

    return $retval;
}


function plugin_getListField_AdCategories(
    $fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_CONF_ADVT, $LANG24, $LANG_ADVT, $_TABLES;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval = COM_createLink(
            $icon_arr['edit'],
            CLASSIFIEDS_ADMIN_URL . "/index.php?editcat=x&cat_id={$A['cat_id']}"
            );
        break;

    case 'delete':
        $retval .= '&nbsp;&nbsp;' . COM_createLink(
            COM_createImage($_CONF['layout_url'] . '/images/admin/delete.png',
                'Delete this item',
                array('title' => 'Delete this item', 'class' => 'gl_mootip')),
            CLASSIFIEDS_ADMIN_URL . 
                "/index.php?deletecat=cat&amp;cat_id={$A['cat_id']}"
            );
        break;

    case 'parent':
        $retval = DB_getItem($_TABLES['ad_category'], 'cat_name', 'cat_id='.$A['papa_id']);
        break;
    default:
        $retval = $fieldvalue;
        break;
    }

    return $retval;

}


/**
*   Create an admin list of categories.  Currently Unused
*   @return string  HTML for admin list of categories
*/
function CLASSIFIEDS_adminCategories()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ACCESS, 
            $_CONF_ADVT, $LANG_ADVT;

    $retval = '';

    $header_arr = array(      # display 'text' and use table field 'field'
        array('text' => $LANG_ADVT['edit'], 'field' => 'edit', 'sort' => false),
        array('text' => $LANG_ADVT['name'], 'field' => 'cat_name', 'sort' => true),
        array('text' => $LANG_ADVT['parent_cat'], 'field' => 'parent', 'sort' => true),
        array('text' => $LANG_ADVT['delete'], 'field' => 'delete', 'sort' => false),
    );

    $defsort_arr = array('field' => 'cat_name', 'direction' => 'asc');

    $text_arr = array( 
        'has_extras' => true,
        'form_url' => CLASSIFIEDS_ADMIN_URL . '/index.php?admin=cat',
    );

    $query_arr = array('table' => 'ad_category',
        'sql' => "SELECT * FROM {$_TABLES['ad_category']}",
        'query_fields' => array(),
        'default_filter' => ''
    );

    $retval .= ADMIN_list('classifieds', 'plugin_getListField_AdCategories', 
                $header_arr, $text_arr, $query_arr, $defsort_arr, 
                '', '', '', $form_arr);

    return $retval;
}


/**
*   Uses lib-admin to list the forms definitions and allow updating.
*
*   @return string HTML for the list
*/
function CLASSIFIEDS_adminAds()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_ADVT;

    $retval = '';

    $header_arr = array(
        array('text' => $LANG_ADMIN['edit'], 'field' => 'edit', 'sort' => false),
        array('text' => $LANG_ADVT['added_on'], 'field' => 'date', 'sort' => true),
        array('text' => $LANG_ADVT['subject'], 'field' => 'subject', 'sort' => true),
        array('text' => $LANG_ADVT['owner'], 'field' => 'owner_id', 'sort' => true),
        array('text' => $LANG_ADVT['delete'], 'field' => 'delete', 'sort' => false),
    );

    $defsort_arr = array('field' => 'date', 'direction' => 'asc');

    $text_arr = array();

    $query_arr = array('table' => 'ad_ads',
        'sql' => "SELECT *, from_unixtime(add_date) as date
               FROM {$_TABLES['ad_ads']}",
        'query_fields' => array('name', 'descript'),
        'default_filter' => ''
    );

    $retval .= ADMIN_list('classifieds', 'CLASSIFIEDS_getField_ad', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, '', '', '', $form_arr);

    return $retval;
}


/**
*   Determine what to display in the admin list for each form.
*
*   @param  string  $fieldname  Name of the field, from database
*   @param  mixed   $fieldvalue Value of the current field
*   @param  array   $A          Array of all name/field pairs
*   @param  array   $icon_arr   Array of system icons
*   @return string              HTML for the field cell
*/
function CLASSIFIEDS_getField_ad($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ACCESS;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval =
            COM_createLink(
                $icon_arr['edit'], CLASSIFIEDS_ADMIN_URL .
                "/index.php?editad={$A['ad_id']}"
            );
       break;

    case 'delete':
        $retval = COM_createLink(
                "<img src=\"" . $_CONF['layout_url'] .
                    "/images/admin/delete.png\"
                    height=\"16\" width=\"16\" border=\"0\"
                    onclick=\"return confirm('Do you really want to delete this item?');\"
                    >",
                CLASSIFIEDS_ADMIN_URL .
                    "/index.php?delete=ad&id={$A['ad_id']}"
            );
        break;

    case 'subject':
        $retval = COM_createLink($fieldvalue,
            CLASSIFIEDS_URL . '/index.php?mode=detail&id=' . $A['ad_id']);
        break;

    case 'owner_id':
        $retval = COM_getDisplayName($A['uid']);
        break;

    default:
        $retval = $fieldvalue;
        break;

    }

    return $retval;
}


?>
