<?php


namespace Acolyte\SmsLaravel;
use DB;


class SMS
{
    public static function checkBalance($client_id, $count, $mask=null){
        $result = DB::table('sms')->where('client_id',$client_id)->first();
        if($result) {
            if($result->status == 'Active'){
                $MaskingSmsPrice = $result->masking_rate; //0.85;
                $NonMaskingSmsPrice = $result->no_masking_rate; //0.75;
                if ($mask) {
                    if ($result->mask == 'enable') {
                        if($result->type =='sms' && $result->balance >= $count){
                            $data = array('code' => 200, 'msg' => 'Enough balance');
                        }elseif($result->type =='amount' && $result->balance >= ($MaskingSmsPrice*$count)){
                            $data = array('code' => 200, 'msg' => 'Enough balance');
                        }else{
                            $data = array('code' => 103, 'msg' => 'Not enough balance');
                        }
                    }else{
                        $data = array('code' => 102, 'msg' => 'Masking is not enabled');
                    }
                }else{
                    if($result->type =='sms' && $result->balance >= $count){
                        $data = array('code' => 200, 'msg' => 'Enough balance');
                    }elseif($result->type =='amount' && $result->balance >= ($NonMaskingSmsPrice*$count)){
                        $data = array('code' => 200, 'msg' => 'Enough balance');
                    }else{
                        $data = array('code' => 103, 'msg' => 'Not enough balance');
                    }
                }
            }else{
                $data = array('code' => 104, 'msg' => 'Your Account is '.$result->status);
            }
        }else{
            $data = array('code' => 101, 'msg' => 'You are not registered!');
        }
        return json_encode($data);
    }
    public static function getUrl($parms=array()){
        $url = config('sms.auth0.SMS_API_URL');
        $apiKey = config('sms.auth0.SMS_API_KEY') ;
        $maskName = config('sms.auth0.SMS_MASK_NAME') ;
        $campaignName = config('sms.auth0.SMS_CAMPAIGN_NAME');

        $defaultParms = array(
            'op' => 'NumberSms',
            'type' => 'TEXT',
            'mobile' => '',
            'smsText' => '',
            'maskName' => $maskName,
            'campaignName' => $campaignName,
        );
        $result = array_diff_key($defaultParms,$parms);
        $parms = array_merge($parms,$result);
        $str = '';

        foreach ($parms as $key => $value) {
            if($value){
                if($key=='smsText') $value = urlencode($value);
                $str .= "$key=$value&";
            }
        }
        $str .= "apiKey=$apiKey";

        $url .= $str;
        return $url;
    }

    public static function send($parms=array()){

        $client_id =  $parms['client_id'];
        $number =  $parms['mobile'];
        $mask =  $parms['mask'];
        $campaign =  $parms['campaign'];
        $type =  $parms['type'];
        $text =  $parms['smsText'];
        $stringLength = strlen($text);
        $quantitySms = $stringLength/160;
        $smsCount = ceil($quantitySms);

        try{
            $result = self::checkBalance($client_id,$smsCount,$mask);
            $result = json_decode($result,true);
            if($result['code']==200) {
                $url = self::getUrl($parms);
                // Create a stream
                $opts = array(
                    'http' => array(
                        'method' => "GET",
                        'header' => "Accept-language: en\r\n" .
                            "Cookie: foo=bar\r\n"
                    )
                );

                $context = stream_context_create($opts);

                // Open the file using the HTTP headers set above
                $response = file_get_contents($url, false, $context);
                $response = explode("||", $response);

                if ($response[0] == 1900) {
                    $data = array('status' => 'success', 'msg' => 'Successfully Delivered');
                    $dataInsert = ['client_id' => $client_id, 'mobile' => $number, 'msg' => $text, 'length' => $stringLength, 'count' => $smsCount, 'mask' => $mask, 'campaign' => $campaign, 'type' => $type, 'status' => 'success','created_at' => date("Y-m-d H:i:s"),'updated_at' => date("Y-m-d H:i:s")];
                    DB::table('sms_logs')->insert($dataInsert);
                    DB::table('sms')->where('client_id',$client_id)->decrement('balance',$smsCount);
                } else {
                    $data = array('status' => 'failed', 'msg' => $response[2]);
                    $dataInsert = ['client_id' => $client_id, 'mobile' => $number, 'msg' => $text, 'length' => $stringLength, 'count' => $smsCount, 'mask' => $mask, 'campaign' => $campaign, 'type' => $type, 'status' => 'failed', 'error_log'=> $response[2],'created_at' => date("Y-m-d H:i:s"),'updated_at' => date("Y-m-d H:i:s")];
                    DB::table('sms_logs')->insert($dataInsert);
                }
            }else{
                $data = array('status' => 'failed', 'msg' => $result['msg']);
                $dataInsert = ['client_id' => $client_id, 'mobile' => $number, 'msg' => $text, 'length' => $stringLength, 'count' => $smsCount, 'mask' => $mask, 'campaign' => $campaign, 'type' => $type, 'status' => 'failed','error_log'=> $result['msg'],'created_at' => date("Y-m-d H:i:s"),'updated_at' => date("Y-m-d H:i:s")];
                DB::table('sms_logs')->insert($dataInsert);
            }
        }
        catch(\Exception $e){
            $data = array('status' => 'failed', 'msg' => $e->getMessage());
            $dataInsert = ['client_id' => $client_id, 'mobile' => $number,'msg' => $text, 'length' => $stringLength, 'count' => $smsCount, 'mask' => $mask, 'campaign' => $campaign, 'type' => $type,'status' => 'failed','error_log'=> $e->getMessage(),'created_at' => date("Y-m-d H:i:s"),'updated_at' => date("Y-m-d H:i:s")];
            DB::table('sms_logs')->insert($dataInsert);
        }
        return json_encode($data);
    }

}
