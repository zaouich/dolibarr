<?php
class ActionsMyModule
{
    public $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;

        if ($action == 'confirm_delete_payment') {
            // Your custom code here
            // For example, you can add a log entry:
            dol_syslog("MyModule: Payment deletion confirmed", LOG_DEBUG);

            // Display a message using Dolibarr's setEventMessages function
            $langs->load("mymodule@mymodule");
            setEventMessages($langs->trans("PaymentDeletionConfirmed"), null, 'mesgs');
        }
    }
}