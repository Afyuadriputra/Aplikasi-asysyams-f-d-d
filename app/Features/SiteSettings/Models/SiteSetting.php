<?php

namespace App\Features\SiteSettings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'value_image',
        'value_date',
        'value_text',
    ];

    public function getValueImageAttribute() { 
        return $this->value; 
    }
    public function setValueImageAttribute($val) { 
        $this->attributes['value'] = $val; 
    }

    public function getValueDateAttribute() { 
        return $this->value; 
    }
    public function setValueDateAttribute($val) { 
        $this->attributes['value'] = $val; 
    }

    public function getValueTextAttribute() { 
        return $this->value; 
    }
    public function setValueTextAttribute($val) { 
        $this->attributes['value'] = $val; 
    }
}
