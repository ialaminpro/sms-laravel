composer require acolyte/sms-laravel
        
php artisan vendor:publish

php artisan migrate


use Acolyte\SmsLaravel\SMS;

$parms = array(
            'mobile' => 'XXXXXXXXX',
            'smsText' => 'Here your text',
            'mask' => '',
            'campaign' => '',
            'type' => '',
            'client_id' => '',
        );
$result = SMS::send($parms);

example.com/alamin/clients

