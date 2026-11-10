<?php
namespace App\Services;
use App\Models\MfaMethod;
use App\Models\MfaRecoveryCode;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
final class MfaService {
    public function begin(User $user): array { $secret=$this->base32Encode(random_bytes(20)); $method=MfaMethod::updateOrCreate(['user_id'=>$user->id,'type'=>'totp'],['secret_encrypted'=>Crypt::encryptString($secret),'is_primary'=>true,'verified_at'=>null]); $label=rawurlencode('Moneta:'.$user->email); return ['method_id'=>$method->id,'secret'=>$secret,'otpauth_url'=>'otpauth://totp/'.$label.'?secret='.$secret.'&issuer=Moneta&algorithm=SHA1&digits=6&period=30']; }
    public function verify(User $user,string $code): array { $method=MfaMethod::where('user_id',$user->id)->where('type','totp')->firstOrFail(); $secret=Crypt::decryptString($method->secret_encrypted); abort_unless($this->validTotp($secret,$code),422,'Invalid verification code.'); $method->update(['verified_at'=>now()]); $codes=collect(range(1,8))->map(fn()=>strtoupper(Str::random(10)))->all(); foreach($codes as $codeValue) MfaRecoveryCode::create(['user_id'=>$user->id,'code_hash'=>hash('sha256',$codeValue)]); return ['recovery_codes'=>$codes]; }
    public function verifyRecoveryCode(User $user,string $code): bool { $record=MfaRecoveryCode::where('user_id',$user->id)->whereNull('used_at')->get()->first(fn($item)=>hash_equals($item->code_hash,hash('sha256',$code))); if(!$record) return false; $record->update(['used_at'=>now()]); return true; }
    public function enabled(User $user): bool { return MfaMethod::where('user_id',$user->id)->whereNotNull('verified_at')->exists(); }
    private function validTotp(string $secret,string $code): bool { if(!preg_match('/^\d{6}$/',$code)) return false; $counter=intdiv(now()->timestamp,30); for($offset=-1;$offset<=1;$offset++){ $binary=pack('N*',0,$counter+$offset); $hash=hash_hmac('sha1',$binary,$this->base32Decode($secret),true); $index=ord($hash[19])&0xf; $value=((ord($hash[$index])&0x7f)<<24)|((ord($hash[$index+1])&0xff)<<16)|((ord($hash[$index+2])&0xff)<<8)|(ord($hash[$index+3])&0xff); if(hash_equals(str_pad((string)($value%1000000),6,'0',STR_PAD_LEFT),$code)) return true; } return false; }
    private function base32Encode(string $data): string { $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits=''; foreach(str_split($data) as $char) $bits.=str_pad(decbin(ord($char)),8,'0',STR_PAD_LEFT); $out=''; foreach(str_split($bits,5) as $chunk) $out.=$alphabet[bindec(str_pad($chunk,5,'0'))]; return $out; }
    private function base32Decode(string $data): string { $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits=''; foreach(str_split(strtoupper($data)) as $char){$pos=strpos($alphabet,$char);if($pos===false)continue;$bits.=str_pad(decbin($pos),5,'0',STR_PAD_LEFT);} $out=''; foreach(str_split($bits,8) as $chunk) if(strlen($chunk)===8) $out.=chr(bindec($chunk)); return $out; }
}
