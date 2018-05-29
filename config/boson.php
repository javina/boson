<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Webservice Base Uri
    |--------------------------------------------------------------------------
    |
    | Url donde se encuentra la aplicación con el webservice de combustible    
    |
    */
        'base_uri' => 'http://201.151.150.135/ws/utrax/wsapi/api/v2.2/AlertFuel/individual/history',                       
    /*
    |--------------------------------------------------------------------------
    | Dealer's Identifier 
    |--------------------------------------------------------------------------
    |
    | Identificador de la cuenta distribuidor.
    |
    */        
        'dealer_id' => '01037',


    /*
    |--------------------------------------------------------------------------
    | Nominatim Options
    |--------------------------------------------------------------------------
    | 
    */
        'geocode_url' => 'http://192.168.0.116/nominatim/reverse',        
];
