<?php return [

'%admin%' => [
	'subRoutes' => [
		'blog' => [
			'subRoutes' => [
				'index' => ['controller' => '@blog\Blog::index'],
				'create' => ['controller' => '@blog\Blog::create'],
				'edit/{id}' => ['controller' => '@blog\Blog::edit'],
				'data/{status}.json' => ['controller' => '@blog\Blog::filter'],
				'save' => ['controller' => '@blog\Blog::save', 'csrf' => true],
				'trash' => ['controller' => '@blog\Blog::trash', 'csrf' => true],
				
				'comments' => ['controller' => '@blog\Comment::index'],
			]
		],

		'comments' => [
			'subRoutes' => [
				'data/{status}.json' => ['controller' => '@blog\Comment::filter'],
				'trash' => ['controller' => '@blog\Comment::trash', 'csrf' => true],
				'status' => ['controller' => '@blog\Comment::status', 'csrf' => true],
				'quick-reply' => ['controller' => '@blog\Comment::quickReply', 'csrf' => true],
				'quick-trash' => ['controller' => '@blog\Comment::quickTrash', 'csrf' => true],
			]
		]
	]
],


'blog' => [
	'controller' => '@blog\Frontend::index'
],
	
'blog/{yyyy}/{mm}/{slug}' => [
	'controller' => '@blog\Frontend::view',
	'methods' => 'get',
	'requirements' => [
		'yyyy' => '\d{4}',
		'mm' => '\d{2}'
		]
	],

'blog/comment/submit' => ['controller' => '@blog\Comment::submit', 'methods' => 'post'],

];