<?php

namespace App\Traits;

use App\Jobs\SendBulkEmailJob;

trait SendsBulkEmails
{
    public function sendBulkEmails(array $users, string $mailableClass, array $data = [])
    { 
        if (empty($users)) {
            return;
        }

        SendBulkEmailJob::dispatch($users, $mailableClass, $data);
    }
}