<?php

namespace App\Console\Commands;

use App\Models\PlatformAdminMembership;
use App\Models\PlatformRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PlatformAdminSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

final class CreateSuperAdmin extends Command
{
    protected $signature = 'platform:create-super-admin';

    protected $description = 'Interactively bootstrap the first Platform Super Admin without exposing credentials.';

    public function handle(): int
    {
        $name = $this->promptForName();
        $email = $this->promptForEmail();
        if ($email === '') {
            return self::FAILURE;
        }
        $password = $this->promptForPassword();

        if (!$this->confirm('Create this Platform Super Admin account?', false)) {
            $this->warn('No account was created.');
            return self::SUCCESS;
        }

        try {
            $user = DB::transaction(function () use ($name, $email, $password): User {
                // Repeat the check inside the transaction so a second concurrent
                // bootstrap attempt cannot overwrite an existing identity.
                if (User::whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->exists()) {
                    throw new \RuntimeException('An account with this email already exists. No changes were made.');
                }

                (new PlatformAdminSeeder())->run();
                $platformRole = PlatformRole::where('name', 'platform_super_admin')->lockForUpdate()->firstOrFail();
                if (PlatformAdminMembership::where('platform_role_id', $platformRole->id)->where('status', 'active')->exists()) {
                    throw new \RuntimeException('A Platform Super Admin already exists. Use the controlled invitation process for additional staff.');
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'account_status' => 'pending_email',
                    'user_type' => 'staff',
                    'is_active' => true,
                ]);

                $generalRole = Role::firstOrCreate(['name' => 'platform_super_admin'], ['guard_name' => 'web']);
                DB::table('role_assignments')->insert([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'role_id' => $generalRole->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                PlatformAdminMembership::create([
                    'user_id' => $user->id,
                    'platform_role_id' => $platformRole->id,
                    'status' => 'active',
                ]);

                return $user;
            });
        } catch (\RuntimeException|QueryException $exception) {
            $this->error($exception instanceof QueryException
                ? 'The account could not be created because the email is already registered or the database rejected the request.'
                : $exception->getMessage());
            return self::FAILURE;
        }

        // Verification is sent only after the account transaction commits.
        $user->sendEmailVerificationNotification();

        $this->info('Platform Super Admin account created.');
        $this->line('Email verification is required before administrator login.');
        $this->line('MFA enrollment is required before platform access.');
        $this->line('Admin login URL: '.config('app.url').'/admin/login');
        $this->line('No password or MFA secret was displayed.');

        return self::SUCCESS;
    }

    private function promptForName(): string
    {
        do {
            $name = trim((string) $this->ask('Full name'));
            if ($name === '' || mb_strlen($name) > 120) {
                $this->error('Name is required and must be 120 characters or fewer.');
                $name = '';
            }
        } while ($name === '');

        return $name;
    }

    private function promptForEmail(): string
    {
        do {
            $email = strtolower(trim((string) $this->ask('Email address')));
            $valid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($email) <= 255;
            $exists = $valid && User::whereRaw('LOWER(email) = ?', [$email])->exists();

            if (!$valid) {
                $this->error('Enter a valid email address.');
                $email = '';
            } elseif ($exists) {
                $this->error('An account with this email already exists. No account will be overwritten.');
                return '';
            }
        } while ($email === '');

        return $email;
    }

    private function promptForPassword(): string
    {
        do {
            $password = (string) $this->secret('Password');
            $validation = Validator::make(['password' => $password], [
                'password' => [Password::min(8)->max(12)->mixedCase()->numbers()->symbols()],
            ]);

            if ($validation->fails()) {
                $this->error('Password must be 8 to 12 characters and include upper/lowercase letters, numbers, and symbols.');
                $password = '';
            }
        } while ($password === '');

        do {
            $confirmation = (string) $this->secret('Confirm password');
            if (!hash_equals($password, $confirmation)) {
                $this->error('Passwords do not match.');
                $confirmation = '';
            }
        } while ($confirmation === '');

        return $password;
    }
}
