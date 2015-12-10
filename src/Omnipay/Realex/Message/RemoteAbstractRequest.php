<?php

namespace Omnipay\Realex\Message;

use Omnipay\Common\Message\AbstractRequest;

/**
 * Realex Purchase Request
 */
abstract class RemoteAbstractRequest extends AbstractRequest {
	protected $cardBrandMap = array(
		'mastercard' => 'mc',
		'diners_club' => 'diners',
		'mc' => 'mc',
	);

	/**
	 * Override some of the default Omnipay card brand names
	 *
	 * @return mixed
	 */
	protected function getCardBrand() {
		$number = preg_replace('/[^\d]/', '', $this->getCard()->getNumber());
		if (preg_match('/^3[47][0-9]{13}$/', $number)) {
			return 'AMEX';
		} elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/', $number)) {
			return 'DINERS';
		} elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/', $number)) {
			return 'DISCOVER';
		} elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/', $number)) {
			return 'JCB';
		} elseif (preg_match('/^5[1-5][0-9]{14}$/', $number)) {
			return 'MC';
		} elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $number)) {
			return 'VISA';
		} else {
			return 'UNKNOWN';
		}
	}

	public function getMerchantId() {
		return $this->getParameter('merchantId');
	}

	public function setMerchantId($value) {
		return $this->setParameter('merchantId', $value);
	}

	public function getAccount() {
		return $this->getParameter('account');
	}

	public function setAccount($value) {
		return $this->setParameter('account', $value);
	}

	public function getSecret() {
		return $this->getParameter('secret');
	}

	public function setSecret($value) {
		return $this->setParameter('secret', $value);
	}

	public function getReturnUrl() {
		return $this->getParameter('returnUrl');
	}

	public function setReturnUrl($value) {
		return $this->setParameter('returnUrl', $value);
	}

	public function sendData($data) {
		// register the payment
		$this->httpClient->setConfig(array(
			'curl.options' => array(
				'CURLOPT_SSLVERSION' => 1,
				'CURLOPT_SSL_VERIFYPEER' => false,
			),
		));
		$httpResponse = $this->httpClient->post($this->getEndpoint(), null, $data)->send();

		return $this->createResponse($httpResponse->getBody(true));
	}

	abstract public function getEndpoint();

	abstract protected function createResponse($data);
}
