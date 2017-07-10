<?php
namespace Dfe\TwoCheckout;
use Df\Core\A;
// 2016-08-21
final class Exception extends \Df\Payment\Exception {
	/**
	 * 2016-08-21
	 * @override
	 * @see \Df\Core\Exception::__construct()
	 * @used-by \Dfe\TwoCheckout\Method::charge()
	 * @param array(string => mixed) $response
	 * @param array(string => mixed) $request [optional]
	 */
	function __construct(array $response, array $request = []) {
		$this->_req = $request;
		$this->_res = dfao($response);
		parent::__construct();
	}

	/**
	 * 2016-08-19
	 * @override
	 * @see \Df\Core\Exception::message()
	 * @used-by \Dfe\TwoCheckout\Method::api()
	 * @return string
	 */
	function message() {return df_api_rr_failed('2Checkout', $this->_res->a(), $this->_req);}

	/**
	 * 2016-08-21
	 * @override
	 * @see \Df\Core\Exception::messageC()
	 * @return string
	 */
	function messageC() {return dfp_error_message(df_first(df_clean($this->_res->a([
		'errors/0/message', 'exception/errorMsg'
	]))));}

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