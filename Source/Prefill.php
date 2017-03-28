<?php
namespace Dfe\TwoCheckout\Source;
final class Prefill extends \Df\Config\Source {
	/**
	 * 2016-05-17
	 * https://www.2checkout.com/documentation/sandbox/test-data/
	 * https://mage2.pro/t/1631
	 * @override
	 * @see \Df\Config\Source::map()
	 * @used-by \Df\Config\Source::toOptionArray()
	 * @return array(string => string)
	 */
	protected function map() {return [
		0 => 'No'
		, '4000000000000002' => 'Success'
		, '4333433343334333' => 'Failure'
	];}
}