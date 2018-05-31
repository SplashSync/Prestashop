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
                    
//====================================================================//
// *******************************************************************//
//                     SPLASH FOR PRESTASHOP                          //
// *******************************************************************//
//                     SHOP ACTIVITY WIDGET                           //
// *******************************************************************//
//====================================================================//

namespace   Splash\Local\Widgets;

use Splash\Models\WidgetBase;
use Splash\Core\SplashCore      as Splash;

use AdminStatsController;
use Configuration;

/**
 * @abstract    Splash Widget - Display of Main Shop Activity
 */
class Activity extends WidgetBase
{
    
    //====================================================================//
    // Object Definition Parameters
    //====================================================================//
    
    /**
     *  Widget Name (Translated by Module)
     */
    protected static $NAME            =  "Prestashop Activity Widget";
    
    /**
     *  Widget Description (Translated by Module)
     */
    protected static $DESCRIPTION     =  "Display Main Activity of your E-Commerce";
    
    /**
     *  Widget Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO            =  "fa fa-map-signs";
    
    //====================================================================//
    // Define Standard Options for this Widget
    // Override this array to change default options for your widget
    public static $OPTIONS       = array(
        "Width"     =>      self::SIZE_XL
    );
    
    //====================================================================//
    // General Class Variables
    //====================================================================//

    //====================================================================//
    // Class Main Functions
    //====================================================================//
    
    /**
     *  @abstract     Return requested Customer Data
     *
     *  @param        array   $params               Search parameters for result List.
     *                        $params["start"]      Maximum Number of results
     *                        $params["end"]        List Start Offset
     *                        $params["groupby"]    Field name for sort list (Available fields listed below)

     */
    public function get($params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Load Default Language
        Splash::local()->LoadDefaultLanguage();

        //====================================================================//
        // Setup Widget Core Informations
        //====================================================================//

        $this->setTitle($this->getName());
        $this->setIcon($this->getIcon());
        
        //====================================================================//
        // Build Activity Block
        //====================================================================//
        $this->buildActivityBlock($params);
        
        //====================================================================//
        // Set Blocks to Widget
        $this->setBlocks($this->BlocksFactory()->Render());

        //====================================================================//
        // Publish Widget
        return $this->Render();
    }
        

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//


  
    /**
    *   @abstract     Block Building - Inputs Parameters
    */
    private function buildActivityBlock($Inputs = array())
    {

        //====================================================================//
        // Verify Inputs
        if (!is_array($Inputs) && !is_a($Inputs, "ArrayObject")) {
            $this->BlocksFactory()
                    ->addNotificationsBlock(array("warning" => "Inputs is not an Array! Is " . get_class($Inputs)));
        }
        if (!isset($Inputs["DateStart"]) || !isset($Inputs["DateEnd"])) {
            $this->BlocksFactory()->addNotificationsBlock(array("warning" => "No Date Range Defined!"));
            return;
        }
        
        //====================================================================//
        // Init Dates
        $this->importDates($Inputs);
        
        //====================================================================//
        // Load Default Currency
        $this->currency = new \Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        
        //====================================================================//
        // Load Splash Module
        $this->spl = Splash::local()->getLocalModule();
        if ($this->spl == false) {
            return false;
        }

        //====================================================================//
        // Build data Array
        $RawData                =   $this->getData($this->DateStart, $this->DateEnd);
        $RefineData             =   $this->refineData($this->DateStart, $this->DateEnd, $RawData);
        $ActivityData           =   $this->addupData($RefineData);

        //====================================================================//
        // Build SparkInfo Options
        //====================================================================//
        $this->SparkOptions = array(
            "AllowHtml"         =>  true,
            "Width"             =>  self::SIZE_XS
        );
            
        //====================================================================//
        // Add SparkInfo Blocks
        $this->BlocksFactory()
                
                ->addSparkInfoBlock(array(
                    "title"     =>      $this->spl->l('Sales'),
                    "fa_icon"   =>      "line-chart",
                    "value"     =>      \Tools::displayPrice($ActivityData["sales"], $this->currency),
                    ), $this->SparkOptions)

                ->addSparkInfoBlock(array(
                    "title"     =>      $this->spl->l('Orders'),
                    "fa_icon"   =>      "shopping-cart ",
                    "value"     =>      $ActivityData["orders"],
                    ), $this->SparkOptions)
                
                ->addSparkInfoBlock(array(
                    "title"     =>      $this->spl->l('Average Cart Value'),
                    "fa_icon"   =>      "shopping-cart ",
                    "value"     =>      $ActivityData["average_cart_value"],
                    ), $this->SparkOptions)
                
                ->addSparkInfoBlock(array(
                    "title"     =>      $this->spl->l('Net Profit'),
                    "fa_icon"   =>      "money",
                    "value"     =>      \Tools::displayPrice($ActivityData["net_profits"], $this->currency),
                    ), $this->SparkOptions)
                ;
    }
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

    
    protected function getData($date_from, $date_to)
    {
        // We need the following figures to calculate our stats
        $tmp_data = array(
            'visits' => array(),
            'orders' => array(),
            'total_paid_tax_excl' => array(),
            'total_purchases' => array(),
            'total_expenses' => array()
        );

                $tmp_data['visits'] = AdminStatsController::getVisits(false, $date_from, $date_to, 'day');
                $tmp_data['orders'] = AdminStatsController::getOrders($date_from, $date_to, 'day');
                $tmp_data['total_paid_tax_excl'] = AdminStatsController::getTotalSales($date_from, $date_to, 'day');
                $tmp_data['total_purchases'] = AdminStatsController::getPurchases($date_from, $date_to, 'day');
                $tmp_data['total_expenses'] = AdminStatsController::getExpenses($date_from, $date_to, 'day');

        return $tmp_data;
    }

    protected function refineData($date_from, $date_to, $gross_data)
    {
        $refined_data = array(
            'sales' => array(),
            'orders' => array(),
            'average_cart_value' => array(),
            'visits' => array(),
            'conversion_rate' => array(),
            'net_profits' => array()
        );

        $from = strtotime($date_from);
        $to = min(time(), strtotime($date_to));
        for ($date = $from; $date <= $to; $date = strtotime('+1 day', $date)) {
//                    Splash::log()->www("Date" , $date );
            $refined_data['sales'][$date] = 0;
            if (isset($gross_data['total_paid_tax_excl'][$date])) {
                $refined_data['sales'][$date] += $gross_data['total_paid_tax_excl'][$date];
            }

            $refined_data['orders'][$date] = isset($gross_data['orders'][$date]) ? $gross_data['orders'][$date] : 0;

            $refined_data['average_cart_value'][$date] = $refined_data['orders'][$date]
                    ? $refined_data['sales'][$date] / $refined_data['orders'][$date] : 0;

            $refined_data['visits'][$date] = isset($gross_data['visits'][$date]) ? $gross_data['visits'][$date] : 0;

            $refined_data['conversion_rate'][$date] = $refined_data['visits'][$date]
                    ? $refined_data['orders'][$date] / $refined_data['visits'][$date] : 0;

            $refined_data['net_profits'][$date] = 0;
            if (isset($gross_data['total_paid_tax_excl'][$date])) {
                $refined_data['net_profits'][$date] += $gross_data['total_paid_tax_excl'][$date];
            }
            if (isset($gross_data['total_purchases'][$date])) {
                $refined_data['net_profits'][$date] -= $gross_data['total_purchases'][$date];
            }
            if (isset($gross_data['total_expenses'][$date])) {
                $refined_data['net_profits'][$date] -= $gross_data['total_expenses'][$date];
            }
        }
        return $refined_data;
    }

    protected function addupData($data)
    {
        $summing = array(
            'sales' => 0,
            'orders' => 0,
            'average_cart_value' => 0,
            'visits' => 0,
            'conversion_rate' => 0,
            'net_profits' => 0
        );

        $summing['sales'] = array_sum($data['sales']);
        $summing['orders'] = array_sum($data['orders']);
        $summing['average_cart_value'] = $summing['sales'] ? $summing['sales'] / $summing['orders'] : 0;
        $summing['visits'] = array_sum($data['visits']);
        $summing['conversion_rate'] = $summing['visits'] ? $summing['orders'] / $summing['visits'] : 0;
        $summing['net_profits'] = array_sum($data['net_profits']);

        return $summing;
    }
}
