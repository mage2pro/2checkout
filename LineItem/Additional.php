<?php
namespace Dfe\TwoCheckout\LineItem;
# 2022-11-16
final class Additional extends \Dfe\TwoCheckout\LineItem {
	/**
	 * 2016-05-29 «Your custom product identifier. Optional»
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::id()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 */
	protected function id():string {return $this->_id;}

	/**
	 * 2016-05-29
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::nameRaw()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 */
	protected function nameRaw():string {return $this->_name;}

	/**
	 * 2016-05-23
	 * «Price of the line item.
	 * Format: 0.00-99999999.99, defaults to 0 if a value isn’t passed in
	 * or if value is incorrectly formatted, no negatives
	 * (use positive values for coupons). Required».
	 * Здесь нужно указывать именно цену товара, а не цену строки заказа.
	 * Т.е. умножать на количество здесь не надо: проверил опытным путём.
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::price()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 */
	protected function price():string {return $this->_price;}

	/**
	 * 2016-05-23 «Y or N. Will default to Y if the type is shipping. Optional»
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::tangible()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 */
	protected function tangible():bool {return $this->_tangible;}

	/**
	 * 2016-05-23
	 * «The type of line item that is being passed in.
	 * (Always Lower Case, ‘product’, ‘shipping’, ‘tax’ or ‘coupon’, defaults to ‘product’) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::type()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 */
	protected function type():string {return $this->_type;}

	/**
	 * 2022-11-16
	 * @used-by self::p()
	 */
	private function __construct(string $type, string $price, string $name = '', bool $tangible = false, string $id = '') {
		$this->_id = $id ?: $type;
		$this->_name = $name;
		$this->_price = $price;
		$this->_tangible = $tangible;
		$this->_type = $type;
	}

	/**
	 * 2022-11-16
	 * @used-by self::__construct()
	 * @used-by self::id()
	 * @var string
	 */
	private $_id;

	/**
	 * 2022-11-16
	 * @used-by self::__construct()
	 * @used-by self::nameRaw()
	 * @var string
	 */
	private $_name;

	/**
	 * 2022-11-16
	 * @used-by self::__construct()
	 * @used-by self::price()
	 * @var string
	 */
	private $_price;

	/**
	 * 2022-11-16
	 * @used-by self::__construct()
	 * @used-by self::tangible()
	 * @var bool
	 */
	private $_tangible;

	/**
	 * 2022-11-16
	 * @used-by self::__construct()
	 * @used-by self::type()
	 * @var string
	 */
	private $_type;

	/**
	 * 2022-11-16 It works in PHP ≥ 5.4: https://3v4l.org/V4sAU
	 * @used-by \Dfe\TwoCheckout\Charge::liDiscount()
	 * @used-by \Dfe\TwoCheckout\Charge::liShipping()
	 * @used-by \Dfe\TwoCheckout\Charge::liTax()
	 * @used-by \Dfe\TwoCheckout\Charge::lineItems()
	 * @return array(string => string)
	 */
	static function p(string $type, string $price, string $name = '', bool $tangible = false, string $id = ''):array {
		return (new self($type, $price, $name, $tangible, $id))->build();
	}
}