<?php
class InterfaceMyCustomModuleTriggers
{
    public $family = 'mycustommodule';
    public $description = "Triggers of My Custom Module";
    public $version = '1.0.0';
    public $picto = 'generic';

    public function getName()
    {
        return $this->name;
    }

    public function getFamily()
    {
        return $this->family;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getPicture()
    {
        return $this->picto;
    }

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if ($action === 'BILL_PAYED' || $action === 'BILL_UNPAYED') {
            // Load the customer's email address
            $soc = new Societe($object->db);
            $soc->fetch($object->socid);
            $to = $soc->email;

            // Generate the invoice PDF
            require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
            require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
            require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
            $modellist = ModelePDFInvoices::liste_modeles($object->db);
            $model = $conf->global->FACTURE_ADDON_PDF;
            $object->setDocModel($user, $model);
            $outputlangs = $langs;
            $ret = $object->fetch($object->id); // Reload to get new records
            $result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
            $pdf_path = $conf->facture->dir_output . '/' . $object->ref . '/' . $object->ref . '.pdf';
				        // Prepare the email subject and message
						$langs->load("mails");
						$subject = $langs->trans('InvoiceStatusChanged');
						$message = $langs->trans('YourInvoiceStatusHasBeenChanged', $object->ref);
				
						// Send the email with the invoice PDF attached
						$mailfile = new CMailFile($subject, $to, $conf->email_from, $message, array($pdf_path), array(), array(), '', '', 0, 1);
						$mailfile->sendfile();
					}
				
					return 0;
				}}