<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBulkEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
     protected array $users;
    protected string $mailableClass;
    protected $data;

    public function __construct(array $users, string $mailableClass, array $data = [])
    {
        $this->users = $users;
        $this->mailableClass = $mailableClass;
        $this->data = $data;
    }

    public function handle(): void
    {
        foreach ($this->users as $user) {

            if (empty($user->email)) {
                continue;
            }

            Mail::to($user->email)
                ->send(new $this->mailableClass($this->data));
        }
    }
}
