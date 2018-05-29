<?php

namespace Intralix\Boson\Models\Receiver;

use Illuminate\Database\Eloquent\Model;


class Positions extends Model
{
    //
    protected $table = 'bh_table_Transactions';       
    protected $primaryKey = 'nRecord'; 
    public $timestamps = false;    
    protected $connection = 'receiver';
    
}
