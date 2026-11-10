<?php
namespace App\Services;
use App\Models\Country;
use Illuminate\Validation\ValidationException;
final class CountryEligibilityService { public function assertRegistrationAllowed(string $countryCode): void { $country=Country::with('capabilities')->whereKey(strtoupper($countryCode))->where('is_active',true)->first(); if (!$country || !$country->capabilities?->registration_enabled) throw ValidationException::withMessages(['country_code'=>'Registration is not available in this country.']); } public function capability(string $countryCode,string $capability): bool { return (bool) Country::with('capabilities')->whereKey(strtoupper($countryCode))->first()?->capabilities?->{$capability}; } }
