<?php

use \WHMCS\Billing\Invoice;
use \WHMCS\Billing\Invoice\Item;
use WHMCS\Database\Capsule;

function credit_invoice_credit() {
	$invoiceId = filter_input(INPUT_POST, 'invoice', FILTER_SANITIZE_NUMBER_INT);
	$invoice = Invoice::with('items')->findOrFail($invoiceId);

	$credit = Invoice::newInvoice($invoice->userid);
	$credit->subtotal = -$invoice->subtotal;
	$credit->tax = -$invoice->tax;
	$credit->total = -$invoice->total;
	$credit->adminNotes = "Facture|{$invoiceId}";
	$credit->dateCreated = Carbon\Carbon::now();
	$credit->dateDue = Carbon\Carbon::now();
	$credit->datePaid = Carbon\Carbon::now();
	$credit->save();
	$newItems[] = [
		'invoiceid' => $credit->id,
		'userid' => $credit->userid,
		'description' => "Note de crédit facture n°{$invoice->invoiceNumber}",
		'amount' => $credit->subtotal,
		'taxed' => true,
	];
	Capsule::table('tblinvoiceitems')->insert($newItems);
	$credit->setStatusUnpaid();
	$credit->save();
	$credit->status = 'Paid';
	$credit->save();

	$invoice->status = 'Paid';
	$invoice->adminNotes = $invoice->adminNotes . PHP_EOL . "Note de crédit|{$credit->id}";
	$invoice->save();

	redirect_to_invoice($credit->id);
};

function invoice_is_credited($invoiceId) {
	$invoice = Invoice::findOrFail($invoiceId);
	preg_match('/Note de crédit\|(\d*)/', $invoice->adminNotes, $match);
	return $match;
}

function invoice_is_creditnote($invoiceId) {
	$invoice = Invoice::findOrFail($invoiceId);
	preg_match('/Facture\|(\d*)/', $invoice->adminNotes, $match);
	return $match;
}

function redirect_to_invoice($invoiceId) {
	header("Location: invoices.php?action=edit&id={$invoiceId}");
	die();
}
