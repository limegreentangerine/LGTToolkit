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
        'desktop' => 2,
        'hd' => 3,
        'gap' => [
            'mobile' => 10,
            'desktop' => 20,
            'hd' => 30
        ]
    ]
], 'lgt_toolkit');
