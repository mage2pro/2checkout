<?php
namespace Dfe\TwoCheckout;
class LineItem extends \Df\Core\O {
	/**
	 * 2016-05-29
	 * @see \Dfe\TwoCheckout\LineItem\Product::build()
	 * @used-by \Dfe\TwoCheckout\LineItem::buildLI()
	 * @return array(string => string)
	 */
	protected function build() {return df_clean([
		'type' => $this->type()
		,'name' => $this->name()
		,'price' => $this->price()
		/**
		 * 2016-05-29
		 * Почему-то пока этот параметр игнорируется для всех line items, кроме shipping.
		 * https://mail.google.com/mail/u/0/#sent/154fa43ce41483c3
		 */
		,'tangible' => $this->tangible() ? 'Y' : 'N'
		,'productId' => $this->id()
	]);}

	/**
	 * 2016-05-29
	 * «Your custom product identifier. Optional»
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 * @see \Dfe\TwoCheckout\LineItem\Product::id()
	 * @return string
	 */
	protected function id() {return $this[self::$P__ID];}

	/**
	 * 2016-05-29
	 * @used-by \Dfe\TwoCheckout\LineItem::name()
	 * @see \Dfe\TwoCheckout\LineItem\Product::nameRaw()
	 * @return string
	 */
	protected function nameRaw() {return $this[self::$P__NAME];}

	/**
	 * 2016-05-23
	 * «Price of the line item.
	 * Format: 0.00-99999999.99, defaults to 0 if a value isn’t passed in
	 * or if value is incorrectly formatted, no negatives
	 * (use positive values for coupons). Required»
	 *
	 * Здесь нужно указывать именно цену товара, а не цену строки заказа.
	 * Т.е. умножать на количество здесь не надо: проверил опытным путём. 
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 * @return string
	 */
	protected function price() {return $this[self::$P__PRICE];}

	/**
	 * 2016-05-23
	 * «Y or N. Will default to Y if the type is shipping. Optional»
	 * @see \Dfe\TwoCheckout\LineItem\Product::tangible()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 * @return bool
	 */
	protected function tangible() {return $this[self::$P__TANGIBLE];}

	/**
	 * 2016-05-23
	 * «The type of line item that is being passed in.
	 * (Always Lower Case, ‘product’, ‘shipping’, ‘tax’ or ‘coupon’, defaults to ‘product’) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * @see \Dfe\TwoCheckout\LineItem\Product::type()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 * @return string
	 */
	protected function type() {return $this[self::$P__TYPE];}

	/**
	 * 2016-05-23
	 * «Name of the item passed in. (128 characters max, cannot use ‘<' or '>’,
	 * defaults to capitalized version of ‘type’.) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 *
	 * 2016-05-29
	 * The fallback to the «capitalized version of ‘type’» does not work:
	 * the server responds "Bad request - parameter error" if the "name" is absent.
	 * https://mail.google.com/mail/u/0/#sent/154f4ade595abd5b
	 * @return string
	 */
	private function name() {return dfc($this, function() {return
		self::adjustText($this->nameRaw() ?: df_ucfirst($this->type()))
	;});}

	/**
	 * 2016-05-29
	 * @override
	 * @return void
	 */
	protected function _construct() {
		parent::_construct();
		$this
			->_prop(self::$P__ID, DF_V_STRING, false)
			->_prop(self::$P__NAME, DF_V_STRING, false)
			->_prop(self::$P__PRICE, DF_V_STRING_NE)
			->_prop(self::$P__TANGIBLE, DF_V_BOOL, false)
			->_prop(self::$P__TYPE, DF_V_STRING_NE)
		;
	}

	/**
	 * @param string $type
	 * @param string $price
	 * @param string|null $name [optional]
	 * @param bool $tangible [optional]
	 * @param string|null $id [optional]
	 * @return array(string => string)
	 */
	public static function buildLI($type, $price, $name = null, $tangible = false, $id = null) {
		return (new self([
			self::$P__TYPE => $type
			, self::$P__PRICE => $price
			, self::$P__NAME => $name
			, self::$P__TANGIBLE => $tangible
			, self::$P__ID => $id ?: $type
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
	 * Опытным путём установил, что у description
	 * такое же ограничение по длине, как и у name.
	 *
	 * @param string $text
	 * @return string
	 */
	protected static function adjustText($text) {
		$text = strtr($text, ['<' => '«', '>' => '»']);
		return mb_strlen($text) <= 128 ? $text : mb_substr($text, 0, 127) . '…';
	}

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