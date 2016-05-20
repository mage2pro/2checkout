<?php
namespace Dfe\TwoCheckout;
use Dfe\TwoCheckout\Settings as S;
use Dfe\TwoCheckout\Source\Action;
use Dfe\TwoCheckout\Source\Metadata;
use Magento\Payment\Model\Method\AbstractMethod as M;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException as LE;
use Magento\Payment\Model\Info as I;
use Magento\Payment\Model\InfoInterface as II;
use Magento\Sales\Model\Order as O;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment as OP;
use Magento\Sales\Model\Order\Payment\Transaction;
class Method extends \Df\Payment\Method {
	/**
	 * 2016-03-07
	 * @override
	 * @see \Df\Payment\Method::::authorize()
	 * @param II|I|OP $payment
	 * @param float $amount
	 * @return $this
	 */
	public function authorize(II $payment, $amount) {
		return $this->charge($payment, $amount, $capture = false);
	}

	/**
	 * 2016-03-07
	 * @override
	 * @see \Df\Payment\Method::canCapture()
	 * @return bool
	 */
	public function canCapture() {return true;}

	/**
	 * 2016-03-08
	 * @override
	 * @see \Df\Payment\Method::canCapturePartial()
	 * @return bool
	 */
	public function canCapturePartial() {return true;}

	/**
	 * 2016-03-08
	 * @override
	 * @see \Df\Payment\Method::canRefund()
	 * @return bool
	 */
	public function canRefund() {return true;}

	/**
	 * 2016-03-08
	 * @override
	 * @see \Df\Payment\Method::canRefundPartialPerInvoice()
	 * @return bool
	 */
	public function canRefundPartialPerInvoice() {return true;}

	/**
	 * 2016-03-06
	 * @override
	 * @see \Df\Payment\Method::capture()
	 * @see https://stripe.com/docs/charges
	 *
	 * $amount содержит значение в учётной валюте системы.
	 * https://github.com/magento/magento2/blob/6ce74b2/app/code/Magento/Sales/Model/Order/Payment/Operations/CaptureOperation.php#L37-L37
	 * https://github.com/magento/magento2/blob/6ce74b2/app/code/Magento/Sales/Model/Order/Payment/Operations/CaptureOperation.php#L76-L82
	 *
	 * @param II|I|OP $payment
	 * @param float $amount
	 * @return $this
	 * @throws \Stripe\Error\Card
	 */
	public function capture(II $payment, $amount) {
		if (!$payment[self::WEBHOOK_CASE]) {
			$this->charge($payment, $amount);
		}
		return $this;
	}

	/**
	 * 2016-05-20
	 * «Once you have passed the token to your server,
	 * you can use it along with your private key to charge the credit card
	 * and create a new sale using the authorization API call.
	 * The authorization will capture and deposit automatically within 48 hours of being placed.»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * Я так понял, у них нет двух отдельных режимов authorize и capture.
	 * Вместо этого у них всегда используется authorize, который через 48 часов
	 * автоматически превращается в capture.
	 * Поэтому в Magento я всегда буду помечать транзакцию как capture.
	 * @override
	 * @see \Df\Payment\Method::getConfigPaymentAction()
	 * @return string
	 */
	public function getConfigPaymentAction() {return M::ACTION_AUTHORIZE_CAPTURE;}

	/**
	 * 2016-03-15
	 * @override
	 * @see \Df\Payment\Method::getInfoBlockType()
	 * @return string
	 */
	public function getInfoBlockType() {return \Magento\Payment\Block\Info\Cc::class;}

	/**
	 * 2016-03-15
	 * @override
	 * @see \Df\Payment\Method::refund()
	 * @param II|I|OP  $payment
	 * @param float $amount
	 * @return $this
	 */
	public function refund(II $payment, $amount) {
		if (!$payment[self::WEBHOOK_CASE]) {
			$this->_refund($payment, $amount);
		}
		return $this;
	}

	/**
	 * 2016-03-08
	 * @override
	 * @see \Df\Payment\Method::setStore()
	 * @param int $storeId
	 * @return void
	 */
	public function setStore($storeId) {
		parent::setStore($storeId);
		S::s()->setScope($storeId);
	}

	/**
	 * 2016-05-03
	 * @override
	 * @see \Df\Payment\Method::iiaKeys()
	 * @used-by \Df\Payment\Method::assignData()
	 * @return string[]
	 */
	protected function iiaKeys() {return [self::$TOKEN];}

	/**
	 * 2016-05-06
	 * https://mage2.pro/t/898/3
	 * Использовать ли @see \Df\Payment\Block\ConfigurableInfo вместо @see \Df\Payment\Block\Info
	 * @override
	 * @see \Df\Payment\Method::useConfigurableBlockInfo()
	 * @used-by \Df\Payment\Method::getInfoBlockType()
	 * @return bool
	 */
	protected function useConfigurableBlockInfo() {return false;}

	/**
	 * 2016-03-17
	 * @param II|I|OP $payment
	 * @param float|null $amount [optional]
	 * @return void
	 */
	private function _refund(II $payment, $amount = null) {
		$this->api(function() use($payment, $amount) {
			/**
			 * 2016-03-17
			 * Метод @uses \Magento\Sales\Model\Order\Payment::getAuthorizationTransaction()
			 * необязательно возвращает транзакцию типа «авторизация»:
			 * в первую очередь он стремится вернуть родительскую транзакцию:
			 * https://github.com/magento/magento2/blob/8fd3e8/app/code/Magento/Sales/Model/Order/Payment/Transaction/Manager.php#L31-L47
			 * Это как раз то, что нам нужно, ведь наш модуль может быть настроен сразу на capture,
			 * без предварительной транзакции типа «авторизация».
			 */
			/** @var Transaction|false $parent */
			$parent = $payment->getAuthorizationTransaction();
			if ($parent) {
				/** @var Creditmemo $cm */
				$cm = $payment->getCreditmemo();
				/**
				 * 2016-03-24
				 * Credit Memo и Invoice отсутствуют в сценарии Authorize / Capture
				 * и присутствуют в сценарии Capture / Refund.
				 */
				if (!$cm) {
					$metadata = [];
				}
				else {
					/** @var Invoice $invoice */
					$invoice = $cm->getInvoice();
					$metadata = df_clean([
						'Comment' => $payment->getCreditmemo()->getCustomerNote()
						,'Credit Memo' => $cm->getIncrementId()
						,'Invoice' => $invoice->getIncrementId()
					])
						+ $this->metaAdjustments($cm, 'positive')
						+ $this->metaAdjustments($cm, 'negative')
					;
				}
				// 2016-03-16
				// https://stripe.com/docs/api#create_refund
				\Stripe\Refund::create(df_clean([
					// 2016-03-17
					// https://stripe.com/docs/api#create_refund-amount
					'amount' => !$amount ? null : self::amount($payment, $amount)
					/**
					 * 2016-03-18
					 * Хитрый трюк,
					 * который позволяет нам не заниматься хранением идентификаторов платежей.
					 * Система уже хранит их в виде «ch_17q00rFzKb8aMux1YsSlBIlW-capture»,
					 * а нам нужно лишь отсечь суффиксы (Stripe не использует символ «-»).
					 */
					,'charge' => df_first(explode('-', $parent->getTxnId()))
					// 2016-03-17
					// https://stripe.com/docs/api#create_refund-metadata
					,'metadata' => $metadata
					// 2016-03-18
					// https://stripe.com/docs/api#create_refund-reason
					,'reason' => 'requested_by_customer'
				]));
			}
		});
	}

	/**
	 * 2016-03-17
	 * Чтобы система показала наше сообщение вместо общей фразы типа
	 * «We can't void the payment right now» надо вернуть объект именно класса
	 * @uses \Magento\Framework\Exception\LocalizedException
	 * https://mage2.pro/t/945
	 * https://github.com/magento/magento2/blob/8fd3e8/app/code/Magento/Sales/Controller/Adminhtml/Order/VoidPayment.php#L20-L30
	 * @param callable $function
	 * @throws LE
	 */
	private function api($function) {
		df_leh(function() use($function) {S::s()->init(); $function();});
	}

	/**
	 * 2016-03-07
	 * @override
	 * @see https://stripe.com/docs/charges
	 * @see \Df\Payment\Method::capture()
	 * @param II|I|OP $payment
	 * @param float|null $amount [optional]
	 * @param bool|null $capture [optional]
	 * @return $this
	 * @throws \Stripe\Error\Card
	 */
	private function charge(II $payment, $amount = null, $capture = true) {
		$this->api(function() use($payment, $amount, $capture) {
			df_assert($capture);
			/**
			 * 2016-05-20
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 * https://github.com/2Checkout/2checkout-php/wiki/Charge_Authorize#example-usage
			 * @var array(string => mixed) $r
			 */
			$r = Charge::request($payment, $this->iia(self::$TOKEN), $amount);
			/**
			 * 2016-05-20
			 * «If an error occurs when attempting to authorize the sale,
			 * such as a processing error or an error in the JSON request,
			 * the errorCode and errorMsg will be returned in the exception sub object
			 * in the response body.»
			 */
			/** @var array(string => mixed)|null $e */
			$e = dfa($r, 'exception');
			if ($e) {
				df_error(dfa($e, 'errorMsg'));
			}
			else {
				/** @var array(string => mixed)|null $rr */
				$rr = dfa($r, 'response');
				/**
				 * 2016-05-20
				 * https://www.2checkout.com/documentation/payment-api/create-sale
				 * «Code indicating the result of the authorization attempt.»
				 */
				df_assert_eq('APPROVED', dfa($rr, 'responseCode'));
				df_assert(is_null(dfa($r, 'validationErrors')));
				/**
				 * 2016-03-15
				 * Иначе операция «void» (отмена авторизации платежа) будет недоступна:
				 * «How is a payment authorization voiding implemented?»
				 * https://mage2.pro/t/938
				 * https://github.com/magento/magento2/blob/8fd3e8/app/code/Magento/Sales/Model/Order/Payment.php#L540-L555
				 * @used-by \Magento\Sales\Model\Order\Payment::canVoid()
				 *
				 * 2016-05-20
				 * https://www.2checkout.com/documentation/payment-api/create-sale
				 * «2Checkout Invoice ID»
				 */
				$payment->setTransactionId(dfa($rr, 'transactionId'));
				/**
				 * 2016-03-15
				 * Аналогично, иначе операция «void» (отмена авторизации платежа) будет недоступна:
				 * https://github.com/magento/magento2/blob/8fd3e8/app/code/Magento/Sales/Model/Order/Payment.php#L540-L555
				 * @used-by \Magento\Sales\Model\Order\Payment::canVoid()
				 * Транзакция ситается завершённой, если явно не указать «false».
				 */
				$payment->setIsTransactionClosed(true);
			}
		});
		return $this;
	}

	/**
	 * 2016-03-18
	 * @param Creditmemo $cm
	 * @param string $type
	 * @return array(string => float)
	 */
	private function metaAdjustments(Creditmemo $cm, $type) {
		/** @var string $iso3Base */
		$iso3Base = $cm->getBaseCurrencyCode();
		/** @var string $iso3 */
		$iso3 = $cm->getOrderCurrencyCode();
		/** @var bool $multiCurrency */
		$multiCurrency = $iso3Base !== $iso3;
		/**
		 * 2016-03-18
		 * @uses \Magento\Sales\Api\Data\CreditmemoInterface::ADJUSTMENT_POSITIVE
		 * https://github.com/magento/magento2/blob/8fd3e8/app/code/Magento/Sales/Api/Data/CreditmemoInterface.php#L32-L35
		 * @uses \Magento\Sales\Api\Data\CreditmemoInterface::ADJUSTMENT_NEGATIVE
		 * https://github.com/magento/magento2/blob/8fd3e8/app/code/Magento/Sales/Api/Data/CreditmemoInterface.php#L72-L75
		 */
		/** @var string $key */
		$key = 'adjustment_' . $type;
		/** @var float $a */
		$a = $cm[$key];
		/** @var string $label */
		$label = ucfirst($type) . ' Adjustment';
		return !$a ? [] : (
			!$multiCurrency
			? [$label => $a]
			: [
				"{$label} ({$iso3})" => $a
				/**
				 * 2016-03-18
				 * @uses \Magento\Sales\Api\Data\CreditmemoInterface::BASE_ADJUSTMENT_POSITIVE
				 * https://github.com/magento/magento2/blob/8fd3e8/app/code/Magento/Sales/Api/Data/CreditmemoInterface.php#L112-L115
				 * @uses \Magento\Sales\Api\Data\CreditmemoInterface::BASE_ADJUSTMENT_NEGATIVE
				 * https://github.com/magento/magento2/blob/8fd3e8/app/code/Magento/Sales/Api/Data/CreditmemoInterface.php#L56-L59
				 */
				,"{$label} ({$iso3Base})" => $cm['base_' . $key]
			]
		);
	}

	/**
	 * 2016-03-26
	 * @used-by \Dfe\TwoCheckout\Method::capture()
	 * @used-by \Dfe\TwoCheckout\Method::refund()
	 * @used-by \Dfe\TwoCheckout\Handler\Charge::payment()
	 */
	const WEBHOOK_CASE = 'dfe_already_done';

	/**
	 * 2016-02-29
	 * @used-by Dfe/Stripe/etc/frontend/di.xml
	 * @used-by \Dfe\TwoCheckout\ConfigProvider::getConfig()
	 */
	const CODE = 'dfe_two_checkout';
	/**
	 * 2016-03-06
	 * @var string
	 */
	private static $TOKEN = 'token';

	/**
	 * 2016-03-07
	 * https://stripe.com/docs/api/php#create_charge-amount
	 * «A positive integer in the smallest currency unit
	 * (e.g 100 cents to charge $1.00, or 1 to charge ¥1, a 0-decimal currency)
	 * representing how much to charge the card.
	 * The minimum amount is $0.50 (or equivalent in charge currency).»
	 *
	 * «Zero-decimal currencies»
	 * https://support.stripe.com/questions/which-zero-decimal-currencies-does-stripe-support
	 * Here is the full list of zero-decimal currencies supported by Stripe:
	 * BIF: Burundian Franc
	 * CLP: Chilean Peso
	 * DJF: Djiboutian Franc
	 * GNF: Guinean FrancJ
	 * PY: Japanese Yen
	 * KMF: Comorian Franc
	 * KRW: South Korean Won
	 * MGA: Malagasy Ariary
	 * PYG: Paraguayan Guaraní
	 * RWF: Rwandan Franc
	 * VND: Vietnamese Đồng
	 * VUV: Vanuatu Vatu
	 * XAF: Central African Cfa Franc
	 * XOF: West African Cfa Franc
	 * XPF: Cfp Franc
	 *
	 * @param $payment II|I|OP
	 * @param float $amount
	 * @return int
	 */
	private static function amount(II $payment, $amount) {
		/** @var string[] $zeroDecimal */
		static $zeroDecimal = [
			'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA'
			,'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
		];
		/** @var string $iso3 */
		$iso3 = $payment->getOrder()->getBaseCurrencyCode();
		return ceil($amount * (in_array($iso3, $zeroDecimal) ? 1 : 100));
	}
}