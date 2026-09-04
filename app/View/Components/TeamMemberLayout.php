<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class TeamMemberLayout extends Component
{
    public function render(): View
    {
        return view('layouts.team-member');
    }
}
