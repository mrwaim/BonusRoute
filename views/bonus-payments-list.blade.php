@extends('app')

@section('page-header')

    <h2>Bonus Management</h2>

    <div class="right-wrapper pull-right">
        <ol class="breadcrumbs">
            <li>
                <a href="index.html">
                    <i class="fa fa-home"></i>
                </a>
            </li>
            <li><span>Bonus Management</span></li>
            <li><span>Bonus Payments List</span></li>

        </ol>

        <div class="sidebar-right-toggle"></div>
    </div>
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