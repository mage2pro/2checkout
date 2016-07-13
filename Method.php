<?php
namespace Dfe\TwoCheckout;
use Dfe\TwoCheckout\Block\Info as InfoBlock;
use Dfe\TwoCheckout\Settings as S;
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
	 * @see \Df\Payment\Method::canCapture()
	 * @return bool
	 */
	public function canCapture() {return true;}

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
		$this->charge($payment, $amount);
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
	 * 2016-05-03
	 * @override
	 * @see \Df\Payment\Method::iiaKeys()
	 * @used-by \Df\Payment\Method::assignData()
	 * @return string[]
	 */
	protected function iiaKeys() {return [self::$TOKEN];}

	/**
	 * 2016-05-21
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
			/** @var Transaction $tCapture */
			$tCapture = $payment->getAuthorizationTransaction();
			if ($tCapture) {
				/** @var Creditmemo $cm */
				$cm = $payment->getCreditmemo();
				/**
				 * 2016-03-24
				 * Credit Memo и Invoice отсутствуют в сценарии Authorize / Capture
				 * и присутствуют в сценарии Capture / Refund.
				 */
				df_assert($cm);
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
					,'comment' => df_cc_n(
						df_trim($cm->getCustomerNote())
						,'Magento Credit Memo: ' . $cm->getIncrementId()
					)
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
					,'currency' => 'customer'
					/**
					 * 2016-05-21
					 * «The amount to refund.
					 * Only needed when issuing a partial refund.
					 * If an amount is not specified,
					 * the remaining amount for the invoice is assumed.»
					 * https://www.2checkout.com/documentation/api/sales/refund-invoice
					 */
					, 'amount' => $amount
				]);
				/**
				 * 2016-05-22
				 * Используем @uses df_on_save(), потому что нам нужен идентификатор возврата,
				 * а в этой точке программы возврат ещё не имеет идентификатора.
				 */
				df_on_save($cm, function() use($cm, $payment) {
					\Twocheckout_Sale::comment([
						'sale_id' => $payment->getAdditionalInformation(InfoBlock::SALE_ID)
						, 'sale_comment' => df_credit_memo_backend_url($cm->getId())
					]);
				});
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
			 *
			 * Раньше тут стоял код:
					$e = dfa($r, 'exception');
					if ($e) {
						df_error(dfa($e, 'errorMsg'));
					}
			 * Однако он бесполезен и избыточен, потому что в случае сбоя
			 * библиотека 2Checkout сама взозбуждает исключительную ситуацию:
			 * @see \Twocheckout_Util::checkError()
			 * https://github.com/2Checkout/2checkout-php/blob/cbac8da68155b6f557db0da0ac16a48a0faa5400/lib/Twocheckout/Api/TwocheckoutUtil.php#L65-L72
			 */
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
			 * https://github.com/magento/magento2/blob/8fd3e8/app/code/Magento/Sales/Model/Order/Payment.php#L540-L555
			 * @used-by \Magento\Sales\Model\Order\Payment::canVoid()
			 *
			 * 2016-05-20
			 * https://www.2checkout.com/documentation/payment-api/create-sale
			 * «2Checkout Invoice ID»
			 */
			$payment->setTransactionId($id);
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
			df_order_payment_add($payment, dfa_select_ordered($card, [
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
			$payment->unsAdditionalInformation(self::$TOKEN);
			/**
			 * 2016-03-15
			 * Аналогично, иначе операция «void» (отмена авторизации платежа) будет недоступна:
			 * https://github.com/magento/magento2/blob/8fd3e8/app/code/Magento/Sales/Model/Order/Payment.php#L540-L555
			 * @used-by \Magento\Sales\Model\Order\Payment::canVoid()
			 * Транзакция ситается завершённой, если явно не указать «false».
			 */
			$payment->setIsTransactionClosed(true);
			/**
			 * 2016-05-21
			 * Пока не знаю, как передавать нестандартные параметры нормальным способом.
			 * Похоже, для Payment API такой возможности пока нет.
			 * https://mail.google.com/mail/u/0/#sent/154d5138c541ed85
			 * Вариант 'x_custom_username' => $this->order()->getIncrementId()
			 * у меня не работает.
			 *
			 * Поэтому для удобства администратора указываем в комментарии к заказу в 2Checkout
			 * номер заказа в Magento.
			 * https://www.2checkout.com/documentation/api/sales/create-comment
			 */
			/**
			 * 2016-05-22
			 * Используем @uses df_on_save(), потому что нам нужен идентификатор заказа,
			 * а в этой точке программы (в момент платежа) заказ ещё не имеет идентификатора.
			 */
			df_on_save($this->o(), function() use($saleId) {
				\Twocheckout_Sale::comment([
					'sale_id' => $saleId
					, 'sale_comment' => implode(' ', [
						'Magento Order:'
						, $this->o()->getIncrementId()
						, df_order_backend_url($this->o()->getId())
					])
				]);
			});
		});
		return $this;
	}

	/**
	 * 2016-03-26
	 * @used-by \Dfe\TwoCheckout\Method::capture()
	 * @used-by \Dfe\TwoCheckout\Method::refund()
	 * @used-by \Dfe\TwoCheckout\Handler\Charge::payment()
	 */
	const WEBHOOK_CASE = 'dfe_webhook_case';

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
}