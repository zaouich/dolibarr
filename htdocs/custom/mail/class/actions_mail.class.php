<?php
class ActionsMail
{
    public $module_name = 'MyModule';

    public function invoicecard($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;

        if ($action == 'confirm_delete_paiement' && $confirm == 'yes' && $usercancreate) {
            // Your custom code here
            // For example, you can add a log entry:
            echo('<script>console.log("Hello World!");</script>');
			exit;

            // Display a message using Dolibarr's setEventMessages function
            $langs->load("mymodule@mymodule");
            setEventMessages($langs->trans("PaymentDeletionConfirmed"), null, 'mesgs');
        }
    }
}