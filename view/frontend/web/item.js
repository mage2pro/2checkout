define ([
	'Df_Payment/js/view/payment/cc-form'
	,'jquery'
	, 'df'
	, 'mage/translate'
	, 'underscore'
	,'Dfe_TwoCheckout/API'
], function(Component, $, df, $t, _, TCO) {
	'use strict';
	return Component.extend({
		defaults: {clientConfig: {id: 'dfe-2checkout'}},
		/**
		 * 2016-03-01
		 * 2016-03-08
		 * Раньше реализация была такой:
		 * return _.keys(this.getCcAvailableTypes())
		 *
		 * Не стал делать реализацию на сервере, потому что там меня не устраивал
		 * порядок следования платёжных систем (первой была «American Express»)
		 * https://github.com/magento/magento2/blob/cf7df72/app/code/Magento/Payment/etc/payment.xml#L10-L44
		 * А изменить этот порядок коротко не получается:
		 * https://github.com/magento/magento2/blob/487f5f45/app/code/Magento/Payment/Model/CcGenericConfigProvider.php#L105-L124
		 *
		 * 2016-05-18
		 * https://www.2checkout.com/faq#what-payment-methods-does-2checkout-offer-my-customers
		 *
		 * @returns {String[]}
	 	 */
		getCardTypes: function() {return ['VI', 'MC', 'AE', 'JCB', 'DI', 'DN'];},
		/**
		 * 2016-03-06
   		 * @override
   		 */
		getData: function () {
			return {
				/**
				 * 2016-05-03
				 * Если не засунуть «token» внутрь «additional_data»,
				 * то получим сбой:
				 * «Property "Token" does not have corresponding setter
				 * in class "Magento\Quote\Api\Data\PaymentInterface»
				 */
				additional_data: {token: this.token}
				,method: this.item.method
			};
		},
		/**
		 * 2016-03-08
		 * @return {String}
		*/
		getTitle: function() {
			var result = this._super();
			return result + (!this.config('isTest') ? '' : ' [<b>2Checkout SANDBOX</b>]');
		},
		/**
		 * 2016-03-02
		 * @return {Object}
		*/
		initialize: function() {
			this._super();
			/**
			 * 2016-05-18
			 * https://www.2checkout.com/documentation/payment-api/create-token
			 */
			TCO.loadPubKey(this.isTest() ? 'sandbox' : 'production');
			// 2016-05-18
			// «Mage2.PRO» → «Payment» → «2Checkout» → «Prefill the Payment Form with Test Data?»
			// https://mage2.pro/t/topic/1631
			/** @type {String|Boolean} */
			var prefill = this.config('prefill');
			if (prefill) {
				this.creditCardNumber(prefill);
				this.creditCardExpMonth(7);
				this.creditCardExpYear(2019);
				this.creditCardVerificationNumber(123);
			}
			return this;
		},
		pay: function() {
			var _this = this;
			/**
			 * 2016-05-18
			 * https://www.2checkout.com/documentation/payment-api/create-token
			 */
			TCO.requestToken(
				function(data){
					// 2016-05-18
					// https://www.2checkout.com/documentation/payment-api/create-token
					_this.token = data.response.token.token;
					_this.placeOrder();
				},
				function(data){
					_this.messageContainer.addErrorMessage({
						'message': $t(
							// 2016-05-18
							// https://www.2checkout.com/documentation/payment-api/create-token
							// This error code indicates that the ajax call failed.
							// We recommend that you retry the token request.
							200 === data.errorCode
							? 'Please, try again.'
							: data.errorMsg
						)
					});
				},
				{
					cvv: this.dfCardVerification()
					,expMonth: this.dfCardExpirationMonth()
					,expYear: this.dfCardExpirationYear()
					,ccNo: this.dfCardNumber()
					,publishableKey: this.config('publishableKey')
					,sellerId: this.config('accountNumber')
				}
			);
		}
	});
});
