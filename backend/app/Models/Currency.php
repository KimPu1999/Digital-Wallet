<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Currency extends Model { protected $fillable=['code','name','symbol','decimals','display_order','is_active']; protected $casts=['is_active'=>'boolean','decimals'=>'integer','display_order'=>'integer']; public function accounts(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(WalletCurrencyAccount::class); } }
