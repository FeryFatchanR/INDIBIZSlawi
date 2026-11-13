<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    use LogsActivity;

    protected $fillable = ['group', 'key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public function activityLabel(): string
    {
        return $this->group.'.'.$this->key;
    }
}
