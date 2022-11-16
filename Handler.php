<?php
namespace Dfe\TwoCheckout;
use Dfe\TwoCheckout\Handler\DefaultT;
use Dfe\TwoCheckout\Settings as S;
use Exception as E;
/**
 * @see \Dfe\TwoCheckout\Handler\DefaultT
 * @see \Dfe\TwoCheckout\Handler\RefundIssued
 */
abstract class Handler extends \Df\Core\O {
	/**
	 * 2016-03-28
	 * @used-by self::p()
	 * @see \Dfe\TwoCheckout\Handler\DefaultT::eligible()
	 * @see \Dfe\TwoCheckout\Handler\RefundIssued::process()
	 */
	abstract protected function eligible():bool;

	/**
	 * 2016-03-25
	 * @used-by self::p()
	 * @see \Dfe\TwoCheckout\Handler\DefaultT::process()
	 * @see \Dfe\TwoCheckout\Handler\RefundIssued::process()
	 * @return mixed
	 */
	abstract protected function process();

	/**
	 * 2016-05-22
	 * https://www.2checkout.com/documentation/notifications/refund-issued
	 * «Indicates type of message (
	 * ORDER_CREATED, FRAUD_STATUS_CHANGED, SHIP_STATUS_CHANGED
	 * , INVOICE_STATUS_CHANGED, REFUND_ISSUED, RECURRING_INSTALLMENT_SUCCESS
	 * , RECURRING_INSTALLMENT_FAILED, RECURRING_STOPPED, RECURRING_COMPLETE
	 * , or RECURRING_RESTARTED )»
	 * @return string
	 */
	final protected function type() {return $this['message_type'];}

	/**
	 * 2016-03-25
	 * @param array(string => mixed) $request
	 * @return mixed
	 * @throws E
	 */
	final static function p(array $request) {
		/** @var mixed $result */
		try {
			$s = dfps(__CLASS__); /** @var S $s */
			$s->init();
			# 2016-05-22
			# https://github.com/2Checkout/2checkout-php/wiki/Notification_Check#example-usage
			# https://www.2checkout.com/documentation/notifications/
			# «Each notification message will include an MD5 hash
			# that is computed using the secret word that you set up
			# under the Site Management page in the seller area.
			# The hash is returned on each message through the md5_hash key
			# and is computed as follows:
			# UPPERCASE(MD5_ENCRYPTED(sale_id + vendor_id + invoice_id + Secret Word))»
			if (!df_my_local() && !\Twocheckout_Notification::check($request, $s->secretWord())) {
				df_error('Invalid signature.');
			}
			/**
			 * 2016-05-22
			 * https://www.2checkout.com/documentation/notifications/refund-issued
			 * «Indicates type of message (
			 * ORDER_CREATED, FRAUD_STATUS_CHANGED, SHIP_STATUS_CHANGED
			 * , INVOICE_STATUS_CHANGED, REFUND_ISSUED, RECURRING_INSTALLMENT_SUCCESS
			 * , RECURRING_INSTALLMENT_FAILED, RECURRING_STOPPED, RECURRING_COMPLETE
			 * , or RECURRING_RESTARTED )»
			 * 2022-11-12
			 * "PHP ≥ 8.1: «Passing null to parameter … of type string is deprecated»":
			 * https://github.com/mage2pro/core/issues/173
			 * @var string $type
			 */
			$type = df_nts(dfa($request, 'message_type'));
			dfp_report(__CLASS__, $request, strtolower($type));
			/**
			 * REFUND_ISSUED => Handler\RefundIssued
			 * @var string $suffix
			 */
			$suffix = df_cc_class('Handler', df_underscore_to_camel($type));
			$i = df_new(df_con(__CLASS__, $suffix, DefaultT::class), $request); /** @var Handler $i */
			$result = $i->eligible() ? $i->process() : 'The event is not for our store.';
		}
		catch (E $e) {
			df_500();
			df_sentry(__CLASS__, $e, ['extra' => ['request' => $request]]);
			if (df_my_local()) {
				throw $e; # 2016-03-27 Удобно видеть стек на экране.
			}
			$result = __($e->getMessage());
		}
		return $result;
	}
}