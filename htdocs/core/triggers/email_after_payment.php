<?php
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

class InterfaceEmailAfterPayment extends DolibarrTriggers
{
    public $family = 'demo';
    public $description = "Triggers to send email after entering or deleting a payment in the Invoice module.";

    public function getName()
    {
        return "EmailAfterPayment";
    }

    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        return "1.0";
    }

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if ($action == 'BILL_PAYED' || $action == 'BILL_UNPAYED') {
            require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

            // Set email subject and message
            $subject = "Invoice Payment Update";
            $message = "Dear customer,\n\nYour invoice payment has been updated. Please check your account for more details.\n\nBest regards,\nYour Company";

            // Set sender and recipient email addresses
            $from = $conf->global->MAIN_INFO_SOCIETE_MAIL;
            $to = $object->thirdparty->email;

            // Send the email
            $mail = new CMailFile($subject, $to, $from, $message);
            $result = $mail->sendfile();

            if (!$result) {
                $this->errors[] = $langs->trans('ErrorFailedToSendMail', $to);
                return -1;
            }
        }

        return 0;
    }
}