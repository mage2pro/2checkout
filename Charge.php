<?php
namespace Dfe\TwoCheckout;
use Dfe\TwoCheckout\LineItem\Product as LIP;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Item as OI;
use Magento\Sales\Model\Order\Payment as OrderPayment;
/**
 * 2016-05-20
 * https://www.2checkout.com/documentation/payment-api/create-sale
 * https://github.com/2Checkout/2checkout-php/wiki/Charge_Authorize#example-usage
 * @method Method m()
 * @method Settings ss()
 */
final class Charge extends \Df\Payment\Charge\WithToken {
	/**
	 * 2016-05-19
	 * @return array(string => mixed)
	 */
	private function _request() {return [
		/**
		 * 2016-05-19
		 * «sellerId»
		 * «Your 2Checkout account number. Required»
		 * https://www.2checkout.com/documentation/payment-api/create-sale
		 * 'sellerId' => S::s()->accountNumber()
		 * несмотря на пример документации https://github.com/2Checkout/2checkout-php/wiki/Charge_Authorize#example-usage
		 * этот параметр указывать не только не обязательно,
		 * но и не имеет смысла, потому что значение перетрётся
		 * заданным при вызове @see \Twocheckout::sellerId()
		 * https://github.com/2Checkout/2checkout-php/blob/cbac8da/lib/Twocheckout/Api/TwocheckoutApi.php#L28
		 *
		 * «privateKey» указывать не нужно и нет смысла по той же причине:
		 * https://github.com/2Checkout/2checkout-php/blob/cbac8da/lib/Twocheckout/Api/TwocheckoutApi.php#L27
		 */
		// 2016-05-19
		// «Your custom order identifier. Required.»
		// https://www.2checkout.com/documentation/payment-api/create-sale
		'merchantOrderId' => $this->oii()
		// 2016-05-19
		// «The credit card token. Required.»
		// https://www.2checkout.com/documentation/payment-api/create-sale
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
		,'currency' => $this->currencyC()
		/**
		 * 2016-05-19
		 * «Object that defines the billing address using the attributes specified below.
		 * Required. (Passed as a sub object to the Authorization Object.)»
		 * https://www.2checkout.com/documentation/payment-api/create-sale
		 */
		,'billingAddr' => Address::build($this->addressBS(), $isBilling = true)
		/**
		 * 2016-05-19
		 * «Object that defines the shipping address using the attributes specified below.
		 * Optional. Only required if a shipping lineitem is passed.
		 * (Passed as a sub object to the Authorization Object.) »
		 * https://www.2checkout.com/documentation/payment-api/create-sale
		 */
		,'shippingAddr' => Address::build($this->addressSB())
		/**
		 * 2016-05-21
		 * Пока не знаю, как передавать нестандартные параметры.
		 * Похоже, для Payment API такой возможности пока нет.
		 * https://mail.google.com/mail/u/0/#sent/154d5138c541ed85
		 * Вариант 'x_custom_username' => $this->o()->getIncrementId()
		 * у меня не работает.
		 *
		 * 2016-05-23
		 * «"Unknown parameters" are not supported with the Payment API,
		 * as custom parameters are not supported with the payment API.
		 * Your site needs to record any custom data with the order, and associate that data with the API's response after the sale is made.»
		 * https://mail.google.com/mail/u/0/#inbox/154d5138c541ed85
		 */
	] + ($this->ss()->passOrderItems()
		/**
		 * 2016-05-23
		 * «Array of lineitem objects using the attributes specified below.
		 * Will be returned in the order that they are passed in.
		 * (Passed as a sub object to the Authorization Object.)
		 * (Only Use if you are not passing in total.)»
		 * https://www.2checkout.com/documentation/payment-api/create-sale
		 */
		? ['lineItems' => $this->lineItems()]
		/**
		 * 2016-05-19
		 * «The Sale Total. Format: 0.00-99999999.99,
		 * defaults to 0 if a value isn’t passed in or if value is incorrectly formatted,
		 * no negatives (Only Use if you are not passing in lineItems.)»
		 * https://www.2checkout.com/documentation/payment-api/create-sale
		 */
		: ['total' => $this->amountF()]
	);}

	/**
	 * 2016-05-23
	 * @return array(string => string)|null
	 */
	private function lineItem_discount() {return
		!$this->o()->getDiscountAmount() ? null : LineItem::buildLI(
			'coupon'
			,$this->cFromOrderF($this->o()->getDiscountAmount())
			,df_ccc(': ',
				$this->o()->getDiscountDescription() === $this->o()->getCouponCode()
					? $this->o()['coupon_rule_name'] : null
				,$this->o()->getDiscountDescription()
			)
		)
	;}

	/**
	 * 2016-05-23
	 * @return array(string => string)|null
	 */
	private function lineItem_shipping() {return
		!$this->o()->getShippingAmount() ? null : LineItem::buildLI(
			'shipping'
			,$this->cFromOrderF($this->o()->getShippingAmount())
			,$this->o()->getShippingDescription()
			,true
		)
	;}

	/**
	 * 2016-05-23
	 * @return array(string => string)|null
	 */
	private function lineItem_tax() {return
		!$this->o()->getTaxAmount() ? null :
			LineItem::buildLI('tax', $this->cFromOrderF($this->o()->getTaxAmount()))
	;}
	
	/**
	 * 2016-05-23
	 * @return array(array(string => string))
	 */
	private function lineItems() {
		/** @var array(array(string => string)) $result */
		$result = $this->oiLeafs(function(OI $item) {return LIP::buildP($this, $item);});
		$result = df_clean(array_merge($result, [
			$this->lineItem_shipping()
			,$this->lineItem_discount()
			,$this->lineItem_tax()
		]));
		/** @var float $total */
		$total = array_sum(array_map(function(array $item) {return
			floatval($item['price'])
			* dfa($item, 'quantity', 1)
			* ('coupon' === $item['type'] ? -1 : 1)
		;}, $result));
		/** @var float $rest */
		$rest = $this->amount() - $total;
		if (!df_is0($rest)) {
			$result[]= LineItem::buildLI(
				$rest > 0 ? 'tax' : 'coupon'
				,$this->amountFormat($rest)
				,'Correction'
				,false
				,'correction'
			);
		}
		return $result;
	}

	/**
	 * 2016-05-19
	 * @param Method $method
	 * @param string $token
	 * @param float|null $amount [optional]
	 * @return array(string => mixed)
	 */
	static function request(Method $method, $token, $amount = null) {return
		(new self([
			self::$P__AMOUNT => $amount
			, self::$P__METHOD => $method
			, self::$P__TOKEN => $token
		]))->_request()
	;}
}