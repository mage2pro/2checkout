<?php
namespace Dfe\TwoCheckout;
use \Twocheckout as T;
/** @method static Settings s() */
final class Settings extends \Df\Payment\Settings\BankCard {
	/**
	 * 2016-05-18
	 * @return string
	 */
	public function accountNumber() {return $this->testable();}

	/**
	 * 2016-05-19
	 * https://github.com/2Checkout/2checkout-php#credentials-and-options
	 * @return void
	 */
	public function init() {
		T::sandbox($this->test());
		T::username($this->username());
		T::password($this->password());
		T::privateKey($this->secretKey());
		T::sellerId($this->accountNumber());
	}

	/**
	 * 2016-05-23
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Pass Order Items to the Payment Gateway?»
	 * @return bool
	 */
	public function passOrderItems() {return $this->b();}

	/**
	 * 2016-03-09
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Prefill the Payment Form with Test Data?»
	 * @see \Dfe\TwoCheckout\Source\Prefill::map()
	 * @return string|false
	 */
	public function prefill() {return $this->bv();}

	/**
	 * 2016-03-02
	 * @used-by \Dfe\TwoCheckout\Handler::p()
	 * @return string
	 */
	public function publishableKey() {return $this->testable();}

	/**
	 * 2016-05-22
	 * @return string
	 */
	public function secretWord() {return $this->testable();}

	/**
	 * 2016-05-18
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Account Number»
	 * @return string
	 */
	protected function liveAccountNumber() {return $this->v();}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live API Password»
	 * @return string
	 */
	protected function livePassword() {return $this->p();}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Publishable Key»
	 * @return string
	 */
	protected function livePublishableKey() {return $this->v();}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Secret Key»
	 * @return string
	 */
	protected function liveSecretKey() {return $this->p();}

	/**
	 * 2016-05-22
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Secret Word»
	 * @return string
	 */
	protected function liveSecretWord() {return $this->p();}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live API Username»
	 * @return string
	 */
	protected function liveUsername() {return $this->v();}

	/**
	 * 2016-05-18
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Account Number»
	 * @return string
	 */
	protected function testAccountNumber() {return $this->v();}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Sandbox API Password»
	 * @return string
	 */
	protected function testPassword() {return $this->p();}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Publishable Key»
	 * @return string
	 */
	protected function testPublishableKey() {return $this->v();}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Secret Key»
	 * @return string
	 */
	protected function testSecretKey() {return $this->p();}

	/**
	 * 2016-05-22
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Sandbox Secret Word»
	 * @return string
	 */
	protected function testSecretWord() {return $this->p();}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Sandbox API Username»
	 * @return string
	 */
	protected function testUsername() {return $this->v();}

	/**
	 * 2016-05-20
	 * @return string
	 */
	private function password() {return $this->testable();}

	/**
	 * 2016-03-02
	 * @return string
	 */
	private function secretKey() {return $this->testable();}

	/**
	 * 2016-05-20
	 * @return string
	 */
	private function username() {return $this->testable();}
}