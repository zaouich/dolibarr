<?php

use phpDocumentor\Reflection\DocBlock\Description;
use Twilio\Rest\Client;

require_once(__DIR__ . '/../../../includes/twilio/sdk/src/Twilio/autoload.php');
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
	$html = '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-family: Arial, sans-serif; font-size: 16px; line-height: 1.4; color: #333333;">';
	$html .= '<tr><td align="center"><h1 style="margin: 0; font-size: 24px; font-weight: bold;">' . $title . '</h1></td></tr>';
	$html .= '<tr><td align="center"><p style="margin: 0;">' . $description . '</p></td></tr>';

	// Add payment information table if variable is true
	if ($payment_infos) {
		$html .= '<tr><td><table border="1" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #dddddd; margin-top: 20px;">';
		$html .= '<caption style="text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 20px;">Your Payment Infos</caption>';
		$html .= '<tr><td align="center" width="50%" style="padding: 10px;">Paid</td><td align="center" width="50%" style="padding: 10px;">' . $total_paid_amount . '</td></tr>';
		$html .= '<tr><td align="center" width="50%" style="padding: 10px;">Left Pay</td><td align="center" width="50%" style="padding: 10px;">' . $remaning_amount . '</td></tr>';
		$html .= '<tr><td align="center" width="50%" style="padding: 10px;">Paid</td><td align="center" width="50%" style="padding: 10px;">' . $total_paid_amount . '</td></tr>';
		$html .= '</table></td></tr>';
		$html .= '<caption style="text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 20px;">Invoice Infos</caption>';
	}

	// Add product information table(s) if variable is true and there are products to display
	if ($products_infos && count($invoice_products) > 0) {
		foreach ($invoice_products as $product) {
			$html .= '<tr><td><table border="1" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #dddddd; margin-top: 20px;">';
			$html .= '<tr><td align="center" width="50%" style="padding: 10px;">Product</td><td align="center" width="50%" style="padding: 10px;">' . $product->product_label . '</td></tr>';
			$html .= '<tr><td align="center" width="50%" style="padding: 10px;">Quantity</td><td align="center" width="50%" style="padding: 10px;">' . $product->qty . '</td></tr>';
			$html .= '<tr><td align="center" width="50%" style="padding: 10px;">Price</td><td align="center" width="50%" style="padding: 10px;">' . $product->subprice . '</td></tr>';
			$html .= '<tr><td align="center" width="50%" style="padding: 10px;">Total</td><td align="center" width="50%" style="padding: 10px;">' . $product->total_ht . '</td></tr>';
			$html .= '</table></td></tr>';
		}
	}

	// Finish building the HTML code
	$html .= '</table>';

	// Output the HTML code
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
	$token = "decfbdf08f46d10a1d94e9a645f088b9";
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
		$left_to_pay = $object->total_ttc - $total_paid;
		$deleted_montant = $paiement->montant;
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');

		$deleted_date = $paiement->datepaye;
		$deleted_date = date('d/m/Y', $deleted_date);
		$currency = $object->multicurrency_code;

		$message = "dear customer \n the payment with the ref (" . $deleted_id . ") was deleted from the invoice with the ref ( " . $object->ref . ") \n the amount of the deleted payment is :" . $deleted_montant . $currency . " \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . $original_amount . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n the deleted payment date is :" . $deleted_date . "";
		if ($left_to_pay == $original_amount) {
			$message .= " \n the invoice status is : not paid";
		} else {
			$message .= " \n the invoice is status is : started";
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
			$okay_mail = check($conf, "email_after_delete_payment");
			$okay_sms = check($conf, "sms_after_delete_payment");
			if ($okay_mail) {
				$description = "dear customer \n the payment with the ref (" . $deleted_id . ") was deleted from the invoice with the ref ( " . $object->ref . ")";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, "payment deleted !", $description, false, true);
				$message_ .= "<table>
				<thead>
				<tr>
					<th>deleted payment</th>
				<tr>
				</thead>
				<tbody>
				<tr>
					<td data-label='deleted amount'>" . $deleted_montant . "</td>
				<tr>
				</tbody>
				</table>";
				echo $message_;
				$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list);
				$result = $mailfile->sendfile();

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);
				echo "***********************************";
				echo "sms sent";
				echo $message;
				exit;
				exit;
			} else {
				return null;
			}
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
					echo $message_;
					$mailfile = new CMailFile($subject, $sendto, $from, $message_);
					$result = $mailfile->sendfile();
					if ($result) {
						setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
					} else {
						setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
					}
				}
				if ($okay_sms) {
					send_sms("+212637342771", $message);
					echo "***********************************";
					echo "sms sent";
					echo $message;
					exit;
				} else {
					return null;
				}
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
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, 'invoice canceled !', $description, false, false);
				echo $message_;
				$mailfile = new CMailFile($subject, $sendto, $from, $message_);
				$result = $mailfile->sendfile();
				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);
				echo "***********************************";
				echo "sms sent";

				echo $message;
				exit;
			} else {
				return null;
			}
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


		$message = "dear customer \n  the invoice with the ref ( " . $object->ref . ") \n is reopened : \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . $original_amount . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n thank you";
		if ($left_to_pay == $original_amount) {
			echo 'the invoice is not started';
			$message .= " \n the invoice status is : not paid";
		} else {
			echo 'the invoice is started';
			$message .= " \n the invoice is status is  started";
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
			$okay_mail = check($conf, "email_after_reopen_invoice");
			$okay_sms = check($conf, "sms_after_reopen_invoice");
			if ($okay_mail) {
				// 
				$description = "dear customer \n  your invoice with the ref ( " . $object->ref . ") is reopened \n";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, 'invoice reopened !', $description, false, true);
				echo $message_;
				$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list);
				$result = $mailfile->sendfile();

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);
				echo "***********************************";
				echo "sms sent";
				echo $message;
				exit;
			} else {
				return null;
			}
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

		$message = "dear customer \n  your invoice with the ref ( " . $object->ref . ") is fully paid \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . $original_amount . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n thank you";
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

				$description = "dear customer \n  your invoice with the ref ( " . $object->ref . ") is paid \n";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, 'invoice paid !', $description, false, true);
				echo $message_;
				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				//send_sms("+212637342771", $message);
				echo "***********************************";
				echo "sms sent";
				echo $message;
				exit;
			} else {
				return null;
			}
		}
	} else {
		return null;
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
		$message = "dear customer \n  your invoice with the ref ( " . $object->ref . ") was created \n the current total paid is :" . $total_paid . $currency . " \n the total amount is :" . $original_amount . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n creation date :" . $validation_date . " \n thank you";
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
				$description = "dear customer \n  your invoice with the ref ( " . $object->ref . ") is created \n";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, 'invoice created !', $description, true, true);
				echo $message_;
				$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list);
				// Set content-type header for sending HTML email 
				$mailfile->headers['Content-Type'] = 'text/html; charset=UTF-8';
				// make email read css
				$mailfile->headers['X-MSMail-Priority'] = 'High';
				$mailfile->headers['X-Mailer'] = 'PHP/' . phpversion();
				$mailfile->headers['X-Priority'] = '3';



				$result = $mailfile->sendfile();

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);
				echo "***********************************";
				echo "sms sent";
				echo $message;
				exit;
			} else {
				return null;
			}
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
		var_dump($object->id);
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
		$payment_ref = $object->ref;



		// get the currency of the invoice
		$currency = $object->multicurrency_code;

		$message = "dear customer \n the payment with the ref (" . $payment_ref . ") was created in the invoice with the ref ( " . $object->ref . ")"  . " \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . $original_amount . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n thank you";
		if ($left_to_pay == $original_amount) {
			echo 'the invoice is not started';
			$message .= " \n the invoice status is : not paid";
		} else {
			echo 'the invoice is started';
			$message .= " \n the invoice is status is : started";
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
			$okay_mail = check($conf, "email_afteradd_payment");
			$okay_sms = check($conf, "sms_afteradd_payment");
			if ($okay_mail) {
				// get payment ref
				$payment_ref = $payment->ref;

				$description = "dear customer \n the payment with the ref (" . $payment_ref . ") was added to the invoice with the ref ( " . $object->ref . ")";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, "payment created !", $description, false, true);

				echo $message_;
				exit;
				$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list);
				$result = $mailfile->sendfile();

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);
				echo "***********************************";
				echo "sms sent";
				echo $message;
				exit;
			} else {
				return null;
			}
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
		var_dump($object->id);
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

		$message = "dear customer \n the commande with the ref (" . $object->ref . ") was created "  . " \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . $original_amount . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n thank you";
		if ($left_to_pay == $original_amount) {
			echo 'the invoice is not started';
			$message .= " \n the invoice status is : not paid";
		} else {
			echo 'the invoice is started';
			$message .= " \n the invoice is status is : started";
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

				echo $message_;
				$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list);
				$result = $mailfile->sendfile();

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);
				echo "***********************************";
				echo "sms sent";
				echo $message;
				exit;
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
		var_dump($object->id);
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

		$message = "dear customer \n the commande with the ref (" . $object->commande->ref . ") was send "  . " \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . $original_amount . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n thank you";
		if ($left_to_pay == $original_amount) {
			echo 'the invoice is not started';
			$message .= " \n the invoice status is : not paid";
		} else {
			echo 'the invoice is started';
			$message .= " \n the invoice is status is : started";
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
			$okay_mail = check($conf, "email_after_sending_commande");
			$okay_sms = check($conf, "sms_after_sending_commande");
			if ($okay_mail) {
				// get payment ref
				$payment_ref = $payment->ref;

				$description = $message = "dear customer \n the commande with the ref (" . $object->commande->ref . ") was sent ";
				$message_ = email_template($object, $currency, $total_paid, $left_to_pay, "commande " . $traking_number . " sent !", $description, false, false);

				echo $message_;
				$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list);
				$result = $mailfile->sendfile();

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
			}
			if ($okay_sms) {
				send_sms("+212637342771", $message);
				echo "***********************************";
				echo "sms sent";
				echo $message;
				exit;
			} else {
				return null;
			}
		}
	} else {
		return null;
	}
}
