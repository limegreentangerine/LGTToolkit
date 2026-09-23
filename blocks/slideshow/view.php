<?php \View::element('slideshow/slideshow', [
    'slides' => [
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
        new stdClass(),
    ],
    'options' => [
        'mobile' => 1,
        'desktop' => 2,
        'hd' => 3,
        'gap' => [
            'mobile' => 10,
            'desktop' => 20,
            'hd' => 30,
        ],
        'template' => [
            'path' => 'slideshow/slides/image',
            'pkgHandle' => 'lgt_toolkit'
        ]
    ],
], 'lgt_toolkit');
