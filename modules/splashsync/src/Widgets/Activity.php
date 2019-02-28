<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

//====================================================================//
// *******************************************************************//
//                     SPLASH FOR PRESTASHOP                          //
// *******************************************************************//
//                     SHOP ACTIVITY WIDGET                           //
// *******************************************************************//
//====================================================================//

namespace   Splash\Local\Widgets;

use AdminStatsController;
use ArrayObject;
use Configuration;
use Currency;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Models\AbstractWidget;
use SplashSync;

/**
 * Splash Widget - Display of Main Shop Activity
 */
class Activity extends AbstractWidget
{
    /**
     * Define Standard Options for this Widget
     * Override this array to change default options for your widget
     *
     * @var array
     */
    public static $OPTIONS = array(
        "Width" => self::SIZE_XL
    );

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Widget Name (Translated by Module)
     *
     * @var string
     */
    protected static $NAME = "Prestashop Activity Widget";

    /**
     * Widget Description (Translated by Module)
     *
     * @var string
     */
    protected static $DESCRIPTION = "Display Main Activity of your E-Commerce";

    /**
     * Widget Icon (FontAwesome or Glyph ico tag)
     *
     * @var string
     */
    protected static $ICO = "fa fa-map-signs";

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var SplashSync
     */
    private $spl;

    /**
     * @var array
     */
    private $sparkOptions = array(
        "AllowHtml" => true,
        "Width" => self::SIZE_XS
    );

    //====================================================================//
    // Class Main Functions
    //====================================================================//

    /**
     * {@inheritdoc}
     */
    public function get($params = array())
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

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
        $blocks = $this->blocksFactory()->render();
        if (is_array($blocks)) {
            $this->setBlocks($blocks);
        }

        //====================================================================//
        // Publish Widget
        return $this->render();
    }

    //====================================================================//
    // Blocks Generation Functions
    //====================================================================//

    /**
     * Block Building - Inputs Parameters
     *
     * @param null|array|ArrayObject $inputs
     */
    private function buildActivityBlock($inputs = array())
    {
        //====================================================================//
        // Verify Inputs
        if (!is_array($inputs) && !is_a($inputs, "ArrayObject")) {
            $this->blocksFactory()
                ->addNotificationsBlock(array("warning" => "Inputs is not an Array!"));
        }
        if (!isset($inputs["DateStart"]) || !isset($inputs["DateEnd"])) {
            $this->blocksFactory()->addNotificationsBlock(array("warning" => "No Date Range Defined!"));

            return;
        }

        //====================================================================//
        // Init Dates
        $this->importDates($inputs);

        //====================================================================//
        // Load Default Currency
        $this->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));

        //====================================================================//
        // Load Splash Module
        $this->spl = Local::getLocalModule();
        if (false == $this->spl) {
            return false;
        }

        //====================================================================//
        // Build data Array
        $rawData = $this->getData($this->DateStart, $this->DateEnd);
        $refineData = $this->refineData($this->DateStart, $this->DateEnd, $rawData);
        $activityData = $this->addupData($refineData);

        //====================================================================//
        // Build SparkInfo Options
        //====================================================================//
        $this->sparkOptions = array(
            "AllowHtml" => true,
            "Width" => self::SIZE_XS
        );

        //====================================================================//
        // Add SparkInfo Blocks
        $this->blocksFactory()
            ->addSparkInfoBlock(array(
                "title" => $this->spl->l('Sales'),
                "fa_icon" => "line-chart",
                "value" => \Tools::displayPrice($activityData["sales"], $this->currency),
            ), $this->sparkOptions)
            ->addSparkInfoBlock(array(
                "title" => $this->spl->l('Orders'),
                "fa_icon" => "shopping-cart ",
                "value" => $activityData["orders"],
            ), $this->sparkOptions)
            ->addSparkInfoBlock(array(
                "title" => $this->spl->l('Average Cart Value'),
                "fa_icon" => "shopping-cart ",
                "value" => $activityData["average_cart_value"],
            ), $this->sparkOptions)
            ->addSparkInfoBlock(array(
                "title" => $this->spl->l('Net Profit'),
                "fa_icon" => "money",
                "value" => \Tools::displayPrice($activityData["net_profits"], $this->currency),
            ), $this->sparkOptions)
                ;
    }

    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

    private function getData($dateFrom, $dateTo)
    {
        // We need the following figures to calculate our stats
        $tmpData = array(
            'visits' => array(),
            'orders' => array(),
            'total_paid_tax_excl' => array(),
            'total_purchases' => array(),
            'total_expenses' => array()
        );

        $tmpData['visits'] = AdminStatsController::getVisits(false, $dateFrom, $dateTo, 'day');
        $tmpData['orders'] = AdminStatsController::getOrders($dateFrom, $dateTo, 'day');
        $tmpData['total_paid_tax_excl'] = AdminStatsController::getTotalSales($dateFrom, $dateTo, 'day');
        $tmpData['total_purchases'] = AdminStatsController::getPurchases($dateFrom, $dateTo, 'day');
        $tmpData['total_expenses'] = AdminStatsController::getExpenses($dateFrom, $dateTo, 'day');

        return $tmpData;
    }

    private function refineData($dateFrom, $dateTo, $grossData)
    {
        $refinedData = array(
            'sales' => array(),
            'orders' => array(),
            'average_cart_value' => array(),
            'visits' => array(),
            'conversion_rate' => array(),
            'net_profits' => array()
        );

        $rawDateFrom = strtotime($dateFrom);
        $rawDateTo = min(time(), strtotime($dateTo));
        for ($date = $rawDateFrom; $date <= $rawDateTo; $date = strtotime('+1 day', $date)) {
            $this->getRefinedSales($refinedData, $date, $grossData);
            $this->getRefinedProfits($refinedData, $date, $grossData);
        }

        return $refinedData;
    }

    private function addupData($data)
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

    private function getRefinedSales(&$refinedData, $date, $grossData)
    {
        $refinedData['sales'][$date] = 0;
        if (isset($grossData['total_paid_tax_excl'][$date])) {
            $refinedData['sales'][$date] += $grossData['total_paid_tax_excl'][$date];
        }

        $refinedData['orders'][$date] = isset($grossData['orders'][$date]) ? $grossData['orders'][$date] : 0;

        $refinedData['average_cart_value'][$date] = $refinedData['orders'][$date]
                ? $refinedData['sales'][$date] / $refinedData['orders'][$date] : 0;

        $refinedData['visits'][$date] = isset($grossData['visits'][$date]) ? $grossData['visits'][$date] : 0;

        $refinedData['conversion_rate'][$date] = $refinedData['visits'][$date]
                ? $refinedData['orders'][$date] / $refinedData['visits'][$date] : 0;
    }

    private function getRefinedProfits(&$refinedData, $date, $grossData)
    {
        $refinedData['net_profits'][$date] = 0;
        if (isset($grossData['total_paid_tax_excl'][$date])) {
            $refinedData['net_profits'][$date] += $grossData['total_paid_tax_excl'][$date];
        }
        if (isset($grossData['total_purchases'][$date])) {
            $refinedData['net_profits'][$date] -= $grossData['total_purchases'][$date];
        }
        if (isset($grossData['total_expenses'][$date])) {
            $refinedData['net_profits'][$date] -= $grossData['total_expenses'][$date];
        }
    }
}
