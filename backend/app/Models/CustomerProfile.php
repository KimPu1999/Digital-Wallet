<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CustomerProfile extends Model { protected $fillable=['user_id','phone','locale','display_name','date_of_birth','address_line1','address_line2','city','postal_code','avatar_path']; protected $hidden=['avatar_path']; protected $casts=['date_of_birth'=>'date']; public function user(): BelongsTo { return $this->belongsTo(User::class); } }
