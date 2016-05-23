<?php
namespace Dfe\TwoCheckout;
use Magento\Framework\App\ScopeInterface;
class Settings extends \Df\Core\Settings {
	/**
	 * 2016-05-18
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Account Number»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	public function accountNumber($s = null) {
		return $this->test($s) ? $this->testAccountNumber($s) : $this->liveAccountNumber($s);
	}

	/**
	 * 2016-02-27
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Enable?»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return bool
	 */
	public function enable($s = null) {return $this->b(__FUNCTION__, $s);}

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
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return bool
	 */
	public function passOrderItems($s = null) {return $this->b(__FUNCTION__, $s);}

	/**
	 * 2016-03-09
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Prefill the Payment Form with Test Data?»
	 * @see \Dfe\TwoCheckout\Source\Prefill::map()
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string|false
	 */
	public function prefill($s = null) {return $this->bv(__FUNCTION__, $s);}

	/**
	 * 2016-03-02
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	public function publishableKey($s = null) {
		return $this->test($s) ? $this->testPublishableKey($s) : $this->livePublishableKey($s);
	}

	/**
	 * 2016-05-22
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	public function secretWord($s = null) {
		return $this->test($s) ? $this->testSecretWord($s) : $this->liveSecretWord($s);
	}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Mode?»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return bool
	 */
	public function test($s = null) {return $this->b(__FUNCTION__, $s);}

	/**
	 * @override
	 * @used-by \Df\Core\Settings::v()
	 * @return string
	 */
	protected function prefix() {return 'df_payment/two_checkout/';}

	/**
	 * 2016-05-18
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Account Number»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function liveAccountNumber($s = null) {return $this->v(__FUNCTION__, $s);}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live API Password»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function livePassword($s = null) {return $this->p(__FUNCTION__, $s);}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Publishable Key»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function livePublishableKey($s = null) {return $this->v(__FUNCTION__, $s);}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Secret Key»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function liveSecretKey($s = null) {return $this->p(__FUNCTION__, $s);}

	/**
	 * 2016-05-22
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live Secret Word»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function liveSecretWord($s = null) {return $this->p(__FUNCTION__, $s);}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Live API Username»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function liveUsername($s = null) {return $this->v(__FUNCTION__, $s);}

	/**
	 * 2016-05-20
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function password($s = null) {
		return $this->test($s) ? $this->testPassword($s) : $this->livePassword($s);
	}

	/**
	 * 2016-03-02
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function secretKey($s = null) {
		return $this->test($s) ? $this->testSecretKey($s) : $this->liveSecretKey($s);
	}

	/**
	 * 2016-05-18
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Account Number»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function testAccountNumber($s = null) {return $this->v(__FUNCTION__, $s);}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Sandbox API Password»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function testPassword($s = null) {return $this->p(__FUNCTION__, $s);}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Publishable Key»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function testPublishableKey($s = null) {return $this->v(__FUNCTION__, $s);}

	/**
	 * 2016-03-02
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Test Secret Key»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function testSecretKey($s = null) {return $this->p(__FUNCTION__, $s);}

	/**
	 * 2016-05-22
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Sandbox Secret Word»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function testSecretWord($s = null) {return $this->p(__FUNCTION__, $s);}

	/**
	 * 2016-05-20
	 * «Mage2.PRO» → «Payment» → «2Checkout» → «Sandbox API Username»
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function testUsername($s = null) {return $this->v(__FUNCTION__, $s);}

	/**
	 * 2016-05-20
	 * @param null|string|int|ScopeInterface $s [optional]
	 * @return string
	 */
	private function username($s = null) {
		return $this->test($s) ? $this->testUsername($s) : $this->liveUsername($s);
	}

	/** @return $this */
	public static function s() {static $r; return $r ? $r : $r = df_o(__CLASS__);}
}


