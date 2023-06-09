use dolibarr_;
alter table llx_facture add last_action varchar(30) ;
alter table llx_commande add last_action varchar(30) ;
drop table llx_auto_send
CREATE TABLE llx_auto_send (
  id INT(11) NOT NULL AUTO_INCREMENT,
  sms_after_create_invoice TINYINT(1) NOT NULL DEFAULT 0,
  email_after_create_invoice TINYINT(1) NOT NULL DEFAULT 0,
  sms_after_delete_invoice TINYINT(1) NOT NULL DEFAULT 0,
  email_after_delete_invoice TINYINT(1) NOT NULL DEFAULT 0,
  sms_afteradd_payment TINYINT(1) NOT NULL DEFAULT 0,
  email_afteradd_payment TINYINT(1) NOT NULL DEFAULT 0,
  sms_after_delete_payment TINYINT(1) NOT NULL DEFAULT 0,
	email_after_delete_payment TINYINT(1) NOT NULL DEFAULT 0,
  sms_after_cancel_payment TINYINT(1) NOT NULL DEFAULT 0,
  email_after_cancel_payment TINYINT(1) NOT NULL DEFAULT 0,
  sms_after_reopen_invoice TINYINT(1) NOT NULL DEFAULT 0,
  email_after_reopen_invoice TINYINT(1) NOT NULL DEFAULT 0,
  sms_after_classify_as_paid TINYINT(1) NOT NULL DEFAULT 0,
  email_after_classify_as_paid TINYINT(1) NOT NULL DEFAULT 0,
  sms_after_classify_as_paid_partially TINYINT(1) NOT NULL DEFAULT 0,
   email_after_classify_as_paid_partially TINYINT(1) NOT NULL DEFAULT 0,
   sms_after_validate_commande TINYINT(1) NOT NULL DEFAULT 0,
   email_after_validate_commande TINYINT(1) NOT NULL DEFAULT 0,
   sms_after_sending_commande TINYINT(1) NOT NULL DEFAULT 0,
  email_after_sending_commande TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
);
create TABLE llx_chart ( max int DEFAULT 5, other boolean DEFAULT true, unknowns boolean DEFAULT true );

