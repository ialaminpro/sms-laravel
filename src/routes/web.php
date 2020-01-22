<?php


Route::get('timezones/{timezone?}',
    'alamin\sms\SMSController@time');

Route::get('alamin/clients','alamin\sms\SMSController@index');
