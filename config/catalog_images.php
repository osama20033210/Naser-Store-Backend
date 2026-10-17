<?php

$assets = [];
foreach (json_decode(file_get_contents(resource_path('catalog-images/sources.json')), true, flags: JSON_THROW_ON_ERROR) as $photo) {
    $assets[$photo['key'].'.jpg'] = [
        'author' => $photo['author'] ?: 'Pexels contributor',
        'source_url' => $photo['page'],
        'license_url' => $photo['license'],
    ];
}

return [
    'assets' => $assets,
    // Match both identifiers so a reused SKU never silently receives another product's photo.
    'products' => [
        'DESK-001' => ['name' => 'Standing desk', 'file' => 'desk.jpg'],
        'CHAIR-001' => ['name' => 'Ergonomic chair', 'file' => 'chair.jpg'],
        'LAMP-001' => ['name' => 'Task lamp', 'file' => 'task-lamp.jpg'],
        'DEMO-P01' => ['name' => 'Adjustable desk (Demo)', 'file' => 'desk.jpg'],
        'DEMO-P02' => ['name' => 'Office chair (Demo)', 'file' => 'chair.jpg'],
        'DEMO-P03' => ['name' => 'Desk organizer (Demo)', 'file' => 'desk-organizer.jpg'],
        'DEMO-P04' => ['name' => 'Monitor arm (Demo)', 'file' => 'monitor-arm.jpg'],
        'DEMO-P05' => ['name' => 'Task lamp (Demo)', 'file' => 'task-lamp.jpg'],
        'DEMO-P06' => ['name' => 'Floor lamp (Demo)', 'file' => 'floor-lamp.jpg'],
        'DEMO-P07' => ['name' => 'Reading light (Demo)', 'file' => 'reading-light.jpg'],
        'DEMO-P08' => ['name' => 'Wall light (Demo)', 'file' => 'wall-light.jpg'],
        'DEMO-P09' => ['name' => 'Desk mat (Demo)', 'file' => 'desk-mat.jpg'],
        'DEMO-P10' => ['name' => 'Laptop stand (Demo)', 'file' => 'desk.jpg'],
        'DEMO-P11' => ['name' => 'Cable organizer (Demo)', 'file' => 'cable-organizer.jpg'],
        'DEMO-P12' => ['name' => 'Storage tray (Demo)', 'file' => 'storage-tray.jpg'],
        'DEMO-P13' => ['name' => 'Drawer cabinet (Demo)', 'file' => 'drawer-cabinet.jpg'],
        'DEMO-P14' => ['name' => 'Book shelf (Demo)', 'file' => 'bookshelf.jpg'],
        'DEMO-P15' => ['name' => 'Storage box (Demo)', 'file' => 'storage-box.jpg'],
        'DEMO-P16' => ['name' => 'Wall shelf (Demo)', 'file' => 'wall-shelf.jpg'],
        'DEMO-P17' => ['name' => 'Notebook set (Demo)', 'file' => 'notebook.jpg'],
        'DEMO-P18' => ['name' => 'Pen set (Demo)', 'file' => 'pens.jpg'],
        'DEMO-P19' => ['name' => 'Weekly planner (Demo)', 'file' => 'planner.jpg'],
        'DEMO-P20' => ['name' => 'Document folder (Demo)', 'file' => 'folder.jpg'],
        'DEMO-P21' => ['name' => 'Footrest (Demo)', 'file' => 'footrest.jpg'],
        'DEMO-P22' => ['name' => 'Seat cushion (Demo)', 'file' => 'seat-cushion.jpg'],
        'DEMO-P23' => ['name' => 'Acoustic panel (Demo)', 'file' => 'acoustic-panel.jpg'],
        'DEMO-P24' => ['name' => 'Coat stand (Demo)', 'file' => 'coat-stand.jpg'],
    ],
    'categories' => [
        'Workspace' => 'workspace.jpg',
        'Demo / Workspace' => 'workspace.jpg',
        'Demo / Lighting' => 'task-lamp.jpg',
        'Demo / Accessories' => 'accessories.jpg',
        'Demo / Storage' => 'bookshelf.jpg',
        'Demo / Stationery' => 'notebook.jpg',
        'Demo / Home office' => 'chair.jpg',
    ],
];

