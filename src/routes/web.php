<?php


Route::get('timezones/{timezone?}',
    'acolyte\sms\SMSController@time');

Route::get('acolyte/clients','acolyte\sms\SMSController@index');
