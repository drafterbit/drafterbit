<?php namespace Drafterbit\Extensions\System\Asset;

use Drafterbit\Component\Asset\AssetInterface;  
use Drafterbit\Component\Asset\Filter\FilterInterface;

class DrafterbitChosenFilter implements FilterInterface
{
    protected $path;
    public function __construct($path)
    {
        $this->path = $path;
    }

    public function filterLoad(AssetInterface $asset)
    {
    }


    public function filterDump(AssetInterface $asset)
    {
        $content = $asset->getContent();
        $content = str_replace('chosen-sprite.png', base_url($this->path.'/chosen/chosen-sprite.png'), $content);
        $content = str_replace('chosen-sprite@2x.png', base_url($this->path.'/chosen/chosen-sprite@2x.png'), $content);
        $asset->setContent($content);
    }
}