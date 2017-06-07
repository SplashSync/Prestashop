<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 * 
 **/

use Splash\Tests\Tools\ObjectsCase;

/**
 * @abstract    Local Objects Test Suite - Specific Verifications for Invoices Objects. 
 *
 * @author SplashSync <contact@splashsync.com>
 */
class L01InvoicesTest extends ObjectsCase {
    
    /**
     * @dataProvider ObjectFieldsProvider
     */
    public function testFromModule()
    {
        $this->assertTrue(True);
    }
    
    /**
     * @dataProvider ObjectFieldsProvider
     */
    public function testSingleFieldFromService()
    {
        $this->assertTrue(True);
    }
    
    
}
