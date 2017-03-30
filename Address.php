<?php
namespace Dfe\TwoCheckout;
use Df\Core\Visitor;
use Magento\Sales\Model\Order as O;
use Magento\Sales\Model\Order\Address as A;
class Address extends \Df\Core\O {
	/** @return A */
	private function a() {return $this[self::$P__A];}

	/**
	 * 2016-05-20
	 * «Card holder’s city. (64 characters max) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * @return string|null
	 */
	private function city() {return $this->a()->getCity() ?: $this->visitor()->city();}

	/**
	 * 2016-05-20
	 * «Card holder’s country. (64 characters max) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 *
	 * Раньше реализация была такой:
			return
				$this->a()->getCountryId()
				? df_country_ctn($this->a()->getCountryId())
				: $this->visitor()->countryName()
			;
	 * Но теперь дибилоид из службы поддержки утверждает,
	 * что надо передавать 3-символьный код страны.
	 *
	 * @return string|null
	 */
	private function country() {return
		df_country_2_to_3($this->a()->getCountryId() ?: $this->visitor()->iso2())
	;}

	/**
	 * 2016-05-20
	 * @return string
	 */
	private function countryIso3() {return dfc($this, function() {return
		df_country_2_to_3($this->a()->getCountryId())
	;});}

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
	 * @param int|null $i [optional]
	 * @return string[]
	 */
	private function line($i = null) {
		if (!isset($this->{__METHOD__})) {
			/** @var string $line1 */
			/** @var string $line2 */
			if (!in_array($this->countryIso3(), ['CHN', 'JPN', 'RUS'])) {
				$line1 = $this->a()->getStreetLine(1);
				$line2 = $this->a()->getStreetLine(2);
			}
			else {
				/** @var string[] $words */
				$words = df_clean(df_trim(explode(' ', df_cc_s($this->a()->getStreet()))));
				/** @var int $wordsCount */
				$wordsCount = count($words);
				/** @var int $wordsCount1 */
				$wordsCount1 = round($wordsCount / 2);
				$line1 = df_cc_s(array_slice($words, 0, $wordsCount1));
				$line2 = df_cc_s(array_slice($words, $wordsCount1));
			}
			$this->{__METHOD__} = [$line1, $line2];
		}
		return is_null($i) ? $this->{__METHOD__} :  $this->{__METHOD__}[$i - 1];
	}

	/**
	 * 2016-05-20
	 * «Card holder’s city. (64 characters max) Required»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * @return string|null
	 */
	private function postcode() {return
		$this->a()->getPostcode() ?: ($this->req() ? $this->visitor()->postCode() : null);
	}

	/**
	 * 2016-05-20
	 * «Card holder’s state. (64 characters max)
	 * Required if “country” value is ARG, AUS, BGR, CAN, CHN, CYP, EGY, FRA, IND,
	 * IDN, ITA, JPN, MYS, MEX, NLD, PAN, PHL, POL, ROU, RUS, SRB, SGP, ZAF, ESP,
	 * SWE, THA, TUR, GBR, USA - Optional for all other “country” values.»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * @return string|null
	 */
	private function region() {return
		$this->a()->getRegion() ?: ($this->req() ? $this->visitor()->regionName() : null)
	;}

	/**
	 * 2016-05-20
	 * @return bool
	 */
	private function req() {return dfc($this, function() {return 
		in_array($this->countryIso3(), self::$req)
	;});}

	/**
	 * 2016-05-20
	 * @return Visitor
	 */
	private function visitor() {return dfc($this, function() {return df_visitor($this->a()->getOrder());});}

	/**
	 * 2016-05-20
	 * @override
	 * @see \Df\Core\O::_construct()
	 */
	protected function _construct() {
		parent::_construct();
		$this->_prop(self::$P__A, A::class);
	}

	/**
	 * 2016-05-19
	 * @param A $oa
	 * @param bool $isBilling [optional]
	 * @return array(mixed => mixed)
	 */
	static function build(A $oa, $isBilling = false) {
		/** @var self $a */
		$a = new self([self::$P__A => $oa]);
		/** @var O $o */
		$o = $oa->getOrder();
		/** @var array(string => string) $result */
		$result = [
			/**
			 * 2016-05-19
			 * «Card holder’s name. (128 characters max) Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			'name' => $oa->getName()
			/**
			 * 2016-05-19
			 * «Card holder’s street address. (64 characters max) Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'addrLine1' => $a->line(1)
			/**
			 * 2016-05-19
			 * «Card holder’s street address line 2. (64 characters max)
			 * Required if “country” value is: CHN, JPN, RUS -
			 * Optional for all other “country” values.»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'addrLine2' =>  $a->line(2)
			/**
			 * 2016-05-19
			 * «Card holder’s city. (64 characters max) Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'city' => $a->city()
			/**
			 * 2016-05-19
			 * «Card holder’s state. (64 characters max)
			 * Required if “country” value is ARG, AUS, BGR, CAN, CHN, CYP, EGY, FRA, IND,
			 * IDN, ITA, JPN, MYS, MEX, NLD, PAN, PHL, POL, ROU, RUS, SRB, SGP, ZAF, ESP,
			 * SWE, THA, TUR, GBR, USA - Optional for all other “country” values.»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'state' => $a->region()
			/**
			 * 2016-05-19
			 * «Card holder’s zip. (16 characters max)
			 * Required if “country” value is ARG, AUS, BGR, CAN, CHN, CYP, EGY, FRA, IND,
			 * IDN, ITA, JPN, MYS, MEX, NLD, PAN, PHL, POL, ROU, RUS, SRB, SGP, ZAF, ESP,
			 * SWE, THA, TUR, GBR, USA - Optional for all other “country” values.»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'zipCode' => $a->postcode()
			/**
			 * 2016-05-19
			 * «Card holder’s country. (64 characters max) Required»
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 */
			,'country' => $a->country()
		];
		if ($isBilling) {
			$result += [
				/**
				 * 2016-05-19
				 * «Card holder’s email. (64 characters max) Required»
				 * https://www.2checkout.com/documentation/payment-api/create-sale
				 */
				'email' => $o->getCustomerEmail()
				/**
				 * 2016-05-19
				 * «Card holder’s phone. (16 characters max) Optional»
				 * https://www.2checkout.com/documentation/payment-api/create-sale
				 */
				,'phoneNumber' => $oa->getTelephone()
				/**
				 * 2016-05-19
				 * «Card holder’s phone extension. (9 characters max) Optional»
				 * https://www.2checkout.com/documentation/payment-api/create-sale
				 */
				, 'phoneExt' => ''
			];
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