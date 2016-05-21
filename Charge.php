<?php
namespace Dfe\TwoCheckout;
use Dfe\TwoCheckout\Settings as S;
use Magento\Payment\Model\Info;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Store\Model\Store;
class Charge extends \Df\Core\O {
	/**
	 * 2016-05-19
	 * @return array(string => mixed)
	 */
	private function _requestParams() {
		/** @var array(string => mixed) $result */
		$result = [
			/**
			 * 2016-05-19
			 * «Your 2Checkout account number. Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 * 'sellerId' => S::s()->accountNumber()
			 * несмотря на пример документации https://github.com/2Checkout/2checkout-php/wiki/Charge_Authorize#example-usage
			 * этот параметр указывать не только не обязательно,
			 * но и не имеет смысла, потому что значение перетрётся
			 * заданным при вызове @see \Twocheckout::sellerId()
			 * https://github.com/2Checkout/2checkout-php/blob/cbac8da/lib/Twocheckout/Api/TwocheckoutApi.php#L28
			 *
			 * privateKey указывать не нужно и нет смысла по той же причине:
			 * https://github.com/2Checkout/2checkout-php/blob/cbac8da/lib/Twocheckout/Api/TwocheckoutApi.php#L27
			 */
			/**
			 * 2016-05-19
			 * «Your custom order identifier. Required.»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			'merchantOrderId' => $this->order()->getIncrementId()
			/**
			 * 2016-05-19
			 * «The credit card token. Required.»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'token' => $this->token()
			/**
			 * 2016-05-19
			 * «Use to specify the currency for the sale. Required.»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 *
			 * «As a merchant, it’s important to offer your customers a recognizable,
			 * local buying experience when buying from you.
			 * Localized payment options offer a buyer an improved customer experience
			 * that is both familiar while boosting confidence in their purchase.
			 * 2Checkout offers 87 currencies so that, as a merchant,
			 * you may provide your customer the option
			 * to pay with the currency of their choice.
			 * The information below lists what currencies are available for your country.
			 * Please be aware the availability of currencies vary from region to region,
			 * so this information is a general reference guide and could vary.»
			 * http://help.2checkout.com/articles/Knowledge_Article/2Checkout-Offers-87-Currency-Options
			 */
			,'currency' => $this->currencyCode()
			/**
			 * 2016-05-19
			 * «Object that defines the billing address using the attributes specified below.
			 * Required. (Passed as a sub object to the Authorization Object.)»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'billingAddr' => Address::build($this->addressBilling(), $isBilling = true)
			/**
			 * 2016-05-19
			 * «Object that defines the shipping address using the attributes specified below.
			 * Optional. Only required if a shipping lineitem is passed.
			 * (Passed as a sub object to the Authorization Object.) »
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'shippingAddr' => Address::build($this->addressShipping())
			/**
			 * 2016-05-19
			 * «The Sale Total. Format: 0.00-99999999.99,
			 * defaults to 0 if a value isn’t passed in or if value is incorrectly formatted,
			 * no negatives (Only Use if you are not passing in lineItems.)»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 *
			 * 2016-05-20
			 * Решил пока не связываться с опцией «lineItems».
			 * «Array of lineitem objects using the attributes specified below.
			 * Will be returned in the order that they are passed in.
			 * (Passed as a sub object to the Authorization Object.)
			 * (Only Use if you are not passing in total.)»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'total' => $this->amount()
			/**
			 * 2016-05-21
			 * Пока не знаю, как передавать нестандартные параметры.
			 * Похоже, для Payment API такой возможности пока нет.
			 * https://mail.google.com/mail/u/0/#sent/154d5138c541ed85
			 * Вариант 'x_custom_username' => $this->order()->getIncrementId()
			 * у меня не работает.
			 */
		];
		return $result;
	}

	/**
	 * 2016-05-19
	 * @return OrderAddress
	 */
	private function addressBilling() {
		if (!isset($this->{__METHOD__})) {
			/** @var OrderAddress $result */
			$result = $this->order()->getBillingAddress();
			$this->{__METHOD__} = $result ? $result : $this->order()->getShippingAddress();
			df_assert($this->{__METHOD__});
		}
		return $this->{__METHOD__};
	}

	/**
	 * 2016-05-19
	 * @return OrderAddress
	 */
	private function addressShipping() {
		if (!isset($this->{__METHOD__})) {
			/** @var OrderAddress $result */
			$result = $this->order()->getShippingAddress();
			$this->{__METHOD__} = $result ? $result : $this->order()->getBillingAddress();
			df_assert($this->{__METHOD__});
		}
		return $this->{__METHOD__};
	}

	/** @return float */
	private function amount() {return $this[self::$P__AMOUNT];}

	/** @return string */
	private function currencyCode() {return $this->order()->getBaseCurrencyCode();}

	/** @return Order */
	private function order() {return $this->payment()->getOrder();}

	/** @return InfoInterface|Info|OrderPayment */
	private function payment() {return $this[self::$P__PAYMENT];}

	/** @return string */
	private function token() {return $this[self::$P__TOKEN];}

	/**
	 * 2016-05-06
	 * @override
	 * @return void
	 */
	protected function _construct() {
		parent::_construct();
		$this
			->_prop(self::$P__AMOUNT, RM_V_FLOAT)
			->_prop(self::$P__PAYMENT, InfoInterface::class)
			->_prop(self::$P__TOKEN, RM_V_STRING_NE)
		;
	}

	/** @var string */
	private static $P__AMOUNT = 'amount';
	/** @var string */
	private static $P__PAYMENT = 'payment';
	/** @var string */
	private static $P__TOKEN = 'token';

	/**
	 * 2016-05-19
	 * @param InfoInterface|Info|OrderPayment $payment
	 * @param string $token
	 * @param float|null $amount [optional]
	 * @return array(string => mixed)
	 */
	public static function request(InfoInterface $payment, $token, $amount = null) {
		return \Twocheckout_Charge::auth((new self([
			self::$P__AMOUNT => $amount ? $amount : $payment->getBaseAmountOrdered()
			, self::$P__PAYMENT => $payment
			, self::$P__TOKEN => $token
		]))->_requestParams());
	}
}