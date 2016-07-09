<?php
namespace Dfe\TwoCheckout\Handler;
use Dfe\TwoCheckout\Handler;
use Magento\Sales\Api\CreditmemoManagementInterface as ICreditmemoService;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Service\CreditmemoService;
/**
 * 2016-05-22
 * REFUND_ISSUED
 * https://www.2checkout.com/documentation/notifications/refund-issued
 */
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
	 * «How is an online refunding implemented?» https://mage2.pro/t/959
	 *
	 * Сначала хотел cделать по аналогии с @see \Magento\Paypal\Model\Ipn::_registerPaymentRefund()
	 * https://github.com/magento/magento2/blob/9546277/app/code/Magento/Paypal/Model/Ipn.php#L467-L501
	 * Однако используемый там метод @see \Magento\Sales\Model\Order\Payment::registerRefundNotification()
	 * нерабочий: «Invalid method Magento\Sales\Model\Order\Creditmemo::register»
	 * https://mage2.pro/t/1029
	 *
	 * Поэтому делаю по аналогии с
	 * @see \Magento\Sales\Controller\Adminhtml\Order\Creditmemo\Save::execute()
	 *
	 * @see \Dfe\TwoCheckout\Handler::_process()
	 * @used-by \Dfe\TwoCheckout\Handler::process()
	 * @return mixed
	 */
	protected function process() {
		/**
		 * 2016-05-22
		 * @todo Примечание к заказу.
		 */
		/** @var CreditmemoService|ICreditmemoService $cmi */
		$cmi = df_om()->create(ICreditmemoService::class);
		$cmi->refund($this->cm(), false);
		/**
		 * 2016-03-28
		 * @todo Надо отослать покупателю письмо-оповещение о возврате оплаты.
		 * 2016-05-15
		 * Что интересно, при возврате из административной части Magento 2
		 * покупатель тоже не получает уведомление.
		 */
		return $this->cm()->getId();
	}

	/**
	 * 2016-03-27
	 * @return Creditmemo
	 */
	private function cm() {
		if (!isset($this->{__METHOD__})) {
			/** @var CreditmemoLoader $cmLoader */
			$cmLoader = df_o(CreditmemoLoader::class);
			$cmLoader->setOrderId($this->order()->getId());
			$cmLoader->setInvoiceId($this->invoice()->getId());
			/** @var float|null $refundAmount */
			$refundAmount = $this->item('refund');
			/** @var float|null $invoiceAmount */
			$invoiceAmount = $this->item('bill', $refundAmount);
			if ($refundAmount !== $invoiceAmount) {
				/**
				 * 2016-05-23
				 * https://mage2.pro/tags/credit-memo-adjustment
				 *
				 * 1)
				 * @used-by \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader::load()
				 * https://github.com/magento/magento2/blob/b366da/app/code/Magento/Sales/Controller/Adminhtml/Order/CreditmemoLoader.php#L186-L186
				 *
				 * 2)
				 * @used-by \Magento\Sales\Model\Order\CreditmemoFactory::createByInvoice()
				 * https://github.com/magento/magento2/blob/b366da/app/code/Magento/Sales/Model/Order/CreditmemoFactory.php#L155-L155
				 *
				 * 3)
				 * @used-by \Magento\Sales\Model\Order\CreditmemoFactory::initData()
				 * https://github.com/magento/magento2/blob/b366da/app/code/Magento/Sales/Model/Order/CreditmemoFactory.php#L244-L246
				 */
				$cmLoader->setCreditmemo(['adjustment_negative' => $invoiceAmount - $refundAmount]);
			}
			/** @varCreditmemo  $result */
			$result = $cmLoader->load();
			df_assert($result);
			/**
			 * 2016-03-28
			 * Важно! Иначе order загрузит payment автоматически вместо нашего,
			 * и флаг @see \Dfe\CheckoutCom\Method::WEBHOOK_CASE будет утерян
			 */
			$result->getOrder()->setData(Order::PAYMENT, $this->payment());
			$this->{__METHOD__} = $result;
		}
		return $this->{__METHOD__};
	}

	/**
	 * 2016-03-27
	 * @return Invoice
	 */
	private function invoice() {
		if (!isset($this->{__METHOD__})) {
			$this->{__METHOD__} = df_invoice_by_transaction($this->order(), $this->parentId());
			df_assert($this->{__METHOD__});
		}
		return $this->{__METHOD__};
	}

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