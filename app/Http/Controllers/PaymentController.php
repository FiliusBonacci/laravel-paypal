<?php


namespace App\Http\Controllers;
use Omnipay\Omnipay;;
use Session;
use Gloudemans\Shoppingcart\Facades\Cart;

class PaymentController extends Controller
{
  public function postPayment()
 {

     $items = array();

    $items[] = array('name' => 'przedluzenie', 'quantity' => 1, 'price' => 5);


     $params = array(
         'cancelUrl'=>'http://localhost:8000/payment/cancel',
         'returnUrl'=>'http://localhost:8000//payment/sucess',
         'amount' =>  '1.00',
         'currency' => 'EUR'
     );

     Session::put('params', $params);
     Session::save();

     $gateway = Omnipay::create('PayPal_Express');
     $gateway->setUsername('SanboxEmail');
     $gateway->setPassword('SanboxPassword');
     $gateway->setSignature('SanboxSignature');
     $gateway->setTestMode(true);

     $response = $gateway->purchase($params)->setItems($items)->send();

     if ($response->isSuccessful()) {

         // payment was successful: update database
         print_r($response);
     } elseif ($response->isRedirect()) {

         // redirect to offsite payment gateway
         $response->redirect();
     } else {
         // payment failed: display message to customer
         echo $response->getMessage();
     }
 }

 /**
  * Fonction permettant de completer la requête de paiement, ainsi que de traiter la réponse de PayPal.
  * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
  *
  */
 public function getSuccessPayment()
 {
     $gateway = Omnipay::create('PayPal_Express');
     $gateway->setUsername('p.jadanowski-facilitator_api1.gmail.com');
     $gateway->setPassword('Y4VZ7EBL7NLJYTM3');
     $gateway->setSignature('AFcWxV21C7fd0v3bYYYRCpSSRl31A0xSC1Ge5CyVxIKno9WhJcsQnD31');
     $gateway->setTestMode(true);

     $params = Session::get('params');
     $response = $gateway->completePurchase($params)->send();
     $paypalResponse = $response->getData(); // this is the raw response object

     if(isset($paypalResponse['PAYMENTINFO_0_ACK']) && $paypalResponse['PAYMENTINFO_0_ACK'] === 'Success') {
         return view('payment.sucess');
     } else {

         //Failed transaction

     }
 }
}
