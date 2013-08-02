<?php
/**
 * This file is part of the C2iS <http://wwww.c2is.fr/> checkLampConf project.
 * André Cianfarani <a.cianfarani@c2is.fr>
 */


namespace tests\units;

require_once 'vendor/bin/atoum';

include realpath(__DIR__) . '/../../checklampconf.php';

use \mageekguy\atoum;

class Apache extends atoum\test
{
    public function testSay()
    {
        $this->string("Hello Worl")->isEqualTo('Hello World!')
        ;
    }
}