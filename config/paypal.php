<?php
return array(
// set your paypal credential
'client_id' =>'AYWJM-cN3PNEfmTVYnJ6N13bhYTeHyPTJnd4-kLdn8ycNpPWO9Tist17FJ82l_jqBc6XfVwzCkNtyOjr',
'secret' => 'EGhTGqZCny7OyXoR0IAENJr3A-E65uM4j8FU3ZTbhjGrC-jZFKdZIdyKm7mcrJ6RQvlAeSo-BbhItSSt',
/**
* SDK configuration
*/
'settings' => array(
/**
* Available option 'sandbox' or 'live'
*/
'mode' => 'sandbox',
/**
* Specify the max request time in seconds
*/
'http.ConnectionTimeOut' => 1000,
/**
* Whether want to log to a file
*/
'log.LogEnabled' => true,
/**
* Specify the file that want to write on
*/
'log.FileName' => storage_path() . '/logs/paypal.log',
/**
* Available option 'FINE', 'INFO', 'WARN' or 'ERROR'
*
* Logging is most verbose in the 'FINE' level and decreases as you
* proceed towards ERROR
*/
'log.LogLevel' => 'FINE'
),
);
