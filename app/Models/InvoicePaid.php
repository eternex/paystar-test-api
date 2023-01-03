<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePaid extends Model
{
    use HasFactory;

    protected $fillable = array('*');
    protected $primaryKey = 'invoice_id';
}
