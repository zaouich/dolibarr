interface_50_modMyModule_MyTrigger.class.php
<?php
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

class InterfaceMyTrigger extends DolibarrTriggers
{
    public $family = 'demo';
    public $description = "Triggers to send an email when an invoice is validated";

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if ($action === 'BILL_VALIDATE') {
            require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
            require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
            require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

            // Load the email template
            $langs->load("commercial");
            $langs->load("bills");
            $langs->load("mails");

            // Get the invoice owner's email address
            $email = $object->thirdparty->email;

            // Prepare the email subject and message
            $subject = $langs->transnoentities('Invoice').' '.$object->ref.' '.$langs->transnoentities('HasBeenValidated');
            $message = $langs->transnoentities('YouReceiveMailBecauseOfNotification', $application, $mysoc->name)."\n";
            $message.= $langs->transnoentities('Invoice').' '.$object->ref.' '.$langs->transnoentities('HasBeenValidated')."\n\n";

            // Generate the invoice PDF
            $object->fetch_thirdparty();
            $outputlangs = $langs;
            $result = $object->generateDocument($object->modelpdf, $outputlangs);
            if ($result <= 0) {
                dol_print_error($db, $object->error, $object->errors);
                exit;
            }

            // Attach the invoice PDF
            $filepdf = $object->last_main_doc;
            $filename = basename($filepdf);

            // Send the email
            $mailfile = new CMailFile($subject, $email, $conf->global->MAIN_MAIL_EMAIL_FROM, $message, array($filepdf), array(), array(), '', '', 0, 1);
            $result = $mailfile->sendfile();

            if ($result) {
                dol_syslog("Email sent to ".$email, LOG_DEBUG);
            } else {
                dol_syslog("Error sending email to ".$email, LOG_ERR);
            }
        }

        return 0;
    }
}