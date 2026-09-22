<?php \View::element('slideshow/slideshow', [
    'slides' => [
        '<div>slide</div>',
        '<div>slide</div>',
        '<div>slide</div>',
        '<div>slide</div>',
        '<div>slide</div>',
        '<div>slide</div>',
        '<div>slide</div>',
        '<div>slide</div>',
        '<div>slide</div>'
    ],
    'options' => [
        'mobile' => 1,
        'desktop' => 3,
        'hd' => 4,
        'gap' => [
            'mobile' => 10,
            'desktop' => 20,
            'hd' => 30
        ],
        'peek' => [
            'mobile' => 50,
            'desktop' => 100,
            'hd' => 150
        ]
    ]
], 'lgt_toolkit');
