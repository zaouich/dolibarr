<?php

use phpDocumentor\Reflection\DocBlock\Description;
use Twilio\Rest\Client;

require_once(__DIR__ . '/../../../includes/twilio/sdk/src/Twilio/autoload.php');
function get_left_to_pay($conf, $invoice_id)
{
	$database_name = $conf->db->name;
	$database_user_name = $conf->db->user;
	$database_password = $conf->db->pass;
	$database_host = $conf->db->host;
	$db = new mysqli($database_host, $database_user_name, $database_password, $database_name);
	if ($db->connect_error) {
		echo "not connected";
		die("Connection failed: " . $db->connect_error);
	} else {
		echo "connected";
	}
	// Execute the SQL statement and fetch the data
	$sql = "SELECT count(*) FROM llx_paiement_facture WHERE fk_facture = $invoice_id";
	echo $invoice_id;
	$result = $db->query($sql);
	echo "result of query : " . $result;
	var_dump($result);

	exit;
	return $result;
}
function email_template($invoice, $currency, $total_paid_amount, $remaning_amount, $title, $description, $products_infos, $payment_infos)
{
	echo $total_paid_amount;
	exit;
	// 1) create the amount table
	$amount = "";
	if ($payment_infos) {
		$amount = "
		<h1>your paiment infos</h1>
		<table  border='1' style='border-collapse: collapse;> '
	<tr>
	<th>Amount</th>
	<th>Value</th>
	</tr>
	<tr>
	<td>Original Amount</td>
	<td>" . $invoice->total_ht . $currency . "</td>
	</tr>
	<tr>
	<td>Total Paid</td>
	<td>" . $total_paid_amount . $currency . "</td>
	</tr>
	<tr>
	<td>Left to Pay</td>
	<td>" . $remaning_amount . $currency . "</td>
	</tr>

	</table>";
	} else {
		$amount = "";
	}

	// 2) create the invoice table
	$invoice_products = $invoice->lines;
	$products = "";
	if ($products_infos) {
		$products = "
		<h1>the invoice infos : </h1>
		<table  border='1' style='border-collapse: collapse;> '
		<tr>
		<th>Product</th>
		<th>Quantity</th>
		<th>Price</th>
		<th>Total</th>
		<th>Discount</th>
		<th>vat</th>
		</tr>";
		foreach ($invoice_products as $product) {
			$products .= "<tr>
	<td>" . $product->product_label . "</td>
	<td>" . $product->qty . "</td>
	<td>" . $product->subprice . $currency . "</td>
	<td>" . $product->total_ht . $currency . "</td>
	<td>" . $product->remise_percent . "</td>
	<td>" . $product->tva_tx . "</td>
	</tr></table>";
		}
	} else {
		$products = "";
	}
	// 3) create template header
	$template_header = "<div>";
	$template_header .= "<h1>" . $title . "</h1>";
	$template_header .= "<h3>" . $description . "</h3>";
	$template_header .= "<div>";
	// 3) create the message then return it
	$email_message =  $template_header . $products . $amount;
	return $email_message;
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
		// get factures all paiements
		$factures = $object->getListOfPayments();
		// get montatnt of the deleted paiement
		$deleted_montant = $paiement->montant;
		// get id of the deleted paiement
		$deleted_id = $paiement->ref;
		// get facture id
		$invoice_id = $paiement->facid;
		echo "*************************";
		echo "<pre>	";
		echo "*************INVOICE ID";
		echo $object->ref;
		echo get_left_to_pay($conf, $object->fk_facture);
		exit;
		// get unpaid amount
		$unpaid_amount = $object->total_ttc - $paiement->montant;
		echo "<pre>	";
		echo "\n*************total paid\n";
		echo get_left_to_pay($conf, $invoice_id);
		echo "\n*************unpaid_amount\n";
		echo $unpaid_amount;
		echo "\n*************total\n";
		echo $object->total_ttc;
		echo "\n*************DELETED AMOUNT\n";
		echo $deleted_montant;
		echo "\n*************PAIMENT\n";
		var_dump($paiement);
		echo "\n*************INVOICE\n";
		var_dump($object->ref);
		// deleted 12
		// ALREADY PAID 2.5
		// LEFT 897.5
		exit;

		// get original amount
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');

		// get left to pay
		$left_to_pay = $original_amount - $total_paid;
		$left_to_pay = number_format($left_to_pay, 2, '.', '');

		// get deleted paiement full date with the time
		$deleted_date = $paiement->datepaye;
		// format date
		$deleted_date = date('d/m/Y', $deleted_date);
		// check the status of the invoice if its started or not


		// get the currency of the invoice
		$currency = $object->multicurrency_code;

		$message = "dear customer \n the payment with the ref (" . $deleted_id . ") was deleted from the invoice with the ref ( " . $object->ref . ") \n the amount of the deleted payment is :" . $deleted_montant . $currency . " \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . $original_amount . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n the deleted payment date is :" . $deleted_date . "";
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
			$okay_mail = check($conf, "email_after_delete_payment");
			$okay_sms = check($conf, "sms_after_delete_payment");
			if ($okay_mail) {
				$description = "dear customer \n the payment with the ref (" . $deleted_id . ") was deleted from the invoice with the ref ( " . $object->ref . ")";
				$message_ = email_template($object, $currency, $original_amount, $left_to_pay, "payment deleted !", $description, false, true);
				$message_ .= "<table>
				<tr>
					<th>deleted payment</th>
				<tr>
				<tr>
					<th>" . $deleted_montant . "</th>
				<tr>
				</table>";
				echo $message_;
				//$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list);
				//$result = $mailfile->sendfile();

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
	var_dump($response);
	exit;
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
					$message_ = email_template($object, $currency, $original_amount, $left_to_pay, 'invoice deleted !', $description, false, false);
					echo $message_;
					//$mailfile = new CMailFile($subject, $sendto, $from, $message);
					//$result = $mailfile->sendfile();
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
				$message_ = email_template($object, $currency, $original_amount, $left_to_pay, 'invoice canceled !', $description, false, false);
				echo $message_;
				//$mailfile = new CMailFile($subject, $sendto, $from, $message);
				//$result = $mailfile->sendfile();
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
		$total_paid = $object->getSommePaiement();
		if (!$total_paid) {
			$total_paid = 0;
		}
		// get original amount
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');

		// get left to pay
		$left_to_pay = $original_amount - $total_paid;
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
				$message_ = email_template($object, $currency, $original_amount, $left_to_pay, 'invoice reopened !', $description, false, true);
				echo $message_;
				//$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list);
				//$result = $mailfile->sendfile();

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
		$total_paid = $object->getSommePaiement();
		if (!$total_paid) {
			$total_paid = 0;
		}
		// get original amount
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');

		// get left to pay
		$left_to_pay = $original_amount - $total_paid;
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
				$message_ = email_template($object, $currency, $original_amount, $left_to_pay, 'invoice paid !', $description, false, true);
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
		$total_paid = $object->getSommePaiement();
		if (!$total_paid) {
			$total_paid = 0;
		}
		// get original amount
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');
		// get left to pay
		$left_to_pay = $original_amount - $total_paid;
		$left_to_pay = number_format($left_to_pay, 2, '.', '');
		// get deleted paiement full date with the time
		// format date
		// check the status of the invoice if its started or not


		// get the currency of the invoice
		$currency = $object->multicurrency_code;
		// get the validation date and time
		$validation_date = $object->date_validation;
		$validation_date = date('d/m/Y', $validation_date);
		$message = "dear customer \n  your invoice with the ref ( " . $object->ref . ") is created \n the current total paid is :" . $total_paid . $currency . " \n the total amount is :" . $original_amount . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n creation date :" . $validation_date . " \n thank you";
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
				$message_ = email_template($object, $currency, $original_amount, $left_to_pay, 'invoice created !', $description, true, true);
				echo $message_;
				exit;
				//$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list);
				//$result = $mailfile->sendfile();

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
function send_email_after_enter_payment($object, $conf, $langs, $mysoc)
{

	if ($conf->global->MAIN_MODULE_MAIL == 1) {
		$result = $object->fetch($object->id); // Reload to get new records
		$result = $object->fetch_thirdparty();
		$invoice_paid = get_left_to_pay($conf, $object->fk_facture);
		// get factures all paiements
		$factures = $object->getListOfPayments();
		// get montatnt of the deleted paiement
		// get id of the deleted paiement

		// get total paid
		$total_paid = $object->getSommePaiement();
		if (!$total_paid) {
			$total_paid = 0;
		}
		// get original amount
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');

		// get left to pay
		$left_to_pay = $original_amount - $total_paid;
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
			$okay_mail = check($conf, "email_after_delete_payment");
			$okay_sms = check($conf, "sms_after_delete_payment");
			if ($okay_mail) {
				// get payment ref
				$payment_ref = $payment->ref;

				$description = "dear customer \n the payment with the ref (" . $payment_ref . ") was added to the invoice with the ref ( " . $object->ref . ")";
				$message_ = email_template($object, $currency, $original_amount, $left_to_pay, "payment created !", $description, false, true);

				echo $message_;
				//$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list);
				//$result = $mailfile->sendfile();

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
// ********************************* end of invoice *********************************
// ********************************* start of commande *********************************
// create commande
function send_email_after_create_commande($object, $conf, $langs, $mysoc)
{
	if ($conf->global->MAIN_MODULE_MAIL == 1) {
		// select * from llx_auto_send
		$result = $object->fetch($object->id); // Reload to get new records
		$result = $object->fetch_thirdparty();
		// get factures all paiements
		// get montatnt of the deleted paiement
		// get id of the deleted paiement

		// get total paid
		$total_paid = $object->getSommePaiement();
		if (!$total_paid) {
			$total_paid = 0;
		}
		// get original amount
		$original_amount = $object->total_ttc;
		$original_amount = number_format($original_amount, 2, '.', '');
		// get left to pay
		$left_to_pay = $original_amount - $total_paid;
		$left_to_pay = number_format($left_to_pay, 2, '.', '');
		// get deleted paiement full date with the time
		// format date
		// check the status of the invoice if its started or not
		$okay_mail = check($conf, "email_after_create_commande");
		$okay_sms = check($conf, "sms_after_create_commande");
		if ($okay_mail) {
		}
	} else {
		return null;
	}
}
