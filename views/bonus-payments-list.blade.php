@extends('app')

@section('page-header')
    @include('elements.page-header', ['section_title' => 'Bonus Management', 'page_title' => 'Bonus Payments List'])
@endsection

@section('content')
    <section class="panel">
        <header class="panel-heading">
            <div class="panel-actions">
                <a href="#" class="panel-action panel-action-toggle" data-panel-toggle></a>
                <a href="#" class="panel-action panel-action-dismiss" data-panel-dismiss></a>
            </div>

            <h2 class="panel-title">Bonus Payments List</h2>
        </header>
        <div class="panel-body">
            <a href="{{ route('bonus-management.bulk-pay') }}" class="btn btn-primary">Export Unpaid Bonus</a>
            <br>
            <br>
            <div class="table-responsive">
                <table class="{{isset($table_class) ? $table_class : 'table table-bordered table-striped table-condensed mb-none'}}">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Order Count</th>
                        <th>New Users Count</th>
                        <th>Bonus Cash</th>
                        <th>Approved Count (Online/Manual)</th>
                        <th>Rejected Count (Online/Manual)</th>
                        <th>Not Reviewed Count (Online/Manual)</th>
                        <th>Open (Online/Manual)</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($data as $itm)
                        <tr>
                            <td>{{ $itm->year }} / {{ $itm->month }}</td>
                            <td>{{ $itm->orders_count }}</td>
                            <td>{{ $itm->new_users_count }}</td>
                            <td>{{ $itm->bonus_payout_cash }}</td>
                            <td>
                                {{ $itm->approve_online }} /
                                {{ $itm->approve_manual }}
                            </td>
                            <td>
                                {{ $itm->reject_online }} /
                                {{ $itm->reject_manual }}
                            </td>
                            <td>
                                {{ $itm->not_reviewed_online }} /
                                {{ $itm->not_reviewed_manual }}
                            </td>
                            <td>
                                <a href="/bonus-management/list-payments/{{ $itm->year }}/{{ $itm->month }}/online">Online</a>
                                /
                                <a href="/bonus-management/list-payments/{{ $itm->year }}/{{ $itm->month }}/manual">Manual</a>
                            </td>
                        </tr>
                    @endforeach


                    </tbody>
                </table>
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
            </div>
        </div>
    </section>
@endsection