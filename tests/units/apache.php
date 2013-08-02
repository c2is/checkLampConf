<?php
/**
 * This file is part of the C2iS <http://wwww.c2is.fr/> checkLampConf project.
 * André Cianfarani <a.cianfarani@c2is.fr>
 */


namespace tests\units;
echo "hhhhh";
var_dump(getcwd());
require_once '/var/www/PHPCI/vendor/bin/atoum';

include './qsdchecklampconf.php';

use \mageekguy\atoum;

class Apache extends atoum\test
{
    public function testSay()
    {
        $this->string("Hello World!")->isEqualTo('Hello World!')
        ;
    }
}