<?php

namespace Loxi5\Framework\Bindings;

class Actions
{
    private $actions = [];

    public function setAction($type, $title, $description, $cancel = null, $confirm = null, $confirm_method = null, $confirm_route = null, $close_on_confirm = true) {
        $this->actions[] = [
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'cancel' => !is_null($cancel) ? $cancel : __('Core::core.cancel'),
            'confirm' => !is_null($cancel) ? $cancel : __('Core::core.confirm'),
            'confirmMethod' => $confirm_method,
            'confirmRoute' => $confirm_route,
            'closeOnConfirm' => $close_on_confirm
        ];
        return $this;
    }

    public function getActions() {
        return $this->actions;
    }
}
