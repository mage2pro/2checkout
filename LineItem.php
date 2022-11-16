<?php
namespace Dfe\TwoCheckout;
/**
 * 2016-05-29
 * @see \Dfe\TwoCheckout\LineItem\Product
 */
class LineItem extends \Df\Core\O {
	/**
	 * 2016-05-29
	 * @used-by self::buildLI()
	 * @see \Dfe\TwoCheckout\LineItem\Product::build()
	 * @return array(string => string)
	 */
	protected function build():array {return df_clean([
		'type' => $this->type()
		,'name' => $this->name()
		,'price' => $this->price()
		# 2016-05-29
		# Почему-то пока этот параметр игнорируется для всех line items, кроме shipping.
		# https://mail.google.com/mail/u/0/#sent/154fa43ce41483c3
		,'tangible' => $this->tangible() ? 'Y' : 'N'
		,'productId' => $this->id()
	]);}

	/**
	 * 2016-05-29 «Your custom product identifier. Optional»
	 * @used-by self::build()
	 * @see \Dfe\TwoCheckout\LineItem\Product::id()
	 */
	protected function id():string {return $this[self::$P__ID];}

	/**
	 * 2016-05-29
	 * @used-by self::name()
	 * @see \Dfe\TwoCheckout\LineItem\Product::nameRaw()
	 */
	protected function nameRaw():string {return $this[self::$P__NAME];}

	/**
	 * 2016-05-23
	 * «Price of the line item.
	 * Format: 0.00-99999999.99, defaults to 0 if a value isn’t passed in
	 * or if value is incorrectly formatted, no negatives
	 * (use positive values for coupons). Required».
	 * Здесь нужно указывать именно цену товара, а не цену строки заказа.
	 * Т.е. умножать на количество здесь не надо: проверил опытным путём. 
	 * @used-by self::build()
	 * @see \Dfe\TwoCheckout\LineItem\Product::price()
	 */
	protected function price():string {return $this[self::$P__PRICE];}

	/**
	 * 2016-05-23 «Y or N. Will default to Y if the type is shipping. Optional»
	 * @used-by self::build()
	 * @see \Dfe\TwoCheckout\LineItem\Product::tangible()
	 */
	protected function tangible():bool {return $this[self::$P__TANGIBLE];}

	/**
	 * 2016-05-23
	 * «The type of line item that is being passed in.
	 * (Always Lower Case, ‘product’, ‘shipping’, ‘tax’ or ‘coupon’, defaults to ‘product’) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * @used-by self::build()
	 * @see \Dfe\TwoCheckout\LineItem\Product::type()
	 */
	protected function type():string {return $this[self::$P__TYPE];}

	/**
	 * 2016-05-23
	 * «Name of the item passed in. (128 characters max, cannot use ‘<' or '>’,
	 * defaults to capitalized version of ‘type’.) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * 2016-05-29
	 * The fallback to the «capitalized version of ‘type’» does not work:
	 * the server responds "Bad request - parameter error" if the "name" is absent.
	 * https://mail.google.com/mail/u/0/#sent/154f4ade595abd5b
	 */
	private function name():string {return dfc($this, function() {return self::adjustText(
		$this->nameRaw() ?: df_ucfirst($this->type())
	);});}

	/**
	 * @used-by \Dfe\TwoCheckout\Charge::liDiscount()
	 * @used-by \Dfe\TwoCheckout\Charge::liShipping()
	 * @used-by \Dfe\TwoCheckout\Charge::liTax()
	 * @used-by \Dfe\TwoCheckout\Charge::lineItems()
	 * @return array(string => string)
	 */
	static function buildLI(string $type, string $price, string $name = '', bool $tangible = false, string $id = ''):array {
		return (new self([
			self::$P__ID => $id ?: $type
			,self::$P__NAME => $name
			,self::$P__PRICE => $price
			,self::$P__TANGIBLE => $tangible
			,self::$P__TYPE => $type
		]))->build();
	}

	/**
	 * 2016-05-29
	 * В именах товаров недопустимы символы < и >:
	 * «Name of the item passed in. (128 characters max, cannot use ‘<' or '>’,
	 * defaults to capitalized version of ‘type’.) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 *
	 * Думаю, в description они тоже недопустимы...
	 * Похоже, description также имеет ограничения по длине, как и name.
	 *
	 * Опытным путём установил, что у description такое же ограничение по длине, как и у name.
	 * @param string $s
	 * @return string
	 */
	protected static function adjustText($s) {return df_chop(strtr($s, ['<' => '«', '>' => '»']), 128);}

	/** @var string */
	private static $P__ID = 'id';

	/** @var string */
	private static $P__NAME = 'name';

	/** @var string */
	private static $P__PRICE = 'price';

	/** @var string */
	private static $P__TANGIBLE = 'tangible';

	/** @var string */
	private static $P__TYPE = 'type';
}