<?php
namespace Dfe\TwoCheckout\LineItem;
use Dfe\TwoCheckout\LineItem;
use Magento\Catalog\Model\Product as P;
use Magento\Sales\Model\Order as O;
use Magento\Sales\Model\Order\Item as OI;
class Product extends LineItem {
	/**
	 * 2016-05-29
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::build()
	 * @used-by \Dfe\TwoCheckout\LineItem::buildLI()
	 * @return array(string => string)
	 */
	protected function build() {
		return parent::build() + [
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
		];
	}

	/**
	 * 2016-05-29
	 * @override
	 * @used-by \Dfe\TwoCheckout\LineItem::build()
	 * @see \Dfe\TwoCheckout\LineItem::id()
	 * @return string
	 */
	protected function id() {return $this->p()->getSku();}

	/**
	 * 2016-05-29
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::nameRaw()
	 * @used-by \Dfe\TwoCheckout\LineItem::name()
	 * @return string
	 */
	protected function nameRaw() {return $this->top()->getName();}

	/**
	 * 2016-05-29
	 * @override
	 * @see \Dfe\TwoCheckout\LineItem::priceRaw()
	 * @used-by \Dfe\TwoCheckout\LineItem::price()
	 * @return string
	 */
	protected function priceRaw() {return df_order_item_price($this->oi());}

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
	private function top() {
		if (!isset($this->{__METHOD__})) {
			$this->{__METHOD__} = $this->oi()->getParentItem() ?: $this->oi();
		}
		return $this->{__METHOD__};
	}

	/**
	 * 2016-05-23
	 * @override
	 * @return void
	 */
	protected function _construct() {
		parent::_construct();
		$this->_prop(self::$P__OI, OI::class);
	}

	/**
	 * 2016-05-23
	 * @param OI $oi
	 * @return array(string => string)
	 */
	public static function buildP(OI $oi) {return (new self([self::$P__OI => $oi]))->build();}

	/** @var string */
	private static $P__OI = 'oi';
}