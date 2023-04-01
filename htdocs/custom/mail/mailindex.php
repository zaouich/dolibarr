<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       mail/mailindex.php
 *	\ingroup    mail
 *	\brief      Home page of mail top menu
 */

// Execute a query

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("mail@mail"));

$action = GETPOST('action', 'alpha');


// Security check
//if (! $user->rights->mail->myobject->read) accessforbidden();
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0)
{
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$sql = "SELECT * FROM llx_auto_send WHERE id = 1";
$result = $db->query($sql);
$data = $result->fetch_assoc();

// Check whether each checkbox should be checked
$sms_after_create_invoice_checked = $data['sms_after_create_invoice'] ? 'checked' : '';
$email_after_create_invoice_checked = $data['email_after_create_invoice'] ? 'checked' : '';
$sms_after_delete_invoice_checked = $data['sms_after_delete_invoice'] ? 'checked' : '';
$email_after_delete_invoice_checked = $data['email_after_delete_invoice'] ? 'checked' : '';
$sms_afteradd_payment_checked = $data['sms_afteradd_payment'] ? 'checked' : '';
$email_afteradd_payment_checked = $data['email_afteradd_payment'] ? 'checked' : '';
$sms_after_delete_payment_checked = $data['sms_after_delete_payment'] ? 'checked' : '';
$email_after_delete_payment_checked = $data['email_after_delete_payment'] ? 'checked' : '';
$sms_after_cancel_payment_checked = $data['sms_after_cancel_payment'] ? 'checked' : '';
$email_after_cancel_payment_checked = $data['email_after_cancel_payment'] ? 'checked' : '';
$sms_after_reopen_invoice_checked = $data['sms_after_reopen_invoice'] ? 'checked' : '';
$email_after_reopen_invoice_checked = $data['email_after_reopen_invoice'] ? 'checked' : '';
$sms_after_classify_as_paid_checked = $data['sms_after_classify_as_paid'] ? 'checked' : '';
$email_after_classify_as_paid_checked = $data['email_after_classify_as_paid'] ? 'checked' : '';
$sms_after_classify_as_paid_partially_checked = $data['sms_after_classify_as_paid_partially'] ? 'checked' : '';
$email_after_classify_as_paid_partially_checked = $data['email_after_classify_as_paid_partially'] ? 'checked' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the values of the checkboxes from the form
    $sms_after_create_invoice = isset($_POST['sms_after_create_invoice']) ? 1 : 0;
    $email_after_create_invoice = isset($_POST['email_after_create_invoice']) ? 1 : 0;
    $sms_after_delete_invoice = isset($_POST['sms_after_delete_invoice']) ? 1 : 0;
    $email_after_delete_invoice = isset($_POST['email_after_delete_invoice']) ? 1 : 0;
    $sms_afteradd_payment = isset($_POST['sms_afteradd_payment']) ? 1 : 0;
    $email_afteradd_payment = isset($_POST['email_afteradd_payment']) ? 1 : 0;
    $sms_after_delete_payment = isset($_POST['sms_after_delete_payment']) ? 1 : 0;
    $email_after_delete_payment = isset($_POST['email_after_delete_payment']) ? 1 : 0;
    $sms_after_cancel_payment = isset($_POST['sms_after_cancel_payment']) ? 1 : 0;
    $email_after_cancel_payment = isset($_POST['email_after_cancel_payment']) ? 1 : 0;
    $sms_after_reopen_invoice = isset($_POST['sms_after_reopen_invoice']) ? 1 : 0;
    $email_after_reopen_invoice = isset($_POST['email_after_reopen_invoice']) ? 1 : 0;
    $sms_after_classify_as_paid = isset($_POST['sms_after_classify_as_paid']) ? 1 : 0;
    $email_after_classify_as_paid = isset($_POST['email_after_classify_as_paid']) ? 1 : 0;
    $sms_after_classify_as_paid_partially = isset($_POST['sms_after_classify_as_paid_partially']) ? 1 : 0;
    $email_after_classify_as_paid_partially = isset($_POST['email_after_classify_as_paid_partially']) ? 1 : 0;

    // Save the values to the database
	$sql = "UPDATE llx_auto_send SET 
        sms_after_create_invoice=$sms_after_create_invoice, 
        email_after_create_invoice=$email_after_create_invoice, 
        sms_after_delete_invoice=$sms_after_delete_invoice, 
        email_after_delete_invoice=$email_after_delete_invoice,
        sms_afteradd_payment=$sms_afteradd_payment, 
        email_afteradd_payment=$email_afteradd_payment, 
        sms_after_delete_payment=$sms_after_delete_payment, 
        email_after_delete_payment=$email_after_delete_payment,
        sms_after_cancel_payment=$sms_after_cancel_payment, 
        email_after_cancel_payment=$email_after_cancel_payment, 
        sms_after_reopen_invoice=$sms_after_reopen_invoice, 
        email_after_reopen_invoice=$email_after_reopen_invoice,
        sms_after_classify_as_paid=$sms_after_classify_as_paid, 
        email_after_classify_as_paid=$email_after_classify_as_paid, 
        sms_after_classify_as_paid_partially=$sms_after_classify_as_paid_partially, 
        email_after_classify_as_paid_partially=$email_after_classify_as_paid_partially
        WHERE id=1";
		$result = $db->query($sql);
if ($result) {
	// refresh the page
	header('Location: '.$_SERVER['REQUEST_URI']);
    echo 'Data updated successfully';
} else {
    echo "Error: " . $db->error();
}


}
llxHeader("", $langs->trans("MailArea"));

print load_fiche_titre($langs->trans("MailArea"), '', 'mail.png@mail');

print '<div class="fichecenter"><div class="fichethirdleft">';


/* BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (! empty($conf->mail->enabled) && $user->rights->mail->read)
{
	$langs->load("orders");

	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.tva as total_tva, c.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
	$sql.= ", s.code_client";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.fk_statut = 0";
	$sql.= " AND c.entity IN (".getEntity('commande').")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND c.fk_soc = ".$socid;

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("DraftOrders").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';
				$orderstatic->id=$obj->rowid;
				$orderstatic->ref=$obj->ref;
				$orderstatic->ref_client=$obj->ref_client;
				$orderstatic->total_ht = $obj->total_ht;
				$orderstatic->total_tva = $obj->total_tva;
				$orderstatic->total_ttc = $obj->total_ttc;
				print $orderstatic->getNomUrl(1);
				print '</td>';
				print '<td class="nowrap">';
				$companystatic->id=$obj->socid;
				$companystatic->name=$obj->name;
				$companystatic->client=$obj->client;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->canvas=$obj->canvas;
				print $companystatic->getNomUrl(1,'customer',16);
				print '</td>';
				print '<td class="right" class="nowrap">'.price($obj->total_ttc).'</td></tr>';
				$i++;
				$total += $obj->total_ttc;
			}
			if ($total>0)
			{

				print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}
END MODULEBUILDER DRAFT MYOBJECT */


print '
<form action="mailindex.php" method="POST">
  <h2>Email Settings</h2>
  <input type="checkbox" name="email_after_create_invoice" value="1" ' . ($data["email_after_create_invoice"] == "1" ? "checked" : "") . '> Send email after creating invoice<br>
  <input type="checkbox" name="email_after_delete_invoice" value="1" ' . ($data["email_after_delete_invoice"] == "1" ? "checked" : "") . '> Send email after deleting invoice<br>
  <input type="checkbox" name="email_afteradd_payment" value="1" ' . ($data["email_afteradd_payment"] == "1" ? "checked" : "") . '> Send email after adding payment<br>
  <input type="checkbox" name="email_after_delete_payment" value="1" ' . ($data["email_after_delete_payment"] == "1" ? "checked" : "") . '> Send email after deleting payment<br>
  <input type="checkbox" name="email_after_cancel_payment" value="1" ' . ($data["email_after_cancel_payment"] == "1" ? "checked" : "") . '> Send email after cancelling payment<br>
  <input type="checkbox" name="email_after_reopen_invoice" value="1" ' . ($data["email_after_reopen_invoice"] == "1" ? "checked" : "") . '> Send email after reopening invoice<br>
  <input type="checkbox" name="email_after_classify_as_paid" value="1" ' . ($data["email_after_classify_as_paid"] == "1" ? "checked" : "") . '> Send email after classifying as paid<br>
  <input type="checkbox" name="email_after_classify_as_paid_partially" value="1" ' . ($data["email_after_classify_as_paid_partially"] == "1" ? "checked" : "") . '> Send email after classifying as paid partially<br>
  <hr>
  <h2>SMS Settings</h2>
  <input type="checkbox" name="sms_after_create_invoice" value="1" ' . ($data["sms_after_create_invoice"] == "1" ? "checked" : "") . '> Send SMS after creating invoice<br>
  <input type="checkbox" name="sms_after_delete_invoice" value="1" ' . ($data["sms_after_delete_invoice"] == "1" ? "checked" : "") . '> Send SMS after deleting invoice<br>
  <input type="checkbox" name="sms_afteradd_payment" value="1" ' . ($data["sms_afteradd_payment"] == "1" ? "checked" : "") . '> Send SMS after adding payment<br>
  <input type="checkbox" name="sms_after_delete_payment" value="1" ' . ($data["sms_after_delete_payment"] == "1" ? "checked" : "") . '> Send SMS after deleting payment<br>
  <input type="checkbox" name="sms_after_cancel_payment" value="1" ' . ($data["sms_after_cancel_payment"] == "1" ? "checked" : "") . '> Send SMS after cancelling payment<br>
  <input type="checkbox" name="sms_after_reopen_invoice" value="1" ' . ($data["sms_after_reopen_invoice"] == "1" ? "checked" : "") . '> Send SMS after reopening invoice<br>
  <input type="checkbox" name="sms_after_classify_as_paid" value="1" ' . ($data["sms_after_classify_as_paid"] == "1" ? "checked" : "") . '> Send sms after classifying as paid<br>
  <input type="checkbox" name="sms_after_classify_as_paid_partially" value="1" ' . ($data["sms_after_classify_as_paid_partially"] == "1" ? "checked" : "") . '> Send sms after classifying as paid partially<br>
  <br>
  <input type="submit" name="submit" value="Save Settings">
  </form>
';


/* BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
if (! empty($conf->mail->enabled) && $user->rights->mail->read)
{
	$sql = "SELECT s.rowid, s.nom as name, s.client, s.datec, s.tms, s.canvas";
	$sql.= ", s.code_client";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.client IN (1, 2, 3)";
	$sql.= " AND s.entity IN (".getEntity($companystatic->element).")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND s.rowid = $socid";
	$sql .= " ORDER BY s.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print $langs->trans("BoxTitleLastCustomersOrProspects",$max);
		else if (! empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print $langs->trans("BoxTitleLastModifiedProspects",$max);
		else print $langs->trans("BoxTitleLastModifiedCustomers",$max);
		print '</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$companystatic->id=$objp->rowid;
				$companystatic->name=$objp->name;
				$companystatic->client=$objp->client;
				$companystatic->code_client = $objp->code_client;
				$companystatic->code_fournisseur = $objp->code_fournisseur;
				$companystatic->canvas=$objp->canvas;
				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$companystatic->getNomUrl(1,'customer',48).'</td>';
				print '<td class="right nowrap">';
				print $companystatic->getLibCustProspStatut();
				print "</td>";
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms),'day')."</td>";
				print '</tr>';
				$i++;


			}

			$db->free($resql);
		}
		else
		{
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table><br>";
	}
}
*/

print '</div></div></div>';

// End of page
llxFooter();
$db->close();
