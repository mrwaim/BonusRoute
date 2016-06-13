@extends('app')

@section('page-header')
    @if($filter == 'org')
        <?php $pageTitle = 'View Bonuses in your organization'; ?>
    @else
        <?php $pageTitle = 'View All Bonuses'; ?>
    @endif

    @include('elements.page-header', ['section_title' => 'Bonus Management', 'page_title' => $pageTitle])
@endsection


@section('content')

    <div class="panel panel-default">
        @if($filter == 'all')
        @include('bonus-route::bonus-tile-partial')
        @endif

        @include('bonus-route::list-bonus-partial', ['show_awarded_to' => true])
    </div>

    @if(!$auth->admin && $filter == 'me')
        <div class="panel panel-default">
            @include('bonus-route::list-bonus-command-partial', ['list' => $bonusCommands, 'show_user' => false])
        </div>
    @endif

@endsection
