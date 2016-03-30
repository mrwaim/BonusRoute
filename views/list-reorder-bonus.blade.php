@extends('app')

@section('page-header')
    @include('elements.page-header', ['section_title' => 'Bonus Management', 'page_title' => 'Bonuses Upon Reorder'])
@endsection


@section('content')

    @include('bonus-route::list-bonus-command-partial', ['show_user' => true, 'list' => $bonus_commands])

@endsection
