<?php
namespace Dfe\TwoCheckout\Handler;
use Dfe\TwoCheckout\Handler;
final class DefaultT extends Handler {
	/**
	 * 2016-05-11 Перекрываем метод, чтобы вернуть «Not implemented.» вместо «The event is not for our store.»
	 * @override
	 * @see \Dfe\TwoCheckout\eligible::p()
	 * @used-by \Dfe\TwoCheckout\Handler::p()
	 */
	protected function eligible():bool {return true;}

	/**
	 * 2016-03-25
	 * @override
	 * @see \Dfe\TwoCheckout\Handler::_process()
	 * @used-by \Dfe\TwoCheckout\Handler::process()
	 * @return mixed
	 */
	protected function process() {return "«{$this->type()}» event handling is not implemented.";}
}