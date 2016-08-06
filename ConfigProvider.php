<?php
namespace Dfe\TwoCheckout;
/** @method Settings s() */
class ConfigProvider extends \Df\Payment\ConfigProvider {
	/**
	 * 2016-08-04
	 * @override
	 * @see \Df\Payment\ConfigProvider::custom()
	 * @used-by \Df\Payment\ConfigProvider::getConfig()
	 * @return array(string => mixed)
	 */
	protected function custom() {return [
		'accountNumber' => $this->s()->accountNumber()
		,'prefill' => $this->s()->prefill()
		,'publishableKey' => $this->s()->publishableKey()
	];}

	/**
	 * 2016-08-06
	 * @override
	 * @see \Df\Payment\ConfigProvider::route()
	 * @used-by \Df\Payment\ConfigProvider::getConfig()
	 * @return string
	 */
	protected function route() {return 'dfe-2checkout';}
}