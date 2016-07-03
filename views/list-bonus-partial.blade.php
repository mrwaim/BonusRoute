<div class="row">
    @if(!$auth->admin && $auth->stockist)
        <div class="col-xs-6 col-md-6 col-lg-4 restock-btn">
            <a class="btn btn-primary btn-block restock-bttn" href='/order-management/restock'>Restock</a>
        </div>
    @endif
</div>
<section class="panel">
    <header class="panel-heading">
        <div class="panel-actions">
            <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
            <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
        </div>

        @if($filter == 'org')
            <h2 class="panel-title">View Bonuses in your organization</h2>
        @else
            <h2 class="panel-title">View All Bonuses</h2>
        @endif

    </header>
    <div class="panel-body">
        <div class="table-responsive">
            @include('bonus-route::list-bonus-table-partial')
            {!! $list->render() !!}
        </div>
    </div>

    @include('elements.export-list')
</section>

