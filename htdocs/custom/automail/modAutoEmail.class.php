<?php
require_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

class modAutoEmail extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs;

        $this->db = $db;
        $this->numero = 104000;
        $this->rights_class = 'autoemail';
        $this->family = "technic";
        $this->module_position = '50';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Automatically send email when payment is added to an invoice";
        $this->version = '1.0';
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