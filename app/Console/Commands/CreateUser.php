<?php

namespace App\Console\Commands;

use App\Domain\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateUser extends Command
{
    protected $signature = 'user:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $validated = [];

        do {
            $name = trim($this->ask('What is the name?'));
            $code = trim($this->ask('What is the code?'));
            $password = $this->secret('What is the password?');

            $hasError = false;

            try {
                $validated = Validator::validate([
                    'name' => $name,
                    'code' => $code,
                    'password' => $password,
                ], [
                    'name' => 'required|string|min:3|max:32|unique:users,name',
                    'code' => 'required|string|min:3|max:32|unique:users,code',
                    'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols()],
                    // 'password' => ['required', 'string', Password::min(3)],
                ]);
            } catch (ValidationException $e) {
                Log::error('Create user. Validation Failed: ' . $e->getMessage());
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->error("{$field}: {$message}");
                    }
                }
                $hasError = true;
            } catch (Throwable $e) {
                $this->error($e->getMessage());
                exit(false);
            }
        } while ($hasError);

        if (!$this->confirm(
            sprintf("Do you wish to create a user with [Name: %s] [Code: %s] [Pass: %s /%d]?",
                $name, $code, Str::limit($password, 2), mb_strlen($password))
        )) {
            $this->alert('No user created.');
        }

        try {
            $user = UserService::create($validated);
            $this->info(sprintf("User created. ID #%s, email: %s", $user->id, $user->email));
        } catch (Throwable $e) {
            $this->error('Failed to create a user.');
            $this->error($e->getMessage());
        }
    }
}
