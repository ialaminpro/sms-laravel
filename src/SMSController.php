<?php
namespace Acolyte\SmsLaravel;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Acolyte\SmsLaravel\SMS;
use Acolyte\SmsLaravel\Models\SmsModel;

class SmsController extends Controller
{
    public function index()
    {
        $clients = SmsModel::get();

//        return view('sms::index',compact('clients'));
        return view('acolyte/sms/index',compact('clients'));
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
