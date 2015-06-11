<?php

namespace Drafterbit\Bundle\SystemBundle\Dashboard;

class InfoPanel extends Panel {

    public function getView()
    {
        
        $request = $this->kernel->getContainer()->get('request_stack')->getCurrentRequest();


        $info['Time'] = date('H:i:s');
        $info['OS'] = $this->getOs();
        $info['Theme'] = $this->kernel->getContainer()->getParameter('theme');
        $info['PHP'] = phpversion();
        //$stat['DB'] = $this['db']->getServerVersion();
        $info['Server'] = $request->server->get('SERVER_SOFTWARE');

        return $this->renderView('DrafterbitSystemBundle:Panel:info.html.twig', [
            'info' => $info
        ]);
    }

    public function getName()
    {
        return 'info';
    }

    private function getOs()
    {
        $os_platform = "Unknown";

        $os_array = [
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        ];

        foreach ($os_array as $regex => $value) {
            $request = $this->kernel->getContainer()->get('request_stack')->getCurrentRequest();
            if (preg_match($regex, $request->server->get('HTTP_USER_AGENT'))) {
                $os_platform = $value;
            }
        }

        return $os_platform;
    }
}