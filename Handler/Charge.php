<?php
namespace Dfe\TwoCheckout\Handler;
use Dfe\TwoCheckout\Handler;
use Df\Sales\Model\Order as DfOrder;
use Df\Sales\Model\Order\Payment as DfPayment;
use Magento\Framework\Exception\LocalizedException as LE;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Api\Data\OrderInterface;
/**
 * 2016-05-22
 */
abstract class Charge extends Handler {
	/**
	 * 2016-05-23
	 * @used-by \Dfe\TwoCheckout\Handler::p()
	 * @override
	 * @see \Dfe\TwoCheckout\Handler::eligible()
	 * @return bool
	 */
	protected function eligible() {return !!$this->payment();}

	/**
	 * 2016-03-26
	 * @return Order|DfOrder
	 * @throws LE
	 */
	protected function order() {
		if (!isset($this->{__METHOD__})) {
			/** @var Order $result */
			$result = $this->payment()->getOrder();
			if (!$result->getId()) {
				throw new LE(__('The order no longer exists.'));
			}
			/**
			 * 2016-03-26
			 * Очень важно! Иначе order создать свой экземпляр payment:
			 * @used-by \Magento\Sales\Model\Order::getPayment()
			 */
			$result[OrderInterface::PAYMENT] = $this->payment();
			$this->{__METHOD__} = $result;
		}
		return $this->{__METHOD__};
	}

	/**
	 * 2016-05-22
	 * Идентификатор транзакции capture.
	 * @return string|null
	 */
	protected function parentId() {return $this['invoice_id'];}

	/**
	 * 2016-05-22
	 * @return Payment|DfPayment|null
	 */
	protected function payment() {
		if (!isset($this->{__METHOD__})) {
			$this->{__METHOD__} = df_n_set($this->paymentByTxnId($this->parentId()));
		}
		return df_n_get($this->{__METHOD__});
	}

	/**
	 * 2016-05-22
	 * @param string|null $id
	 * @return Payment|DfPayment|null
	 */
	private function paymentByTxnId($id) {
		if (!isset($this->{__METHOD__}[$id])) {
			/** @var Payment|null $result */
			$result = null;
			if ($id) {
				/** @var int|null $paymentId */
				$paymentId = df_fetch_one('sales_payment_transaction', 'payment_id', ['txn_id' => $id]);
				if ($paymentId) {
					$result = df_load(Payment::class, $paymentId);
					df_payment_webhook_case($result);
				}
			}
			$this->{__METHOD__}[$id] = df_n_set($result);
		}
		return df_n_get($this->{__METHOD__}[$id]);
	}
}


