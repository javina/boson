<?php
namespace Intralix\Fuel;

use GuzzleHttp\Client;

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
    public function __construct( $config)
    {
        $this->config = $config;
        $this->client = new Client([            
            'base_uri' => $this->config['base_uri'],
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
     * @param string $dealerID Dealer Identifier
     * @param string $selectionType Selection Type default odometer
     * @param string $selectionMode Selection mode default dates     
     * @author Intralix
     **/
    public function getFuelPerformance( $startTime, $endTime, $deviceID, $clientID, $dealerID, $selectionType = 'odometer', $selectionMode ='dates')
    {
        // Vars 
        $cargas = 0;
        $descargas = 0;
        $ralenti = 0;
        $nivel_inicial = 0;
        $nivel_final = 0;
        $odometro_inicial = 0;
        $odometro_final = 0;
        $kilometros_recorridos = 0;
        $results = [];

        // Attempt Ws Call
        $response = $this->client->request('POST',$this->config['base_uri'],  [
            'json' => [
                'startTime' => $startTime,
                'endTime' => $endTime,
                'deviceID' => $deviceID,
                'clientID' => $clientID,
                'dealerID' => $dealerID,
                'selectionType' => $selectionType,
                'selectionMode' => $selectionMode
            ]        
        ]);
        
        // Get Response
        $data =  json_decode($response->getBody());        
        $total_positions = count($data);
        
        if($total_positions > 0 ) {
            // Setting Data
            $odometro_inicial = $data[1]->Odometer;
            $nivel_inicial    = $data[1]->globalVolume;
            $odometro_final   = $data[$total_positions-2]->Odometer;
            $nivel_final      = $data[$total_positions-2]->globalVolume;          
            $kilometros_recorridos  = $odometro_final - $odometro_inicial;
            
            // Sumar Cargas / Descargas
            for ($i=1; $i < $total_positions; $i++) 
            { 
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
            }
            // Calculate Total Level Consumed
            $volumen_total = ($nivel_inicial - $nivel_final ) + ($cargas - abs($descargas));
            // Calculate performance
            $desempno = round($kilometros_recorridos /  $volumen_total, 2);
            $cada_cien = 100 / $desempno;           

            // Results
            $results['nivel_inicial'] = $nivel_inicial;
            $results['nivel_final'] = $nivel_final;
            $results['nivel_cargado'] = $cargas;
            $results['nivel_descargado'] = $descargas;
            $results['odometro_inicial'] = $odometro_inicial;
            $results['odometro_final'] = $odometro_final;
            $results['kilometros_recorridos'] = $kilometros_recorridos;
            $results['volumen_total'] = $volumen_total;
            $results['rendimiento'] = $desempno;
            $results['rendimiento_cien'] = $cada_cien;
            $results['consumido_ralenti'] = $ralenti;
        }        

        return $results;
    }

} // END class Fuel
