<?php

namespace Drafterbit\Bundle\BlogBundle\System\Search;

use Drafterbit\System\Search\ResultFormatterInterface;

class ResultFormatter implements ResultFormatterInterface
{
    protected $container;
    
    public function __construct($container)
    {
        $this->container = $container;
    }

    function getUrl($item)
    {    
        $time = strtotime($item['created_at']);
        $year = date('Y', $time);
        $month = date('m', $time);
        $date = date('d', $time);

        $slug = $item['slug'];

        return $this->container->get('router')->generate(
            'dt_blog_post_front_view',
            ['year' => $year, 'month' => $month, 'date' => $date, 'slug' => $slug]
        );
    }

    function getTitle($item)
    {
        return $item['title'];
    }

    function getSummary($item)
    {
        // only diplay some text
        return substr(strip_tags($item['content']), 0, 250);
    }
}