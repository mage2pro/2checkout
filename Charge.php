<?php
namespace Dfe\TwoCheckout;
use Dfe\TwoCheckout\LineItem\Product as LIP;
use Dfe\TwoCheckout\Settings as S;
use Magento\Payment\Model\Info;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Payment as OrderPayment;
class Charge extends \Df\Payment\Charge\WithToken {
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
	 * 2016-05-23
	 * @return array(string => string)|null
	 */
	private function lineItem_discount() {
		return !$this->o()->getBaseDiscountAmount() ? null : LineItem::buildLI(
			'coupon'
			, $this->o()->getBaseDiscountAmount()
			, df_cc_clean(': ',
				$this->o()->getDiscountDescription() === $this->o()->getCouponCode()
					? $this->o()['coupon_rule_name'] : null
				,$this->o()->getDiscountDescription())
		);
	}

	/**
	 * 2016-05-23
	 * @return array(string => string)|null
	 */
	private function lineItem_shipping() {
		return !$this->o()->getBaseShippingAmount() ? null : LineItem::buildLI(
			'shipping'
			, $this->o()->getBaseShippingAmount()
			, $this->o()->getShippingDescription()
			, true
		);
	}

	/**
	 * 2016-05-23
	 * @return array(string => string)|null
	 */
	private function lineItem_tax() {
		return !$this->o()->getBaseTaxAmount() ? null : LineItem::buildLI(
			'tax', $this->o()->getBaseTaxAmount()
		);
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
				$result[]= LIP::buildP($item);
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
			$result[]= LineItem::buildLI(
				$rest > 0 ? 'tax' : 'coupon', $rest, 'Correction', false, 'correction'
			);
		}
		return $result;
	}

	/**
	 * 2016-05-19
	 * @param InfoInterface|Info|OrderPayment $payment
	 * @param string $token
	 * @param float|null $amount [optional]
	 * @return array(string => mixed)
	 */
	public static function request(InfoInterface $payment, $token, $amount = null) {
		return \Twocheckout_Charge::auth((new self([
			self::$P__AMOUNT => $amount
			, self::$P__PAYMENT => $payment
			, self::$P__TOKEN => $token
		]))->_requestParams());
	}
}