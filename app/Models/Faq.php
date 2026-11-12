<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use LogsActivity, Orderable;

    protected $fillable = ['group', 'question', 'answer', 'sort_order', 'is_visible'];

}
