<?php

namespace Omnipay\Realex\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;

/**
 * Realex Auth Request
 */
class AuthRequest extends RemoteAbstractRequest
{
    protected $endpoint = 'https://epage.payandshop.com/epage-remote.cgi';

    /**
     * Get the XML registration string to be sent to the gateway
     *
     * @return string
     */
    public function getData()
    {
        $this->validate('amount', 'currency', 'transactionReference');

        // Create the hash
        $timestamp = strftime("%Y%m%d%H%M%S");
        $merchantId = $this->getMerchantId();
        $orderId = $this->getTransactionReference();
        $amount = $this->getAmountInteger();
        $currency = $this->getCurrency();
        $cardNumber = $this->getCard()->getNumber();
        $secret = $this->getSecret();
        $tmp = "$timestamp.$merchantId.$orderId.$amount.$currency.$cardNumber";
        $md5hash = md5($tmp);
        $tmp2 = "$md5hash.$secret";
        $md5hash = md5($tmp2);

        $domTree = new \DOMDocument('1.0', 'UTF-8');

        // root element
        $root = $domTree->createElement('request');
        $root->setAttribute('type', 'auth');
        $root->setAttribute('timestamp', $timestamp);
        $root = $domTree->appendChild($root);

        // merchant ID
        $merchantEl = $domTree->createElement('merchantid', $merchantId);
        $root->appendChild($merchantEl);

        // account
        $merchantEl = $domTree->createElement('account', $this->getAccount());
        $root->appendChild($merchantEl);

        // order ID
        $merchantEl = $domTree->createElement('orderid', $orderId);
        $root->appendChild($merchantEl);

        // amount
        $amountEl = $domTree->createElement('amount', $amount);
        $amountEl->setAttribute('currency', $this->getCurrency());
        $root->appendChild($amountEl);

        /**
         * @var \Omnipay\Common\CreditCard $card
         */
        $card = $this->getCard();

        // Card details
        $cardEl = $domTree->createElement('card');

        $cardNumberEl = $domTree->createElement('number', $card->getNumber());
        $cardEl->appendChild($cardNumberEl);

        $expiryEl = $domTree->createElement('expdate', $card->getExpiryDate("my")); // mmyy
        $cardEl->appendChild($expiryEl);

        $cardTypeEl = $domTree->createElement('type', $this->getCardBrand());
        $cardEl->appendChild($cardTypeEl);

        $cardNameEl = $domTree->createElement('chname', $card->getBillingName());
        $cardEl->appendChild($cardNameEl);

        $cvnEl = $domTree->createElement('cvn');

        $cvnNumberEl = $domTree->createElement('number', $card->getCvv());
        $cvnEl->appendChild($cvnNumberEl);

        $presIndEl = $domTree->createElement('presind', 1);
        $cvnEl->appendChild($presIndEl);

        $cardEl->appendChild($cvnEl);

        $issueEl = $domTree->createElement('issueno', $card->getIssueNumber());
        $cardEl->appendChild($issueEl);

        $root->appendChild($cardEl);

        $settleEl = $domTree->createElement('autosettle');
        $settleEl->setAttribute('flag', 1);
        $root->appendChild($settleEl);

        $md5El = $domTree->createElement('md5hash', $md5hash);
        $root->appendChild($md5El);

        $tssEl = $domTree->createElement('tssinfo');
        $addressEl = $domTree->createElement('address');
        $addressEl->setAttribute('type', 'billing');
        $countryEl = $domTree->createElement('country', $card->getBillingCountry());
        $addressEl->appendChild($countryEl);
        $tssEl->appendChild($addressEl);
        $root->appendChild($tssEl);

        $xmlString = $domTree->saveXML($root);

        return $xmlString;
    }

    public function sendData($data)
    {
        // register the payment
        $this->httpClient->setConfig(array(
            'curl.options' => array(
                'CURLOPT_SSLVERSION'     => 1,
                'CURLOPT_SSL_VERIFYPEER' => false
            )
        ));
        $httpResponse = $this->httpClient->post($this->getEndpoint(), null, $data)->send();

        return $this->createResponse($httpResponse->getBody(true));
    }

    protected function createResponse($data)
    {
        return $this->response = new AuthResponse($this, $data);
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }
}