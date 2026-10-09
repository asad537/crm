<?php

namespace App\Console\Commands;

use App\CrmUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** crm:user-password — emergency/ops reset of a CRM user's LOCAL password (never touches mailbox credentials). */
class UserSetPassword extends Command
{
    protected $signature = 'crm:user-password {email : CRM login email} {--generate : Generate a strong password and print it once}';
    protected $description = 'Set (or generate) the local CRM password for a user';

    public function handle(): int
    {
        $user = CrmUser::where('email', strtolower(trim($this->argument('email'))))->first();
        if (!$user) { $this->error('No CRM user with that email.'); return 1; }

        if ($this->option('generate')) {
            $password = Str::random(10) . 'A1!' . Str::random(3);
        } else {
            $password = (string) $this->secret('New password (min 8, upper/lower/number/special)');
            if (strlen($password) < 8 || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                $this->error('Password too weak.'); return 1;
            }
        }
        $user->forceFill(['password' => Hash::make($password), 'password_changed_at' => now()])->save();
        $this->info("Local password updated for {$user->email}.");
        if ($this->option('generate')) {
            $this->line('One-time password (share securely, ask the user to change it): ' . $password);
        }
        return 0;
    }
}
