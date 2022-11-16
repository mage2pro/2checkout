<?php
namespace Dfe\TwoCheckout\Handler;
use Df\Sales\Model\Order as DfOrder;
use Df\Sales\Model\Order\Payment as DfPayment;
use Dfe\TwoCheckout\Handler;
use Magento\Framework\Exception\LocalizedException as LE;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OP;
# 2016-05-22 REFUND_ISSUED https://www.2checkout.com/documentation/notifications/refund-issued
final class RefundIssued extends Handler {
	/**
	 * 2016-05-23
	 * @override
	 * @see \Dfe\TwoCheckout\Handler\Charge::eligible()
	 * @used-by \Dfe\TwoCheckout\Handler::p()
	 */
	protected function eligible():bool {return $this->op() && $this->o()->canCreditmemo();}

	/**
	 * 2016-03-27
	 * @override
	 * @see \Dfe\TwoCheckout\Handler::_process()
	 * @used-by \Dfe\TwoCheckout\Handler::process()
	 * @return int|string
	 */
	final protected function process() {return dfp_refund($this->op(), $this->pid(), dfa($this->itemA(), 'refund')) ?: 'skipped';}

	/**
	 * 2016-05-23
	 * 1) Сценарий полного возврата:
	 *		<...>
	 *		"item_count": "1",
	 *		"item_name_1": "ORD-2016\/05-00213",
	 *		"item_id_1": "",
	 *		"item_list_amount_1": "80.36",
	 *		"item_usd_amount_1": "80.36",
	 *		"item_cust_amount_1": "80.36",
	 *		"item_type_1": "refund",
	 *		"item_duration_1": "",
	 *		"item_recurrence_1": "",
	 *		"item_rec_list_amount_1": "",
	 *		"item_rec_status_1": "",
	 *		"item_rec_date_next_1": "",
	 *		"item_rec_install_billed_1": ""
	 *		<...>
	 * 2) Сценарий частичного возврата:
	 *		<...>
	 *		"item_count": "2",
	 *		"item_name_1": "ORD-2016\/05-00212",
	 *		"item_id_1": "",
	 *		"item_list_amount_1": "80.36",
	 *		"item_usd_amount_1": "80.36",
	 *		"item_cust_amount_1": "80.36",
	 *		"item_type_1": "bill",
	 *		"item_duration_1": "",
	 *		"item_recurrence_1": "",
	 *		"item_rec_list_amount_1": "",
	 *		"item_rec_status_1": "",
	 *		"item_rec_date_next_1": "",
	 *		"item_rec_install_billed_1": "",
	 *		"item_name_2": "Partial Refund",
	 *		"item_id_2": "",
	 *		"item_list_amount_2": "76.34",
	 *		"item_usd_amount_2": "76.34",
	 *		"item_cust_amount_2": "76.34",
	 *		"item_type_2": "refund",
	 *		"item_duration_2": "",
	 *		"item_recurrence_2": "",
	 *		"item_rec_list_amount_2": "",
	 *		"item_rec_status_2": "",
	 *		"item_rec_date_next_2": "",
	 *		"item_rec_install_billed_2": ""
	 *		<...>
	 * @used-by self::process()
	 * @return array(string => float)
	 */
	private function itemA():array {
		$r = []; /** @var array(string => float) $r */
		$count = intval($this['item_count']); /** @var int $count */
		/**
		 * @param string $name
		 * @param int $index
		 */
		$key = function($name, $index):string {return implode('_', ['item', $name, $index]);};
		for ($i = 1; $i <= $count; $i++) {
			# 2016-05-23
			# «Indicates if item is a bill or refund; Value will be bill or refund»
			# https://www.2checkout.com/documentation/notifications/refund-issued
			$r[$this[$key('type', $i)]] = $this[$key('list_amount', $i)];
		}
		return $r;
	}

	/**
	 * 2016-03-26
	 * @used-by \Dfe\TwoCheckout\Handler\RefundIssued::eligible()
	 * @used-by \Dfe\TwoCheckout\Handler\RefundIssued::process()
	 * @return Order|DfOrder
	 * @throws LE
	 */
	private function o() {return df_order($this->op());}

	/**
	 * 2016-05-22
	 * @used-by self::eligible()
	 * @used-by self::o()
	 * @used-by \Dfe\TwoCheckout\Handler\RefundIssued::process()
	 * @return OP|DfPayment|null
	 */
	private function op() {return dfc($this, function() {return /** @var string $pid */
		($pid = $this->pid()) ? dfp_webhook_case(dfp(df_transx($pid, false))) : null
	;});}

	/**
	 * 2016-05-22 Идентификатор транзакции capture.
	 * @used-by self::op()
	 * @used-by \Dfe\TwoCheckout\Handler\RefundIssued::process()
	 */
	private function pid():string {return df_nts($this['invoice_id']);}
}