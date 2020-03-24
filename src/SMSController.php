<?php
namespace Alamin\SMS;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Alamin\SMS\SMS;
use Alamin\SMS\Models\SMSModel;

class SMSController extends Controller
{
    public function index()
    {
        $clients = SMSModel::get();

//        return view('sms::index',compact('clients'));
        return view('alamin/sms/index',compact('clients'));
    }


    public function time($timezone = NULL)
    {

        $parms = array(
            'mobile' => 'XXXXXXXXX',
            'smsText' => 'Here your text',
            'mask' => '',
            'campaign' => '',
            'type' => '',
            'client_id' => '',
        );

        $result = SMS::send($parms);
        dd(json_decode($result,true));

        dump($result);
        $current_time = ($timezone)
            ? Carbon::now(str_replace('-', '/', $timezone))
            : Carbon::now();


        return view('sms::time', compact('current_time'));
    }
}
