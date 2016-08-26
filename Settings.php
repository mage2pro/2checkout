<?php
namespace Dfe\TwoCheckout;
use \Twocheckout as T;
/** @method static Settings s() */
final class Settings extends \Df\Payment\Settings\BankCard {
	/**
	 * 2016-05-18
	 * @return string
	 */
	public function accountNumber() {
		return $this->test() ? $this->testAccountNumber() : $this->liveAccountNumber();
	}

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
	 * @return string
	 */
	public function publishableKey() {
		return $this->test() ? $this->testPublishableKey() : $this->livePublishableKey();
	}

	/**
	 * 2016-05-22
	 * @return string
	 */
	public function secretWord() {
		return $this->test() ? $this->testSecretWord() : $this->liveSecretWord();
	}

	/**
	 * 2016-05-18
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Account Number»
	 * @return string
	 */
	private function liveAccountNumber() {return $this->v();}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live API Password»
	 * @return string
	 */
	private function livePassword() {return $this->p();}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Publishable Key»
	 * @return string
	 */
	private function livePublishableKey() {return $this->v();}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Secret Key»
	 * @return string
	 */
	private function liveSecretKey() {return $this->p();}

	/**
	 * 2016-05-22
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Secret Word»
	 * @return string
	 */
	private function liveSecretWord() {return $this->p();}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live API Username»
	 * @return string
	 */
	private function liveUsername() {return $this->v();}

	/**
	 * 2016-05-20
	 * @return string
	 */
	private function password() {
		return $this->test() ? $this->testPassword() : $this->livePassword();
	}

	/**
	 * 2016-03-02
	 * @return string
	 */
	private function secretKey() {
		return $this->test() ? $this->testSecretKey() : $this->liveSecretKey();
	}

	/**
	 * 2016-05-18
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Account Number»
	 * @return string
	 */
	private function testAccountNumber() {return $this->v();}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Sandbox API Password»
	 * @return string
	 */
	private function testPassword() {return $this->p();}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Publishable Key»
	 * @return string
	 */
	private function testPublishableKey() {return $this->v();}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Secret Key»
	 * @return string
	 */
	private function testSecretKey() {return $this->p();}

	/**
	 * 2016-05-22
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Sandbox Secret Word»
	 * @return string
	 */
	private function testSecretWord() {return $this->p();}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Sandbox API Username»
	 * @return string
	 */
	private function testUsername() {return $this->v();}

	/**
	 * 2016-05-20
	 * @return string
	 */
	private function username() {
		return $this->test() ? $this->testUsername() : $this->liveUsername();
	}
}


