<div class="table-responsive">
    <table class="{{isset($table_class) ? $table_class : 'table table-bordered table-striped table-condensed mb-none'}}">
        <thead>
        <tr>
            <th>No</th>
            <th>Name</th>
            <th>Description</th>
            @if($auth->admin)
                <th>Delete</th>
            @endif
        </tr>
        </thead>
        <tbody>
        <?php $no = 1?>
        @foreach($bonusCategories as $item)
            <tr>
                <td>{{ $no++ }}</td>
                <td>{{ $item->friendly_name }}</td>
                <td>{{ $item->description }}</td>
                @if($auth->admin)
                    <td>
                        <a href="/bonus-management/delete-bonus-category/{{ $item->id }}" class="panel-action panel-action-dismiss"></a>
                    </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
