<?php
namespace Dfe\TwoCheckout;
# 2016-08-04
# @used-by https://github.com/mage2pro/2checkout/blob/1.1.25/etc/frontend/di.xml?ts=4#L9
/** @method Settings s() */
final class ConfigProvider extends \Df\StripeClone\ConfigProvider {
	/**
	 * 2016-08-04
	 * @override
	 * @see \Df\StripeClone\ConfigProvider::config()
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