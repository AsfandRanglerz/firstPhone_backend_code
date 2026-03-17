<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Mail;

class UserRepository implements UserRepositoryInterface
{
    public function all()
    {
        return User::select('id','name','email','phone','image','toggle')->orderBy('id', 'desc')->get();
    }

    public function find($id)
    {
        return User::find($id);
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function update($id, array $data)
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user;
    }

    public function delete($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();
            return true;
        }
        return false;
    }

    public function toggleStatus($id, $status, $reason = null)
    {
        $user = User::find($id);
        if (!$user) return null;

        $user->toggle = $status;
        $user->save();

        if ($status == 0 && $reason) {
            $this->sendDeactivationEmail($user, $reason);
        }elseif ($status == 1) {
        $this->sendActivationEmail($user);
    }

        return $user;
    }

    protected function sendDeactivationEmail($user, $reason)
    {
        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'reason' => $reason
        ];

        try {
            Mail::send('emails.user_deactivated', $data, function($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Account Deactivation Notification');
            });
        } catch (\Exception $e) {
            \Log::error("Failed to send email: " . $e->getMessage());
        }
    }

    protected function sendActivationEmail($user)
{
    $data = [
        'name' => $user->name,
        'email' => $user->email,
    ];

    try {
        Mail::send('emails.user_activated', $data, function($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Account Activation Notification');
        });
    } catch (\Exception $e) {
        \Log::error("Failed to send activation email: " . $e->getMessage());
    }
}
}
