@extends('app')

@section('page-header')
    @include('elements.page-header', ['section_title' => 'Bonus Management', 'page_title' => 'View All Bonuses'])
@endsection


@section('content')

    <div class="panel panel-default">
        @include('bonus-route::bonus-tile-partial')

        @include('bonus-route::list-bonus-partial', ['show_awarded_to' => true])
    </div>

    @if(!$auth->admin)
        <div class="panel panel-default">
            @include('bonus-route::list-bonus-command-partial', ['list' => $bonusCommands, 'show_user' => false])
        </div>
    @endif

@endsection
