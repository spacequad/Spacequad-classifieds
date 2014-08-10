<?php
/**
*   Provide functions to email admins and subscribers when new ads 
*   are submitted.
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    classifieds
*   @version    1.0.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

// Import needed classes
USES_classifieds_class_adtype();


/**
 *  Send an email to all subscribers for the ad's category, or any
 *  parent category.
 *
 *  Email is only sent if the ad is approved and a notification
 *  hasn't already been sent.
 *
 *  @param int $ad_id  ID number of ad 
 */
function catNotify($ad_id = '')
{
    global $_TABLES,  $_CONF, $_CONF_ADVT;

    // require a valid ad ID
    $ad_id = COM_sanitizeID($ad_id);
    if ($ad_id == '')
        return;

    // retrieve the ad info.
    $result = DB_query("SELECT 
            * 
        FROM 
            {$_TABLES['ad_ads']} 
        WHERE 
            ad_id='$ad_id'");
    if (!$result || DB_numrows($result) < 1)
        return;

    $adinfo = DB_fetchArray($result);

    // check approval status and whether a notification was already sent.
    if ($adinfo['sentnotify'] == 1)
        return;

    $cat = (int)$adinfo['cat_id'];
    $subject = trim($adinfo['subject']);
    $descript = trim($adinfo['descript']);
    $price = trim($adinfo['price']);

    // Collect all the parent categories into a comma-separated list, and
    // find all the subscribers in any of the categories
    $catlist = CLASSIFIEDS_ParentCatList($cat);
    $sql = "SELECT 
            uid 
        FROM 
            {$_TABLES['ad_notice']} 
        WHERE cat_id IN ($catlist)";
    $notice = @DB_query($sql);
    if (!$notice)
        return;

    // send the notification to subscribers
    while ($row = DB_fetchArray($notice)) {
        $result = DB_query("
            SELECT 
                username, email, language
            FROM 
                {$_TABLES['users']} 
            WHERE 
                uid='{$row['uid']}'
        ");
        if (!$result)
            continue;

        $name = DB_fetchArray($result);

        // Select the template for the message
        $template_dir = CLASSIFIEDS_PI_PATH . 
                    '/templates/notify/' . $name['language'];
        if (!file_exists($template_dir . '/subscriber.thtml')) {
            $template_dir = CLASSIFIEDS_PI_PATH . '/templates/notify/english';
        }

        // Load the recipient's language.  $LANG_ADVT is *not* global here
        // to avoid overwriting the global language strings.
        $LANG = plugin_loadlanguage_classifieds($name['language']);
    
        $T = new Template($template_dir);
        $T->set_file('message', 'subscriber.thtml');

        //$ad_type = ($adinfo['forsale'] == 1) ? 
        //    $LANG['forsale'] : $LANG['wanted'];
        //$ad_type = CLASSIFIEDS_getAdTypeString($adinfo['ad_type']);
        $ad_type = AdType::GetDescription($adinfo['ad_type']);
        $T->set_var('site_url', $_CONF['site_url']);
        $T->set_var('site_name', $_CONF['site_name']);
        $T->set_var('cat', CLASSIFIEDS_BreadCrumbs($cat), false);
        $T->set_var('subject', $subject);
        $T->set_var('description', $descript);
        $T->set_var('username', COM_getDisplayName($row['uid']));
        $T->set_var('ad_url', "{$_CONF['site_url']}/{$_CONF_ADVT['pi_name']}/index.php?mode=detail&id=$ad_id");
        $T->set_var('price', $price);
        $T->set_var('ad_type', $ad_type);
        $T->parse('output','message');
        $message = $T->finish($T->get_var('output'));

        COM_mail(
            $name['email'],
            "{$LANG['new_ad_listing']} {$_CONF['site_name']}",
            $message,
            "{$_CONF['site_name']} <{$_CONF['site_mail']}>",
            true
        );

    }

    // update the ad's flag to indicate that a notification has been sent
    @DB_query("
        UPDATE
            {$_TABLES['ad_ads']} 
        SET
            sentnotify=1
        WHERE
            ad_id='$ad_id'
    ");

}   // function catNotify()


/**
 *  Subscribe the current user to a specified category's notifications.
 *
 *  @param  integer $cat    Category ID to subscribe
 */
function catSubscribe($cat)
{
    global $_USER, $_TABLES;

    $cat = (int)$cat;
    if ($cat == 0) return;

    // only registered users can subscribe
    if (COM_isAnonUser())
        return;

    DB_save($_TABLES['ad_notice'], "cat_id,uid", "$cat,{$_USER['uid']}");

}   // function catSubscribe


/**
 *  Unscribe the current user from a specified category's notifications.
 *
 *  @param  integer $cat    Category ID to unsubscribe
 */
function catUnSubscribe($cat)
{
    global $_USER, $_TABLES;

    // only registered users can subscribe
    if (COM_isAnonUser())
        return;

    DB_delete($_TABLES['ad_notice'],
        array('cat_id', 'uid'),
        array((int)$cat, $_USER['uid']));

}   // function catUnSubscribe()


/**
 *  Sends an email to the owner of an ad indicating acceptance or rejection.
 *  The language file is determined based on the owner's configured language,
 *  defaulting to English.
 *
 *  @param  string  $ad_id      ID of ad for which to send notification
 *  @param  boolean $approved   TRUE if ad is approved, FALSE if rejected
 */
function CLASSIFIEDS_notifyApproval($ad_id, $approved=TRUE)
{
    global $_TABLES, $_CONF, $_CONF_ADVT;

    // First, determine if we even notify users of this condition
    if (
        $_CONF_ADVT['emailusers'] == 0       // Never notify
        ||
        ($_CONF_ADVT['emailusers'] == 2 && $approved==FALSE)  // approval only
        ||
        ($_CONF_ADVT['emailusers'] == 3 && $approved==TRUE)   // rejection only
    )
        return;

    // If approved, then the ad has already been moved to the main table.
    // Otherwise, the data is still in the submission table.
    $table = $approved == true ? $_TABLES['ad_ads'] : $_TABLES['ad_submission'];
    $ad_id = COM_sanitizeID($ad_id);
    $sql = "SELECT
            subject, descript, price, owner_id, ad_type, cat_id
        FROM
            $table
        WHERE
            ad_id='$ad_id'";
    //echo $sql;die;
    $r = DB_query($sql);
    if (!$r || DB_numRows($r) < 1)
        return;

    USES_classifieds_class_adtype();

    $row = DB_fetchArray($r);
    // Sanitizing this since it gets used in another query.
    $owner_id = (int)$row['owner_id'];
    $username = COM_getDisplayName($owner_id);
    $email = DB_getItem($_TABLES['users'], 'email', "uid=$owner_id");

    // Include the owner's language, if possible.
    $language = DB_getItem($_TABLES['users'], 'language', "uid=$owner_id");
    $LANG = plugin_loadlanguage_classifieds(array($language, $_CONF['language']));

    // If approved, then the ad has already been moved to the main table.
    // Otherwise, the data is still in the submission table.
    if ($approved == true) {
        $template_file = 'approved.thtml';
        $subject = $LANG['subj_approved'];
    } else {
        $template_file = 'rejected.thtml';
        $subject = $LANG['subj_rejected'];
    }

    // Pick the template based on approval status and language
    $template_base = CLASSIFIEDS_PI_PATH . '/templates/notify';

    if (file_exists("$template_base/{$language}/$template_file")) {
        $template_dir = "$template_base/{$language}";
    } else {
        $template_dir = "$template_base/english";
    }

    $T = new Template($template_dir);
    $T->set_file('message', $template_file);
    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('site_name', $_CONF['site_name']);

    $T->set_var('username', $username);
    $T->set_var('subject', $row['subject']);
    $T->set_var('descript', $row['descript']);
    $T->set_var('price', $row['price']);
    $T->set_var('cat', DB_getItem($_TABLES['ad_category'], 'cat_name',
        'cat_id='. intval($row['cat_id'])));
    $T->set_var('ad_type', AdType::GetDescription($A['ad_type']));
    $T->set_var('ad_url', "{$_CONF['site_url']}/{$_CONF_ADVT['pi_name']}/index.php?mode=detail&id=$ad_id");

    $T->parse('output','message');
    $message = $T->finish($T->get_var('output'));

    COM_mail(
            "$username <$email>",
            $subject,
            $message,
            "{$_CONF['site_name']} <{$_CONF['site_mail']}>",
            true
    );

}


/**
 *  Generates a notification email to all uses who have ads that
 *  will expire within the set expiration period.
 */
function CLASSIFIEDS_notifyExpiration()
{
    global $_TABLES, $_CONF, $_CONF_ADVT;

    $interval = intval($_CONF_ADVT['exp_notify_days']) * 3600 * 24;
    $exp_dt = time() + $interval;

    $sql = "SELECT
            ad.ad_id, ad.owner_id, u.notify_exp
        FROM
            {$_TABLES['ad_ads']} ad
        LEFT JOIN
            {$_TABLES['ad_uinfo']} u
        ON
            u.uid = ad.owner_id
        WHERE
            exp_sent = 0
        AND
            u.notify_exp = 1
        AND
            exp_date < $exp_dt";

    $r = DB_query($sql);
    if (!$r)
        return;

    $users = array();
    $ads = array();
    while ($row = DB_fetchArray($r)) {
        $ads[] = $row['ad_id'];
        $users[$row['owner_id']] += 1;
    }

    $template_base = CLASSIFIEDS_PI_PATH . '/templates/notify';

    foreach ($users as $user_id=>$count) {

        $username = COM_getDisplayName($user_id);
        $email = DB_getItem($_TABLES['users'], 'email', "uid=$user_id");
        $language = DB_getItem($_TABLES['users'], 'language', "uid=$user_id");

        // Include the owner's language, if possible.  Fallback to site language.
        $LANG = plugin_loadlanguage_classifieds(array($language, $_CONF['language']));

        if (file_exists("$template_base/$language/expiration.thtml")) {
            $template_dir = "$template_base/$language";
        } else {
            $template_dir = "$template_base/english";
        }

        $T = new Template($template_dir);
        $T->set_file('message', 'expiration.thtml');
        $T->set_var('site_url', $_CONF['site_url']);
        $T->set_var('site_name', $_CONF['site_name']);
        $T->set_var('num_ads', intval($count));
        $T->set_var('username', $username);
        $T->set_var('pi_name', $_CONF_ADVT['pi_name']);

        $T->parse('output','message');
        $message = $T->finish($T->get_var('output'));

        COM_mail(
            "$username <$email>",
            "{$LANG['ad_exp_notice']}",
            $message,
            "{$_CONF['site_name']} <{$_CONF['site_mail']}>",
            true
        );

    }    

    // Mark that the expiration notification has been sent.
    foreach ($ads as $ad) {
        DB_query("UPDATE {$_TABLES['ad_ads']} SET exp_sent=1 WHERE ad_id='$ad'");
    }

}

/**
*   Notify the site adminstrator that an ad has been submitted.
*   @param  array   $A  All ad data, such as from $_POST
*/
function CLASSIFIEDS_notifyAdmin($A)
{
    global $_TABLES, $LANG_ADVT, $_CONF, $_CONF_ADVT;

    // require a valid ad ID
    if ($A['ad_id'] == '')
        return;

    USES_classifieds_class_adtype();

    COM_clearSpeedlimit(300,'advtnotify');
    $last = COM_checkSpeedlimit ('advtnotify');
    if ($last > 0) {
        return true;
    }

    $ad_type = AdType::GetDescription($A['ad_type']);

    // Select the template for the message
    $template_dir = CLASSIFIEDS_PI_PATH . 
                '/templates/notify/' . $_CONF['language'];
    if (!file_exists($template_dir . '/admin.thtml')) {
        $template_dir = CLASSIFIEDS_PI_PATH . '/templates/notify/english';
    }
    $T = new Template($template_dir);
    $T->set_file('message', 'admin.thtml');

    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('admin_url', "{$_CONF['site_admin_url']}/moderation.php");
    $T->set_var('site_name', $_CONF['site_name']);
    $T->set_var('cat', CLASSIFIEDS_BreadCrumbs($A['catid']), false);
    $T->set_var('subject', $A['subject']);
    $T->set_var('description', $A['descript']);
    $T->set_var('username', COM_getDisplayName(2));
    //$T->set_var('ad_url', "{$_CONF['site_url']}/{$_CONF_ADVT['pi_name']}/index.php?mode=detail&id={$A['ad_id']}");
    $T->set_var('price', $A['price']);
    $T->set_var('ad_type', $ad_type);
    $T->parse('output','message');
    $message = $T->finish($T->get_var('output'));

    $group_id = DB_getItem($_TABLES['groups'],'grp_id','grp_name="classifieds Admin"');
    $groups = CLASSIFIEDS_getGroupList($group_id);
    if (empty($groups))
        return;

    $groupList = implode(',',$groups);

    $sql = "SELECT DISTINCT 
                {$_TABLES['users']}.uid,username,fullname,email 
            FROM 
                {$_TABLES['group_assignments']},
                {$_TABLES['users']} 
            WHERE 
                {$_TABLES['users']}.uid > 1 
            AND 
                {$_TABLES['users']}.uid = {$_TABLES['group_assignments']}.ug_uid 
            AND 
                {$_TABLES['group_assignments']}.ug_main_grp_id IN ({$groupList})";

    $result = DB_query($sql);
    $nRows = DB_numRows($result);
    $toCount = 0;
    for ($i=0; $i < $nRows; $i++) {
        $row = DB_fetchArray($result);
        if ($row['email'] != '') {
            COM_errorLog("Classifieds Submit: Sending notification email to: " . 
                    $row['email'] . " - " . $row['username']);
            COM_mail(
                array($row['email'], $row['username']),
                "{$LANG_ADVT['you_have_new_ad']} {$_CONF['site_name']}",
                $message,
                "{$LANG_ADVT['new_ad_notice']} <$email>",
                true
            );
        }   // if valid email

    }   // foreach administrator

    COM_updateSpeedlimit('advtnotify');

}   // function CLASSIFIEDS_notifyAdmin()


/**
*   Get all groups that are under the requested group
*   @param  integer $basegroup  Group ID where search starts
*   @return array   Array of group IDs
*/
function CLASSIFIEDS_getGroupList($basegroup)
{
    global $_TABLES;

    $to_check = array();
    array_push($to_check, $basegroup);

    $checked = array();

    while (sizeof($to_check) > 0) {
        $thisgroup = array_pop($to_check);
        if ($thisgroup > 0) {
            $result = DB_query(
                "SELECT ug_grp_id 
                FROM {$_TABLES['group_assignments']} 
                WHERE ug_main_grp_id = $thisgroup");
            $numGroups = DB_numRows($result);
            for ($i = 0; $i < $numGroups; $i++) {
                $A = DB_fetchArray ($result);
                if (!in_array($A['ug_grp_id'], $checked)) {
                    if (!in_array($A['ug_grp_id'], $to_check)) {
                        array_push($to_check, $A['ug_grp_id']);
                    }
                }
            }
            $checked[] = $thisgroup;
        }
    }

    return $checked;
}


?>
