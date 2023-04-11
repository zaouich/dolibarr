<?php

use phpDocumentor\Reflection\DocBlock\Description;
use Twilio\Rest\Client;
use Twilio\TwiML\Voice\Number;

require_once(__DIR__ . '/../../../includes/twilio/sdk/src/Twilio/autoload.php');
function last_action($action, $conf, $fk_id)
{
	$database_name = $conf->db->name;
	$database_user_name = $conf->db->user;
	$database_password = $conf->db->pass;
	$database_host = $conf->db->host;
	$db = new mysqli($database_host, $database_user_name, $database_password, $database_name);
	if ($db->connect_error) {
		die("Connection failed: " . $db->connect_error);
	} else {
	}
	$sql = "UPDATE llx_facture SET last_action = ? WHERE rowid = ?";
	$stmt = $db->prepare($sql);
	if (!$stmt) {
		die("Prepare statement failed: " . $db->error);
	}
	$stmt->bind_param("si", $action, $fk_id);
	$result = $stmt->execute();
	if (!$result) {
		die("Execute statement failed: " . $stmt->error);
	}
	$stmt->close();
	$db->close();
}
function payed_amount($conf, $invoice_id)
{
	$database_name = $conf->db->name;
	$database_user_name = $conf->db->user;
	$database_password = $conf->db->pass;
	$database_host = $conf->db->host;
	$db = new mysqli($database_host, $database_user_name, $database_password, $database_name);
	if ($db->connect_error) {
		die("Connection failed: " . $db->connect_error);
	} else {
	}
	// Execute the SQL statement and fetch the data
	$sql = "SELECT  sum(amount) FROM llx_paiement_facture WHERE fk_facture = $invoice_id";
	$result = $db->query($sql);
	$data = $result->fetch_assoc();
	$paid = $data['sum(amount)'];
	return $paid;
}

function email_template($invoice, $currency, $total_paid_amount, $remaning_amount, $title, $description, $products_infos, $payment_infos)
{
	// Set variables for payment and product information
	$invoice_products = $invoice->lines;

	// Start building the HTML code
	$html = '<table max-width="700px" cellspacing="0" cellpadding="0" border="0" style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.4; color: #333333;">';
	$html .= '<tr><td align="center"><h1 style="margin: 0; font-size: 24px; font-weight: bold; color: #1a66ff;">' . $title . '</h1></td></tr>';
	$html .= '<tr><td align="center"><p style="margin: 0; color: #707070;">' . $description . '</p></td></tr>';

	// Add payment information table if variable is true
	if ($payment_infos) {
		$html .= '<tr><td><table width="100%" cellspacing="0" cellpadding="0" border="1" style="border: 1px solid #1a66ff; margin: 20px 0; border-collapse: collapse;">';
		$html .= '<tr><td colspan="2" align="center" style="padding: 10px 0; font-size: 20px; font-weight: bold; background-color: #1a66ff; color: #ffffff;">Payment Information</td></tr>';
		$html .= '<tr><td align="center" style="padding: 10px; width: 50%; border-right: 1px solid #1a66ff; background-color: #ebf0ff;">Total</td><td align="center" style="padding: 10px; width: 50%; background-color: #ebf0ff;">' . number_format($invoice->total_ttc, 2)  . ' ' . $currency . '</td></tr>';
		$html .= '<tr><td align="center" style="padding: 10px; width: 50%; border-right: 1px solid #1a66ff; background-color: #ebf0ff;">Paid</td><td align="center" style="padding: 10px; width: 50%; background-color: #ebf0ff;">' . number_format($total_paid_amount, 2) . ' ' . $currency . '</td></tr>';
		$html .= '<tr><td align="center" style="padding: 10px; width: 50%; border-right: 1px solid #1a66ff; background-color: #ebf0ff;">Balance Due</td><td align="center" style="padding: 10px; width: 50%; background-color: #ebf0ff;">' . number_format($remaning_amount, 2) . ' ' . $currency . '</td></tr>';
		$html .= '</table></td></tr>';
	}

	// Add product information table(s) if variable is true and there are products to display
	if ($products_infos && count($invoice_products) > 0) {
		$html .= '<tr><td><table width="100%" cellspacing="0" cellpadding="0" border="1" style="border: 1px solid #1a66ff; margin: 20px 0; border-collapse: collapse;">';
		$html .= '<tr><td colspan="2" align="center" style="padding: 10px 0; font-size: 20px; font-weight: bold; background-color: #1a66ff; color: #ffffff;">Products Details</td></tr>';
		foreach ($invoice_products as $product) {
			$html .= '<tr><td align="center" style="padding: 10px; width: 50%; border-right: 1px solid #1a66ff; background-color: #ebf0ff;">' . $product->product_label . '</td><td align="center" style="padding: 10px; width: 50%; background-color: #ebf0ff;">' . $product->qty . '</td></tr>';
			$html .= '<tr><td align="center" style="padding: 10px; width: 50%; border-right: 1px solid #1a66ff; background-color: #ebf0ff;">Unit Price</td><td align="center" style="padding: 10px; width: 50%; background-color: #ebf0ff;">' . number_format($product->subprice, 2) . ' ' . $currency . '</td></tr>';
			$html .= '<tr><td align="center" style="padding: 10px; width: 50%; border-right: 1px solid #1a66ff; background-color: #ebf0ff;">Total</td><td align="center" style="padding: 10px; width: 50%; background-color: #ebf0ff;">' . number_format($product->total_ht, 2) . ' ' . $currency . '</td></tr>';
			// a blank row with no border and no background color
			$html .= '<tr><td colspan="2" style="border: none; background-color: transparent;"></td></tr>';
		}
		$html .= '</table></td></tr>';
	}
	$html .= '</table>';
	return $html;
}
function check($conf, $method)
{

	$database_name = $conf->db->name;
	$database_user_name = $conf->db->user;
	$database_password = $conf->db->pass;
	$database_host = $conf->db->host;
	$db = new mysqli($database_host, $database_user_name, $database_password, $database_name);
	if ($db->connect_error) {
		die("Connection failed: " . $db->connect_error);
	}

	// Execute the SQL statement and fetch the data
	$sql = "SELECT * FROM llx_auto_send WHERE id = 1";
	$result = $db->query($sql);
	$data = $result->fetch_assoc();

	// check if data[$method] is 1 or 0
	if ($data[$method] == 1) {
		return true;
	} else {
		return null;
	}
}
function send_sms($phone_number, $message)
{
	$sid = "AC6c449db69d57941571115f72f029ae35";
	$token = "be0f3bfe6a8c34969e4a5b5f4ef6e119";
	$twilio = new Client($sid, $token);
	try {
		$message = $twilio->messages->create($phone_number, [
			"body" => $message,
			"from" => "+15856696175"
		]);
		setEventMessages("SMS sent successfully", null, 'mesgs');
	} catch (Exception $e) {

		setEventMessages("SMS not sent", null, 'errors');
	}
}

function send_email_on_payment_delete($object, $paiement, $conf, $langs, $mysoc)
// get the name of the database
{
	if ($conf->global->MAIN_MODULE_MAIL == 1) {
		$result = $object->fetch($object->id); // Reload to get new records
		$result = $object->fetch_thirdparty();

		$deleted_montant = $paiement->montant;
		$deleted_id = $paiement->ref;


		$total_paid = payed_amount($conf, $object->id);
		if ($total_paid == null) {
			$total_paid = 0;
		}

		$left_to_pay = $object->total_ttc - $total_paid;
		$deleted_montant = $paiement->montant;
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');

		$deleted_date = $paiement->datepaye;
		$deleted_date = date('d/m/Y', $deleted_date);
		$currency = $object->multicurrency_code;
		$invoice_status = $object->statut;
		$message = "dear customer \n the payment with the ref (" . $deleted_id . ") was deleted from the invoice with the ref ( " . $object->ref . ") \n the amount of the deleted payment is :" . $deleted_montant . $currency . " \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . number_format($original_amount) . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n the deleted payment date is :" . $deleted_date . "\nthe invoice status is : started";



		// format date

		// send email
		$sendto = $object->thirdparty->email;
		$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
		$subject = '[' . $mysoc->name . '] ' . $langs->trans('Invoice') . ' ' . $object->ref;

		$filename_list = array();
		$mimefilename_list = array();
		$mimetype_list = array();

		// Generate PDF
		$resultPDF = $object->generateDocument($object->modelpdf, $langs);
		if ($resultPDF <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $object->ref, preg_quote($object->ref, '/') . '[^\-]+');
			$file = $fileparams['fullname'];

			$filename_list[] = $file;
			$mimefilename_list[] = $object->ref . '.pdf';
			$mimetype_list[] = 'application/pdf';
		}

		// Send email
		if (!empty($sendto) && !empty($from)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$okay_mail = check($conf, "email_after_delete_payment");
			$okay_sms = check($conf, "sms_after_delete_payment");
			if ($okay_mail) {
				$description = "dear customer \n the payment with the ref (" . $deleted_id . ") was deleted from the invoice with the ref ( " . $object->ref . ")";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, "payment deleted (" . "started" . ")!", $description, false, true);
				$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list, '', '', 0, 1, "text/html");
				$result = $mailfile->sendfile();
				// get facture id

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);
				echo "sms sent : \n\n";
				echo $message;
			} else {
				return null;
			}
			$facture_id = $object->id;
			last_action("send_email_on_payment_delete", $conf, $facture_id);
		}
	} else {
		return null;
	}
}
function send_mail_after_delete_invoice($object, $conf, $langs, $mysoc)
{

	$ch = curl_init('https://textbelt.com/text');
	$data = array(
		'phone' => '212688205052',
		'message' => 'Hello world',
		'key' => 'd248d5beb9b9c174bfe83f2590aac978166467c37dC7Wb337QGxZRtXJtySEb9Y8',
	);

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$response = curl_exec($ch);
	curl_close($ch);

	if ($conf->global->MAIN_MODULE_MAIL == 1) {
		$result = $object->fetch($object->id); // Reload to get new records
		$result = $object->fetch_thirdparty();

		$invoice_ref = $object->ref;
		// check if the invoice is not draft
		if ($object->statut == 0) {
			return null;
		} else {
			$message = 'dear customer, your facture ' . $invoice_ref . " is deleted \n for more information please contact us \n thank you";
			$sendto = $object->thirdparty->email;
			$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
			$subject = '[' . $mysoc->name . '] ' . $langs->trans('Invoice') . ' ' . $object->ref;
			// Send email
			if (!empty($sendto) && !empty($from)) {
				require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
				$okay_mail = check($conf, "email_after_delete_invoice");
				$okay_sms = check($conf, "sms_after_delete_invoice");
				if ($okay_mail) {


					$description = "dear customer \n  your invoice with the ref ( " . $object->ref . ") is deleted \n";
					$message_ = email_template($object, $currency, $total_paid, $left_to_pay, 'invoice deleted !', $description, false, false);
					echo "<pre>";
					echo $message_;
					$mailfile = new CMailFile($subject, $sendto, $from, $message_, '', '', '', '', '', 0, 1, "text/html");
					$result = $mailfile->sendfile();
					if ($result) {
						setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
					} else {
						setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
					}
				}
				if ($okay_sms) {
					send_sms("+212637342771", $message);
					echo "sms sent : \n\n";
					echo $message;
				} else {
					return null;
				}
				$facture_id = $object->id;
				last_action("send_mail_after_delete_invoice", $conf, $facture_id);
			}
		}
	} else {
		return null;
	}
}
function send_email_after_classify_abandoned($object, $conf, $langs, $mysoc)
{
	if ($conf->global->MAIN_MODULE_MAIL == 1) {
		$result = $object->fetch($object->id); // Reload to get new records
		$result = $object->fetch_thirdparty();
		$invoice_ref = $object->ref;
		$message = 'dear customer, your facture ' . $invoice_ref . " is canceled \n for more information please contact us \n thank you";
		$sendto = $object->thirdparty->email;
		$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
		// cancelation reason

		// 
		$subject = '[' . $mysoc->name . '] ' . $langs->trans('Invoice') . ' ' . $object->ref;
		// Send email
		if (!empty($sendto) && !empty($from)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$okay_mail = check($conf, "email_after_cancel_payment");
			$okay_sms = check($conf, "sms_after_cancel_payment");
			if ($okay_mail) {
				$description = "dear customer \n  your invoice with the ref ( " . $object->ref . ") is canceled \n";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, 'invoice canceled !', $description, true, true);
				echo "<pre>";
				echo $message_;
				$mailfile = new CMailFile($subject, $sendto, $from, $message_, '', '', '', '', '', 0, 1, "text/html");
				$result = $mailfile->sendfile();
				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);

				echo "sms sent : \n\n";

				echo $message;
			} else {
				return null;
			}
			$facture_id = $object->id;
			last_action("send_email_after_classify_abandoned", $conf, $facture_id);
		}
	}
}
function send_mail_after_reopen($object, $paiement, $conf, $langs, $mysoc)
{

	if ($conf->global->MAIN_MODULE_MAIL == 1) {
		$result = $object->fetch($object->id); // Reload to get new records
		$result = $object->fetch_thirdparty();
		// get factures all paiements
		$factures = $object->getListOfPayments();
		// get montatnt of the deleted paiement
		$deleted_montant = $paiement->montant;
		// get id of the deleted paiement
		$deleted_id = $paiement->ref;

		// get total paid

		// get original amount
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');

		// get left to pay
		$total_paid = payed_amount($conf, $object->id);
		if (!$total_paid) {
			$total_paid = 0;
		}
		$left_to_pay = $object->total_ttc - $total_paid;
		$left_to_pay = number_format($left_to_pay, 2, '.', '');

		// get deleted paiement full date with the time
		$deleted_date = $paiement->datepaye;
		// format date
		$deleted_date = date('d/m/Y', $deleted_date);
		// check the status of the invoice if its started or not


		// get the currency of the invoice
		$currency = $object->multicurrency_code;
		$invoice_status = $object->getLibStatut(2, $total_paid);

		$message = "dear customer \n  the invoice with the ref ( " . $object->ref . ") \n is reopened : \n the current total paid is :" . number_format($total_paid, 2)  . $currency . " \n the original amount is :" . number_format($original_amount) . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n invoice status is :" . $invoice_status . " \n for more information please contact us \n thank you";



		// format date

		// send email
		$sendto = $object->thirdparty->email;
		$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
		$subject = '[' . $mysoc->name . '] ' . $langs->trans('Invoice') . ' ' . $object->ref;

		$filename_list = array();
		$mimefilename_list = array();
		$mimetype_list = array();

		// Generate PDF
		$resultPDF = $object->generateDocument($object->modelpdf, $langs);
		if ($resultPDF <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $object->ref, preg_quote($object->ref, '/') . '[^\-]+');
			$file = $fileparams['fullname'];

			$filename_list[] = $file;
			$mimefilename_list[] = $object->ref . '.pdf';
			$mimetype_list[] = 'application/pdf';
		}
		// Send email
		if (!empty($sendto) && !empty($from)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$okay_mail = check($conf, "email_after_reopen_invoice");
			$okay_sms = check($conf, "sms_after_reopen_invoice");
			if ($okay_mail) {
				// 
				$description = "dear customer \n  your invoice with the ref ( " . $object->ref . ") is reopened \n";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, 'invoice reopened' . '(' . $invoice_status . ')!', $description, false, true);
				echo "<pre>";
				echo "<pre>";
				echo $message_;
				$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list, '', '', 0, 1, "text/html");
				$result = $mailfile->sendfile();

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);
				echo "sms message \n";
				echo $message;
			} else {
				return null;
			}
			$facture_id = $object->id;
			last_action("send_mail_after_reopen", $conf, $facture_id);
		}
	} else {
		return null;
	}
}
function send_email_after_classify_paid($object, $paiement, $conf, $langs, $mysoc)

{
	$okay = check($conf, "email_after_classify_as_paid");


	if ($okay  && $conf->global->MAIN_MODULE_MAIL == 1) {
		$result = $object->fetch($object->id); // Reload to get new records
		$result = $object->fetch_thirdparty();
		// get factures all paiements
		$factures = $object->getListOfPayments();
		// get montatnt of the deleted paiement
		$deleted_montant = $paiement->montant;
		// get id of the deleted paiement
		$deleted_id = $paiement->ref;

		// get total paid

		// get original amount
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');

		// get left to pay
		$total_paid = payed_amount($conf, $object->id);
		if (!$total_paid) {
			$total_paid = 0;
		}
		$left_to_pay = $object->total_ttc - $total_paid;
		$left_to_pay = number_format($left_to_pay, 2, '.', '');

		// get deleted paiement full date with the time
		$deleted_date = $paiement->datepaye;
		// format date
		$deleted_date = date('d/m/Y', $deleted_date);
		// check the status of the invoice if its started or not


		// get the currency of the invoice
		$currency = $object->multicurrency_code;

		$message = "dear customer \n  your invoice with the ref ( " . $object->ref . ") is fully paid \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . number_format($original_amount) . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n thank you";
		// send email
		$sendto = $object->thirdparty->email;
		$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
		$subject = '[' . $mysoc->name . '] ' . $langs->trans('Invoice') . ' ' . $object->ref;

		$filename_list = array();
		$mimefilename_list = array();
		$mimetype_list = array();

		// Generate PDF
		$resultPDF = $object->generateDocument($object->modelpdf, $langs);
		if ($resultPDF <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $object->ref, preg_quote($object->ref, '/') . '[^\-]+');
			$file = $fileparams['fullname'];

			$filename_list[] = $file;
			$mimefilename_list[] = $object->ref . '.pdf';
			$mimetype_list[] = 'application/pdf';
		}

		// Send email
		if (!empty($sendto) && !empty($from)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$okay_mail = check($conf, "email_after_classify_as_paid");
			$okay_sms = check($conf, "sms_after_classify_as_paid");
			if ($okay_mail) {

				$description = "dear customer \n  your invoice with the ref ( " . $object->ref . ") was paid \n";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, 'invoice paid !', $description, false, true);
				$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list, '', '', 0, 1, "text/html");
				$result = $mailfile->sendfile();
				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				//send_sms("+212637342771", $message);
				echo "sms sent : \n\n";
			} else {
				return null;
			}
			$facture_id = $object->id;
			last_action("send_mail_after_classify_paid", $conf, $facture_id);
		}
	} else {
		return null;
	}
}
function send_email_after_classify_paid_partialy($object, $paiement, $conf, $langs, $mysoc)
{


	$result = $object->fetch($object->id); // Reload to get new records
	$result = $object->fetch_thirdparty();
	// get factures all paiements

	// get total paid

	// get original amount
	$original_amount = number_format($object->total_ttc, 2);
	$original_amount = number_format($original_amount, 2);

	// get left to pay
	$total_paid = number_format(payed_amount($conf, $object->id), 2);
	if (!$total_paid) {
		$total_paid = 0;
	}
	$left_to_pay = $object->total_ttc - $total_paid;
	$left_to_pay = number_format($left_to_pay, 2, '.', '');

	// get deleted paiement full date with the time
	$deleted_date = $paiement->datepaye;
	// format date
	$deleted_date = date('d/m/Y', $deleted_date);
	// check the status of the invoice if its started or not


	// get the currency of the invoice
	$currency = $object->multicurrency_code;

	$message = "dear customer \n  your invoice with the ref ( " . $object->ref . ") was partally paid \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . number_format($original_amount) . $currency . " \n the left to pay is :" . 0 . $currency . " \n  current invoice status is : paid \n thank you";
	// send email
	$sendto = $object->thirdparty->email;
	$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
	$subject = '[' . $mysoc->name . '] ' . $langs->trans('Invoice') . ' ' . $object->ref;

	$filename_list = array();
	$mimefilename_list = array();
	$mimetype_list = array();

	// Generate PDF
	$resultPDF = $object->generateDocument($object->modelpdf, $langs);
	if ($resultPDF <= 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $object->ref, preg_quote($object->ref, '/') . '[^\-]+');
		$file = $fileparams['fullname'];

		$filename_list[] = $file;
		$mimefilename_list[] = $object->ref . '.pdf';
		$mimetype_list[] = 'application/pdf';
	}

	// Send email
	if (!empty($sendto) && !empty($from)) {
		require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
		$okay_mail = check($conf, "email_after_classify_as_paid_partially");
		$okay_sms = check($conf, "sms_after_classify_as_paid_partially");
		if ($okay_mail) {

			$description = "dear customer \n  your invoice with the ref ( " . $object->ref . ") was partally paid \n";
			$message_ = email_template($object, $currency, $total_paid, 0, 'invoice partialy paid  !', $description, false, true);
			echo "<pre>";
			echo "<pre>";
			echo $message_;
			if ($result) {
				setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
			} else {
				setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
			}
		}
		if ($okay_sms) {
			//send_sms("+212637342771", $message);
			echo "sms message :\n\n ";
			echo $message;
		} else {
			return null;
		}
		$facture_id = $object->id;
		last_action("send_mail_after_classify_paid_partialy", $conf, $facture_id);
	}
}
function send_email_after_validate_invoice($object, $conf, $langs, $mysoc)
{
	if ($conf->global->MAIN_MODULE_MAIL == 1) {
		// select * from llx_auto_send
		$result = $object->fetch($object->id); // Reload to get new records
		$result = $object->fetch_thirdparty();
		// get factures all paiements
		// get montatnt of the deleted paiement
		// get id of the deleted paiement

		// get total paid

		// get original amount
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');
		// get left to pay
		$total_paid = payed_amount($conf, $object->id);
		if (!$total_paid) {
			$total_paid = 0;
		}
		$left_to_pay = $object->total_ttc - $total_paid;
		$left_to_pay = number_format($left_to_pay, 2, '.', '');
		// get deleted paiement full date with the time
		// format date
		// check the status of the invoice if its started or not


		// get the currency of the invoice
		$currency = $object->multicurrency_code;
		// get the validation date and time
		$validation_date = $object->date_validation;
		$validation_date = date('d/m/Y', $validation_date);
		$message = "dear customer \n  your invoice with the ref ( " . $object->ref . ") was created \n the current total paid is :" . $total_paid . $currency . " \n the total amount is :" . number_format($original_amount) . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n creation date :" . $validation_date . " \n thank you";
		// send email
		$sendto = $object->thirdparty->email;
		$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
		$subject = '[' . $mysoc->name . '] ' . $langs->trans('Invoice') . ' ' . $object->ref;
		$phone_number = $object->thirdparty->phone;

		$filename_list = array();
		$mimefilename_list = array();
		$mimetype_list = array();

		// Generate PDF
		$resultPDF = $object->generateDocument($object->modelpdf, $langs);
		if ($resultPDF <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $object->ref, preg_quote($object->ref, '/') . '[^\-]+');
			$file = $fileparams['fullname'];

			$filename_list[] = $file;
			$mimefilename_list[] = $object->ref . '.pdf';
			$mimetype_list[] = 'application/pdf';
		}

		// Send email

		// Send email

		if (!empty($sendto) && !empty($from)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$okay_mail = check($conf, "email_after_create_invoice");
			$okay_sms = check($conf, "sms_after_create_invoice");
			if ($okay_mail) {
				$description = "dear customer \n  your invoice with the ref ( " . $object->ref . ") was created \n";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, 'invoice created !', $description, true, true);
				echo "<pre>";
				echo $message_;
				$mailfile = new CMailFile($subject, $sendto, $from, $message_, '', '', '', '', '', 0, 1, 'text/html');




				$result = $mailfile->sendfile();

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);

				echo "sms sent : \n\n";
				echo $message;
			} else {
				return null;
			}
			$facture_id = $object->id;
			last_action("send_mail_after_validate_invoice", $conf, $facture_id);
		}
	} else {
		return null;
	}
}
function send_email_after_enter_payment($object, $conf, $langs, $mysoc)
{


	if ($conf->global->MAIN_MODULE_MAIL == 1) {
		$result = $object->fetch($object->id); // Reload to get new records
		$result = $object->fetch_thirdparty();
		$invoice_paid = payed_amount($conf, $object->id);
		// get factures all paiements
		$factures = $object->getListOfPayments();
		// get montatnt of the deleted paiement
		// get id of the deleted paiement

		// get total paid
		// get original amount
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');

		// get left to pay
		$total_paid = payed_amount($conf, $object->id);
		if (!$total_paid) {
			$total_paid = 0;
		}
		$left_to_pay = $object->total_ttc - $total_paid;
		$left_to_pay = number_format($left_to_pay, 2, '.', '');

		// get payment ref


		// get the currency of the invoice
		$currency = $object->multicurrency_code;
		$invoice_status = $object->getLibStatut(2, $total_paid);

		$message = "dear customer \n a payment was added to the invoice with the ref ( " . $object->ref . ")"  . " \n the current total paid is :" . number_format($total_paid)  . $currency . " \n the original amount is :" . number_format($original_amount) . $currency . " \n the left to pay is :" . $left_to_pay . $currency . "\n the invoice status is :" . $invoice_status . " \n thank you";


		// get invoice status
		// format date

		// send email
		$sendto = $object->thirdparty->email;
		$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
		$subject = '[' . $mysoc->name . '] ' . $langs->trans('Invoice') . ' ' . $object->ref;

		$filename_list = array();
		$mimefilename_list = array();
		$mimetype_list = array();

		// Generate PDF
		$resultPDF = $object->generateDocument($object->modelpdf, $langs);
		if ($resultPDF <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $object->ref, preg_quote($object->ref, '/') . '[^\-]+');
			$file = $fileparams['fullname'];

			$filename_list[] = $file;
			$mimefilename_list[] = $object->ref . '.pdf';
			$mimetype_list[] = 'application/pdf';
		}

		// Send email
		if (!empty($sendto) && !empty($from)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$okay_mail = check($conf, "email_afteradd_payment");
			$okay_sms = check($conf, "sms_afteradd_payment");
			if ($okay_mail) {
				// get payment ref
				$payment_ref = $payment->ref;

				$description = "dear customer \n a payment  was added to the invoice with the ref ( " . $object->ref . ")";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, "payment created (" . $invoice_status . ") !", $description, false, true);

				echo "<pre>";
				echo $message_;

				$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list, '', '', 0, 1, "text/html");
				$result = $mailfile->sendfile();

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);

				echo "sms sent : \n\n";
				echo $message;
			} else {
				return null;
			}
			$facture_id = $object->id;

			last_action("send_mail_after_enter_payment", $conf, $facture_id);
		}
	} else {
		return null;
	}
}
// ********************************* end of invoice *********************************
// ********************************* start of commande *********************************
// create commande
function send_email_after_create_commande($object, $conf, $langs, $mysoc)
{
	if ($conf->global->MAIN_MODULE_MAIL == 1) {
		$result = $object->fetch($object->id); // Reload to get new records
		$result = $object->fetch_thirdparty();
		$invoice_paid = payed_amount($conf, $object->id);
		// get factures all paiements
		// get montatnt of the deleted paiement
		// get id of the deleted paiement

		// get total paid
		// get original amount
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');

		// get left to pay
		$total_paid = payed_amount($conf, $object->id);
		if (!$total_paid) {
			$total_paid = 0;
		}
		$left_to_pay = $object->total_ttc - $total_paid;
		$left_to_pay = number_format($left_to_pay, 2, '.', '');

		// get payment ref
		$payment_ref = $object->ref;



		// get the currency of the invoice
		$currency = $object->multicurrency_code;

		$message = "dear customer \n the commande with the ref (" . $object->ref . ") was created "  . " \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . number_format($original_amount) . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n thank you";
		if ($left_to_pay == $original_amount) {
			$message .= " \n the invoice status is : not paid";
		} else {
			$message .= " \n the invoice  status is : started";
		}


		// format date

		// send email
		$sendto = $object->thirdparty->email;
		$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
		$subject = '[' . $mysoc->name . '] ' . $langs->trans('Invoice') . ' ' . $object->ref;

		$filename_list = array();
		$mimefilename_list = array();
		$mimetype_list = array();

		// Generate PDF
		$resultPDF = $object->generateDocument($object->modelpdf, $langs);
		if ($resultPDF <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $object->ref, preg_quote($object->ref, '/') . '[^\-]+');
			$file = $fileparams['fullname'];

			$filename_list[] = $file;
			$mimefilename_list[] = $object->ref . '.pdf';
			$mimetype_list[] = 'application/pdf';
		}

		// Send email
		if (!empty($sendto) && !empty($from)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$okay_mail = check($conf, "email_after_validate_commande");
			$okay_sms = check($conf, "sms_after_validate_commande");
			if ($okay_mail) {
				// get payment ref
				$payment_ref = $payment->ref;

				$description = $message = "dear customer \n the commande with the ref (" . $object->ref . ") was created ";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, "commande created !", $description, true, false);

				echo "<pre>";
				echo $message_;
				$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list, '', '', 0, 1, "text/html");
				$result = $mailfile->sendfile();

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);

				echo "sms sent : \n\n";
				echo $message;
			} else {
				return null;
			}
		}
	} else {
		return null;
	}
}
function send_email_after_send_commande($object, $conf, $langs, $mysoc)
{
	if ($conf->global->MAIN_MODULE_MAIL == 1) {
		$result = $object->fetch($object->id); // Reload to get new records
		$result = $object->fetch_thirdparty();
		$invoice_paid = payed_amount($conf, $object->id);
		// get factures all paiements
		// get montatnt of the deleted paiement
		// get id of the deleted paiement

		// get total paid
		// get original amount
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');

		// get left to pay
		$total_paid = payed_amount($conf, $object->id);
		if (!$total_paid) {
			$total_paid = 0;
		}
		$left_to_pay = $object->total_ttc - $total_paid;
		$left_to_pay = number_format($left_to_pay, 2, '.', '');
		$traking_number = $object->tracking_number;
		// get payment ref
		$payment_ref = $object->ref;



		// get the currency of the invoice
		$currency = $object->multicurrency_code;

		$message = "dear customer \n the commande with the ref (" . $object->commande->ref . ") was send "  . " \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . number_format($original_amount) . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n thank you";



		// format date

		// send email
		$sendto = $object->thirdparty->email;
		$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
		$subject = '[' . $mysoc->name . '] ' . $langs->trans('Invoice') . ' ' . $object->ref;

		$filename_list = array();
		$mimefilename_list = array();
		$mimetype_list = array();

		// Generate PDF
		$resultPDF = $object->generateDocument($object->modelpdf, $langs);
		if ($resultPDF <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $object->ref, preg_quote($object->ref, '/') . '[^\-]+');
			$file = $fileparams['fullname'];

			$filename_list[] = $file;
			$mimefilename_list[] = $object->ref . '.pdf';
			$mimetype_list[] = 'application/pdf';
		}

		// Send email
		if (!empty($sendto) && !empty($from)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$okay_mail = check($conf, "email_after_sending_commande");
			$okay_sms = check($conf, "sms_after_sending_commande");
			if ($okay_mail) {

				$description = $message = "dear customer \n the commande with the ref (" . $object->commande->ref . ") was sent ";
				if ($traking_number) {
					$description .= " \n the tracking number is : " . $traking_number;
					$message .= " \n the tracking number is : " . $traking_number;
				}

				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, "commande was sent !", $description, false, false);

				$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list, '', '', 0, 1, "text/html");
				$result = $mailfile->sendfile();

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {

				send_sms("+212637342771", $message);
			} else {
				return null;
			}
		}
	} else {
		return null;
	}
}
//*************************** */
function facture_resend_notification($object, $payment, $conf, $lang, $mysoc)
{
	// create a connection to the database
	$database_host = $conf->db->host;
	$database_name = $conf->db->name;
	$database_user = $conf->db->user;
	$database_password = $conf->db->pass;
	$database_port = $conf->db->port;
	// get the invoice data 
	$invoice_id = $object->id;
	$db = new mysqli($database_host, $database_user, $database_password, $database_name, $database_port);
	// get the invoice data
	$sql = "SELECT * FROM llx_facture WHERE rowid = $invoice_id";
	$result = $db->query($sql);
	$invoice_data = $result->fetch_assoc();
	// get the customer data
	$customer_id = $invoice_data['fk_soc'];
	$sql = "SELECT * FROM llx_societe WHERE rowid = $customer_id";
	$result = $db->query($sql);
	$customer_data = $result->fetch_assoc();
	// start the work 
	$last_action = $invoice_data['last_action'];
	if (!$last_action) {
		// error notification
		setEventMessages('Error: there is no actions in this invoice', null, 'errors', 0, 'direct');
	} else {
		if ($last_action == "send_email_on_payment_delete") {
			send_email_on_payment_delete($object, $payment, $conf, $lang, $mysoc);
		} elseif ($last_action == "send_mail_after_delete_invoice") {
			send_mail_after_delete_invoice($object, $conf, $lang, $mysoc);
		} elseif ($last_action == "send_email_after_classify_abandoned") {
			send_email_after_classify_abandoned($object, $conf, $lang, $mysoc);
		} elseif ($last_action == "send_mail_after_reopen") {
			send_mail_after_reopen($object, $payment, $conf, $lang, $mysoc);
		} elseif ($last_action == "send_email_after_classify_paid") {
			send_email_after_classify_paid($object, $payment, $conf, $lang, $mysoc);
		} elseif ($last_action == "send_email_after_classify_paid_partialy") {
			send_email_after_classify_paid_partialy($object, $payment, $conf, $lang, $mysoc);
		} elseif ($last_action == "send_email_after_validate_invoice") {
			send_email_after_validate_invoice($object, $conf, $lang, $mysoc);
		} elseif ($last_action == "send_email_after_enter_payment") {
			send_email_after_enter_payment($object, $conf, $lang, $mysoc);
		}
	}
}
