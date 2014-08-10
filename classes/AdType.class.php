<?php
/**
 *  Class to manage ad types
 *
 *  @author     Lee Garner <lee@leegarner.com>
 *  @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
 *  @package    classifieds
 *  @version    0.3.1
 *  @license    http://opensource.org/licenses/gpl-2.0.php 
 *  GNU Public License v2 or later
 *  @filesource
 */

/**
 *  Class for ad type
 *  @package classifieds
 */
class AdType
{
    /** Database ID 
     *  @var integer */
    var $db_id;

    /** Ad Type String
     *  @var string */
    var $descrip;

    /** Enable or Disabled indicator
     *  @var integer */
    var $enabled;

    /** Error string or value, to be accessible by the calling routines.
     *  @var mixed */
    public  $Error;

    /**
     *  Constructor.
     *  Reads in the specified class, if $id is set.  If $id is zero, 
     *  then a new entry is being created.
     *  @param integer $id Optional type ID
     */
    function AdType($id=0)
    {
        $id = (int)$id;
        if ($id < 1) {
            $this->db_id = 0;
            $this->descrip = '';
            $this->enabled = 1;
        } else {
            $this->setID($id);
            $this->ReadOne();
        }
    }

    function setID($id=0) { $this->db_id = (int)$id; }
    function getID() { return $this->db_id; }

    function setDescrip($str='') { $this->descrip = trim($str); }
    function getDescrip() { return $this->descrip; }

    function setEnabled($val=0) { $this->enabled = $val == 1 ? 1 : 0; }
    function getEnabled() { return $this->enabled; }


    /**
     *  Sets all variables to the matching values from $rows
     *  @param array $row Array of values, from DB or $_POST
     */
    function SetVars($row)
    {
        if (!is_array($row)) return;

        $this->setID($row['id']);
        $this->setDescrip($row['descrip']);
        $this->setEnabled($row['enabled']);
    }


    /**
     *  Read one as type from the database and populate the local values.
     *  @param integer $id Optional ID.  Current ID is used if zero
     */
    function ReadOne($id = 0)
    {
        global $_TABLES;

        $id = (int)$id;
        if ($id == 0) $id = $this->db_id;
        if ($id == 0) {
            $this->error = 'Invalid ID in ReadOn()';
            return;
        }

        $result = $this->dbExecute("SELECT * from {$_TABLES['ad_types']} 
                                    WHERE id=$id");
        $row = DB_fetchArray($result, false);
        $this->SetVars($row);
    }


    /**
     *  Save the current values to the database.
     */
    function Save()
    {
        if ($this->db_id > 0) {
            $this->Update();
        } else {
            $this->Insert();
        }
    }


    /**
     *  Helper function to execute a database query
     *  @param string $sql SQL query to execute
     *  @return object SQL result object
     */
    function dbExecute($sql)
    {
        if ($sql == '') return;

        $result = DB_query($sql);
        return $result;
    }


    /**
     *  Delete the curret cls/div record from the database
     */
    function Delete()
    {
        global $_TABLES;

        if ($this->db_id > 0)
            DB_delete($_TABLES['ad_types'], 'id', $this->db_id);

        $this->db_id = 0;
    }


    /**
     *  Adds the current values to the databae as a new record
     */
    function Insert()
    {
        global $_TABLES;

        if (!$this->isValidRecord())
            return;

        $sql = "INSERT INTO
                {$_TABLES['ad_types']}
                (descrip, enabled)
            VALUES (
                '" . DB_escapeString($this->descrip) . "', 
                {$this->enabled}
            )";

        $this->dbExecute($sql);
    }


    /**
     *  Updates the database for the current cls/div
     */
    function Update()
    {
        global $_TABLES;

        if (!$this->isValidRecord())
            return;

        $sql = "UPDATE 
                {$_TABLES['ad_types']}
            SET
                descrip='" . DB_escapeString($this->getDescrip()) . "',
                enabled=" . $this->getEnabled(). "
            WHERE
                id=" . $this->getID();

        $this->dbExecute($sql);
    }


    /**
     *  Determines if the current values are valid.
     *  @return boolean True if ok, False otherwise.
     */
    function isValidRecord()
    {
        if ($this->getDescrip() == '') {
            return false;
        } else {
            return true;
        }
    }


    /**
     *  Creates the edit form
     *  @param integer $id Optional ID, current record used if zero
     *  @return string HTML for edit form
     */
    function showForm($id = 0)
    {
        global $_TABLES, $_CONF, $_CONF_ADVT, $LANG_ADVT;

        $id = (int)$id;
        if ($id > 0) $this->Read($id);

        $T = new Template(CLASSIFIEDS_PI_PATH . '/templates');
        $T->set_file('admin','adtypeform.thtml');

        $T->set_var(array(
            'pi_admin_url'  => CLASSIFIEDS_ADMIN_URL,
            'cancel_url'    => CLASSIFIEDS_ADMIN_URL . '/index.php?admin=type',
            'show_name'     => $this->showName,
            'type_id'       => $this->getID(),
            'descrip'       => htmlspecialchars($this->getDescrip()),
            'ena_chk'   => $this->getEnabled() == 1 ? 'checked="checked"' : '',
        ) );

        //$T->set_var('form_action', "{$_SERVER['PHP_SELF']}");
        //$T->set_var('actionvar','saveadtype');

        // add a delete button if this division isn't used anywhere
        if ($this->getID() > 0 && !$this->isUsed()) {
            $T->set_var('show_del_btn', 'true');
        } else {
            $T->set_var('show_del_btn', '');
        }

        $T->parse('output','admin');
        $display .= $T->finish($T->get_var('output'));
        return $display;

    }   // function showForm()

 
    /**
     *  Creates a dropdown selection for the specified list, with the
     *  record corresponding to $sel selected.
     *  @param  integer $sel    Optional item ID to select
     *  @param  string  $sql    Optional SQL query
     *  @return string HTML for selection dropdown
     */
    function makeSelection($sel=0, $sql='')
    {
        global $_TABLES;

        if ($sql == '') {
            $sql = "SELECT id,descrip
                FROM {$_TABLES['ad_types']}
                WHERE enabled=1
                ORDER BY descrip ASC";
        }
        $result = DB_query($sql);
        if (!$result) {
            $this->Error = 1;
            return '';
        }

        $selection = '';
        while ($row = DB_fetcharray($result)) {
            $selected = '';
            if (is_array($sel)) {
                // Multiple selections, check if the current one is among them
                if (in_array($row['id'], $sel)) {
                    $selected = "selected";
                }
            } else {
                if ($sel == 0) {
                    // No selection, take the first one found.
                    $sel = $row['id'];
                }
                if ($sel == $row['id']) {
                    $selected = "selected";
                }
            }

            if (is_object($this)) {
                // Set the current id, only if this is an instantiated object
                if ($selected == 'selected' && $this->getID() == 0) {
                    $this->setID($row['id']);
                }
            }

            $selection .= "<option value=\"{$row['id']}\" $selected>".
                                htmlspecialchars($row['descrip']).
                                "</option>\n";
        }

        return $selection;

    }   // function makeSelection()


    /**
    *   Sets the "enabled" field to the specified value.
    *
    *   @param  integer     $newval New value to set
    *   @param  integer     $id     ID number of element to modify
    *   @return integer     New value (old value if failed)
    */
    function toggleEnabled($newval, $id=0)
    {
        global $_TABLES;

        if ($id == 0) {
            if (is_object($this))
                $id = $this->getID();
            else
                return;
        }

        $id = (int)$id;
        $newval = $newval == 1 ? 1 : 0;

        $sql = "UPDATE {$_TABLES['ad_types']}
            SET enabled=$newval
            WHERE id=$id";
        //echo $sql;die;
        DB_query($sql, 1);
        if (DB_error()) {
            $retval = $newval == 1 ? 0 : 1;
        } else {
            $retval = $newval;
        }
        return $retval;
    }


    /**
     *  Determine if this ad type is used by any ads in the database.
     *  @return boolean True if used, False if not
     */
    function isUsed()
    {
        global $_TABLES;

        if (DB_count($_TABLES['ad_ads'], 'ad_type', $this->getID()) > 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     *  Returns the string corresponding to the $id parameter.
     *  Designed to be used standalone; if this is an object,
     *  we already have the description in a variable.
     *  @param  integer $id     Database ID of the ad type
     *  @return string          Ad Type Description
     */
    function GetDescription($id=0)
    {
        global $_TABLES;

        $id = (int)$id;
        return DB_getItem($_TABLES['ad_types'], 'descrip', "id='$id'");
    }

}   // class AdType


?>
