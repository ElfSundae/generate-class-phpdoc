<?php

namespace App\Notification;

use App\Models\PushMessage;

class Pusher
{
    /**
     * This method specifies the return type and parameter types in the doc block.
     *
     * @param string|array $tokens
     * @param string|array|\App\Notification\Payload $payload
     * @param array $options
     * @return bool
     */
    public function send($tokens, $payload, $options = [])
    {
    }

    /**
     * This method specifies types in the method signature using "type declarations".
     */
    public function received(array|string|PushMessage $message, ?int $status = null): static
    {
        return $this;
    }
}
