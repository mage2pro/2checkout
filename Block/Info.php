<?php
namespace Dfe\TwoCheckout\Block;
use Magento\Framework\DataObject;
class Info extends \Df\Payment\Block\ConfigurableInfo {
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
		/** @var bool $sandbox */
		$sandbox = $this->iia('sandbox');
		if (!$this->getIsSecureMode()) {
			$result->setData('Sale', df_tag('a', [
				'target' => '_blank', 'href' =>
					(
						$sandbox
						? 'https://sandbox.2checkout.com/sandbox/'
						: 'https://www.2checkout.com/va/'
					) . 'sales/detail?sale_id=' . $this->iia('sale_id')
			], $this->iia('sale_id')));
		}
		$result->addData([
			'Card Number' => implode('********', $this->iia('first_six_digits', 'last_two_digits'))
		]);
		if (!$this->getIsSecureMode()) {
			if ($sandbox) {
				$result->setData('Mode', 'Sandbox');
			}
		}
		return $result;
	}
}


