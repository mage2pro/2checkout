<?php
namespace Dfe\TwoCheckout;
use Dfe\TwoCheckout\Handler\DefaultT;
use Dfe\TwoCheckout\Settings as S;
use Exception as E;
abstract class Handler extends \Df\Core\O {
	/**
	 * 2016-03-25
	 * @used-by \Dfe\TwoCheckout\Handler::p()
	 * @return mixed
	 */
	abstract protected function process();

	/**
	 * 2016-03-28
	 * @used-by \Dfe\TwoCheckout\Handler::p()
	 * @return bool
	 */
	protected function eligible() {return false;}

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
	protected function type() {return $this['message_type'];}

	/**
	 * 2016-03-25
	 * @param array(string => mixed) $request
	 * @return mixed
	 * @throws E
	 */
	public static function p(array $request) {
		/** @var mixed $result */
		try {
			S::s()->init();
			/**
			 * 2016-05-22
			 * https://github.com/2Checkout/2checkout-php/wiki/Notification_Check#example-usage
			 * https://www.2checkout.com/documentation/notifications/
			 * «Each notification message will include an MD5 hash
			 * that is computed using the secret word that you set up
			 * under the Site Management page in the seller area.
			 * The hash is returned on each message through the md5_hash key
			 * and is computed as follows:
			 * UPPERCASE(MD5_ENCRYPTED(sale_id + vendor_id + invoice_id + Secret Word))»
			 */
			//if (!\Twocheckout_Notification::check($request, S::s()->secretWord())) {
			//	df_error('Invalid signature.');
			//}
			/**
			 * 2016-05-22
			 * https://www.2checkout.com/documentation/notifications/refund-issued
			 * «Indicates type of message (
			 * ORDER_CREATED, FRAUD_STATUS_CHANGED, SHIP_STATUS_CHANGED
			 * , INVOICE_STATUS_CHANGED, REFUND_ISSUED, RECURRING_INSTALLMENT_SUCCESS
			 * , RECURRING_INSTALLMENT_FAILED, RECURRING_STOPPED, RECURRING_COMPLETE
			 * , or RECURRING_RESTARTED )»
			 * @var string|null $type
			 */
			$type = dfa($request, 'message_type');
			/**
			 * REFUND_ISSUED => Handler\RefundIssued
			 * @var string $suffix
			 */
			$suffix = df_implode_class('handler', implode(df_ucfirst(explode('_', strtolower($type)))));
			$class = df_convention(__CLASS__, $suffix, DefaultT::class);
			/** @var Handler $i */
			$i = df_create($class, $request);
			$result = $i->eligible() ? $i->process() : 'The event is not for our store.';
		}
		catch (E $e) {
			df_response()->setStatusCode(500);
			if (df_is_it_my_local_pc()) {
				// 2016-03-27
				// Удобно видеть стек на экране.
				throw $e;
			}
			else {
				$result = __($e->getMessage());
			}
		}
		return $result;
	}
}