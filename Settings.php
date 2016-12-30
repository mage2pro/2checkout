<?php
namespace Dfe\TwoCheckout;
use \Twocheckout as T;
/** @method static Settings s() */
final class Settings extends \Df\StripeClone\Settings {
	/**
	 * 2016-05-18
	 * @return string
	 */
	public function accountNumber() {return $this->testable();}

	/**
	 * 2016-05-19
	 * https://github.com/2Checkout/2checkout-php#credentials-and-options
	 * @override
	 * @see \Df\StripeClone\Settings::init()
	 * @used-by \Df\StripeClone\Method::api()
	 * @return void
	 */
	public function init() {
		T::sandbox($this->test());
		T::username($this->testable('username'));
		T::password($this->testableP('password'));
		T::privateKey($this->testableP('secretKey'));
		T::sellerId($this->accountNumber());
	}

	/**
	 * 2016-05-23
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Pass Order Items to the Payment Gateway?»
	 * @return bool
	 */
	public function passOrderItems() {return $this->b();}

	/**
	 * 2016-05-22
	 * @return string
	 */
	public function secretWord() {return $this->testableP();}
}