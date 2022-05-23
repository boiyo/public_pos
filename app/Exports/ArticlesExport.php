<?php

namespace App\Exports;

//use App\Article;
//use App\Models\Articels;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ArticlesExport implements FromView
{
    public function view(): View
    {
        return view('member.MemberInformationDetails');
    }
}
