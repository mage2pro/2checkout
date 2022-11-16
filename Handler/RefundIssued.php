<?php
namespace Dfe\TwoCheckout\Handler;
# 2016-05-22 REFUND_ISSUED https://www.2checkout.com/documentation/notifications/refund-issued
final class RefundIssued extends Charge {
	/**
	 * 2016-05-23
	 * @override
	 * @see \Dfe\TwoCheckout\Handler\Charge::eligible()
	 * @used-by \Dfe\TwoCheckout\Handler::p()
	 */
	protected function eligible():bool {return parent::eligible() && $this->o()->canCreditmemo();}

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
}