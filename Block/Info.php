<?php
namespace Dfe\TwoCheckout\Block;
// 2016-05-23
/** @final Unable to use the PHP «final» keyword here because of the M2 code generation. */
class Info extends \Df\Payment\Block\Info {
	/**
	 * 2016-05-23
	 * @return string
	 */
	final function cardNumber() {return dfc($this, function() {return implode(
		'********', $this->iia(self::CARD_F6, self::CARD_L2)
	);});}

	/**
	 * 2016-05-23
	 * @override
	 * @see \Magento\Framework\View\Element\Template::getTemplate()
	 * @see \Magento\Payment\Block\Info::$_template
	 * @return string
	 */
	final function getTemplate() {return
		'frontend' === $this->getArea() ? 'Dfe_TwoCheckout::info.phtml' : parent::getTemplate()
	;}

	/**
	 * 2016-05-21
	 * @override
	 * @see \Df\Payment\Block\Info::prepare()
	 * @used-by \Df\Payment\Block\Info::_prepareSpecificInformation()
	 */
	final protected function prepare() {
		$this->siEx('Sale', df_tag_ab($this->iia('sale_id'),
			"https://{$this->isTest('sandbox.2checkout.com/sandbox', 'www.2checkout.com/va')}/"
			,"sales/detail?sale_id={$this->iia(self::SALE_ID)}"
		));
		$this->si('Card Number', $this->cardNumber());
	}

	/**
	 * 2016-07-13
	 * @override
	 * @see \Df\Payment\Block\Info::testModeLabel()
	 * @used-by \Df\Payment\Block\Info::markTestMode()
	 * @return string
	 */
	final protected function testModeLabel() {return 'Sandbox';}

	/**
	 * 2016-07-29
	 * @override
	 * @see \Df\Payment\Block\Info::testModeLabelLong()
	 * @used-by \Df\Payment\Block\Info::title()
	 * @return string
	 */
	final protected function testModeLabelLong() {return 'Sandbox Mode';}

	/**
	 * 2016-05-21
	 * @used-by \Dfe\TwoCheckout\Method::charge()
	 * @used-by \Dfe\TwoCheckout\Block\Info::_prepareSpecificInformation()
	 */
	const CARD_F6 = 'first_six_digits';

	/**
	 * 2016-05-21
	 * @used-by \Dfe\TwoCheckout\Method::charge()
	 * @used-by \Dfe\TwoCheckout\Block\Info::_prepareSpecificInformation()
	 */
	const CARD_L2 = 'last_two_digits';

	/**
	 * 2016-05-21
	 * Идентификатор документа-sale в 2Checkout.
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * Обратите внимание, что он отличается от идентификатора документа-invoice в 2Checkout.
	 * @used-by \Dfe\TwoCheckout\Method::charge()
	 * @used-by \Dfe\TwoCheckout\Block\Info::_prepareSpecificInformation()
	 */
	const SALE_ID = 'sale_id';
}