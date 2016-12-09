<?php

use Splash\Tests\Tools\ObjectsCase;
use Splash\Client\Splash;

/**
 * @abstract    Local Objects Test Suite - Specific Verifications for Invoices Objects. 
 *
 * @author SplashSync <contact@splashsync.com>
 */
class L01InvoicesTest extends ObjectsCase {
    
    /**
     * @dataProvider ObjectFieldsProvider
     */
    public function testFromModule($ObjectType, $Field)
    {
        $this->assertTrue(False);
    }
    
    /**
     * @dataProvider ObjectFieldsProvider
     */
    public function testSingleFieldFromService($ObjectType, $Field)
    {
        $this->assertTrue(True);
    }
    
    
}
