<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    use LogsActivity, Orderable;

    protected $fillable = ['label', 'value', 'suffix', 'icon', 'sort_order', 'is_visible'];

}
