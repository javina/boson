<?php
namespace Intralix\Boson;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Intralix\Boson\FuelPerformance;

/**
 * Class to call Bosson Boson Ws
 *
 * @package Intralix\Boson
 * @author  Intralix
 **/
class Boson
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
        $this->client = new Client([]);
    }   

    /**
     * Calculate Boson Fuel Performance
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
        $results = [];
               
        // Get Response
        $data =  $this->getWsFuelData($startTime, $endTime, $deviceID, $clientID);         
        $total_positions = count($data);           
        
        if($total_positions > 0 ) 
        {
            $fuel_performance = new FuelPerformance($data);            
            $results = $fuel_performance->getResults();            
        }        
 
        return $results;
    }


    /**
     * Consume Boson Fuel WebService
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
                
        } catch (Exception $e) {                                
            
            $response = [];
            Log::error($e->getMessage());
        }        
        
        return $response;                        
    }   
        
} // END class Fuel
