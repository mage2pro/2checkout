<?php
namespace Dfe\TwoCheckout;
use Df\Payment\Token;
use Dfe\TwoCheckout\Block\Info as InfoBlock;
use Magento\Framework\Exception\LocalizedException as LE;
use Magento\Sales\Model\Order as O;
use Magento\Sales\Model\Order\Creditmemo as CM;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment as OP;
use Magento\Sales\Model\Order\Payment\Transaction;
/** @method Settings s() */
final class Method extends \Df\Payment\Method {
	/**
	 * 2016-09-07
	 * 2016-05-23
	 * https://www.2checkout.com/documentation/checkout/parameter-sets/pass-through-products/
	 * «Price of the line item.
	 * Format: 0.00-99999999.99, defaults to 0 if a value isn’t passed in
	 * or if value is incorrectly formatted, no negatives
	 * (use positive values for coupons).»
	 *
	 * 2016-05-19
	 * «Format: 0.00-99999999.99,
	 * defaults to 0 if a value isn’t passed in or if value is incorrectly formatted, no negatives»
	 * https://www.2checkout.com/documentation/payment-api/create-sale
	 * @override
	 * @see \Df\Payment\Method::amountFormat()
	 * @used-by \Df\Payment\Operation::amountFormat()
	 * @param float|int $a
	 * @return string
	 */
	function amountFormat($a) {return df_f2(df_assert_le(99999999.99, abs($a)));}

	/**
	 * 2016-03-07
	 * @override
	 * @see \Df\Payment\Method::canCapture()
	 * @return bool
	 */
	function canCapture() {return true;}

	/**
	 * 2016-03-08
	 * @override
	 * @see \Df\Payment\Method::canRefund()
	 * @return bool
	 */
	function canRefund() {return true;}

	/**
	 * 2016-03-08
	 * @override
	 * @see \Df\Payment\Method::canRefundPartialPerInvoice()
	 * @return bool
	 */
	function canRefundPartialPerInvoice() {return true;}

	/**
	 * 2016-05-21
	 * @override
	 * @see \Df\Payment\Method::_refund()
	 * @param float $a
	 */
	protected function _refund($a) {$this->api(function() use($a) {
		/**
		 * 2016-03-17
		 * Метод @uses \Magento\Sales\Model\Order\Payment::getAuthorizationTransaction()
		 * необязательно возвращает транзакцию типа «авторизация»:
		 * в первую очередь он стремится вернуть родительскую транзакцию:
		 * https://github.com/magento/magento2/blob/2.1.0/app/code/Magento/Sales/Model/Order/Payment/Transaction/Manager.php#L31-L47
		 * Это как раз то, что нам нужно, ведь наш модуль может быть настроен сразу на capture,
		 * без предварительной транзакции типа «авторизация».
		 */
		/** @var Transaction $tCapture */
		if ($tCapture = $this->ii()->getAuthorizationTransaction()) {
			/** @var CM|null $cm */
			// 2016-03-24
			// Credit Memo и Invoice отсутствуют в сценарии Authorize / Capture
			// и присутствуют в сценарии Capture / Refund.
			$cm = df_assert($this->ii()->getCreditmemo());
			/**
			 * 2016-05-21
			 * https://www.2checkout.com/documentation/api/sales/refund-invoice
			 * https://github.com/2Checkout/2checkout-php/wiki/Sale_Refund
			 * https://github.com/2Checkout/2checkout-php/wiki#exceptions
			 */
			\Twocheckout_Sale::refund([
				'invoice_id' => $tCapture->getTxnId()
				/**
				 * 2016-05-21
				 * «ID representing the reason the refund was issued.
				 * Required. (values: 1-17 from the following list can be used
				 * except for 7 as it is for internal use only)»
				 * https://www.2checkout.com/documentation/api/sales/refund-invoice
				 * @todo Надо сделать настраиваемым.
				 * Пока зашито значение «5:	Other».
				 *
				 * В личном кабинете магазина в 2Сheckout этот параметр никак не отображается.
				 * Спросил у техподдержки, для чего он:
				 * «[Payment API] How is a refund's «category» parameter used?»
				 * https://mail.google.com/mail/u/0/#sent/154d4e34743cb87f
				 *
				 * 2016-05-23
				 * Ответили:
				 * «It should appear under the sale's comment, at the bottom of the page.
				 * From the 2CO dashboard:
				 * Sales tab, click on specific sale that has been refunded,
				 * scroll to bottom of page once sale page is loaded.»
				 */
				,'category' => 5
				/**
				 * 2016-05-21
				 * «Message explaining why the refund was issued.
				 * Required. May not contain ‘<’ or ‘>’. (5000 character max)»
				 * https://www.2checkout.com/documentation/api/sales/refund-invoice
				 *
				 * Комментарий отображается в личном кабинете магазина в 2Checkout.
				 *
				 * Переносы строк пока в комментариях не сохраняются:
				 * https://mail.google.com/mail/u/0/#sent/154d4d9e86de1576
				 * «Is any way to preserve line breaks for a sale comments?»
				 * Но всё равно их добавляем:
				 * вдруг в будущем появится возможность их сохранения.
				 *
				 * Идеально было бы вообще ставить ссылку на документ-возврат
				 * в интернет-магазине. Но теги тем более не сохраняются.
				 * Если они добавят поддержку переносов строк, то попрошу их и о тегах.
				 */
				,'comment' =>
					df_trim($cm->getCustomerNote()) . "\nMagento Credit Memo: {$cm->getIncrementId()}"
			// 2017-04-10
			// Избегаем сбоя из-за погрешности округления:
			// «Amount greater than remaining balance on invoice.»
			] + (df_is0($cm->getBaseGrandTotal() - $cm->getInvoice()->getBaseGrandTotal()) ? [] : [
				/**
				 * 2016-05-21
				 * «Currency type of refund amount.
				 * Can be ‘usd’, ‘vendor’ or ‘customer’. Only required if amount is used.»
				 * https://www.2checkout.com/documentation/api/sales/refund-invoice
				 *
				 * [Payment API / Refund] What do mean «vendor» and «customer» currency codes?
				 * https://mail.google.com/mail/u/0/#sent/154d347f4c3d79a8
				 *
				 * Сначала пытался поставить тут $cm->getBaseCurrencyCode()
				 * но это неправильно.
				 *
				 * 2016-05-23
				 * Судя по ответу техподдержки,
				 * Похоже, что в нашем случае правильное значение — «customer»:
				 * «It means the value can be set to a specific currency ('usd'),
				 * the vendor's currency settings (Site Management Sub-tab inside of 2CO acct),
				 * or the currency the customer selected at checkout (hosted checkouts).»
				 * https://mail.google.com/mail/u/0/#inbox/154d347f4c3d79a8
				 */
				'currency' => 'customer'
				// 2016-05-21
				// «The amount to refund.
				// Only needed when issuing a partial refund.
				// If an amount is not specified,
				// the remaining amount for the invoice is assumed.»
				// https://www.2checkout.com/documentation/api/sales/refund-invoice
				,'amount' => $this->amountFormat($a)
			]));
			/**
			 * 2016-05-22
			 * Используем @uses df_on_save(), потому что нам нужен идентификатор возврата,
			 * а в этой точке программы возврат ещё не имеет идентификатора.
			 */
			df_on_save($cm, function() use($cm) {
				\Twocheckout_Sale::comment([
					'sale_id' => $this->iia(InfoBlock::SALE_ID)
					,'sale_comment' => df_cm_backend_url($cm)
				]);
			});
		}
	});}

	/**
	 * 2016-11-13
	 * Этот метод косвенно (через amountParse) используется при refund.
	 * @override
	 * @see \Df\Payment\Method::amountFactor()
	 * @used-by \Df\Payment\Method::amountFormat()
	 * @used-by \Df\Payment\Method::amountParse()
	 * @return int
	 */
	protected function amountFactor() {return 1;}

	/**
	 * 2017-02-08
	 * @override
	 * The result should be in the basic monetary unit (like dollars), not in fractions (like cents).
	 * «Does 2Checkout have minimum and maximum amount limitations on a single payment?»
	 *
	 * I have got an answer from the 2Checkout support:
	 * «The minimum recommended charge is $1.00 as banks may elect to reject lesser payments.
	 * You must charge a positive value with 2CO, so no negative or zero-amount sales are permitted.
	 * There is no upper limit to a payment amount.»
	 *
	 * «The system will not return an error if the value charged is at least positive -
	 * we are unable to account for a bank's decision to allow or reject a payment
	 * based on a very small amount charged.
	 * We recommend that you charge the approximate equivalent of $1.00 USD,
	 * regardless of the currency used during the test.»
	 *
	 * https://mage2.pro/t/2686
	 *
	 * 2017-04-15
	 * The «USD» currency could be not set up in the store,
	 * so use @uses df_currency_convert_safe() to get rid from a failure like «Undefined rate from "AUD-USD"».
	 *
	 * @see \Df\Payment\Method::amountLimits()
	 * @used-by \Df\Payment\Method::isAvailable()
	 * @return \Closure
	 */
	protected function amountLimits() {return function($c) {return [
		df_currency_convert_safe(1, 'USD', $c), null
	];};}

	/**
	 * 2016-08-14
	 * @override
	 * @see \Df\Payment\Method::charge()
	 * @used-by \Df\Payment\Method::authorize()
	 * @used-by \Df\Payment\Method::capture()
	 * @param bool $capture [optional]
	 */
	protected function charge($capture = true) {$this->api(function() {
		/**
		 * 2016-08-21
		 * @see \Twocheckout_Api_Requester::doCall()
		 * https://github.com/2Checkout/2checkout-php/blob/0.3.1/lib/Twocheckout/Api/TwocheckoutApi.php#L25-L31
		 */
		/** @var array(string => mixed) $p */
		$p = ['api' => 'checkout'] + Charge::p($this);
		df_sentry_extra($this, 'Request Params', $p);
		/** @var \Twocheckout_Api_Requester $requester */
		$requester = new \Twocheckout_Api_Requester;
		/**
		 * 2016-08-21
		 * По аналогии с @see \Twocheckout_Charge::auth()
		 */
		/** @var string $url */
		$url = "/checkout/api/1/{$this->s()->accountNumber()}/rs/authService";
		/** @var array(string => mixed) $r */
		$r = df_json_decode($requester->doCall($url, $p));
		/**
		 * 2016-08-21
		 * По аналогии с @see \Twocheckout_Util::checkError()
		 */
		if (isset($r['errors']) || isset($r['exception'])) {
			throw new Exception($r, $p);
		}
		/**
		 * 2016-05-20
		 * «If an error occurs when attempting to authorize the sale,
		 * such as a processing error or an error in the JSON request,
		 * the errorCode and errorMsg will be returned in the exception sub object
		 * in the response body.»
		 *
		 * Раньше тут стоял код:
		 *		$e = dfa($r, 'exception');
		 *		if ($e) {
		 *			df_error(dfa($e, 'errorMsg'));
		 *		}
		 * Однако он бесполезен и избыточен, потому что в случае сбоя
		 * библиотека 2Checkout сама взозбуждает исключительную ситуацию:
		 * @see \Twocheckout_Util::checkError()
		 * https://github.com/2Checkout/2checkout-php/blob/cbac8da68155b6f557db0da0ac16a48a0faa5400/lib/Twocheckout/Api/TwocheckoutUtil.php#L65-L72
		 */
		/** @var array(string => mixed)|null $rr */
		$rr = dfa($r, 'response');
		// 2016-05-20
		// https://www.2checkout.com/documentation/payment-api/create-sale
		// «Code indicating the result of the authorization attempt.»
		df_assert_eq('APPROVED', dfa($rr, 'responseCode'));
		df_assert(is_null(dfa($r, 'validationErrors')));
		/**
		 * 2016-05-21
		 * Идентификатор документа-invoice в 2Checkout.
		 * https://www.2checkout.com/documentation/payment-api/create-sale
		 * Обратите внимание, что он отличается
		 * от идентификатора документа-sale в 2Checkout.
		 * @var string $id
		 */
		$id = dfa($rr, 'transactionId');
		/**
		 * 2016-05-21
		 * Идентификатор документа-sale в 2Checkout.
		 * https://www.2checkout.com/documentation/payment-api/create-sale
		 * Обратите внимание, что он отличается
		 * от идентификатора документа-invoice в 2Checkout.
		 * @var string $saleId
		 */
		$saleId = dfa($rr, 'orderNumber');
		/**
		 * 2016-03-15
		 * Иначе операция «void» (отмена авторизации платежа) будет недоступна:
		 * «How is a payment authorization voiding implemented?»
		 * https://mage2.pro/t/938
		 * https://github.com/magento/magento2/blob/2.1.0/app/code/Magento/Sales/Model/Order/Payment.php#L540-L555
		 * @used-by \Magento\Sales\Model\Order\Payment::canVoid()
		 *
		 * 2016-05-20
		 * https://www.2checkout.com/documentation/payment-api/create-sale
		 * «2Checkout Invoice ID»
		 */
		$this->ii()->setTransactionId($id);
		/**
		 * 2016-05-20
		 * https://www.2checkout.com/documentation/api/sales/detail-sale
		 * https://github.com/2Checkout/2checkout-php/wiki#example-admin-api-usage
		 * @var array(string => string|mixed)
		 *
		 * 2016-05-21
		 * Этот запрос в промышленном («live») режиме с включенной опцией «Demo Setting»
		 * закончится сбоем: «Unable to find record.»
		 * https://mail.google.com/mail/u/0/#sent/154d2ebd2fddf942
		 *
		 * «I try to use a Payment API live mode with the "demo setting" enabled.
		 * A payment trasaction is succeeded, but the Sale and Invoice are not created,
		 * and the https://www.2checkout.com/api/sales/detail_sale API does not work.
		 *
		 * See the screenshots attached.
		 *
		 * Is it normal?
		 * Is it because of the  "demo setting" or because of my account is not fully registered ("your application is 25% complete")?
		 * I am developing a redistributable payment extension for Magento 2 ecommerce system.
		 * So how should a store administrator test my extension? Shoud it register a Sandbox account for testing?
		 * Or is any possibility to make full testing with a live account
		 * ("full" - I mean: 1) sale, 2) get sale details. 3) refund).»
		 *
		 * Кстати, включена ли опция «Demo Setting»
		 * мы можем узнать посредством API:
		 * https://www.2checkout.com/documentation/api/account/detail-company-info
		 */
		$sr = \Twocheckout_Sale::retrieve(['invoice_id' => $id]);
		//df_log($sr);
		/** @var array(string => string|array) $sale */
		$sale = dfa($sr, 'sale');
		/** @var array(string => string) $card */
		$card = dfa_deep($sale, 'customer/pay_method');
		/**
		 * 2016-03-15
		 * https://mage2.pro/t/941
		 * https://stripe.com/docs/api#card_object-last4
		 * «How is the \Magento\Sales\Model\Order\Payment's setCcLast4() / getCcLast4() used?»
		 *
		 * 2016-05-20
		 * Мы не можем получить 4 последние цифры карты,
		 * вместо этого получаем 4 первых и 2 последних.
		 */
		$this->iiaAdd(dfa_select_ordered($card, [
			InfoBlock::CARD_F6, InfoBlock::CARD_L2
		]) + [
			/**
			 * 2016-05-21
			 * Идентификатор документа-sale в 2Checkout.
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 * Обратите внимание, что он отличается
			 * от идентификатора документа-invoice в 2Checkout.
			 * Его также можно получить посредством dfa($sale, 'sale_id')
			 */
			InfoBlock::SALE_ID => $saleId
		]);
		$this->iiaUnset(Token::KEY);
		/**
		 * 2016-03-15
		 * Если оставить открытой транзакцию «capture»,
		 * то операция «void» (отмена авторизации платежа) будет недоступна:
		 * https://github.com/magento/magento2/blob/2.1.0/app/code/Magento/Sales/Model/Order/Payment.php#L540-L555
		 * @used-by \Magento\Sales\Model\Order\Payment::canVoid()
		 * Транзакция считается закрытой, если явно не указать «false».
		 *
		 * 2017-01-16
		 * Наоборот: если закрыть транзакцию типа «authorize»,
		 * то операция «Capture Online» из административного интерфейса будет недоступна:
		 * @see \Magento\Sales\Model\Order\Payment::canCapture()
		 *		if ($authTransaction && $authTransaction->getIsClosed()) {
		 *			$orderTransaction = $this->transactionRepository->getByTransactionType(
		 *				Transaction::TYPE_ORDER,
		 *				$this->getId(),
		 *				$this->getOrder()->getId()
		 *			);
		 *			if (!$orderTransaction) {
		 *				return false;
		 *			}
		 *		}
		 * https://github.com/magento/magento2/blob/2.1.3/app/code/Magento/Sales/Model/Order/Payment.php#L263-L281
		 * «How is \Magento\Sales\Model\Order\Payment::canCapture() implemented and used?»
		 * https://mage2.pro/t/650
		 * «How does Magento 2 decide whether to show the «Capture Online» dropdown
		 * on a backend's invoice screen?»: https://mage2.pro/t/2475
		 */
		$this->ii()->setIsTransactionClosed(true);
		/**
		 * 2016-05-21
		 * Пока не знаю, как передавать нестандартные параметры нормальным способом.
		 * Похоже, для Payment API такой возможности пока нет.
		 * https://mail.google.com/mail/u/0/#sent/154d5138c541ed85
		 * Вариант 'x_custom_username' => $this->o()->getIncrementId()
		 * у меня не работает.
		 *
		 * Поэтому для удобства администратора указываем в комментарии к заказу в 2Checkout
		 * номер заказа в Magento.
		 * https://www.2checkout.com/documentation/api/sales/create-comment
		 *
		 * 2016-05-22
		 * Используем @uses df_on_save(), потому что нам нужен идентификатор заказа,
		 * а в этой точке программы (в момент платежа) заказ ещё не имеет идентификатора.
		 */
		df_on_save($this->o(), function() use($saleId) {
			\Twocheckout_Sale::comment(['sale_id' => $saleId, 'sale_comment' => df_cc_s(
				'Magento Order:', $this->oii(), df_order_backend_url($this->o())
			)]);
		});
	});}

	/**
	 * 2016-05-03
	 * @override
	 * @see \Df\Payment\Method::iiaKeys()
	 * @used-by \Df\Payment\Method::assignData()
	 * @return string[]
	 */
	protected function iiaKeys() {return [Token::KEY];}

	/**
	 * 2016-03-17
	 * Чтобы система показала наше сообщение вместо общей фразы типа
	 * «We can't void the payment right now» надо вернуть объект именно класса
	 * @uses \Magento\Framework\Exception\LocalizedException
	 * https://mage2.pro/t/945
	 * https://github.com/magento/magento2/blob/2.1.0/app/code/Magento/Sales/Controller/Adminhtml/Order/VoidPayment.php#L20-L30
	 * @param callable $function
	 * @return mixed
	 * @throws LE
	 */
	private function api(callable $function) {
		try {$this->s()->init(); return $function();}
		catch (Exception $e) {throw $e;}
		catch (\Exception $e) {throw df_le($e);}
	}

	/**
	 * 2016-02-29
	 * @used-by https://github.com/mage2pro/2checkout/blob/1.4.1/etc/di.xml#L9
	 * @used-by https://github.com/mage2pro/2checkout/blob/1.4.1/etc/frontend/di.xml#L16
	 * @used-by \Df\Payment\Method::codeS()
	 * @see \Df\Payment\Settings::prefix()
	 */
	const CODE = 'dfe_two_checkout';
}