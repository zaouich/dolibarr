<?php



use Twilio\Rest\Client;

require_once(__DIR__ . '/../../../includes/twilio/sdk/src/Twilio/autoload.php');
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
				//$mailfile = new CMailFile($subject, $sendto, $from, $message, $filename_list, $mimetype_list, $mimefilename_list);
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
		$subject = '[' . $mysoc->name . '] ' . $langs->trans('Invoice') . ' ' . $object->ref;
		// Send email
		if (!empty($sendto) && !empty($from)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$okay_mail = check($conf, "email_after_cancel_payment");
			$okay_sms = check($conf, "sms_after_cancel_payment");
			if ($okay_mail) {
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
		$link_to_download_attachment = '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=facture&file=' . $object->ref . '/' . $object->ref . '.pdf">download the invoice</a>';
		// Send email
		if (!empty($sendto) && !empty($from)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$okay_mail = check($conf, "email_after_reopen_invoice");
			$okay_sms = check($conf, "sms_after_reopen_invoice");
			if ($okay_mail) {

				$amount = "<table  border='1' style='border-collapse: collapse;> '
		<tr>
		<th>Amount</th>
		<th>Value</th>
		</tr>
		<tr>
		<td>Original Amount</td>
		<td>" . $original_amount . $currency . "</td>
		</tr>
		<tr>
		<td>Total Paid</td>
		<td>" . $total_paid . $currency . "</td>
		</tr>
		<tr>
		<td>Left to Pay</td>
		<td>" . $left_to_pay . $currency . "</td>
		</tr>

		</table>";

				$products = $object->lines;

				$table = "<table border='1' style='border-collapse: collapse;'>
		<tr>
		<th>Product</th>
		<th>Quantity</th>
		<th>Price</th>
		<th>Total</th>
		<th>Discount</th>
		<th>Vat</th>
		</tr>";
				foreach ($products as $product) {
					$table .= "<tr>
			<td>" . $product->product_label . "</td>
			<td>" . $product->qty . "</td>
			<td>" . $product->subprice . "</td>
			<td>" . $product->total_ht . "</td>
			<td>" . $product->remise_percent . "</td>
			<td>" . $product->tva_tx . "</td>
			</tr>";
				}
				$table .= "</table>";
				$message_ = $table . $amount;
				echo $message_;
				//$mailfile = new CMailFile($subject, $sendto, $from, $message_, $filename_list, $mimetype_list, $mimefilename_list);
				//$result = $mailfile->sendfile();

				if ($result) {
					setEventMessages($langs->trans('MailSuccessfulySent', $from, $sendto), null, 'mesgs');
				} else {
					setEventMessages('ErrorFailedToSendMail, From: ' . $from . ', To: ' . $sendto, null, 'errors', 0, 'direct');
				}
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

				$amount = "<table  border='1' style='border-collapse: collapse;> '
		<tr>
		<th>Amount</th>
		<th>Value</th>
		</tr>
		<tr>
		<td>Original Amount</td>
		<td>" . $original_amount . $currency . "</td>
		</tr>
		<tr>
		<td>Total Paid</td>
		<td>" . $total_paid . $currency . "</td>
		</tr>
		<tr>
		<td>Left to Pay</td>
		<td>" . $left_to_pay . $currency . "</td>
		</tr>

		</table>";

				$products = $object->lines;

				$table = "<table border='1' style='border-collapse: collapse;'>
		<tr>
		<th>Product</th>
		<th>Quantity</th>
		<th>Price</th>
		<th>Total</th>
		<th>Discount</th>
		<th>Vat</th>
		</tr>";
				foreach ($products as $product) {
					$table .= "<tr>
			<td>" . $product->product_label . "</td>
			<td>" . $product->qty . "</td>
			<td>" . $product->subprice . "</td>
			<td>" . $product->total_ht . "</td>
			<td>" . $product->remise_percent . "</td>
			<td>" . $product->tva_tx . "</td>
			</tr>";
				}
				$table .= "</table>";
				$message_ = $table . $amount;
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
function send_email_after_classify_paid_partialy($object, $paiement, $conf, $langs, $mysoc)

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
		$left_to_pay = 0;

		// get deleted paiement full date with the time
		$deleted_date = $paiement->datepaye;
		// format date
		$deleted_date = date('d/m/Y', $deleted_date);
		// check the status of the invoice if its started or not


		// get the currency of the invoice
		$currency = $object->multicurrency_code;

		$message = "dear customer \n  your invoice with the ref ( " . $object->ref . ") is partially paid \n the current total paid is :" . $total_paid . $currency . " \n the original amount is :" . $original_amount . $currency . " \n the left to pay is :" . $left_to_pay . $currency . " \n thank you";
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
				$amount = "<table  border='1' style='border-collapse: collapse;> '
		<tr>
		<th>Amount</th>
		<th>Value</th>
		</tr>
		<tr>
		<td>Original Amount</td>
		<td>" . $original_amount . $currency . "</td>
		</tr>
		<tr>
		<td>Total Paid</td>
		<td>" . $total_paid . $currency . "</td>
		</tr>
		<tr>
		<td>Left to Pay</td>
		<td>" . $left_to_pay . $currency . "</td>
		</tr>

		</table>";

				$products = $object->lines;

				$table = "<table border='1' style='border-collapse: collapse;'>
		<tr>
		<th>Product</th>
		<th>Quantity</th>
		<th>Price</th>
		<th>Total</th>
		<th>Discount</th>
		<th>Vat</th>
		</tr>";
				foreach ($products as $product) {
					$table .= "<tr>
			<td>" . $product->product_label . "</td>
			<td>" . $product->qty . "</td>
			<td>" . $product->subprice . "</td>
			<td>" . $product->total_ht . "</td>
			<td>" . $product->remise_percent . "</td>
			<td>" . $product->tva_tx . "</td>
			</tr>";
				}
				$table .= "</table>";
				$message_ = $table . $amount;
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
				$amount = "<table  border='1' style='border-collapse: collapse;> '
		<tr>
		<th>Amount</th>
		<th>Value</th>
		</tr>
		<tr>
		<td>Original Amount</td>
		<td>" . $original_amount . $currency . "</td>
		</tr>
		<tr>
		<td>Total Paid</td>
		<td>" . $total_paid . $currency . "</td>
		</tr>
		<tr>
		<td>Left to Pay</td>
		<td>" . $left_to_pay . $currency . "</td>
		</tr>

		</table>";

				$products = $object->lines;

				$table = "<table border='1' style='border-collapse: collapse;'>
		<tr>
		<th>Product</th>
		<th>Quantity</th>
		<th>Price</th>
		<th>Total</th>
		<th>Discount</th>
		<th>Vat</th>
		</tr>";
				foreach ($products as $product) {
					$table .= "<tr>
			<td>" . $product->product_label . "</td>
			<td>" . $product->qty . "</td>
			<td>" . $product->subprice . "</td>
			<td>" . $product->total_ht . "</td>
			<td>" . $product->remise_percent . "</td>
			<td>" . $product->tva_tx . "</td>
			</tr>";
				}
				$table .= "</table>";
				$message_ = $table . $amount;
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
