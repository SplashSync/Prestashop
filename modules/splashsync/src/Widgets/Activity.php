<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
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

namespace Splash\Local\Widgets;

use AdminStatsController;
use Configuration;
use Currency;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Local;
use Splash\Models\AbstractWidget;
use SplashSync;

/**
 * Splash Widget - Display of Main Shop Activity
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Activity extends AbstractWidget
{
    /**
     * Define Standard Options for this Widget
     * Override this array to change default options for your widget
     *
     * @var array
     */
    public static array $options = array(
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
    protected static string $name = "Prestashop Activity Widget";

    /**
     * Widget Description (Translated by Module)
     *
     * @var string
     */
    protected static string $description = "Display Main Activity of your E-Commerce";

    /**
     * Widget Icon (FontAwesome or Glyph ico tag)
     *
     * @var string
     */
    protected static string $ico = "fa fa-map-signs";

    /**
     * @var Currency
     */
    private Currency $currency;

    /**
     * @var SplashSync
     */
    private SplashSync $spl;

    /**
     * @var array
     */
    private array $sparkOptions = array(
        "AllowHtml" => true,
        "Width" => self::SIZE_XS
    );

    //====================================================================//
    // Class Main Functions
    //====================================================================//

    /**
     * {@inheritdoc}
     */
    public function get(array $parameters = array()): ?array
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
        $this->buildActivityBlock($parameters);

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
     * @param array $inputs
     *
     * @return void
     */
    private function buildActivityBlock(array $inputs = array()): void
    {
        //====================================================================//
        // Verify Inputs
        if (!isset($inputs["DateStart"]) || !isset($inputs["DateEnd"])) {
            $this->blocksFactory()->addNotificationsBlock(array("warning" => "No Date Range Defined!"));

            return;
        }

        //====================================================================//
        // Init Dates
        $this->importDates($inputs);

        //====================================================================//
        // Load Default Currency
        $this->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));

        //====================================================================//
        // Load Splash Module
        $this->spl = Local::getLocalModule();

        //====================================================================//
        // Build data Array
        $rawData = $this->getData((string) $this->dateStart, (string) $this->dateEnd);
        $refineData = $this->refineData((string) $this->dateStart, (string) $this->dateEnd, $rawData);
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

    /**
     * Get Activity Data
     *
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    private function getData(string $dateFrom, string $dateTo)
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

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $grossData
     *
     * @return array
     */
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
        for ($date = $rawDateFrom; $date <= $rawDateTo; $date = strtotime('+1 day', (int) $date)) {
            $this->getRefinedSales($refinedData, $date, $grossData);
            $this->getRefinedProfits($refinedData, $date, $grossData);
        }

        return $refinedData;
    }

    /**
     * @param array $data
     *
     * @return array
     */
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

    /**
     * @param array     $refinedData
     * @param false|int $date
     * @param array     $grossData
     *
     * @return void
     */
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

    /**
     * @param array     $refinedData
     * @param false|int $date
     * @param array     $grossData
     *
     * @return void
     */
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
