define ([
	'df'
	,'Df_Payment/card'
	,'Dfe_TwoCheckout/API'
], function(df, parent, TCO) {'use strict'; return parent.extend({
	/**
	 * 2016-08-25
	 * https://mail.google.com/mail/u/0/#inbox/156ae0f52f7e5964
	 * @override
	 * @see mage2pro/core/Payment/view/frontend/web/js/view/payment/mixin.js
	 * @returns {String}
	 */
	debugMessage: df.c(function() {
		/**
		 * 2016-08-25
		 * The list of currencies supported by 2Checkout sandbox environment.
		 * @type {String[]}
		 */
		var codes = ['AED', 'ARS', 'AUD', 'BRL', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'HKD',
			'ILS', 'INR', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'RON', 'RUB',
			 'SEK', 'SGD', 'TRY', 'USD', 'ZAR'
		];
		return -1 < codes.indexOf(this.dfc.currency) ? '' : df.t(
			'The transaction will <b><a href="{url}" target="_blank">fail</a></b> with the message «<b>Bad request - parameter error</b>», because 2Checkout does not support the «<b>{currency}</b>» in the sandbox mode (but supports it in the production mode).'
			,{
				currency: this.dfc.currency
				,url: 'https://mage2.pro/t/1986'
			}
		);
	}),
	defaults: {df: {test: {suffix: 'SANDBOX'}}},
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
			this.prefillWithAFutureData();
			this.creditCardVerificationNumber(123);
		}
		return this;
	},
	/**
	 * @override
	 * @see https://github.com/magento/magento2/blob/2.1.0/app/code/Magento/Checkout/view/frontend/web/js/view/payment/default.js#L127-L159
	 * @used-by https://github.com/magento/magento2/blob/2.1.0/lib/web/knockoutjs/knockout.js#L3863
	 * @param {this} _this
	*/
	placeOrder: function(_this) {
		if (this.validate()) {
			/**
			 * 2016-05-18
			 * https://www.2checkout.com/documentation/payment-api/create-token
			 */
			TCO.requestToken(
				function(data){
					// 2016-05-18
					// https://www.2checkout.com/documentation/payment-api/create-token
					_this.token = data.response.token.token;
					_this.placeOrderInternal();
				},
				function(data){
					// 2016-05-18
					// https://www.2checkout.com/documentation/payment-api/create-token
					// This error code indicates that the ajax call failed.
					// We recommend that you retry the token request.
					_this.showErrorMessage(
						200 === data.errorCode ? 'Please, try again.' : data.errorMsg
					);
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
	}
});});
