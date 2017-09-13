<?php
namespace Dfe\TwoCheckout\Controller\Index;
use Df\Framework\Controller\Response\Json;
use Dfe\TwoCheckout\Handler;
/**
 * 2016-03-18
 * The controller is for ADVANCED integration:
 * - capturing and refunding payments through the 2Checkout interface (instead of the Magento interface),
 * - reverse notifications about the chargebacks and disputes.
 *
 * 2016-03-27
 * Оказывается, аналогичная функциональность реализована в методе
 * @see \Magento\Paypal\Model\Ipn::_registerTransaction()
 * https://github.com/magento/magento2/blob/9546277/app/code/Magento/Paypal/Model/Ipn.php#L222-L278
 */
class Index extends \Magento\Framework\App\Action\Action {
	/**
	 * 2016-03-18
	 * @override
	 * @see \Magento\Framework\App\Action\Action::execute()   
	 * @used-by \Magento\Framework\App\Action\Action::dispatch():
	 * 		$result = $this->execute();
	 * https://github.com/magento/magento2/blob/2.2.0-RC1.8/lib/internal/Magento/Framework/App/Action/Action.php#L84-L125
	 * @return Json
	 */
	function execute() {return df_leh(function() {return Json::i(Handler::p($this->params()));});}

	/**
	 * 2016-05-22
	 * @return array(string => string)
	 */
	private function params() {return 
		df_my_local() ? $this->paramsLocal() : $this->getRequest()->getParams()
	;}

	/**
	 * 2016-05-22
	 * @return array(string => string)
	 */
	private function paramsLocal() {return
		df_json_decode(file_get_contents(BP . '/_my/test/2Checkout/3/4.REFUND_ISSUED.json'))
	;}
}