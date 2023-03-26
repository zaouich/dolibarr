<?php
if (!defined('NOREQUIREUSER'))   define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB'))     define('NOREQUIREDB', '1');
if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN'))   define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', '1');
if (!defined('NOLOGIN'))         define('NOLOGIN', '1');
if (!defined('NOREQUIREMENU'))   define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');

require_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

class modMyCustomModule extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs;

        $this->db = $db;
        $this->numero = 104000;
        $this->rights_class = 'mycustommodule';
        $this->family = "technic";
        $this->module_position = '50';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "A custom module to send invoice email automatically";
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special = 0;
        $this->picto='generic';
    }

    public function init($options = '')
    {
        $sql = array();
        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}