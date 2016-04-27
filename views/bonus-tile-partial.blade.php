<div class="row">
    <div class="col-md-6">
        <section class="panel panel-featured-left panel-featured-primary">
            <div class="panel-body">
                <div class="widget-summary r-summary">
                    <div class="widget-summary-col widget-summary-col-icon">
                        <div class="summary-icon bg-primary">
                            <i class="fa fa-ticket"></i>
                        </div>
                    </div>
                    <div class="widget-summary-col">
                        <div class="summary">
                            <h4 class="title">Total Bonus</h4>

                            <div class="info">
                                <strong class="amount">RM {{$totalBonus->cash}}</strong>
                                @if($totalBonus->gold)<br/>
                                <strong class="amount">Gold {{$totalBonus->gold}} gm</strong><br/>
                                @endif
                                @if($totalBonus->bonusNotChosen)
                                    <strong class="amount">Not selected {{$totalBonus->bonusNotChosen}}</strong>
                                @endif
                            </div>
                        </div>
                        <div class="summary-footer">
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @if(!$auth->staff)
        <div class="col-md-6">
            <section class="panel panel-featured-left panel-featured-primary">
                <div class="panel-body">
                    <div class="widget-summary r-summary">
                        <div class="widget-summary-col widget-summary-col-icon">
                            <div class="summary-icon bg-primary">
                                <i class="fa fa-trophy"></i>
                            </div>
                        </div>
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Bonus Earned This Month</h4>

                                <div class="info">
                                    <strong class="amount">RM {{$bonusThisMonth->cash}}</strong>
                                    @if($bonusThisMonth->gold)<br/>
                                    <strong class="amount">Gold {{$bonusThisMonth->gold}} gm</strong><br/>
                                    @endif
                                    @if($bonusThisMonth->bonusNotChosen)
                                        <strong class="amount">Not selected {{$bonusThisMonth->bonusNotChosen}}</strong>
                                    @endif
                                </div>
                            </div>
                            <div class="summary-footer">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    @endif

    @if($auth->admin)
        <div class="col-md-6">
            <section class="panel panel-featured-left panel-featured-primary">
                <div class="panel-body">
                    <div class="widget-summary r-summary">
                        <div class="widget-summary-col widget-summary-col-icon">
                            <div class="summary-icon bg-primary">
                                <i class="fa fa-trophy"></i>
                            </div>
                        </div>
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Top Bonus Earner</h4>

                                <div class="info">
                                    @if($topBonusUser && $topBonusUser->user)
                                        <strong>@link($topBonusUser->user)</strong>
                                    @else
                                        <strong>None</strong>
                                    @endif
                                </div>
                                <div class="info">
                                    @if($topBonusUser && $topBonusUser->user)
                                        <strong class="amount">RM {{$topBonusUser->cash}}</strong>
                                        @if($topBonusUser->gold)
                                            <strong class="amount">Gold {{$topBonusUser->gold}} gm</strong><br/>
                                        @endif
                                        @if($topBonusUser->bonusNotChosen)
                                            <strong class="amount">Not selected {{$topBonusUser->bonusNotChosen}}</strong>
                                        @endif
                                    @else
                                        <strong>None</strong>
                                    @endif
                                </div>
                            </div>
                            <div class="summary-footer">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    @endif
</div>