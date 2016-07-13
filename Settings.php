<?php
namespace Dfe\TwoCheckout;
use Magento\Framework\App\ScopeInterface as S;
/** @method static Settings s() */
class Settings extends \Df\Payment\Settings {
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
		\Twocheckout::sandbox($this->test());
		\Twocheckout::username($this->username());
		\Twocheckout::password($this->password());
		\Twocheckout::privateKey($this->secretKey());
		\Twocheckout::sellerId($this->accountNumber());
	}

	/**
	 * 2016-05-23
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Pass Order Items to the Payment Gateway?»
	 * @return bool
	 */
	public function passOrderItems() {return $this->b(__FUNCTION__);}

	/**
	 * 2016-03-09
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Prefill the Payment Form with Test Data?»
	 * @see \Dfe\TwoCheckout\Source\Prefill::map()
	 * @return string|false
	 */
	public function prefill() {return $this->bv(__FUNCTION__);}

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
	 * @override
	 * @used-by \Df\Core\Settings::v()
	 * @return string
	 */
	protected function prefix() {return 'df_payment/two_checkout/';}

	/**
	 * 2016-05-18
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Account Number»
	 * @return string
	 */
	private function liveAccountNumber() {return $this->v(__FUNCTION__);}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live API Password»
	 * @return string
	 */
	private function livePassword() {return $this->p(__FUNCTION__);}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Publishable Key»
	 * @return string
	 */
	private function livePublishableKey() {return $this->v(__FUNCTION__);}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Secret Key»
	 * @return string
	 */
	private function liveSecretKey() {return $this->p(__FUNCTION__);}

	/**
	 * 2016-05-22
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Secret Word»
	 * @return string
	 */
	private function liveSecretWord() {return $this->p(__FUNCTION__);}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live API Username»
	 * @return string
	 */
	private function liveUsername() {return $this->v(__FUNCTION__);}

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
	private function testAccountNumber() {return $this->v(__FUNCTION__);}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Sandbox API Password»
	 * @return string
	 */
	private function testPassword() {return $this->p(__FUNCTION__);}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Publishable Key»
	 * @return string
	 */
	private function testPublishableKey() {return $this->v(__FUNCTION__);}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Secret Key»
	 * @return string
	 */
	private function testSecretKey() {return $this->p(__FUNCTION__);}

	/**
	 * 2016-05-22
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Sandbox Secret Word»
	 * @return string
	 */
	private function testSecretWord() {return $this->p(__FUNCTION__);}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Sandbox API Username»
	 * @return string
	 */
	private function testUsername() {return $this->v(__FUNCTION__);}

	/**
	 * 2016-05-20
	 * @return string
	 */
	private function username() {
		return $this->test() ? $this->testUsername() : $this->liveUsername();
	}
}


