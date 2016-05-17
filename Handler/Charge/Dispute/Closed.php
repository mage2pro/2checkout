<?php
namespace Dfe\TwoCheckout\Handler\Charge\Dispute;
use Dfe\TwoCheckout\Handler\Charge\Dispute;
// 2016-03-25
// https://stripe.com/docs/api#event_types-charge.dispute.closed
// Occurs when the dispute is closed
// and the dispute status changes to charge_refunded, lost, warning_closed, or won.
class Closed extends Dispute {
	/**
	 * 2016-03-25
	 * @override
	 * @see \Dfe\TwoCheckout\Handler::_process()
	 * @used-by \Dfe\TwoCheckout\Handler::process()
	 * @return mixed
	 */
	protected function process() {
		/** @var int $paymentId */
		//$paymentId = df_fetch_one('sales_payment_transaction', 'payment_id', [
		//	'txn_id' => dfa_deep($request, 'data/object/id')
		//]);
		return __CLASS__;
	}
}