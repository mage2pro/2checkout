<?php
// 2016-05-22
// REFUND_ISSUED
// https://www.2checkout.com/documentation/notifications/refund-issued
namespace Dfe\TwoCheckout\Handler;
class RefundIssued extends Charge {
	/**
	 * 2016-05-23
	 * @used-by \Dfe\TwoCheckout\Handler::p()
	 * @override
	 * @see \Dfe\TwoCheckout\Handler::eligible()
	 * @return bool
	 */
	protected function eligible() {return parent::eligible() && $this->order()->canCreditmemo();}

	/**
	 * 2016-03-27
	 * @override
	 * @see \Dfe\TwoCheckout\Handler::_process()
	 * @used-by \Dfe\TwoCheckout\Handler::process()
	 * @return int|string
	 */
	final protected function process() {return
		dfp_refund(
			$this->payment()
			,df_invoice_by_transaction($this->order(), $this->parentId())
			,$this->item('refund')
		) ?: 'skipped'
	;}
	
	/**
	 * 2016-05-23
	 * 1) Сценарий полного возврата:
	 		<...>
			"item_count": "1",
			"item_name_1": "ORD-2016\/05-00213",
			"item_id_1": "",
			"item_list_amount_1": "80.36",
			"item_usd_amount_1": "80.36",
			"item_cust_amount_1": "80.36",
			"item_type_1": "refund",
			"item_duration_1": "",
			"item_recurrence_1": "",
			"item_rec_list_amount_1": "",
			"item_rec_status_1": "",
			"item_rec_date_next_1": "",
			"item_rec_install_billed_1": ""
	 		<...>
	 * 2) Сценарий частичного возврата:
			<...>
			"item_count": "2",
			"item_name_1": "ORD-2016\/05-00212",
			"item_id_1": "",
			"item_list_amount_1": "80.36",
			"item_usd_amount_1": "80.36",
			"item_cust_amount_1": "80.36",
			"item_type_1": "bill",
			"item_duration_1": "",
			"item_recurrence_1": "",
			"item_rec_list_amount_1": "",
			"item_rec_status_1": "",
			"item_rec_date_next_1": "",
			"item_rec_install_billed_1": "",
			"item_name_2": "Partial Refund",
			"item_id_2": "",
			"item_list_amount_2": "76.34",
			"item_usd_amount_2": "76.34",
			"item_cust_amount_2": "76.34",
			"item_type_2": "refund",
			"item_duration_2": "",
			"item_recurrence_2": "",
			"item_rec_list_amount_2": "",
			"item_rec_status_2": "",
			"item_rec_date_next_2": "",
			"item_rec_install_billed_2": ""
			<...>
	 *
	 * @param string|null $key [optional]
	 * @param float|null $default [optional]
	 * @return float|array(string => float)
	 */
	private function item($key = null, $default = null) {
		if (!isset($this->{__METHOD__})) {
			/** @var array(string => float) $result */
			$result = [];
			/** @var int $count */
			$count = intval($this['item_count']);
			for ($i = 1; $i <= $count; $i++) {
				/**
				 * 2016-05-23
				 * «Indicates if item is a bill or refund; Value will be bill or refund»
				 * https://www.2checkout.com/documentation/notifications/refund-issued
				 */
				$result[$this[$this->itemKey('type', $i)]] = $this[$this->itemKey('list_amount', $i)];
			}
			$this->{__METHOD__} = $result;
		}
		return !$key ? $this->{__METHOD__} : dfa($this->{__METHOD__}, $key, $default);
	}

	/**
	 * 2016-05-23
	 * @param string $name
	 * @param int $index
	 * @return string
	 */
	private function itemKey($name, $index) {return implode('_', ['item', $name, $index]);}
}