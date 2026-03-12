<?php

namespace App\Repositories;


use App\Models\Vendor;
use Illuminate\Support\Facades\Mail;
use App\Repositories\Interfaces\VendorRepositoryInterface;

class VendorRepository implements VendorRepositoryInterface
{
    public function all()
    {
        return Vendor::with('subscription.plan')->orderBy('id', 'desc')->get();
    }

    public function find($id)
    {
        return Vendor::find($id);
    }

    public function create(array $data)
    {
        return Vendor::create($data);
    }

    public function update($id, array $data)
    {
        $user = Vendor::findOrFail($id);
        $user->update($data);
        return $user;
    }

    public function delete($id)
    {
        $user = Vendor::find($id);
        if ($user) {
            $user->delete();
            return true;
        }
        return false;
    }

    public function updateStatus($id, $status, $reason = null)
    {
        $vendor = Vendor::find($id);
        if (!$vendor) return null;

        $vendor->status = $status;
        $vendor->save();

        // Optional email notifications
        if ($status === 'deactivated' && $reason) {
            $this->sendDeactivationEmail($vendor, $reason);
        } elseif ($status === 'activated') {
            $this->sendActivationEmail($vendor);
        }

        return $vendor;
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
                    ->subject('Vendor Account Deactivated');
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
                ->subject('Vendor Account Activated');
        });
    } catch (\Exception $e) {
        \Log::error("Failed to send activation email: " . $e->getMessage());
    }
}
}
