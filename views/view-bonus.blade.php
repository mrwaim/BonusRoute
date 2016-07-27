@extends('app')

@section('content')

    @include('elements.success-message-partial')

    <div class="row">
        <div class="col-md 12 col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    <h2 class="panel-title">View Bonus</h2>
                </header>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="bonus">
                                <div class="bonus-content">
                                    ID
                                </div>
                                <div class="bonus-content content2">
                                    {{$item->id + 1024}}
                                </div>
                            </div>
                            <div class="bonus">
                                <div class="bonus-content">
                                    Name
                                </div>
                                <div class="bonus-content content2">
                                    {{$item->bonusType->friendly_name}}
                                </div>
                            </div>
                            <div class="bonus">
                                <div class="bonus-content">
                                    Description
                                </div>
                                <div class="bonus-content content2">
                                    {{$item->bonusType->description}}
                                </div>
                            </div>
                            <div class="bonus">
                                <div class="bonus-content">
                                    Status
                                </div>
                                <div class="bonus-content content2">
                                    {{$item->bonusStatus ? $item->bonusStatus->name : 'Active'}}
                                </div>
                            </div>
                            <div class="bonus">
                                <div class="bonus-content">
                                    Workflow Status
                                </div>
                                <div class="bonus-content content2">
                                    {{{$item->workflow_status}}}
                                </div>
                            </div>
                            <div class="bonus">
                                <div class="bonus-content">
                                    Award To
                                </div>
                                <div class="bonus-content content2">
                                    @link($item->user) {{$item->awarded_to_user_id == $user->id ? ' (You)' : ''}}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bonus">
                                <div class="bonus-content">
                                    Date Time
                                </div>
                                <div class="bonus-content content2">
                                    {{$item->created_at}}
                                </div>
                            </div>
                            <div class="bonus">
                                <div class="bonus-content">
                                    Date Time Updated
                                </div>
                                <div class="bonus-content content2">
                                    {{$item->updated_at}}
                                </div>
                            </div>
                            @if ($item->orderItem->order)
                                <div class="bonus">
                                    <div class="bonus-content">
                                        Order
                                    </div>
                                    <div class="bonus-content content2">
                                        @if($item->bonusType->key == 'restock-bonus')
                                            @if($item->parent_bonus_id)
                                                @olink($item->orderItem->order), paired
                                                with @olink($item->parentBonus->orderItem->order)
                                            @elseif (count($item->childBonuses) > 0)
                                                @olink($item->orderItem->order), paired from
                                                @foreach ($item->childBonuses as $child)
                                                    @olink($child->orderItem->order)
                                                @endforeach
                                            @else
                                                @olink($item->orderItem->order) (Pair not found)
                                            @endif
                                        @else
                                            @olink($item->orderItem->order)
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="bonus">
                                <div class="bonus-content">
                                    Payout
                                </div>
                                <div class="bonus-content content2">
                                    @if($item->bonusStatus->name != 'Cancelled')
                                        @if($item->bonusPayout)
                                            {{$item->bonusPayout->friendly_name}}

                                            @if($item->bonusPayout->hidden)
                                                (Hidden)
                                            @endif
                                        @else
                                            @if($item->awarded_to_user_id == $user->id)
                                                Select Payout
                                                <ul>
                                                    @foreach($item->bonusType->bonusTypeBonusPayoutOptions as $option)
                                                        <li>
                                                            <a href='/bonus-management/choose-payout/{{$item->id}}/{{$option->payout_id}}'>
                                                                @if ($option->bonusPayout->bonusCurrency->key == 'gold')
                                                                    <img src="{{asset('/images/Gold-Bar-icon.png')}}"
                                                                         width="40" class="img-responsive">
                                                                @else
                                                                    <img src="{{asset('/images/Paper-Money-icon.png')}}"
                                                                         width="40" class="img-responsive">
                                                                @endif
                                                                {{$option->bonusPayout->friendly_name}}</a></li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                Payout not selected
                                            @endif
                                        @endif
                                    @else
                                        Bonus Cancelled
                                    @endif
                                </div>
                            </div>

                            <div class="bonus">
                                <div class="bonus-content">
                                    Bonus Amount
                                </div>
                                <div class="bonus-content content2">
                                    @if ($item->bonusPayout)
                                        {{ $item->bonusPayout->payout }}
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    @if($auth->admin && (!$item->bonusStatus || $item->bonusStatus->name != 'Cancelled'))
        <div class="row">
            <div class="col-md 12 col-lg-12">
                <section class="panel">
                    <header class="panel-heading">
                        <h2 class="panel-title">Cancel bonus</h2>
                    </header>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                Warning - You should only cancel a bonus if you canceled the order
                            </div>
                            <form method="POST" action="{{ url('/bonus-management/cancel-bonus') }}">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="bonus_id" value="{{$item->id}}">
                                @include('elements.confirm-dialog', ['confirmId' => 'CancelBonus', 'confirmTitle' => 'Confirm cancel bonus', 'confirmText' => 'Are you sure that you want to cancel this bonus?', 'confirmAction' => 'Cancel Bonus'])
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    @endif
@endsection