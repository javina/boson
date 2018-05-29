<?php

namespace Intralix\Boson\Models;

use Illuminate\Database\Eloquent\Model;
use Config;
use Log;
use DB;
use GuzzleHttp\Client;
use Intralix\Boson\Models\Receiver\Positions;


class GeoReverse extends Model
{
    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public static function processPositions()
    {
        // HttpClient
        $client = new Client();
        $url = Config::get('boson.geocode_url');     
        $estatus = '';
        
        //Get Positions Related to the import        
        $positions = Positions::where('Reference', '~')            
            ->where('Latitude', '<>','~' )
            ->where('Longitude','<>','~' )
            //->where('Device_Id','PHX1-21242')
            //->where('DateTime_GPS', '>=','2018-03-16')
            ->take(1000)
            ->orderBy('DateTime_GPS','desc')
            ->get();                 
        
        $total = count($positions);            
        if($total > 0) 
        {

            foreach ($positions as $position) 
            {
               
               $lat = trim($position->Latitude);
               $lon = trim($position->Longitude);
               
                if(isset($lat) && isset($lon) && $lat != '~' && $lon != '~') 
                {
                    // Http Call To Nomintim GeoReverse Service     
                    $res = $client->request('GET', $url, [          
                        'query' => [
                            'format' => 'json',
                            'lat' => $lat,
                            'lon' => $lon,
                            'zoom' => 18,
                            'addressdetails' => 1
                        ]
                    ]);
                                        
                    // If Call was correct
                    if($res->getStatusCode() == '200') 
                    {            
                        $data = json_decode($res->getBody());  

                        if(isset($data->display_name))
                        {
                            DB::connection('receiver')->table('bh_table_Transactions')
                                ->where('nRecord', $position->nRecord)                                                          
                                ->lockForUpdate()
                                ->update(['Reference' => $data->display_name]);
                        }
                        else{
                            DB::connection('receiver')->table('bh_table_Transactions')
                                ->where('nRecord', $position->nRecord)
                                ->lockForUpdate()
                                ->update(['Reference' => 'ND']);
                        }
                    }
                } else {

                    Log::warning('No Data nRecord :: ' . $position->nRecord);
                }
            }
            
            $estatus = 'Terminado. Proceso correcto para al posicion  ['. $position->nrecord . ']';
        } else {

            $estatus = 'Nada que procesar LgpsPosition';
        }

        Log::info($estatus);
        return $estatus;
    }      

}
