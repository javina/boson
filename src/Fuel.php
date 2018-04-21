<?php
namespace Intralix\Fuel;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Carbon\Carbon;

/**
 * Class to call Bosson Fuel Ws
 *
 * @package Intralix\Fuel
 * @author  Intralix
 **/
class Fuel
{
    /* Http Client **/
    protected $client;
    /* Configuration **/
    protected $config;

    /**
     * Class Constructor
     *
     * @return void
     * @author Intralix
     **/    
    public function __construct( $config )
    {
        $this->config = $config;
        $this->client = new Client([            
            //'base_uri' => $this->config['base_uri'],
        ]);
    }   

    /**
     * Attemps to call Fuel Ws
     *
     * @return array $results 
     * @param string $startTime Start Date
     * @param string $endTime End Date
     * @param string $deviceID Device Identifier
     * @param string $clientID Client Identifier     
     * @author Intralix
     **/
    public function getFuelPerformance( $startTime, $endTime, $deviceID, $clientID )
    {
        // Vars 
        $results = [];
        $cargas = 0;
        $descargas = 0;
        $ralenti = 0;
        $nivel_inicial = 0;
        $nivel_final = 0;
        $odometro_inicial = 0;
        $odometro_final = 0;
        $kilometros_recorridos = 0;
        $velocidadades = [];
        $top_speed = 0;
        $top_speed_date = '';
        $average_speed = 0;
        $max_fuel = 0;
        $max_fuel_date = '';
        $min_fuel = 0;
        $min_fuel_date = '';
        $counter = 0;
        
       
        // Get Response
        $data =  $this->getWsFuelData($startTime, $endTime, $deviceID, $clientID);     
        $total_positions = count($data);           
        
        if($total_positions > 0 ) 
        {
            $odometro_inicial = $data[1]->Odometer;
            $nivel_inicial = $data[1]->globalVolume;
            $fecha_inicial = $data[1]->dateGPS;

            $odometro_final = $data[$total_positions-2]->Odometer;
            $nivel_final = $data[$total_positions-2]->globalVolume;          
            $fecha_final = $data[$total_positions-1]->dateGPS;

            $kilometros_recorridos  = $odometro_final - $odometro_inicial;
            
            for ($i=1; $i < $total_positions; $i++) 
            { 
                //echo $data[$i]->eventName . ' <br>';            
                if($data[$i]->eventName == 'Evento/Cambio brusco en nivel')
                {
                    if($data[$i]->volumeChange > 0 )
                    {
                        $cargas += $data[$i]->volumeChange;
                    }
                    else 
                    {
                        $descargas += $data[$i]->volumeChange;   
                    }
                } 
                else if ( strpos($data[$i]->eventName,'Combustible consumido en vac')!==false)
                {
                    $ralenti += $data[$i]->volumeChange;
                }    

                // Acumulado de Velocidades
                if($data[$i]->Speed != 'ND')
                {
                    if($data[$i]->Speed > 0)
                        $counter ++;

                    if($i == 1)
                    {
                        $top_speed = $data[$i]->Speed;                                                
                        $top_speed_date = $data[$i]->dateGPS;
                    }
                    else if($data[$i]->Speed > $top_speed)
                    {
                        $top_speed = $data[$i]->Speed;
                        $top_speed_date = $data[$i]->dateGPS;  
                    }
                }

                // Volumen Máximo
                if($data[$i]->globalVolume != 'ND')
                {
                    if($i == 1)
                    {
                        $max_fuel = $data[$i]->globalVolume;                                                
                        $max_fuel_date = $data[$i]->dateGPS;
                    }
                    else if($data[$i]->globalVolume > $max_fuel)
                    {
                        $max_fuel = $data[$i]->globalVolume;
                        $max_fuel_date = $data[$i]->dateGPS;  
                    }
                }


                // Volumen Mínimo
                if($data[$i]->globalVolume != 'ND')
                {
                    if($i == 1)
                    {
                        $min_fuel = $data[$i]->globalVolume;                                                
                        $min_fuel_date = $data[$i]->dateGPS;
                    }
                    else if($data[$i]->globalVolume < $min_fuel)
                    {
                        $min_fuel = $data[$i]->globalVolume;
                        $min_fuel_date = $data[$i]->dateGPS;  
                    }
                }

                /* Promedio de la velocidad**/
                
                if($data[$i]->Speed == 'ND')
                    $average_speed += 0;
                else
                    $average_speed += $data[$i]->Speed;

            }
            
            /* Volumen **/
            $volumen_total = ($nivel_inicial - $nivel_final ) + ($cargas - abs($descargas));
            if($volumen_total > 0)
                $desempeno = round($kilometros_recorridos /  $volumen_total, 2);
            else
                $desempeno = 0;

            if($desempeno > 0)
                $cada_cien = 100 / $desempeno;
            else
                $cada_cien = 0;

            if($counter > 0)
                $velocidad_promedio = round($average_speed / $counter, 2 );
            else
                $velocidad_promedio = 0;                

            $results = [                 
                'fecha_inicial' => Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T','',$fecha_inicial))->toDateTimeString(),
                'fecha_final' => Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T','',$fecha_final))->toDateTimeString(),
                'odometro_inicial' => ($odometro_inicial),
                'odometro_final' => ($odometro_final),
                'distancia_recorrida' => ($kilometros_recorridos),
                'velocidad_promedio' => $velocidad_promedio,
                'velocidad_max' => $top_speed,
                'velocidad_max_fecha' => Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T','',$top_speed_date))->toDateTimeString(),
                'combustible_inicial' => ($nivel_inicial),
                'combustible_final' => ($nivel_final),
                'volumen_minimo' => $min_fuel,
                'volumen_minimo_fecha' => Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T','',$min_fuel_date))->toDateTimeString(),
                'volumen_max' => $max_fuel,
                'volumen_max_fecha' => Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T','',$max_fuel_date))->toDateTimeString(),
                'volumen_recargado' => ($cargas),
                'volumen_descargado' => ($descargas),
                'combustible_consumido' => ($volumen_total),
                'consumido_en_vacio' => $ralenti,
                'consumo_cien_km' => ($cada_cien),
                'rendimiento_litro' => ($desempeno),
                //'periodo' => 'Mensual',
            ];            
        }        

        return $results;
    }


    /**
     * undocumented function
     *
     * @param string $startTime Start Date
     * @param string $endTime End Date
     * @param string $deviceID Device Identifier
     * @param string $clientID Client Identifier
     * @param string $dealerID Dealer Identifier
     * @param string $selectionType Selection Type default odometer
     * @param string $selectionMode Selection mode default dates     
     * @return mixed array $response
     * @author Intralix
     **/
    public function getWsFuelData( $startTime, $endTime, $deviceID, $clientID, $selectionType = 'odometer', $selectionMode ='dates')
    {        
        // Get Data   
        $response = [];
        try {

            $response = $this->client->request('POST', $this->config['base_uri'],  [
                'json' => [
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                    'deviceID' => $deviceID,
                    'clientID' => $clientID,
                    'dealerID' => $this->config['dealer_id'],
                    'selectionType' => $selectionType,
                    'selectionMode' => $selectionMode
                ]        
            ]);
                        
            Log::info($response->getStatusCode() . ' :: From Receiver Fuel WS Server.');                        
            $response = json_decode($response->getBody());
            $response = ($response !== null) ? $response : [];
                
        } catch (Exception $e) 
        {                                
            $response = [];
            Log::error($e->getMessage());
        }        
        
        return $response;                        
    }   
        
} // END class Fuel
