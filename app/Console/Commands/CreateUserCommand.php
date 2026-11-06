<?php

namespace App\Console\Commands;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateUserCommand extends Command
{
    protected $signature = 'ehrungsfinder:user {email?} {--name=} {--admin : Volle Verwaltungsrechte} {--role=* : Fachrolle je Organisation, z. B. wjd:approver oder wj-st:editor}';

    protected $description = 'Legt eine Person für den Verwaltungsbereich an oder aktualisiert ihre Rollen';

    public function handle(): int
    {
        $email = $this->argument('email') ?? text('E-Mail', required: true);
        $user = User::firstWhere('email', $email);

        if (! $user) {
            $user = new User([
                'email' => $email,
                'name' => $this->option('name') ?: text('Name', required: true),
                'password' => password('Passwort (leer = zufällig)') ?: Str::password(20),
            ]);
            $user->email_verified_at = now();
        }

        if ($this->option('admin')) {
            $user->is_admin = true;
        }

        $user->save();

        foreach ($this->option('role') as $assignment) {
            [$code, $role] = array_pad(explode(':', $assignment, 2), 2, null);
            $organisation = Organisation::firstWhere('code', $code);

            if (! $organisation || ! in_array($role, [User::ROLE_EDITOR, User::ROLE_APPROVER], true)) {
                $this->error("Ungültige Rolle „{$assignment}“. Format: <organisation>:editor|approver");

                return self::FAILURE;
            }

            $user->organisations()->syncWithoutDetaching([$organisation->id => ['role' => $role]]);
        }

        $this->info("Gespeichert: {$user->email}".($user->is_admin ? ' (Admin)' : ''));

        return self::SUCCESS;
    }
}
