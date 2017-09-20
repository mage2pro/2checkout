// 2016-05-18
define([
	'df', 'Df_StripeClone/main', 'https://www.2checkout.com/checkout/api/2co.min.js'
], function(df, parent) {'use strict';
/** 2017-09-06 @uses Class::extend() https://github.com/magento/magento2/blob/2.2.0-rc2.3/app/code/Magento/Ui/view/base/web/js/lib/core/class.js#L106-L140 */	
return parent.extend({
	/**
	 * 2016-08-25
	 * https://mail.google.com/mail/u/0/#inbox/156ae0f52f7e5964
	 * @override
	 * @see mage2pro/core/Payment/view/frontend/web/mixin.js
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
		return -1 < codes.indexOf(this.paymentCurrency().code) ? '' : df.t(
			'The transaction will <b><a href="{url}" target="_blank">fail</a></b> with the message «<b>Bad request - parameter error</b>», because 2Checkout does not support the «<b>{currency}</b>» currency in the sandbox mode (but supports it in the production mode).'
			,{
				currency: this.paymentCurrency().name
				,url: 'https://mage2.pro/t/1986'
			}
		);
	}),
	defaults: {df: {
		// 2016-11-10
		// https://mage2.pro/t/1631
		// 2016-11-13
		// 2Checkout ожидает CVV именно в виде строки.
		// Если передать число, то будет сбой: «TypeError: e.cvv.replace is not a function»
		// потому что 2Checkout обрабатывает значение так:
		// e.cvv = e.cvv.replace(/[^0-9]+/g,'');
		card: {prefill: {cvv: '123'}}
		,test: {suffix: 'SANDBOX'}
	}},
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
	 * 2017-02-05
	 * The bank card network codes: https://mage2.pro/t/2647
	 *
	 * @returns {String[]}
	 */
	getCardTypes: function() {return ['VI', 'MC', 'AE', 'JCB', 'DI', 'DN'];},
	/**
	 * 2016-05-18
	 * https://www.2checkout.com/documentation/payment-api/create-token
	 * @override
	 * @see Df_Payment/card::initialize()
	 * https://github.com/mage2pro/core/blob/2.4.21/Payment/view/frontend/web/card.js#L77-L110
	 * @returns {exports}
	*/
	initialize: function() {
		this._super();
		TCO.loadPubKey(this.isTest() ? 'sandbox' : 'production');
		return this;
	},
	/**
	 * @override
	 * @see Df_StripeClone/main::placeOrder()
	 * @used-by Df_Payment/main.html:
	 *		<button
	 *			class="action primary checkout"
	 *			type="submit"
	 *			data-bind="
	 *				click: placeOrder
	 *				,css: {disabled: !isPlaceOrderActionAllowed()}
	 *				,enable: dfIsChosen()
	 *			"
	 *			disabled
	 *		>
	 *			<span data-bind="df_i18n: 'Place Order'"></span>
	 *		</button>
	 * https://github.com/mage2pro/core/blob/2.9.10/Payment/view/frontend/web/template/main.html#L57-L68
	 * https://github.com/magento/magento2/blob/2.1.0/lib/web/knockoutjs/knockout.js#L3863
	 * @param {this} _this
	 * @param {Event} event
	 */
	placeOrder: function(_this, event) {
		if (event) {
			event.preventDefault();
		}
		if (this.validate()) {
			// 2017-07-26 «Sometimes getting duplicate orders in checkout»: https://mage2.pro/t/4217
			this.state_waitingForServerResponse(true);
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
					_this.showErrorMessage(200 === data.errorCode ? 'Please, try again.' : data.errorMsg);
					_this.state_waitingForServerResponse(false);
				},
				{
					cvv: this.creditCardVerificationNumber()
					,expMonth: this.creditCardExpMonth()
					,expYear: this.creditCardExpYear()
					,ccNo: this.creditCardNumber()
					,publishableKey: this.publicKey()
					,sellerId: this.config('accountNumber')
				}
			);
		}
	}
});});
