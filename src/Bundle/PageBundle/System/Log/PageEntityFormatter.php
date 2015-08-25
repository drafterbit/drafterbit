<?php

namespace Drafterbit\Bundle\PageBundle\System\Log;

use Drafterbit\System\Log\BaseEntityFormatter;

class PageEntityFormatter extends BaseEntityFormatter
{
    public function getName()
    {
        return 'page';
    }

    public function format($id)
    {
        $em = $this->getKernel()->getContainer()->get('doctrine')->getManager();
        $post = $em->getRepository('PageBundle:Page')->find($id);

        $label = $post->getTitle();

        $url = $this->getKernel()
            ->getContainer()
            ->get('router')
            ->generate('dt_page_edit', ['id' => $id]);

        if($label) {
            return '<a href="'.$url.'">'.$label.'</a>';
        }

        return '<em>'.__('unsaved').'</em>';
    }
}
