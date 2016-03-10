<div class="row">
    <section class="panel">
        <header class="panel-heading">
            <div class="panel-actions">
                <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
                <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
            </div>

            <h2 class="panel-title">Bonus upon reorder</h2>
        </header>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-condensed mb-none">
                    <thead>
                    <tr>
                        @if($show_user)
                            <th>User</th>
                        @endif
                        <th>Bonus Type</th>
                        <th>Bonus Value</th>
                        @if(!$show_user)
                            <th>For Order</th>
                        @endif
                        <th>Expires</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($list as $item)
                        <tr name="bonus_command_row">
                            @if($show_user)
                                <th>@link($item->user)</th>
                            @endif
                            <td>
                                @if($item->name == 'payPairRestockBonus')
                                    Restock Bonus
                                @elseif($item->name == 'payFullReferralRestockBonus')
                                    Referral Bonus
                                @elseif($item->name == 'payPartialReferralRestockBonus')
                                    Partial Referral Bonus
                                @elseif($item->name == 'upgradePartialReferralBonusToFull')
                                    Upgrade 1 Referral bonus from 20% to 100%
                                @else
                                    {{$item->name}}
                                @endif
                            </td>
                            <td>
                                @if($item->name == 'payPairRestockBonus')
                                    1 gm Gold or RM 150
                                @elseif($item->name == 'payFullReferralRestockBonus')
                                    RM 80
                                @elseif($item->name == 'payPartialReferralRestockBonus')
                                    20% of RM 80
                                @elseif($item->name == 'upgradePartialReferralBonusToFull')
                                    80% of RM 80
                                @else
                                    {{$item->name}}
                                @endif
                            </td>

                            @if(!$show_user)
                                @if ($item->order->id)
                                    <td>@olink($item->order)</td>
                                @else
                                    <td>New order</td>
                                @endif
                            @endif

                            <td>{{$item->getExpiry()}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

