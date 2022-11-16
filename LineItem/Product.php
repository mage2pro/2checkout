<?php
namespace Dfe\TwoCheckout\LineItem;
use Dfe\TwoCheckout\Charge;
use Dfe\TwoCheckout\LineItem;
use Magento\Catalog\Model\Product as P;
use Magento\Sales\Model\Order\Item as OI;
# 2016-05-29
final class Product extends LineItem {
	/**
	 * 2016-05-29
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::build()
	 * @used-by \Dfe\TwoCheckout\LineItem::buildP()
	 * @return array(string => string)
	 */
	protected function build():array {return parent::build() + df_clean([
		 # 2016-05-29
		 # 1) Это поле отсутствует в документации,
		 # однако, судя по ответу сервера, оно тоже поддерживается.
		 # Спрошу у техподдержки о его формате...
		 # 2) Опытным путём установил, что теги надо удалять, иначе описание не отобразится.
		 # Но даже в этом случае значение иногда сохраняется, иногда нет.
		 # 3) Опытным путём установил, что у description такое же ограничение по длине, как и у name.
		'description' => self::adjustText(strip_tags(
			$this->product()->getData('short_description') ?: $this->product()->getData('description')
		))
		# 2016-05-23
		# «Array of option objects using the attributes specified below. Optional
		# Will be returned in the order that they are passed in.
		# (Passed as a sub object of a lineItem object.)»
		# https://www.2checkout.com/documentation/payment-api/create-sale
		,'options' => $this->options()
		# 2016-05-23
		# «Quantity of the item passed in.
		# (0-999, defaults to 1 if not passed in or incorrectly formatted.) Optional»
		# https://www.2checkout.com/documentation/payment-api/create-sale
		,'quantity' => df_oqi_qty($this->_oi)
	]);}

	/**
	 * 2016-05-29
	 * 1) Изначально ставил здесь $this->p()->getSku()
	 * Однако для использования API «Refund Lineitem» нам нужен идентификатор,
	 * который будет надёжно идентифицировать строку заказа.
	 * https://www.2checkout.com/documentation/api/sales/refund-lineitem
	 * 2) Для «Refund Lineitem» нам нужно указывать не наш идентификатор,
	 * а идентификатор строки заказа в 2Checkout.
	 * Однако мы запросто можем его получить, выполнив запрос «Detail Sale»:
	 * https://www.2checkout.com/documentation/api/sales/detail-sale
	 * Ответ на этот запрос в массиве lineitems вернёт:
	 * 		lineitem_id: идентификатор строки заказа в 2Checkout
	 *  	vendor_product_id: наш идентификатор.
	 * Вот тут-то мы их и сопоставим.
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::id()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 */
	protected function id():string {return $this->_oi->getQuoteItemId();}

	/**
	 * 2016-05-29
	 * 2017-02-01
	 * Раньше здесь стояло $this->top()->getName().
	 * Идея была в том, что название «New Very Prive» лучше, чем «New Very Prive-37-Almond».
	 * Теперь же я думаю наоборот.
	 * @see \Dfe\CheckoutCom\Charge::cProduct()
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::nameRaw()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 */
	protected function nameRaw():string {return $this->_oi->getName();}

	/**
	 * 2016-05-29
	 * 2017-02-01
	 * @uses df_oqi_price() использует
	 * @see \Magento\Sales\Model\Order\Item::getPrice(),
	 * а не @see \Magento\Sales\Model\Order\Item::getPriceInclTax().
	 * Это именно то, что нам нужно: мы не размазываем налоги по товарам,
	 * а передаём их платёжной системе отдельной строкой:
	 * @see \Dfe\TwoCheckout\Charge::lineItem_tax()
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::price()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 */
	protected function price():string {return $this->_c->cFromDocF(df_oqi_price($this->_oi));}

	/**
	 * 2016-05-23
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::tangible()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 */
	protected function tangible():bool {return df_tangible($this->product());}

	/**
	 * 2016-05-29
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::type()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 */
	protected function type():string {return 'product';}

	/**
	 * 2022-11-16
	 * @used-by self::p()
	 */
	private function __construct(Charge $c, OI $oi) {$this->_c = $c; $this->_oi = $oi;}

	/**
	 * @used-by self::build()
	 * @return array(array(string => string))
	 */
	private function options():array {return
		!($op = df_oqi_top($this->_oi)->getProductOptions()) || !($ai = dfa($op, 'attributes_info')) ? [] :
			array_map(function(array $i) {return [
				# 2016-05-23
				# «Name of product option.
				# Ex. Size (64 characters max – cannot include ‘<' or '>’) Required»
				# https://www.2checkout.com/documentation/payment-api/create-sale
				'optName' => dfa($i, 'label')
				# 2016-05-23
				# «Option selected.
				# Ex. Small (64 characters max, cannot include ‘<' or '>’) Required»
				# https://www.2checkout.com/documentation/payment-api/create-sale
				,'optValue' => dfa($i, 'value')
				# 2016-05-23
				# «Option price in seller currency. (0.00 for no cost options) Required»
				# https://www.2checkout.com/documentation/payment-api/create-sale
				,'optSurcharge' => '0.00'
			];}, $ai)
	;}

	/**
	 * 2016-05-29
	 * @used-by self::build()
	 * @used-by self::tangible()
	 */
	private function product():P {return $this->_oi->getProduct();}

	/**
	 * 2016-05-23
	 * 2022-11-16 It works in PHP ≥ 5.4: https://3v4l.org/V4sAU
	 * @used-by \Dfe\TwoCheckout\Charge::lineItems()
	 * @return array(string => string)
	 */
	static function p(Charge $c, OI $oi):array {return (new self($c, $oi))->build();}

	/**
	 * 2022-11-16
	 * @used-by self::__construct()
	 * @used-by self::price()
	 * @var Charge
	 */
	private $_c;

	/**
	 * 2022-11-16
	 * @used-by self::__construct()
	 * @used-by self::build()
	 * @used-by self::id()
	 * @used-by self::nameRaw()
	 * @used-by self::price()
	 * @var OI
	 */
	private $_oi;
}