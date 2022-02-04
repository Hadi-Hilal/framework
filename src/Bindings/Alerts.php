<?php

namespace Loxi5\Framework\Bindings;

class Alerts
{
    private $alerts = [];

    public function setAlert($type, $message, $duration = 0) {
        $this->alerts[] = [
            'type' => $type,
            'message' => $message,
            'duration' => $duration
        ];
        return $this;
    }

    public function getAlerts() {
        return $this->alerts;
    }
}
