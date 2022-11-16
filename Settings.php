<?php
namespace Dfe\TwoCheckout;
use Twocheckout as T;
/** @method static Settings s() */
final class Settings extends \Df\StripeClone\Settings {
	/**
	 * 2016-05-18
	 * @used-by self::init()
	 * @used-by \Dfe\TwoCheckout\ConfigProvider::config()
	 * @used-by \Dfe\TwoCheckout\Method::charge()
	 */
	function accountNumber():string {return $this->testable();}

	/**
	 * 2016-05-19
	 * https://github.com/2Checkout/2checkout-php#credentials-and-options
	 * @override
	 * @see \Df\Payment\Settings::init()
	 * @used-by \Df\Payment\Method::action()
	 */
	function init():void {
		# 2022-11-16
		# The @see \Twocheckout::sandbox() method has been removed: https://github.com/2Checkout/2checkout-php/commit/f1f44364
		# The previous code was:
		#		T::sandbox($this->test());
		T::username($this->testable('username'));
		T::password($this->testableP('password'));
		T::privateKey($this->privateKey());
		T::sellerId($this->accountNumber());
	}

	/**
	 * 2016-05-23 «Mage2.PRO» → «Payment» → «2Checkout» → «Pass Order Items to the Payment Gateway?»
	 */
	function passOrderItems():bool {return $this->b();}

	/**
	 * 2016-05-22
	 */
	function secretWord():string {return $this->testableP();}
}