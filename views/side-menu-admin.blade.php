@include('elements.side-menu-parent-item', [
'folder' => 'bonus-management',
'menu' => 'Bonus',
'menuIcon' => 'fa-trophy',
'children' => [ [
'filter' => 'all',
'url' => 'list/all',
'menu' => 'Bonus Awarded',
], [
'filter' => 'reorder',
'url' => 'list/reorder',
'menu' => 'Bonus Upon Reorder',
], [
'page' => 'bonus-payments-list',
'url' => 'bonus-payments-list',
'menu' => 'Bonus Payments List',
], [
'page' => 'view',
'menu' => 'Bonus Details',
]
]])
