<div class="row">
    @if(!$auth->admin)
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

        <h2 class="panel-title">View All Bonuses</h2>
    </header>
    <div class="panel-body">
        @include('bonus-route::list-bonus-table-partial')
    </div>
</section>

