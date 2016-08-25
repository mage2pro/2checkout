<?php
namespace Dfe\TwoCheckout;
use Df\Core\A;
// 2016-08-21
class Exception extends \Df\Payment\Exception {
	/**
	 * 2016-08-21
	 * @override
	 * @see \Df\Payment\Exception::__construct()
	 * @param array(string => mixed) $response
	 * @param array(string => mixed) $request [optional]
	 */
	public function __construct(array $response, array $request = []) {
		$this->_req = $request;
		$this->_res = dfao($response);
		parent::__construct();
	}

	/**
	 * 2016-08-19
	 * @override
	 * @see \Df\Payment\Exception::message()
	 * @return string
	 */
	public function message() {return df_cc_n(
		'The 2Checkout request is failed.'
		,"Response:", df_json_encode_pretty($this->_res->a())
		,!$this->_req ? null : ['Request:', df_json_encode_pretty($this->_req)]
	);}

	/**
	 * 2016-08-21
	 * @override
	 * @see \Df\Payment\Exception::messageForCustomer()
	 * @return string
	 */
	public function messageForCustomer() {
		return dfp_error_message(df_first(df_clean($this->_res->a([
			'errors/0/message', 'exception/errorMsg'
		]))));
	}

	/**
	 * 2016-08-21
	 * @used-by \Dfe\TwoCheckout\Exception::__construct()
	 * @used-by \Dfe\TwoCheckout\Exception::message()
	 * @var array(string => mixed)
	 */
	private $_req;

	/**
	 * 2016-08-21
	 * @used-by \Dfe\TwoCheckout\Exception::__construct()
	 * @used-by \Dfe\TwoCheckout\Exception::message()
	 * @var A
	 */
	private $_res;
}