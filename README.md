"require": {
    "alamin/sms": "dev-master"
    }    
 "repositories": [
        {
            "type": "path",
            "url": "packages/alamin/sms",
            "options": {
                "symlink": true
            }
        }
    ]
    "autoload": {
            "psr-4": {  
                "Alamin\\SMS\\": "packages/alamin/sms/src/"
            },
        },
        
"require": {
    "alamin/sms": "^1.0.0"
    }
"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/ialaminpro/sms-laravel"
        }
    ]
"autoload": {
        "psr-4": {  
            "Alamin\\SMS\\": "vendor/alamin/sms/src/"
        },
    },
        
php artisan vendor:publish

php artisan migrate

Route alamin/clients

$parms = array(
            'mobile' => 'XXXXXXXXX',
            'smsText' => 'Here your text',
            'mask' => '',
            'campaign' => '',
            'type' => '',
            'client_id' => '',
        );
$result = SMS::send($parms);

