@if($config->has_bonus)
    @include('elements.side-menu-parent-item', [
    'folder' => 'bonus-management',
    'menu' => 'Bonus',
    'menuIcon' => 'fa-trophy',
    'menuId' => 'bonus_menu',
    'children' => [ [
    'filter' => 'me',
    'url' => 'list/me',
    'menu' => 'Bonus Rewarded to You',
    ], [
    'filter' => 'down-line',
    'url' => 'list/down-line',
    'menu' => 'Bonus rewarded to your Downliner',
    ], [
    'page' => 'view',
    'menu' => 'Bonus Details',
    ]
    ]])
@endif