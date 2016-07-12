<?php
namespace Dfe\TwoCheckout\Block;
use Magento\Framework\DataObject;
class Info extends \Df\Payment\Block\ConfigurableInfo {
	/**
	 * 2016-05-23
	 * @return string
	 */
	public function cardNumber() {
		if (!isset($this->{__METHOD__})) {
			$this->{__METHOD__} = implode('********', $this->iia(self::CARD_F6, self::CARD_L2));
		}
		return $this->{__METHOD__};
	}

	/**
	 * 2016-05-23
	 * @override
	 * @see \Magento\Framework\View\Element\Template::getTemplate()
	 * @return string
	 */
	public function getTemplate() {
		return 'frontend' === $this->getArea() ? 'Dfe_TwoCheckout::info.phtml' : parent::getTemplate();
	}

	/**
	 * 2016-07-12
	 * @override
	 * @see \Df\Payment\Block\ConfigurableInfo::_isSandbox()
	 * @used-by \Df\Payment\Block\ConfigurableInfo::isSandbox()
	 * @return bool
	 */
	protected function _isSandbox() {return $this->iia('sandbox');}

	/**
	 * 2016-05-21
	 * @override
	 * @see \Magento\Payment\Block\ConfigurableInfo::_prepareSpecificInformation()
	 * @used-by \Magento\Payment\Block\Info::getSpecificInformation()
	 * @param DataObject|null $transport
	 * @return DataObject
	 */
	protected function _prepareSpecificInformation($transport = null) {
		/** @var DataObject $result */
		$result = parent::_prepareSpecificInformation($transport);
		if (!$this->getIsSecureMode()) {
			$result->setData('Sale', df_tag('a', [
				'target' => '_blank', 'href' =>
					(
						$this->isSandbox()
						? 'https://sandbox.2checkout.com/sandbox/'
						: 'https://www.2checkout.com/va/'
					) . 'sales/detail?sale_id=' . $this->iia(self::SALE_ID)
			], $this->iia('sale_id')));
		}
		$result->addData(['Card Number' => $this->cardNumber()]);
		if (!$this->getIsSecureMode()) {
			if ($this->isSandbox()) {
				$result->setData('Mode', 'Sandbox');
			}
		}
		return $result;
	}

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


