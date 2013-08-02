<?php
/**
 * This file is part of the C2iS <http://wwww.c2is.fr/> checkLampConf project.
 * André Cianfarani <a.cianfarani@c2is.fr>
 */


namespace tests\units;
$p = getcwd();
file_put_contents("/tmp/andre.log",$p);
require_once 'vendor/bin/atoum';

include './checklampconf.php';

use \mageekguy\atoum;

class Apache extends atoum\test
{
    public function testSay()
    {
        $this->string("Hello World!")->isEqualTo('Hello World!')
        ;
    }
}