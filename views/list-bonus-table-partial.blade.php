<div class="table-responsive">
    <table class="{{isset($table_class) ? $table_class : 'table table-bordered table-striped table-condensed mb-none'}}">
        <thead>
        <tr>
            <th>ID</th>
            <th>Date</th>
            @if(isset($show_awarded_to) && $show_awarded_to)
                <th>Awarded To</th>
            @endif
            <th>Status</th>
            <th>Workflow Status</th>
            <th>Bonus Type</th>
            <th>Bonus Payout</th>
        </tr>
        </thead>
        <tbody>
        @foreach($list as $item)
            @if (!$item->bonusPayout || !$item->bonusPayout->hidden)
                <tr name="bonus_row">
                    <td><a href='/bonus-management/view/{{$item->id}}'>#{{1024 + $item->id}}</a></td>
                    <td>{{$item->created_at->toDateString()}}</td>
                    @if(isset($show_awarded_to) && $show_awarded_to)
                        <td>{{$item->user->name}}</td>
                    @endif
                    <td>{{$item->bonusStatus->name}}</td>
                    <td>{{$item->workflow_status}}</td>
                    @if($item->bonusPayout)
                        <td>{{$item->bonusPayout->friendly_name}}</td>
                    @else
                        <td>Bonus not yet chosen</td>
                        @endif
                        </td>
                        <td>{{$item->bonusType->friendly_name}}</td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>
    {!! $list->render() !!}
</div>
