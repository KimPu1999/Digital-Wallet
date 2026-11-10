<?php
namespace App\Services;
use App\Models\Beneficiary; use App\Models\BeneficiaryAccount; use App\Models\User; use Illuminate\Support\Facades\Crypt; use Illuminate\Support\Facades\DB; use Illuminate\Support\Str; use Illuminate\Validation\ValidationException;
final class BeneficiaryService
{
    public function create(User $user, array $data): Beneficiary
    {
        [$ownerType,$merchantId]=$this->ownerContext($user); $details=$data['account_details'] ?? []; $fingerprint=hash('sha256',json_encode($details,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        return DB::transaction(function() use($user,$ownerType,$merchantId,$data,$details,$fingerprint): Beneficiary {
            if(Beneficiary::where('user_id',$ownerType==='customer'?$user->id:null)->where('merchant_id',$merchantId)->where('details_fingerprint',$fingerprint)->exists()) throw ValidationException::withMessages(['beneficiary'=>'This beneficiary already exists.']);
            $beneficiary=Beneficiary::create(['user_id'=>$ownerType==='customer'?$user->id:null,'merchant_id'=>$merchantId,'owner_type'=>$ownerType,'display_name'=>$data['display_name'],'beneficiary_type'=>$data['beneficiary_type'],'country_code'=>strtoupper($data['country_code'] ?? ''),'status'=>'active','verification_status'=>'unverified','risk_status'=>'not_screened','search_name'=>Str::lower($data['display_name']),'details_fingerprint'=>$fingerprint,'metadata'=>['sandbox_only'=>true]]);
            $beneficiary->accounts()->create(['account_type'=>$data['beneficiary_type'],'currency_code'=>isset($data['currency_code'])?strtoupper($data['currency_code']):null,'account_details_encrypted'=>Crypt::encryptString(json_encode($details,JSON_THROW_ON_ERROR)),'account_last4'=>$this->last4($details)]);
            return $beneficiary->load('accounts');
        });
    }
    public function update(User $user, Beneficiary $beneficiary, array $data): Beneficiary { $this->assertOwned($user,$beneficiary); $beneficiary->update(array_filter(['display_name'=>$data['display_name']??null,'country_code'=>isset($data['country_code'])?strtoupper($data['country_code']):null,'status'=>$data['status']??null],fn($v)=>$v!==null)); return $beneficiary->load('accounts'); }
    public function deactivate(User $user, Beneficiary $beneficiary): Beneficiary { $this->assertOwned($user,$beneficiary); $beneficiary->update(['status'=>'deactivated']); return $beneficiary; }
    public function assertOwned(User $user, Beneficiary $beneficiary): void { abort_unless($beneficiary->isOwnedBy($user),404,'Beneficiary not found.'); }
    private function ownerContext(User $user): array { if($user->user_type!=='merchant') return ['customer',null]; $membership=DB::table('merchant_memberships')->where('user_id',$user->id)->orderBy('created_at')->first(); abort_unless($membership,403,'Merchant membership is required.'); return ['merchant',$membership->merchant_id]; }
    private function last4(array $details): ?string { foreach(['account_number','wallet_account_id','phone','identifier'] as $key) if(isset($details[$key])) return substr(preg_replace('/\D/','',(string)$details[$key]),-4); return null; }
}
