<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Validator;
use URL;
use Session;
use Redirect;
use Input;

// All Paypal Details class
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\ExecutePayment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Transaction;

class AddMoneyController extends HomeController
{

    private $_api_context;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->moduleTitleS = 'addmoney';
        $this->moduleTitleP = 'themes.userTheme.addMoney';

        view()->share('moduleTitleP',$this->moduleTitleP);
        view()->share('moduleTitleS',$this->moduleTitleS);

        // setup PayPal api context
        $paypal_conf = \Config::get('paypal');
        $this->_api_context = new ApiContext(new OAuthTokenCredential($paypal_conf['client_id'], $paypal_conf['secret']));
        $this->_api_context->setConfig($paypal_conf['settings']);
    }



    /**
     * Show the application paywith paypalpage.
     *
     * @return \Illuminate\Http\Response
     */
    public function payWithPaypal()
    {
        return view($this->moduleTitleP.'.paywithpaypal');
    }

    /**
     * Store a details of payment with paypal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postPaymentWithpaypal(Request $request)
    {
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $item_1 = new Item();

        $item_1->setName('Item 1') // item name
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice($request->get('amount')); // unit price

        $item_list = new ItemList();
        $item_list->setItems(array($item_1));

        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal($request->get('amount'));

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($item_list)
            ->setDescription('Your transaction description');

        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl(URL::route('payment.status')) // Specify return URL
            ->setCancelUrl(URL::route('payment.status'));

        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions(array($transaction));
            // dd($payment->create($this->_api_context));exit;
        try {
            $payment->create($this->_api_context);
        } catch (\PayPal\Exception\PPConnectionException $ex) {
            if (\Config::get('app.debug')) {
                notificationMsg('error','Connection timeout');
                return Redirect::route('addmoney.paywithpaypal');
                // echo "Exception: " . $ex->getMessage() . PHP_EOL;
                // $err_data = json_decode($ex->getData(), true);
                // exit;
            } else {
                notificationMsg('error','Some error occur, sorry for inconvenient');
                return Redirect::route('addmoney.paywithpaypal');
                // die('Some error occur, sorry for inconvenient');
            }
        }

        foreach($payment->getLinks() as $link) {
            if($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }

        // add payment ID to session
        Session::put('paypal_payment_id', $payment->getId());

        if(isset($redirect_url)) {
            // redirect to paypal
            return Redirect::away($redirect_url);
        }

        notificationMsg('error','Unknown error occurred');
        return Redirect::route('addmoney.paywithpaypal');
    }

    public function getPaymentStatus()
    {
        // Get the payment ID before session clear
        $payment_id = Session::get('paypal_payment_id');
        // clear the session payment ID
        Session::forget('paypal_payment_id');
        if (empty(Input::get('PayerID')) || empty(Input::get('token'))) {
            notificationMsg('error','Payment failed');
            return Redirect::route('addmoney.paywithpaypal');
        }
        $payment = Payment::get($payment_id, $this->_api_context);
        // PaymentExecution object includes information necessary
        // to execute a PayPal account payment.
        // The payer_id is added to the request query parameters
        // when the user is redirected from paypal back to your site
        $execution = new PaymentExecution();
        $execution->setPayerId(Input::get('PayerID'));
        //Execute the payment
        $result = $payment->execute($execution, $this->_api_context);
        // echo '<pre>';print_r($result);echo '</pre>';exit; // DEBUG RESULT, remove it later
        if ($result->getState() == 'approved') { // payment made
            $user_id = auth()->guard('web')->user()->id;
            $inputs['currency'] = 'USD';
            $inputs['transaction_type'] = 'add money';
            $inputs['by_transaction'] = 'paypal';
            $inputs['amount'] = $result->transactions[0]->amount->total;
            $inputs['user_id'] = $user_id;

            $this->summarydetail->addSummaryetails($inputs['amount'],'Add amount in wallet by paypal');
            $last_id = $this->addmoneydetails->addAddMoneyDetails($inputs);

            // Set Value for Summary Table.
            $summary['user_id'] = $user_id;
            $summary['amount'] = $result->transactions[0]->amount->total;
            $summary['original_amount'] = $result->transactions[0]->amount->total;
            $summary['short_note'] = 'Add amount in wallet by paypal';
            $summary['related_table_name'] = 'add_money_details';
            $summary['related_table_data_id'] = $last_id;
            $summary['action'] = 'AM';
            $summary['pay_sign'] = '+';
            $summary['ip'] = $request->ip();

            $this->summary->addSummary($summary);

            notificationMsg('success','Payment success');
            return Redirect::route('addmoney.paywithpaypal');
        }
        notificationMsg('error','Payment failed');
        return Redirect::route('addmoney.paywithpaypal');
    }
  }
