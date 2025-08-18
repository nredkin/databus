<?php

namespace App\Console\Commands;

use App\Domain\Enums\MessageStatus;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageUser extends Command
{
    protected $signature = 'user:manage {--delete} {--set-master=} {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage a user by email.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        if (empty($email)) {
            $this->warn('Add an email as argument.');
            $this->info("> php artisan user:manage <email>");
            $emails = User::query()->orderBy('email')->select(['id', 'email', 'code', 'is_master'])->get()->toArray();
            $this->table(['Id', 'Email', 'Code', 'IsMaster'], $emails);
            return;
        }

        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            $this->warn('User not found.');
        }

        if ($this->option('delete')) {
            $this->deleteUser($user);
        } elseif (($setMaster = $this->option('set-master')) !== null) {
            $this->setMaster($user, $setMaster);
        } else {
            $this->warn('No correct argument/option specified.');
        }
    }

    private function deleteUser(User $user)
    {
        $count = $user->outcomeMessages()->where('status', MessageStatus::Awaiting->value)->count();
        if ($count > 0) {
            $this->warn('Messages [' . $count . '] from the user are not received yet.');
            return;
        }
        try {
            DB::beginTransaction();
            if ($user->delete()) {
                $this->info('User deleted.');
            } else {
                $this->error('User not deleted.');
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
        }
    }

    private function setMaster(User $user, $setMaster)
    {
        $user->is_master = !!$setMaster;
        $user->update(['is_master']);
    }
}
