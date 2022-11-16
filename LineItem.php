<?php
namespace Dfe\TwoCheckout;
/**
 * 2016-05-29
 * @see \Dfe\TwoCheckout\LineItem\Additional
 * @see \Dfe\TwoCheckout\LineItem\Product
 */
abstract class LineItem extends \Df\Core\O {
	/**
	 * 2016-05-29 «Your custom product identifier. Optional»
	 * @used-by self::build()
	 * @see \Dfe\TwoCheckout\LineItem\Additional::id()
	 * @see \Dfe\TwoCheckout\LineItem\Product::id()
	 */
	abstract protected function id():string;

	/**
	 * 2016-05-29
	 * @used-by self::build()
	 * @see \Dfe\TwoCheckout\LineItem\Additional::nameRaw()
	 * @see \Dfe\TwoCheckout\LineItem\Product::nameRaw()
	 */
	abstract protected function nameRaw():string;

	/**
	 * 2016-05-23
	 * «Price of the line item.
	 * Format: 0.00-99999999.99, defaults to 0 if a value isn’t passed in
	 * or if value is incorrectly formatted, no negatives
	 * (use positive values for coupons). Required».
	 * Здесь нужно указывать именно цену товара, а не цену строки заказа.
	 * Т.е. умножать на количество здесь не надо: проверил опытным путём.
	 * @used-by self::build()
	 * @see \Dfe\TwoCheckout\LineItem\Additional::price()
	 * @see \Dfe\TwoCheckout\LineItem\Product::price()
	 */
	abstract protected function price():string;

	/**
	 * 2016-05-23 «Y or N. Will default to Y if the type is shipping. Optional»
	 * @used-by self::build()
	 * @see \Dfe\TwoCheckout\LineItem\Additional::tangible()
	 * @see \Dfe\TwoCheckout\LineItem\Product::tangible()
	 */
	abstract protected function tangible():bool;

	/**
	 * 2016-05-23
	 * «The type of line item that is being passed in.
	 * (Always Lower Case, ‘product’, ‘shipping’, ‘tax’ or ‘coupon’, defaults to ‘product’) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * @used-by self::build()
	 * @see \Dfe\TwoCheckout\LineItem\Additional::type()
	 * @see \Dfe\TwoCheckout\LineItem\Product::type()
	 */
	abstract protected function type():string;

	/**
	 * 2016-05-29
	 * @used-by self::buildLI()
	 * @used-by \Dfe\TwoCheckout\LineItem\Product::build()
	 * @see \Dfe\TwoCheckout\LineItem\Product::build()
	 * @return array(string => string)
	 */
	protected function build():array {return df_clean([
		'type' => $this->type()
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
		,'name' => self::adjustText($this->nameRaw() ?: df_ucfirst($this->type()))
		,'price' => $this->price()
		# 2016-05-29
		# Почему-то пока этот параметр игнорируется для всех line items, кроме shipping.
		# https://mail.google.com/mail/u/0/#sent/154fa43ce41483c3
		,'tangible' => $this->tangible() ? 'Y' : 'N'
		,'productId' => $this->id()
	]);}

	/**
	 * 2016-05-29
	 * 1) В именах товаров недопустимы символы < и >:
	 * «Name of the item passed in. (128 characters max, cannot use ‘<' or '>’,
	 * defaults to capitalized version of ‘type’.) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * 2) Думаю, в description они тоже недопустимы...
	 * Похоже, description также имеет ограничения по длине, как и name.
	 * 3) Опытным путём установил, что у description такое же ограничение по длине, как и у name.
	 * @used-by self::build()
	 * @used-by \Dfe\TwoCheckout\LineItem\Product::build()
	 */
	final protected static function adjustText(string $s):string {return df_chop(strtr($s, ['<' => '«', '>' => '»']), 128);}
}