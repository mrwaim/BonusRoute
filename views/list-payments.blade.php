@extends('app')

@section('page-header')
    @include('elements.page-header', ['section_title' => 'Bonus Management', 'page_title' => 'List Payments'])
@endsection

@section('content')
    @if(empty($payments_approvals))
        <div class="row">
            <div class="alert alert-info">
                <p class="center">
                    Bonus Payment Processing Completed.
                    @if(count($data))
                        <a href="/bonus-management/excel/{{ $report }}/{{ $filter }}" class="btn btn-default">Get
                            Excel</a>
                        <a href="/bonus-management/bulk-pay/{{ $report }}/{{ $filter }}" class="btn btn-default">Billplz Format</a>
                        <a href="/bonus-management/txt/{{ $report }}/{{ $filter }}" class="btn btn-default">Get
                            Txt</a>
                    @endif
                </p>
            </div>
        </div>
    @endif
    <section class="panel">
        <header class="panel-heading">
            <div class="panel-actions">
                <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
                <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
            </div>

            <h2 class="panel-title">List Payments</h2>
        </header>
        @include('elements.error-message-partial')
        <div class="panel-body">
            <div class="table-responsive">
                <table class="{{isset($table_class) ? $table_class : 'table table-bordered table-striped table-condensed mb-none'}}">
                    <thead>
                    <tr>
                        <th class="text-center">User</th>
                        <th class="text-center">Amount of Bonus</th>
                        <th class="text-center">Amount of Orders</th>
                        <th class="text-center">Amount of Introductions</th>
                        <th class="text-center">Approval</th>
                        <th class="text-center">Payment</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($data as $itm)
                        <tr>
                            <td>@link($itm->user)</td>
                            <td>{{ $itm->bonus_payout_cash }}</td>
                            <td>{{ $itm->orders_count }}</td>
                            <td>{{ $itm->introductions_count }}</td>
                            <td>
                                <a href="/bonus-management/set-payments-approvals?status=approve&id={{ $itm->id }}&user_type={{ $user_type }}"
                                   class="set-list-payments {{ ($itm->approved_state === 'approve')? 'text-bold':'' }}">approve</a>
                                /
                                <a href="/bonus-management/set-payments-approvals?status=reject&id={{ $itm->id }}&user_type={{ $user_type }}"
                                   class="set-list-payments {{ ($itm->approved_state === 'reject')? 'text-bold':'' }}">reject</a>
                                /
                                <a href="/bonus-management/set-payments-approvals?status=not-reviewed&id={{ $itm->id }}&user_type={{ $user_type }}"
                                   class="set-list-payments {{ ($itm->approved_state === 'not-reviewed' || empty($itm->approved_state))? 'text-bold':'' }}">not
                                    reviewed</a>
                            </td>
                            <td>
                                <a href="{{ route('bonus-management.payment-state', ['status' => 'paid', 'id'=> $itm->id, 'user_type' => $user_type]) }}"
                                   class="set-list-payments {{ ($itm->payment_state === 'paid')? 'text-bold':'' }}">Paid</a>
                                /
                                <a href="{{ route('bonus-management.payment-state', ['status' => 'unpaid', 'id'=> $itm->id, 'user_type' => $user_type]) }}"
                                   class="set-list-payments {{ ($itm->payment_state === 'unpaid' || empty($itm->payment_state))? 'text-bold':''}}">Unpaid</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <div>
        <form action="/bonus-management/set-approvals-all" method="post">
            <button class="btn btn-success approve-all-bonuses" type="submit">Approve All</button>
            <input type="hidden" name="monthly_report_id" value="{{ $report }}">
            <input type="hidden" name="type" value="{{ $user_type }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
        </form>

    </div>
@endsection