<?php

namespace Loxi5\Framework\Http\Middleware;

use Illuminate\Http\Request;

class Widget extends \Inertia\Middleware
{
    public function rootView(Request $request)
    {
        return 'Theme::widget';
    }
}
