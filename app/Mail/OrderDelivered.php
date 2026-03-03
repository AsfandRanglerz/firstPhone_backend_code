<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderDelivered extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $name;   
    protected $order_number;   

    public function __construct($name, $order_number)
    {
        $this->name = $name;
        $this->order_number = $order_number;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.OrderDelivered')->with([
                        'name' => $this->name,
                        'order_number' => $this->order_number
                    ]);
    }
}
