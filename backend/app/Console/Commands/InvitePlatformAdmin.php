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
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

final class InvitePlatformAdmin extends Command
{
    protected $signature = 'platform:invite-admin {--role=platform_super_admin}';

    protected $description = 'Create an additional platform staff account through a controlled server-side invitation flow.';

    public function handle(): int
    {
        $roleName = strtolower(trim((string) $this->option('role')));
        $allowedRoles = [
            'platform_super_admin',
            'compliance_admin',
            'finance_operations',
            'risk_analyst',
            'support_agent',
            'read_only_auditor',
        ];

        if (!in_array($roleName, $allowedRoles, true)) {
            $this->error('Unsupported platform role.');
            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Full name'));
        $email = strtolower(trim((string) $this->ask('Email address')));
        $password = (string) $this->secret('Temporary password');
        $confirmation = (string) $this->secret('Confirm temporary password');

        $validation = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'password' => [Password::min(8)->max(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validation->fails()) {
            $this->error($validation->errors()->first());
            return self::FAILURE;
        }

        if (!hash_equals($password, $confirmation)) {
            $this->error('Passwords do not match.');
            return self::FAILURE;
        }

        if (!$this->confirm('Create this additional platform staff account?', false)) {
            $this->warn('No account was created.');
            return self::SUCCESS;
        }

        try {
            $user = DB::transaction(function () use ($name, $email, $password, $roleName): User {
                if (User::whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->exists()) {
                    throw new \RuntimeException('An account with this email already exists. No changes were made.');
                }

                (new PlatformAdminSeeder())->run();
                $platformRole = PlatformRole::where('name', $roleName)->lockForUpdate()->firstOrFail();
                $generalRole = Role::firstOrCreate(['name' => $roleName], ['guard_name' => 'web']);

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'account_status' => 'pending_email',
                    'user_type' => 'staff',
                    'is_active' => true,
                ]);

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

        $user->sendEmailVerificationNotification();

        $this->info('Platform staff account created.');
        $this->line('Role: '.$roleName);
        $this->line('Email verification and MFA enrollment are required before access.');
        $this->line('The password was not displayed.');

        return self::SUCCESS;
    }
}
