<?php
namespace Dfe\TwoCheckout\Handler;
use Dfe\TwoCheckout\Handler;
use Df\Sales\Model\Order as DfOrder;
use Df\Sales\Model\Order\Payment as DfPayment;
use Magento\Framework\Exception\LocalizedException as LE;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OP;
# 2016-05-22
/** @see \Dfe\TwoCheckout\Handler\RefundIssued */
abstract class Charge extends Handler {
	/**
	 * 2016-05-23
	 * @override
	 * @see \Dfe\TwoCheckout\Handler::eligible()
	 * @used-by \Dfe\TwoCheckout\Handler::p()
	 * @see \Dfe\TwoCheckout\Handler\RefundIssued::eligible()
	 * @return bool
	 */
	protected function eligible() {return !!$this->op();}

	/**
	 * 2016-03-26
	 * @used-by \Dfe\TwoCheckout\Handler\RefundIssued::eligible()
	 * @used-by \Dfe\TwoCheckout\Handler\RefundIssued::process()
	 * @return Order|DfOrder
	 * @throws LE
	 */
	final protected function o() {return df_order($this->op());}

	/**
	 * 2016-05-22 Идентификатор транзакции capture.
	 * @used-by self::op()
	 * @used-by \Dfe\TwoCheckout\Handler\RefundIssued::process()
	 */
	final protected function pid():string {return df_nts($this['invoice_id']);}

	/**
	 * 2016-05-22
	 * @used-by self::eligible()
	 * @used-by self::o()
	 * @used-by \Dfe\TwoCheckout\Handler\RefundIssued::process()
	 * @return OP|DfPayment|null
	 */
	final protected function op() {return dfc($this, function() {return /** @var string $pid */
		($pid = $this->pid()) ? dfp_webhook_case(dfp(df_transx($pid, false))) : null
	;});}
}