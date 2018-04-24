<?php
namespace Intralix\Boson;

use Carbon\Carbon;

/**
 * Class to call Bosson Boson Ws
 *
 * @package Intralix\Boson
 * @author  Intralix
 **/
class FuelPerformance
{

    protected $cargas;    
    protected $descargas;
    protected $ralenti;
    protected $nivel_inicial;
    protected $nivel_final;
    protected $odometro_inicial;
    protected $odometro_final;
    protected $kilometros_recorridos;
    protected $velocidadades;
    protected $top_speed;
    protected $top_speed_date;
    protected $average_speed;
    protected $max_fuel;
    protected $max_fuel_date;
    protected $min_fuel;
    protected $min_fuel_date;
    protected $counter;
    protected $data;

    /**
     * Class Constructor
     *
     * @return void
     * @author Intralix
     **/    
    public function __construct( $data )
    {
        $this->cargas = 0;
        $this->descargas = 0;
        $this->ralenti = 0;
        $this->nivel_inicial = 0;
        $this->nivel_final = 0;
        $this->odometro_inicial = 0;
        $this->odometro_final = 0;
        $this->kilometros_recorridos = 0;
        $this->velocidadades = [];
        $this->top_speed = 0;
        $this->top_speed_date = '';
        $this->average_speed = 0;
        $this->max_fuel = 0;
        $this->max_fuel_date = '';
        $this->min_fuel = 0;
        $this->min_fuel_date = '';
        $this->counter = 0;
        $this->data = $data;

        // Process
        $this->scanData();
    }  


    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function scanData()
    {         
        // Get Response        
        $total_positions = count($this->data);           
        
        if($total_positions > 0 ) 
        {
            // Obtenemos los valores Iniciales y Finales
            $this->setMinAndMaxValues();
            
            // Recorremos la información o posiciones
            for ($i=1; $i < $total_positions; $i++) 
            {                

                if($this->data[$i]->eventName == 'Evento/Cambio brusco en nivel')
                {
                    if($this->data[$i]->volumeChange > 0 )                    
                        $this->acumularCargas($this->data[$i]->volumeChange);                    
                    else                     
                        $this->acumularDescargas($this->data[$i]->volumeChange);                    
                } 
                
                if ( strpos($this->data[$i]->eventName,'Combustible consumido en vac')!==false)                  
                    $this->acumularEnVacio($this->data[$i]->volumeChange);                

                // Acumulado de Velocidades
                $this->acumularVelocidades( $this->data[$i] );               

                // Volumen Máximo
                $this->acumularVolumenGlobalMax( $this->data[$i] );
            
                // Volumen Mínimo
                $this->acumularVolumenGlobalMin( $this->data[$i] );

                /* Promedio de la velocidad**/       
                $this->velocidadPromedio( $this->data[$i] );                      
            }
            
            /* Volumen **/
            $this->volumen_total = $this->volumenTotal();
            
            // Desempeño
            $this->desempeno = $this->calcularDesempeno();
            
            // Calcular cada 100
            $this->cada_cien = $this->calcularCadaCien();

            // Valocidad Promedio
            $this->velocidad_promedio = $this->valocidadPromedio();
                       
        }                
    }


    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function setMinAndMaxValues()
    {
        // Valores Iniciales
        $this->odometro_inicial = $this->data[1]->Odometer;
        $this->nivel_inicial = $this->data[1]->globalVolume;
        $this->fecha_inicial = $this->data[1]->dateGPS;

        // Valores finales
        $this->odometro_final = $this->data[$total_positions-2]->Odometer;
        $this->nivel_final = $this->data[$total_positions-2]->globalVolume;          
        $this->fecha_final = $this->data[$total_positions-1]->dateGPS;
        
        // Kilometraje Recorrido        
        $this->kilometros_recorridos  = $this->odometro_final - $this->odometro_inicial;

        // Temporal
        $this->top_speed = $this->data[1]->Speed;                                                
        $this->top_speed_date = $this->data[1]->dateGPS;
        $this->max_fuel = ($this->data[1]->globalVolume != 'ND') ?? 0;  
        $this->max_fuel_date = $this->data[1]->dateGPS;
        // Mas Fuel
        $this->max_fuel = ($this->data[1]->globalVolume != 'ND') ?? 0;  
        $this->max_fuel_date = $this->data[1]->dateGPS;
        // Min Fuel
        $this->min_fuel = ($this->data[1]->globalVolume != 'ND') ?? 0;                                                  
        $this->min_fuel_date = $this->data[1]->dateGPS;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
    **/
    public function acumularCargas($value)
    {
        $this->cargas += $value;
    }    

    /**
     * undocumented function
     *
     * @return void
     * @author 
    **/
    public function acumularDescargas($value)
    {
        $this->descargas += $value;
    }        

    /**
     * undocumented function
     *
     * @return void
     * @author 
    **/
    public function acumularEnVacio($value)
    {
        $this->ralenti += $value;
    }        

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function acumularVelocidades($value)
    {
        if($value->Speed != 'ND')
        {
            if($value->Speed > 0)
            $this->counter ++;

            if($value->Speed > $this->top_speed)
            {
                $this->top_speed = $value->Speed;
                $this->top_speed_date = $value->dateGPS;  
            }
        }
    }    

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function acumularVolumenGlobalMax( $value )
    {
        if($value->globalVolume != 'ND')
        {
            if($value->globalVolume > $this->max_fuel)
            {
                $this->max_fuel = $value->globalVolume;
                $this->max_fuel_date = $value->dateGPS;  
            }
        }
    }  

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function acumularVolumenGlobalMin( $value )
    {
        if($value->globalVolume != 'ND')
        {
            if($value->globalVolume < $this->min_fuel)
            {
                $this->min_fuel = $value->globalVolume;
                $this->min_fuel_date = $value->dateGPS;  
            }
        }
    }                      

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function velocidadPromedio( $value )
    {
        if($value->Speed == 'ND')
            $this->average_speed += 0;
        else
            $this->average_speed += $value->Speed;
    }   

    /**
      * undocumented function
      *
      * @return void
      * @author 
      **/
     public function volumenTotal()
     {
        return ($this->nivel_inicial - $this->nivel_final ) + ($this->cargas - abs($this->descargas));
     }  

   /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function calcularDesempeno()
    {
        $value = 0;

        if($this->volumen_total > 0)
            $value = round($this->kilometros_recorridos /  $this->volumen_total, 2);
        
        return $value;
    }    

   /**
     * undocumented function
     *
     * @return void
     * @author 
    **/
    public function calcularCadaCien()
    {
        $value = 0;

        if($this->desempeno > 0)
            $value = 100 / $this->desempeno;
        
        return $value;
    } 

    /**
    * undocumented function
    *
    * @return void
    * @author 
    **/
    public function valocidadPromedio()
    {
        $value = 0;
        
        if($this->counter > 0)
            $value = round($this->average_speed / $this->counter, 2 );
        
        return $value;
    }           

    /**
     * undocumented function summary
     *
     * Undocumented function long description
     *
     * @param Type $var Description
     * @return type
     * @throws conditon
     **/
    public function getResults()
    {
        return [                 
            'fecha_inicial' => Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T','',$this->fecha_inicial))->toDateTimeString(),
            'fecha_final' => Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T','',$this->fecha_final))->toDateTimeString(),
            'odometro_inicial' => ($this->odometro_inicial),
            'odometro_final' => ($this->odometro_final),
            'distancia_recorrida' => ($this->kilometros_recorridos),
            'velocidad_promedio' => $this->velocidad_promedio,
            'velocidad_max' => $this->top_speed,
            'velocidad_max_fecha' => Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T','',$this->top_speed_date))->toDateTimeString(),
            'combustible_inicial' => ($this->nivel_inicial),
            'combustible_final' => ($this->nivel_final),
            'volumen_minimo' => $this->min_fuel,
            'volumen_minimo_fecha' => Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T','',$this->min_fuel_date))->toDateTimeString(),
            'volumen_max' => $this->max_fuel,
            'volumen_max_fecha' => Carbon::createFromFormat('Y-m-d H:i:s', str_replace('T','',$this->max_fuel_date))->toDateTimeString(),
            'volumen_recargado' => ($this->cargas),
            'volumen_descargado' => ($this->descargas),
            'combustible_consumido' => ($this->volumen_total),
            'consumido_en_vacio' => $this->ralenti,
            'consumo_cien_km' => ($this->cada_cien),
            'rendimiento_litro' => ($this->desempeno),
            //'periodo' => 'Mensual',
        ];          
    }
}
