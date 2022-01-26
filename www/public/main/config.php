<?php
defined('TheEnd') || die('Oops, has error!');

$this->addCss([
    'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
    'assets/css/style.css',
    'assets/css/custom.css',
])
    ->addJs([
        'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js',
        'assets/js/notify.min.js',
        'assets/js/lib.scripts.js',
        'assets/js/custom.js'
    ])
    ->setTitle('Короткие url - сокращение ссылок')
    ->setDescription('Сокращение длинных url ссылок для сайтов')
    ->setKeywords('short url fast, short url, suf.pw, shortener, сокращение ссылок, сокращение урл, сократить url, короткие ссылки, сократить ссылку, короткий урл, suf, small url');