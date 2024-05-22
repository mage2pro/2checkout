<?php
namespace Dfe\TwoCheckout;
use Df\Core\O;
# 2016-08-21
final class Exception extends \Df\Payment\Exception {
	/**
	 * 2016-08-21
	 * @override
	 * @see \Df\Core\Exception::__construct()
	 * @used-by \Dfe\TwoCheckout\Method::charge()
	 * @param array(string => mixed) $response
	 * @param array(string => mixed) $request [optional]
	 */
	function __construct(array $res, array $req = []) {
		$this->_req = $req;
		$this->_res = new O($res);
		parent::__construct();
	}

	/**
	 * 2016-08-19
	 * @override
	 * @see \Df\Core\Exception::message()
	 * @used-by df_xts()
	 * @used-by \Df\Core\Exception::throw_()
	 * @used-by \Dfe\TwoCheckout\Method::api()
	 */
	function message():string {return df_api_rr_failed('2Checkout', $this->_res->a(), $this->_req);}

	/**
	 * 2016-08-21
	 * @override
	 * @see \Df\Core\Exception::messageC()
	 * @used-by \Df\Payment\PlaceOrderInternal::message()
	 */
	function messageC():string {return dfp_error_message(df_first(df_clean(
		$this->_res->a('errors/0/message'), $this->_res->a('exception/errorMsg')
	)));}

	/**
	 * 2016-08-21
	 * @used-by self::__construct()
	 * @used-by self::message()
	 * @var array(string => mixed)
	 */
	private $_req;

	/**
	 * 2016-08-21
	 * @used-by self::__construct()
	 * @used-by self::message()
	 * @var O
	 */
	private $_res;
}