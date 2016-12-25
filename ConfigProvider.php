<?php
namespace Dfe\TwoCheckout;
/** @method Settings s() */
class ConfigProvider extends \Df\StripeClone\ConfigProvider {
	/**
	 * 2016-08-04
	 * @override
	 * @see \Df\Payment\ConfigProvider::config()
	 * @used-by \Df\Payment\ConfigProvider::getConfig()
	 * @return array(string => mixed)
	 */
	protected function config() {return [
		'accountNumber' => $this->s()->accountNumber()
	] + parent::config();}

	/**
	 * 2016-08-06
	 * @override
	 * @see \Df\Payment\ConfigProvider::route()
	 * @used-by \Df\Payment\ConfigProvider::getConfig()
	 * @return string
	 */
	protected function route() {return 'dfe-2checkout';}
}