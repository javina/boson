<?php

namespace Intralix\Boson\Commands;

use Illuminate\Console\Command;
use Intralix\Boson\Models\GeoReverse;
use Config;
use Log;

class GeoReverseReceiverPositions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lgps:reverse-geocode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta la geo codifiación inversa para los datos de Last Position.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //        
        $estatus = GeoReverse::processPositions();
        $this->info( $estatus );
    }
}
