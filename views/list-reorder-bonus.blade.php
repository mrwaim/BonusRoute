@extends('app')

@section('page-header')
    <h2>Bonus Management</h2>

    <div class="right-wrapper pull-right">
        <ol class="breadcrumbs">
            <li>
                <a href="index.html">
                    <i class="fa fa-home"></i>
                </a>
            </li>
            <li><span>Bonus Management</span></li>
            <li><span>Bonuses Upon Reorder</span></li>
        </ol>

        <div class="sidebar-right-toggle"></div>
    </div>
@endsection


@section('content')

    @include('bonus-route::list-bonus-command-partial', ['show_user' => true, 'list' => $bonus_commands])

@endsection
