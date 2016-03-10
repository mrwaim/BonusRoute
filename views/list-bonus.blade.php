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
            <li><span>View All Bonuses</span></li>
        </ol>

        <div class="sidebar-right-toggle"></div>
    </div>
@endsection


@section('content')

    <div class="panel panel-default">
        @if($auth->admin)
            @include('bonus-route::bonus-tile-partial')
        @endif

        @include('bonus-route::list-bonus-partial', ['show_awarded_to' => true])
    </div>

    @if(!$auth->admin)
        <div class="panel panel-default">
            @include('bonus-route::list-bonus-command-partial', ['list' => $bonusCommands, 'show_user' => false])
        </div>
    @endif

@endsection
