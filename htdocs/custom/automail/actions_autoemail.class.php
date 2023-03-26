<?php
class ActionsAutoEmail
{
    public function addPayment($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        if ($action == 'add_paiement') {
            require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
            require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
            require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
            require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';

            $formmail = new FormMail($object->db);

            $langs->load("bills");
            $langs->load("mails");

            $subject = $langs->transnoentities("InvoicePaymentReceived", $object->ref);
            $message = $langs->transnoentities("InvoicePaymentReceivedText", $object->ref, $object->getTotalTTC());
            $message .= "\n\n" . $langs->transnoentities("InvoiceStatus") . ": " . $object->getLibStatut(1);

            $sendto = $object->thirdparty->email;
            $from = $conf->global->MAIN_INFO_SOCIETE_NOM."<".$conf->global->MAIN_INFO_SOCIETE_MAIL.">";

            if ($sendto && $from) {
                $deliveryreceipt = 0;
                $msgishtml = 0;

                // Generate the invoice PDF
                $outputlangs = $langs;
                $result = pdf_create($object->db, $object, '', $conf->global->FACTURE_ADDON_PDF, $outputlangs);
                if ($result <= 0) {
                    dol_print_error('', $result);
                    exit;
                }

                // Attach the invoice PDF to the email
                $fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $object->ref, preg_quote($object->ref, '/').'[^\-]+');
                $file = $fileparams['fullname'];

                $result = $formmail->sendmail('facture', $subject, $sendto, $from, $message, $deliveryreceipt, $msgishtml, '', '', 0, 1, $file);
                if ($result) {
                    setEventMessages($langs->trans("MailSent"), null, 'mesgs');
                } else {
                    setEventMessages($langs->trans("MailError"), null, 'errors');
                }
            }
        }
    }
}