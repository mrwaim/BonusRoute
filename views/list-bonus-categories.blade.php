@extends('app')

@section('page-header')
    @include('elements.page-header', ['section_title' => 'Bonus Management', 'page_title' => 'View All Bonus Categories'])
@endsection

@section('content')
    @include('elements.success-message-partial')

    <section class="panel">
        <header class="panel-heading">
            <div class="panel-actions">
                <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
                <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
            </div>

            <h2 class="panel-title">View All Bonus Categories</h2>
        </header>
        <div class="panel-body">
            @include('bonus-route::list-bonus-categories-partial')
        </div>
    </section>
@endsection
