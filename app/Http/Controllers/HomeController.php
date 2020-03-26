<?php

namespace App\Http\Controllers;

use App\BaseAPI\T_PAY;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithDrawRequest;
use App\TPayDeposit;
use App\TPayWithDraw;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use TPay\API\API\AppB2C;
use TPay\API\API\AppC2BSTKPush;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index()
    {
        $balance = TPayDeposit::query()
                ->where('is_paid', true)
                ->sum('amount') - TPayWithDraw::query()
                ->where('is_withdrawn', true)
                ->sum('amount');

        return view('home', [
            'balance' => $balance
        ]);
    }

    /**
     * make c2b/stk push requests
     * @param DepositRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function c2b(DepositRequest $request)
    {
        try {
            //Set request options as shown here
            $options = [
                'secretCode' => config('tpay.app_secret_code'),
                'phoneNumber' => $this->trimPhoneNumber($request->phone_number),
                'referenceCode' => $request->referenceCode,
                'amount' => $request->amount,
                'resultURL' => URL::signedRoute('stk.callback'),
            ];

            //Sen the request to tpay
            $response = AppC2BSTKPush::appC2BSTKPush($options);

            // check if response is ok
            if (!$response->data->success) {
                alert('STK', 'Failed to initiate stk push.', 'error');
                return redirect()->back();
            }

            //create lipa na m-pesa here
            T_PAY::createSTKTPayTransaction($request);

            //proceed here with your response to use.
            alert('STk', 'Check phone stk has been initiated', 'success');
            return redirect()->back();
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }


    /**
     * withdraw processing here
     * @param WithDrawRequest $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function b2c(WithDrawRequest $request)
    {
        try {
            //Set request options as shown here
            $options = [
                'secretCode' => config('tpay.app_secret_code'),
                'phoneNumber' => $this->trimPhoneNumber($request->phone_number),
                'referenceCode' => $request->referenceCode,
                'amount' => $request->amount,
                'resultURL' => URL::signedRoute('withdraw.callback'),
            ];

            //make the b2c withdraw here
            $response = AppB2C::appB2C($options);

            // check if response is ok
            if (!$response->data->success) {
                alert('WithDraw', 'Failed to initiate withdraw.', 'error');
                return redirect()->back();
            }

            //store the sent request
            T_PAY::createWithDrawTransaction(auth()->id(), $this->trimPhoneNumber($request->phone_number), $request->referenceCode, (integer)$request->amount);

            //proceed here with your response to use.
            alert('WithDraw', 'Check phone withdraw has been initiated', 'success');
            return redirect()->back();
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }


    /**
     * trim phone
     * number here
     * @param $phoneNumber
     * @return null|string|string[]
     */
    public function trimPhoneNumber($phoneNumber)
    {
        return preg_replace("/^0/", "254", $phoneNumber);
    }
}
