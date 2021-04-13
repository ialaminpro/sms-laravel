[![Youtube][youtube-shield]][youtube-url]
[![Facebook][facebook-shield]][facebook-url]
[![Instagram][instagram-shield]][instagram-url]
[![LinkedIn][linkedin-shield]][linkedin-url]

## Contact

Al Amin - [ialamin.pro@gmail.com](mailto:ialamin.pro@gmail.com)


<!-- MARKDOWN LINKS & IMAGES -->

[youtube-shield]: https://img.shields.io/badge/-Youtube-black.svg?style=flat-square&logo=youtube&color=555&logoColor=white
[youtube-url]: https://youtube.com
[facebook-shield]: https://img.shields.io/badge/-Facebook-black.svg?style=flat-square&logo=facebook&color=555&logoColor=white
[facebook-url]: https://facebook.com/ialaminpro
[instagram-shield]: https://img.shields.io/badge/-Instagram-black.svg?style=flat-square&logo=instagram&color=555&logoColor=white
[instagram-url]: https://instagram.com/ialamin.pro
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-black.svg?style=flat-square&logo=linkedin&colorB=555
[linkedin-url]: https://www.linkedin.com/in/ialaminpro



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

