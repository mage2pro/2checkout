<?php
namespace Dfe\TwoCheckout\LineItem;
use Dfe\TwoCheckout\Charge;
use Dfe\TwoCheckout\LineItem;
use Dfe\TwoCheckout\Method as M;
use Magento\Catalog\Model\Product as P;
use Magento\Sales\Model\Order as O;
use Magento\Sales\Model\Order\Item as OI;
final class Product extends LineItem {
	/**
	 * 2016-05-29
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::build()
	 * @used-by \Dfe\TwoCheckout\LineItem::buildLI()
	 * @return array(string => string)
	 */
	protected function build() {return parent::build() + df_clean([
		/**
		 * 2016-05-23
		 * «Quantity of the item passed in.
		 * (0-999, defaults to 1 if not passed in or incorrectly formatted.) Optional»
		 * https://www.2checkout.com/documentation/payment-api/create-sale
		 */
		'quantity' => $this->oi()->getQtyOrdered()
		/**
		 * 2016-05-23
		 * «Array of option objects using the attributes specified below. Optional
		 * Will be returned in the order that they are passed in.
		 * (Passed as a sub object of a lineItem object.)»
		 * https://www.2checkout.com/documentation/payment-api/create-sale
		 */
		,'options' => $this->options()
		/**
		 * 2016-05-29
		 * Это поле отсутствует в документации,
		 * однако, судя по ответу сервера, оно тоже поддерживается.
		 * Спрошу у техподдержки о его формате...
		 *
		 * Опытным путём установил, что теги надо удалять, иначе описание не отобразится.
		 * Но даже в этом случае значение иногда сохраняется, иногда нет.
		 *
		 * Опытным путём установил, что у description
		 * такое же ограничение по длине, как и у name.
		 */
		,'description' => $this->description()
	]);}

	/**
	 * 2016-05-29
	 * Изначально ставил здесь $this->p()->getSku()
	 * Однако для использования API «Refund Lineitem» нам нужен идентификатор,
	 * который будет надёжно идентифицировать строку заказа.
	 * https://www.2checkout.com/documentation/api/sales/refund-lineitem
	 *
	 * Обратите внимание, что для «Refund Lineitem» нам нужно указывать не наш идентификатор,
	 * а идентификатор строки заказа в 2Checkout.
	 * Однако мы запросто можем его получить, выполнив запрос «Detail Sale»:
	 * https://www.2checkout.com/documentation/api/sales/detail-sale
	 * Ответ на этот запрос в массиве lineitems вернёт:
	 * 	lineitem_id: идентификатор строки заказа в 2Checkout
	 *  vendor_product_id: наш идентификатор.
	 * Вот тут-то мы их и сопоставим.
	 *
	 * @override
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 * @see \Dfe\TwoCheckout\LineItem::id()
	 * @return string
	 */
	protected function id() {return $this->oi()->getQuoteItemId();}

	/**
	 * 2016-05-29   
	 * 2017-02-01
	 * Раньше здесь стояло $this->top()->getName().
	 * Идея была в том, что название «New Very Prive» лучше, чем «New Very Prive-37-Almond».
	 * Теперь же я думаю наоборот.
	 * @see \Dfe\CheckoutCom\Charge::cProduct()
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::nameRaw()
	 * @used-by \Dfe\TwoCheckout\LineItem::name()
	 * @return string
	 */
	protected function nameRaw() {return $this->oi()->getName();}

	/**
	 * 2016-05-29
	 * 2017-02-01
	 * @uses df_oi_price() использует
	 * @see \Magento\Sales\Model\Order\Item::getPrice(),
	 * а не @see \Magento\Sales\Model\Order\Item::getPriceInclTax().
	 * Это именно то, что нам нужно: мы не размазываем налоги по товарам,
	 * а передаём их платёжной системе отдельной строкой:
	 * @see \Dfe\TwoCheckout\Charge::lineItem_tax()
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::price()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 * @return string
	 */
	protected function price() {return $this->charge()->cFromOrderF(df_oi_price($this->oi()));}

	/**
	 * 2016-05-23
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::tangible()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 * @return bool
	 */
	protected function tangible() {return !df_virtual_or_downloadable($this->p());}

	/**
	 * 2016-05-29
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::type()
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 * @return string
	 */
	protected function type() {return 'product';}

	/** @return Charge */
	private function charge() {return $this[self::$P__C];}

	/**
	 * 2016-05-29
	 * @return string
	 */
	private function description() {return self::adjustText(strip_tags(
		$this->p()->getData('short_description') ?: $this->p()->getData('description')
	));}

	/** @return M */
	private function m() {return $this->charge()->m();}

	/** @return OI */
	private function oi() {return $this[self::$P__OI];}

	/** @return array(array(string => string)) */
	private function options() {
		/** @var array(array(string => string)) $result */
		$result = [];
		/** @var array(string => mixed)|null */
		$op = $this->top()->getProductOptions();
		if ($op) {
			/** @var array(array(string => string)) $ai */
			$ai = dfa($op, 'attributes_info');
			if ($ai) {
				foreach ($ai as $option) {
					/** @var array(string => string) $option */
					$result[]= [
						/**
						 * 2016-05-23
						 * «Name of product option.
						 * Ex. Size (64 characters max – cannot include ‘<' or '>’) Required»
						 * https://www.2checkout.com/documentation/payment-api/create-sale
						 */
						'optName' => dfa($option, 'label')
						/**
						 * 2016-05-23
						 * «Option selected.
						 * Ex. Small (64 characters max, cannot include ‘<' or '>’) Required»
						 * https://www.2checkout.com/documentation/payment-api/create-sale
						 */
						,'optValue' => dfa($option, 'value')
						/**
						 * 2016-05-23
						 * «Option price in seller currency. (0.00 for no cost options) Required»
						 * https://www.2checkout.com/documentation/payment-api/create-sale
						 */
						,'optSurcharge' => '0.00'
					];
				}
			}
		}
		return $result;
	}

	/**
	 * 2016-05-29
	 * @return P
	 */
	private function p() {return $this->oi()->getProduct();}

	/**
	 * 2016-05-23
	 * @return OI
	 */
	private function top() {return dfc($this, function() {return
		$this->oi()->getParentItem() ?: $this->oi()
	;});}

	/**
	 * 2016-05-23
	 * @override
	 * @return void
	 */
	protected function _construct() {
		parent::_construct();
		$this
			->_prop(self::$P__C, Charge::class)
			->_prop(self::$P__OI, OI::class)
		;
	}

	/**
	 * 2016-05-23
	 * @param Charge $c
	 * @param OI $oi
	 * @return array(string => string)
	 */
	static function buildP(Charge $c, OI $oi) {return
		(new self([self::$P__C => $c, self::$P__OI => $oi]))->build();
	}

	/** @var string */
	private static $P__C = 'c';

	/** @var string */
	private static $P__OI = 'oi';
}