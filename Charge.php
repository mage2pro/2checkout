<?php
namespace Dfe\TwoCheckout;
use Dfe\TwoCheckout\Settings as S;
use Magento\Payment\Model\Info;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Payment as OrderPayment;
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
			'merchantOrderId' => $this->o()->getIncrementId()
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
			 * 2016-05-21
			 * Пока не знаю, как передавать нестандартные параметры.
			 * Похоже, для Payment API такой возможности пока нет.
			 * https://mail.google.com/mail/u/0/#sent/154d5138c541ed85
			 * Вариант 'x_custom_username' => $this->o()->getIncrementId()
			 * у меня не работает.
			 */
		];
		if (!S::s()->passOrderItems()) {
			/**
			 * 2016-05-19
			 * «The Sale Total. Format: 0.00-99999999.99,
			 * defaults to 0 if a value isn’t passed in or if value is incorrectly formatted,
			 * no negatives (Only Use if you are not passing in lineItems.)»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			$result['total'] = $this->amount();
		}
		else {
			/**
			 * 2016-05-23
			 * «Array of lineitem objects using the attributes specified below.
			 * Will be returned in the order that they are passed in.
			 * (Passed as a sub object to the Authorization Object.)
			 * (Only Use if you are not passing in total.)»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			$result['lineItems'] = $this->lineItems();
		}
		return $result;
	}

	/**
	 * 2016-05-19
	 * @return OrderAddress
	 */
	private function addressBilling() {
		if (!isset($this->{__METHOD__})) {
			/** @var OrderAddress $result */
			$result = $this->o()->getBillingAddress();
			$this->{__METHOD__} = $result ? $result : $this->o()->getShippingAddress();
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
			$result = $this->o()->getShippingAddress();
			$this->{__METHOD__} = $result ? $result : $this->o()->getBillingAddress();
			df_assert($this->{__METHOD__});
		}
		return $this->{__METHOD__};
	}

	/** @return float */
	private function amount() {return $this[self::$P__AMOUNT];}

	/** @return string */
	private function currencyCode() {return $this->o()->getBaseCurrencyCode();}

	/**
	 * 2016-05-23
	 * @return array(string => string)|null
	 */
	private function lineItem_discount() {
		return !$this->o()->getBaseDiscountAmount() ? null : [
			/**
			 * 2016-05-23
			 * «The type of line item that is being passed in.
			 * (Always Lower Case, ‘product’, ‘shipping’, ‘tax’ or ‘coupon’, defaults to ‘product’) Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			'type' => 'coupon'
			/**
			 * 2016-05-23
			 * «Name of the item passed in. (128 characters max, cannot use ‘<' or '>’,
			 * defaults to capitalized version of ‘type’.) Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'name' => df_cc_clean(': ',
				$this->o()->getDiscountDescription() === $this->o()->getCouponCode()
					? $this->o()['coupon_rule_name'] : null
				,$this->o()->getDiscountDescription())
			/**
			 * 2016-05-23
			 * «Quantity of the item passed in.
			 * (0-999, defaults to 1 if not passed in or incorrectly formatted.) Optional»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'quantity' => 1
			/**
			 * 2016-05-23
			 * «Price of the line item.
			 * Format: 0.00-99999999.99, defaults to 0 if a value isn’t passed in
			 * or if value is incorrectly formatted, no negatives
			 * (use positive values for coupons). Required»
			 *
			 * Здесь нужно указывать именно цену товара, а не цену строки заказа.
			 * Т.е. умножать на количество здесь не надо: проверил опытным путём.
			 */
			,'price' => abs($this->o()->getBaseDiscountAmount())
			/**
			 * 2016-05-23
			 * «Y or N. Will default to Y if the type is shipping. Optional»
			 */
			,'tangible' => 'N'
		];
	}

	/**
	 * 2016-05-23
	 * @return array(string => string)|null
	 */
	private function lineItem_shipping() {
		return !$this->o()->getBaseShippingAmount() ? null : [
			/**
			 * 2016-05-23
			 * «The type of line item that is being passed in.
			 * (Always Lower Case, ‘product’, ‘shipping’, ‘tax’ or ‘coupon’, defaults to ‘product’) Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			'type' => 'shipping'
			/**
			 * 2016-05-23
			 * «Name of the item passed in. (128 characters max, cannot use ‘<' or '>’,
			 * defaults to capitalized version of ‘type’.) Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'name' => $this->o()->getShippingDescription()
			/**
			 * 2016-05-23
			 * «Quantity of the item passed in.
			 * (0-999, defaults to 1 if not passed in or incorrectly formatted.) Optional»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'quantity' => 1
			/**
			 * 2016-05-23
			 * «Price of the line item.
			 * Format: 0.00-99999999.99, defaults to 0 if a value isn’t passed in
			 * or if value is incorrectly formatted, no negatives
			 * (use positive values for coupons). Required»
			 *
			 * Здесь нужно указывать именно цену товара, а не цену строки заказа.
			 * Т.е. умножать на количество здесь не надо: проверил опытным путём.
			 */
			,'price' => $this->o()->getBaseShippingAmount()
			/**
			 * 2016-05-23
			 * «Y or N. Will default to Y if the type is shipping. Optional»
			 */
			,'tangible' => 'Y'
			/**
			 * 2016-05-23
			 * «Your custom product identifier. Optional»
			 * Например: «flatrate_flatrate»
			 */
			,'productId' =>	$this->o()->getShippingMethod()
		];
	}

	/**
	 * 2016-05-23
	 * @return array(string => string)|null
	 */
	private function lineItem_tax() {
		return !$this->o()->getBaseTaxAmount() ? null : [
			/**
			 * 2016-05-23
			 * «The type of line item that is being passed in.
			 * (Always Lower Case, ‘product’, ‘shipping’, ‘tax’ or ‘coupon’, defaults to ‘product’) Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			'type' => 'tax'
			/**
			 * 2016-05-23
			 * «Name of the item passed in. (128 characters max, cannot use ‘<' or '>’,
			 * defaults to capitalized version of ‘type’.) Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'name' => 'Tax'
			/**
			 * 2016-05-23
			 * «Quantity of the item passed in.
			 * (0-999, defaults to 1 if not passed in or incorrectly formatted.) Optional»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'quantity' => 1
			/**
			 * 2016-05-23
			 * «Price of the line item.
			 * Format: 0.00-99999999.99, defaults to 0 if a value isn’t passed in
			 * or if value is incorrectly formatted, no negatives
			 * (use positive values for coupons). Required»
			 *
			 * Здесь нужно указывать именно цену товара, а не цену строки заказа.
			 * Т.е. умножать на количество здесь не надо: проверил опытным путём.
			 */
			,'price' => $this->o()->getBaseTaxAmount()
		];
	}
	
	/**
	 * 2016-05-23
	 * @return array(array(string => string))
	 */
	private function lineItems() {
		/** @var array(array(string => string)) $result */
		$result = [];
		foreach ($this->o()->getItems() as $item) {
			/** @var OrderItem $item */
			/**
			 * 2016-03-24
			 * Если товар является настраиваемым, то
			 * @uses \Magento\Sales\Model\Order::getItems()
			 * будет содержать как настраиваемый товар, так и его простой вариант.
			 */
			if (!$item->getChildrenItems()) {
				$result[]= LineItem::build($item);
			}
		}
		$result = df_clean(array_merge($result, [
			$this->lineItem_shipping()
			,$this->lineItem_discount()
			,$this->lineItem_tax()
		]));
		/** @var float $total */
		$total = 0.0;
		foreach ($result as $item) {
			/** @var array(string => string|int|float) $item */
			$total += $item['price'] * dfa($item, 'quantity', 1) * ('coupon' === $item['type'] ? -1 : 1);
		}
		/** @var float $rest */
		$rest = $this->o()->getBaseTotalDue() - $total;
		if (abs($rest) >= 0.01) {
			$result[]= [
				/**
				 * 2016-05-23
				 * «The type of line item that is being passed in.
				 * (Always Lower Case, ‘product’, ‘shipping’, ‘tax’ or ‘coupon’, defaults to ‘product’) Required»
				 * https://www.2checkout.com/documentation/payment-api/create-sale
				 */
				'type' => $rest > 0 ? 'tax' : 'coupon'
				/**
				 * 2016-05-23
				 * «Name of the item passed in. (128 characters max, cannot use ‘<' or '>’,
				 * defaults to capitalized version of ‘type’.) Required»
				 * https://www.2checkout.com/documentation/payment-api/create-sale
				 */
				,'name' => 'Correction'
				/**
				 * 2016-05-23
				 * «Quantity of the item passed in.
				 * (0-999, defaults to 1 if not passed in or incorrectly formatted.) Optional»
				 * https://www.2checkout.com/documentation/payment-api/create-sale
				 */
				,'quantity' => 1
				/**
				 * 2016-05-23
				 * «Price of the line item.
				 * Format: 0.00-99999999.99, defaults to 0 if a value isn’t passed in
				 * or if value is incorrectly formatted, no negatives
				 * (use positive values for coupons). Required»
				 *
				 * Здесь нужно указывать именно цену товара, а не цену строки заказа.
				 * Т.е. умножать на количество здесь не надо: проверил опытным путём.
				 */
				,'price' => abs($rest)
			];
		}
		return $result;
	}

	/** @return Order */
	private function o() {return $this->payment()->getOrder();}

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