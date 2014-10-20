<?php //

/*
 * ***********************************************************************
 Copyright [2014] [PagSeguro Internet Ltda.]

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 * ***********************************************************************
 */

require_once "../PagSeguroLibrary/PagSeguroLibrary.php";

/**
 * Class with a main method to illustrate the usage of the domain class PagSeguroPaymentRequest
 */
class CreatePaymentRequest
{

    public static function main()
    {
        // Instantiate a new payment request
        $paymentRequest = new PagSeguroPaymentRequest();

        // Set the Payment Mode for this payment request
        $paymentRequest->setPaymentMode('default');

        // Set the Payment Method for this payment request
        $paymentMethod = new PagSeguroPaymentMethodType();
        $paymentRequest->setPaymentMethod($paymentMethod->getTypeFromValue(2));

        /**
        * @todo Change the receiver Email
        */
        $paymentRequest->addParameter('receiverEmail', 'vendedor@lojamodelo.com.br');

        // Another way to set checkout parameters
        $paymentRequest->addParameter('notificationURL', 'http://www.lojamodelo.com.br/nas');
        $paymentRequest->addParameter('senderBornDate', '07/05/1981');

        // Set the currency
        $paymentRequest->setCurrency("BRL");

        // Add an item for this payment request
        $paymentRequest->addItem(
            '0001', 
            'Descricao do item a ser vendido', 
            2, 
            10.00
        );

        // Add an item for this payment request
        $paymentRequest->addItem(
            '0002', 
            'Descricao do item a ser vendido', 
            2, 
            5.00
        );

        // Add an item for this payment request
        $paymentRequest->addItem(
            '0003', 
            'Descricao do item a ser vendido', 
            2, 
            3.00
        );

        // Set a reference code for this payment request. It is useful to identify this payment
        // in future notifications.
        $paymentRequest->setReference("REF123");

        // Set your customer information.
        // If you using SANDBOX you must use an email @sandbox.pagseguro.com.br
        $paymentRequest->setSender(
            'João Comprador',
            'comprador@lojamodelo.com.br',
            '11',
            '56273440',
            'CPF',
            '156.009.442-76',
            true
        );

        // Set shipping information for this payment request
        $sedexCode = PagSeguroShippingType::getCodeByType('SEDEX');
        $paymentRequest->setShippingType($sedexCode);
        $paymentRequest->setShippingAddress(
            '01452002',
            'Av. Brig. Faria Lima',
            '1384',
            'apto. 114',
            'Jardim Paulistano',
            'São Paulo',
            'SP',
            'BRA'
        );

        //Set billing adress for this payment
        $billindAdress = new PagSeguroAddress(
            array(
                'postalCode' => '01452002',
                'street' => 'Av. Brig. Faria Lima',
                'number' => '1384',
                'complement' => 'apto. 114',
                'district' => 'Jardim Paulistano',
                'city' => 'São Paulo',
                'state' => 'SP',
                'country' => 'BRA'
            )
        );
        $billing = new PagSeguroBilling();
        $billing->setAddress($billindAdress);

        /**
         *@todo Replace the parameters below with your credit card information (number, cvv, expirationMounth and expiration year)
         */
        $paymentRequest->setCreditCard(
            array(
                'number' => '4111111111111111',
                'cvv' => '123',
                'expirationMonth' => '12',
                'expirationYear' => '2030'
            )
        );

        try {
            /**
             * @todo
             * #### Credentials #####
             * Replace the parameters below with your credentials (e-mail and token)
             * You can also get your credentials from a config file. See an example:
             * $credentials = PagSeguroConfig::getAccountCredentials();
             */
             $credentials = new PagSeguroAccountCredentials("vendedor@lojamodelo.com.br",
                "E231B2C9BCC8474DA2E260B6C8CF60D3");

            //Build Credit Card credentials for Payment
            try {
                PagSeguroDirectPaymentService::buildCreditCard(
                    $credentials, 
                    $paymentRequest->getCreditCard(),
                    $paymentRequest->getItemsTotalAmount($paymentRequest->getItems()) //Getting total amount 
                );
            } catch (Exception $e) {
                die($e->getMessage());
            }

            /**
            *@todo Define installment amount
            */
            $instalmentQuantity = 3;

            //Instance a new installment
            $installments = PagSeguroDirectPaymentService::getInstallment($instalmentQuantity);
            $installment = new PagSeguroInstallment();
            $installment->setInstallment(
                array(
                    'quantity' => $installments['quantity'],
                    'value'    => $installments['installmentAmount']
                )
            );

            //Set credit card for payment
            $paymentRequest->setCreditCard(
                array(
                    'token' => PagSeguroDirectPaymentService::getCardToken(),
                    'installment' => $installment,
                    'holder' => new PagSeguroCreditCardHolder(
                    array(
                        'name' => 'Comprador', //Equals in Credit Card
                        'documents' => array(
                            'type' => 'CPF',
                            'value' => '1234567809'
                        ),
                        'birthDate' => date('01/10/1979'), 
                        'areaCode' => 11, 
                        'number' => 56273440
                    )
                ),
                'billing' => $billing
                )
            );
   
            // Register this payment request in PagSeguro to obtain the payment URL to redirect your customer.
            $return = $paymentRequest->register($credentials, "DIRECT_PAYMENT");

            self::printTransactionReturn($return);

        } catch (PagSeguroServiceException $e) {
            die($e->getMessage());
        }
    } 

    public static function printTransactionReturn($transaction)
    {

        if ($transaction) {
            echo utf8_decode("<h2>Retorno da transação do Checkout Transparente</h2>");
            echo "<p><strong>Date: </strong> ".$transaction->getDate() ."</p> ";
            echo "<p><strong>lastEventDate: </strong> ".$transaction->getLastEventDate()."</p> ";
            echo "<p><strong>code: </strong> ".$transaction->getCode() ."</p> ";
            echo "<p><strong>reference: </strong> ".$transaction->getReference() ."</p> ";
            echo "<p><strong>type: </strong> ".$transaction->getType()->getValue() ."</p> ";
            echo "<p><strong>status: </strong> ".$transaction->getStatus()->getValue() ."</p> ";
            
            echo "<p><strong>paymentMethodType: </strong> ".$transaction->getPaymentMethod()->getType()->getValue() ."</p> ";
            echo "<p><strong>paymentModeCode: </strong> ".$transaction->getPaymentMethod()->getCode()->getValue() ."</p> ";
            echo "<p><strong>paymentLink: </strong> ".$transaction->getPaymentLink() ."</p> ";

            echo "<p><strong>grossAmount: </strong> ".$transaction->getGrossAmount() ."</p> ";
            echo "<p><strong>discountAmount: </strong> ".$transaction->getDiscountAmount() ."</p> ";
            echo "<p><strong>feeAmount: </strong> ".$transaction->getFeeAmount() ."</p> ";
            echo "<p><strong>netAmount: </strong> ".$transaction->getNetAmount() ."</p> ";
            echo "<p><strong>extraAmount: </strong> ".$transaction->getExtraAmount() ."</p> ";

            echo "<p><strong>installmentCount: </strong> ".$transaction->getInstallmentCount() ."</p> ";
            echo "<p><strong>itemCount: </strong> ".$transaction->getItemCount() ."</p> ";

            echo "<p><strong>Items: </strong></p>";
            foreach ($transaction->getItems() as $item) 
            {
                echo "<p><strong>id: </strong> ". $item->getId() ."</br> ";
                echo "<strong>description: </strong> ". $item->getDescription() ."</br> ";
                echo "<strong>quantity: </strong> ". $item->getQuantity() ."</br> ";
                echo "<strong>amount: </strong> ". $item->getAmount() ."</p> ";  
            }

            echo "<p><strong>senderName: </strong> ".$transaction->getSender()->getName() ."</p> ";
            echo "<p><strong>senderEmail: </strong> ".$transaction->getSender()->getEmail() ."</p> ";
            echo "<p><strong>senderPhone: </strong> ".$transaction->getSender()->getPhone()->getAreaCode() . " - " . 
                 $transaction->getSender()->getPhone()->getNumber() . "</p> ";
            echo "<p><strong>Shipping: </strong></p>";
            echo "<p><strong>street: </strong> ".$transaction->getShipping()->getAddress()->getStreet() ."</p> ";
            echo "<p><strong>number: </strong> ".$transaction->getShipping()->getAddress()->getNumber()  ."</p> ";
            echo "<p><strong>complement: </strong> ".$transaction->getShipping()->getAddress()->getComplement()  ."</p> ";
            echo "<p><strong>district: </strong> ".$transaction->getShipping()->getAddress()->getDistrict()  ."</p> ";
            echo "<p><strong>postalCode: </strong> ".$transaction->getShipping()->getAddress()->getPostalCode()  ."</p> ";
            echo "<p><strong>city: </strong> ".$transaction->getShipping()->getAddress()->getCity()  ."</p> ";
            echo "<p><strong>state: </strong> ".$transaction->getShipping()->getAddress()->getState()  ."</p> ";
            echo "<p><strong>country: </strong> ".$transaction->getShipping()->getAddress()->getCountry()  ."</p> ";
        }

      echo "<pre>";
    }
}

CreatePaymentRequest::main();