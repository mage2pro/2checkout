<?php
namespace Dfe\TwoCheckout;
use Magento\Sales\Model\Order as O;
use Magento\Sales\Model\Order\Item as OI;
class LineItem extends \Df\Core\O {
	/** @return string */
	private function name() {return $this->top()->getName();}

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
	public static function build(OI $oi) {
		/** @var $this $li */
		$li = new self([self::$P__OI => $oi]);
		/** @var array(string => string) $result */
		$result = [
			/**
			 * 2016-05-23
			 * «The type of line item that is being passed in.
			 * (Always Lower Case, ‘product’, ‘shipping’, ‘tax’ or ‘coupon’, defaults to ‘product’) Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			'type' => 'product'
			/**
			 * 2016-05-23
			 * «Name of the item passed in. (128 characters max, cannot use ‘<' or '>’,
			 * defaults to capitalized version of ‘type’.) Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'name' => $li->name()
			/**
			 * 2016-05-23
			 * «Quantity of the item passed in.
			 * (0-999, defaults to 1 if not passed in or incorrectly formatted.) Optional»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'quantity' => $oi->getQtyOrdered()
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
			,'price' => df_order_item_price($oi)
			/**
			 * 2016-05-23
			 * «Y or N. Will default to Y if the type is shipping. Optional»
			 */
			,'tangible' => df_virtual_or_downloadable($oi->getProduct()) ? 'N' : 'Y'
			/**
			 * 2016-05-23
			 * «Your custom product identifier. Optional»
			 */
			,'productId' =>	$oi->getProduct()->getSku()
			/**
			 * 2016-05-23
			 * «Array of option objects using the attributes specified below. Optional
			 * Will be returned in the order that they are passed in.
			 * (Passed as a sub object of a lineItem object.)»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'options' => $li->options()
		];
		return $result;
	}

	/** @var string */
	private static $P__OI = 'oi';
}