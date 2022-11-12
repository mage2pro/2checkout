<?php
namespace Dfe\TwoCheckout;
use Df\Core\Visitor;
use Magento\Sales\Model\Order as O;
use Magento\Sales\Model\Order\Address as A;
final class Address extends \Df\Core\O {
	/**
	 * @used-by self::city()
	 * @used-by self::countryIso3()
	 * @used-by self::line()
	 * @used-by self::postcode()
	 * @used-by self::region()
	 * @used-by self::visitor()
	 */
	private function aa():A {return $this[self::$P__A];}

	/**
	 * 2016-05-20
	 * «Card holder’s city. (64 characters max) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * @used-by self::build()
	 */
	private function city():string {return df_nts($this->aa()->getCity() ?: $this->visitor()->city());}

	/**
	 * 2016-05-20
	 * @used-by build()
	 * @used-by req()
	 * @return string
	 */
	private function countryIso3() {return dfc($this, function() {return df_country_2_to_3(
		$this->aa()->getCountryId() ?: $this->visitor()->iso2()
	);});}

	/**
	 * 2016-05-20
	 * 1)
	 * «Card holder’s street address. (64 characters max) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 *
	 * 2)
	 * «Card holder’s street address line 2. (64 characters max)
	 * Required if “country” value is: CHN, JPN, RUS -
	 * Optional for all other “country” values.»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * https://mail.google.com/mail/u/0/#inbox/154ca839388e9f7d
	 *
	 * 2017-04-08
	 * 2017-09-16
	 * Any value longer than 64 characters will lead to the «600 - Authorization Failed» failure:
	 *	{
	 *		"exception": {
	 *			"errorCode": "600",
	 *			"errorMsg": "Authorization Failed",
	 *			"exception": false,
	 *			"httpStatus": "400"
	 *		},
	 *		"response": null,
	 *		"validationErrors": null
	 *	}
	 *
	 * @param int|null $i [optional]
	 * @return string[]
	 */
	private function line($i = null) {/** @var string[] $r */$r = dfc($this, function() {
		/** @var string $s */
		/** @var string $s1 */
		/** @var string $s2 */
		$s = mb_substr(df_trim(df_cc_s($this->aa()->getStreet())), 0, 128);
		/** @var int $len */
		$len = mb_strlen($s);
		if ($len <= 64) {
			/**
			 * 2017-09-16
			 * An empty value of `addrLine2` for CHN, JPN, RUS will lead to the «600 - Authorization Failed» failure.
			 * E.g., the following address will fail:
			 *	"billingAddr": {
			 *		"addrLine1": "проспект Ленина, 59",
			 *		"addrLine2": "",
			 *		"city": "Абакан",
			 *		"country": "RUS",
			 *		"email": "dfediuk@gmail.com",
			 *		"name": "Dmitry Fedyuk",
			 *		"phoneExt": "",
			 *		"phoneNumber": "+79629197300",
			 *		"state": "Хакасия",
			 *		"zipCode": "655017"
			 *	}
			 */
			list($s1, $s2) = [$s, !in_array($this->countryIso3(), ['CHN', 'JPN', 'RUS']) ? '' : '---'];
		}
		else {
			$end1 = mb_strrpos(mb_substr($s, 0, 64), ' '); /** @var int $end1 */
			$s1 = mb_substr($s, 0, $end1);
			$s2 = mb_substr(trim(mb_substr($s, mb_strlen($s1))), 0, 64);
		}
		return [$s1, $s2];
	}); return is_null($i) ? $r : $r[$i - 1];}

	/**
	 * 2016-05-20 https://www.2checkout.com/documentation/payment-api/create-sale
	 * @used-by self::build()
	 */
	private function postcode():string {return df_nts(
		$this->aa()->getPostcode() ?: ($this->req() ? $this->visitor()->postCode() : '')
	);}

	/**
	 * 2016-05-20
	 * «Card holder’s state. (64 characters max)
	 * Required if “country” value is ARG, AUS, BGR, CAN, CHN, CYP, EGY, FRA, IND,
	 * IDN, ITA, JPN, MYS, MEX, NLD, PAN, PHL, POL, ROU, RUS, SRB, SGP, ZAF, ESP,
	 * SWE, THA, TUR, GBR, USA - Optional for all other “country” values.»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * @used-by self::build()
	 */
	private function region():string {return df_nts(
		$this->aa()->getRegion() ?: ($this->req() ? $this->visitor()->regionName() : '')
	);}

	/**
	 * 2016-05-20
	 * @return bool
	 */
	private function req() {return dfc($this, function() {return in_array($this->countryIso3(), self::$req);});}

	/**
	 * 2016-05-20
	 * @return Visitor
	 */
	private function visitor() {return dfc($this, function() {return df_visitor($this->aa()->getOrder());});}

	/**
	 * 2016-05-19 https://www.2checkout.com/documentation/payment-api/create-sale    
	 * 2017-04-10
	 * An order/quote can be without a shipping address (consist of the Virtual products). In this case:
	 * *) @uses \Magento\Sales\Model\Order::getShippingAddress() returns null
	 * *) @uses \Magento\Quote\Model\Quote::getShippingAddress() returns an empty object.
	 * @param A|null $oa
	 * @param bool $isBilling [optional]
	 * @return array(mixed => mixed)
	 */
	static function build($oa, $isBilling = false) {
		/** @var array(string => string) $result */
		if (!$oa) {
			$result = [];
		}
		else {
			$a = new self([self::$P__A => $oa]); /** @var self $a */
			$o = $oa->getOrder(); /** @var O $o */
			$result = [
				# 2016-05-19 «Card holder’s street address. (64 characters max) Required»
				'addrLine1' => $a->line(1)
				# 2016-05-19
				# «Card holder’s street address line 2. (64 characters max)
				# Required if “country” value is: CHN, JPN, RUS.
				# Optional for all other “country” values.»
				,'addrLine2' => $a->line(2)
				# 2016-05-19 «Card holder’s city. (64 characters max) Required»
				,'city' => $a->city()
				# 2016-05-19 «Card holder’s country. (64 characters max) Required»
				,'country' => $a->countryIso3()				
				# 2016-05-19 «Card holder’s name. (128 characters max) Required»
				,'name' => $oa->getName()				
				# 2016-05-19
				# «Card holder’s state. (64 characters max)
				# Required if “country” value is ARG, AUS, BGR, CAN, CHN, CYP, EGY, FRA, IND,
				# IDN, ITA, JPN, MYS, MEX, NLD, PAN, PHL, POL, ROU, RUS, SRB, SGP, ZAF, ESP,
				# SWE, THA, TUR, GBR, USA - Optional for all other “country” values.»
				,'state' => $a->region()
				# 2016-05-19
				# «Card holder’s zip. (16 characters max)
				# Required if “country” value is ARG, AUS, BGR, CAN, CHN, CYP, EGY, FRA, IND,
				# IDN, ITA, JPN, MYS, MEX, NLD, PAN, PHL, POL, ROU, RUS, SRB, SGP, ZAF, ESP,
				# SWE, THA, TUR, GBR, USA - Optional for all other “country” values.»
				,'zipCode' => $a->postcode()
			] + (!$isBilling ? [] : [
				# 2016-05-19 «Card holder’s email. (64 characters max) Required»
				'email' => $o->getCustomerEmail()
				# 2016-05-19 «Card holder’s phone extension. (9 characters max) Optional»
				,'phoneExt' => ''					
				# 2016-05-19 «Card holder’s phone. (16 characters max) Optional»
				,'phoneNumber' => $oa->getTelephone()				
			]);
		}
		return $result;
	}

	/** @var string */
	private static $P__A = 'a';

	/**
	 * 2016-05-20
	 * «Card holder’s state. (64 characters max)
	 * Required if “country” value is ARG, AUS, BGR, CAN, CHN, CYP, EGY, FRA, IND,
	 * IDN, ITA, JPN, MYS, MEX, NLD, PAN, PHL, POL, ROU, RUS, SRB, SGP, ZAF, ESP,
	 * SWE, THA, TUR, GBR, USA - Optional for all other “country” values.»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 *
	 * «Card holder’s zip. (16 characters max)
	 * Required if “country” value is ARG, AUS, BGR, CAN, CHN, CYP, EGY, FRA, IND,
	 * IDN, ITA, JPN, MYS, MEX, NLD, PAN, PHL, POL, ROU, RUS, SRB, SGP, ZAF, ESP,
	 * SWE, THA, TUR, GBR, USA - Optional for all other “country” values.»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 *
	 * @var string[]
	 */
	private static $req = [
		'ARG', 'AUS', 'BGR', 'CAN', 'CHN', 'CYP', 'EGY', 'FRA', 'IND'
		,'IDN', 'ITA', 'JPN', 'MYS', 'MEX', 'NLD', 'PAN', 'PHL', 'POL'
		, 'ROU', 'RUS', 'SRB', 'SGP', 'ZAF', 'ESP', 'SWE', 'THA', 'TUR', 'GBR', 'USA'
	];
}