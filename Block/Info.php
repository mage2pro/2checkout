<?php
namespace Dfe\TwoCheckout\Block;
# 2016-05-23
/** @final Unable to use the PHP «final» keyword here because of the M2 code generation. */
class Info extends \Df\Payment\Block\Info {
	/**
	 * 2016-05-21
	 * @override
	 * @see \Df\Payment\Block\Info::prepare()
	 * @used-by \Df\Payment\Block\Info::prepareToRendering()
	 */
	final protected function prepare() {
		$this->siEx('Sale', df_tag_ab($this->iia('sale_id'),
			"https://{$this->isTest('sandbox.2checkout.com/sandbox', 'www.2checkout.com/va')}/"
			,"sales/detail?sale_id={$this->iia(self::SALE_ID)}"
		));
		$this->si('Card Number', implode('········', $this->iia(self::CARD_F6, self::CARD_L2)));
	}

	/**
	 * 2016-07-13
	 * @override
	 * @see \Df\Payment\Block\Info::testModeLabel()
	 * @used-by \Df\Payment\Block\Info::_toHtml()
	 * @return string
	 */
	final protected function testModeLabel() {return 'Sandbox';}

	/**
	 * 2016-05-21
	 * @used-by prepare()
	 * @used-by \Dfe\TwoCheckout\Method::charge()
	 */
	const CARD_F6 = 'first_six_digits';

	/**
	 * 2016-05-21
	 * @used-by prepare()
	 * @used-by \Dfe\TwoCheckout\Method::charge()
	 */
	const CARD_L2 = 'last_two_digits';

	/**
	 * 2016-05-21
	 * Идентификатор документа-sale в 2Checkout.
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * Обратите внимание, что он отличается от идентификатора документа-invoice в 2Checkout.
	 * @used-by prepare()
	 * @used-by \Dfe\TwoCheckout\Method::_refund()
	 * @used-by \Dfe\TwoCheckout\Method::charge()
	 */
	const SALE_ID = 'sale_id';
}