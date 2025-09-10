<?php
/**
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
 *
 * @author Splash Sync
 * @copyright Splash Sync SAS
 * @license MIT
 */

namespace Splash\Local\Objects\Product;

use Configuration;
use DbQuery;
use Product;
use Shop;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\PmAdvancedPack;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Product Objects Lists
 */
trait ObjectsListTrait
{
    /**
     * {@inheritdoc}
     */
    public function objectsList(?string $filter = null, array $params = array()): array
    {
        Splash::log()->deb('MsgLocalFuncTrace', __CLASS__, __FUNCTION__);

        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select('p.`id_product`            as id');
        $sql->select('pa.`id_product_attribute`  as id_attribute');
        $sql->select('p.`reference` as ref');
        $sql->select('pa.`reference` as ref_attribute');
        $sql->select('p.`supplier_reference` as supplier_reference');
        $sql->select('pa.`supplier_reference` as supplier_reference_attribute');
        $sql->select('pl.`name` as name');
        $sql->select('p.`quantity` as stock');
        $sql->select('p.`weight` as weight');
        $sql->select('pa.`weight` as weight_attribute');
        $sql->select('p.`available_for_order` as available_for_order');
        $sql->select('p.`date_add` as created');
        $sql->select('p.`date_upd` as modified');
        //====================================================================//
        // Build FROM
        $sql->from('product', 'p');
        //====================================================================//
        // Build JOIN
        $sqlWhere = '(pl.id_product = p.id_product AND pl.id_lang = ';
        $sqlWhere .= (int)  SLM::getDefaultLangId() . Shop::addSqlRestrictionOnLang('pl') . ')';
        $sql->leftJoin('product_lang', 'pl', $sqlWhere);
        $sql->leftJoin('product_attribute', 'pa', '(pa.id_product = p.id_product) ');
        //====================================================================//
        // Setup filters
        $sqlFilters = self::getFilters($filter);
        if (!empty($sqlFilters)) {
            $sql->where($sqlFilters);
        }
        //====================================================================//
        // Compute Total Number of Results
        $total = $this->getObjectListTotal($sql);
        //====================================================================//
        // Execute Generic Search
        $result = $this->getObjectsListRawData($sql, 'ref', $params);
        if (false === $result) {
            return array();
        }
        //====================================================================//
        // Init Result Array
        $data = array();
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($result as $product) {
            array_push($data, $this->getTransformedProductArray($product));
        }

        //====================================================================//
        // Compute List Meta Informations
        $data['meta']['current'] = count($data);   // Store Current Number of results
        $data['meta']['total'] = $total;         // Store Total Number of results
        Splash::log()->deb('MsgLocalTpl', __CLASS__, __FUNCTION__, ' ' . $data['meta']['current'] . ' Products Found.');

        return $data;
    }

    /**
     * Build Sql Where Conditions
     *
     * @param null|string $filter
     *
     * @return string
     */
    private static function getFilters($filter = null)
    {
        Splash::log()->deb('MsgLocalFuncTrace', __CLASS__, __FUNCTION__);
        //====================================================================//
        // Init
        $where = '';

        //====================================================================//
        // Setup String Filters
        // Add filters with names conversions. Added LOWER function to be NON case sensitive
        if (!empty($filter)) {
            //====================================================================//
            // Search by Product Name
            $stringFilters = ' LOWER( pl.name )         LIKE LOWER( \'%' . pSQL($filter) . '%\') ';
            //====================================================================//
            // Search by Product Name
            $stringFilters .= ' OR LOWER( pl.name )     LIKE LOWER( \'%' . pSQL($filter) . '%\') ';
            //====================================================================//
            // Search by Product Ref
            $stringFilters .= ' OR LOWER( p.reference ) LIKE LOWER( \'%' . pSQL($filter) . '%\') ';
            //====================================================================//
            // Search by Product Supplier Ref
            $stringFilters .= ' OR LOWER( p.supplier_reference ) LIKE LOWER( \'%' . pSQL($filter) . '%\') ';
            //====================================================================//
            // Search by Product Variant Ref
            $stringFilters .= ' OR LOWER( pa.reference ) LIKE LOWER( \'%' . pSQL($filter) . '%\') ';
            //====================================================================//
            // Search by Product Short Desc
            $stringFilters .= ' OR LOWER(pl.description_short ) LIKE LOWER( \'%' . pSQL($filter) . '%\') ';

            $where .= '(' . $stringFilters . ') ';
        }

        //====================================================================//
        // Setup Virtual Products Filters
        if (empty(Configuration::get('SPLASH_SYNC_VIRTUAL'))) {
            $where .= empty($where) ? '' : ' AND ';
            $where .= '(p.is_virtual != 1)';
        }

        //====================================================================//
        // Setup Pack Products Filters
        if (empty(Configuration::get('SPLASH_SYNC_PACKS'))) {
            if (!PmAdvancedPack::isFeatureActive()) {
                $where .= empty($where) ? '' : ' AND ';
                $where .= ' (p.cache_is_pack != 1)';
            } elseif (!empty($packIds = PmAdvancedPack::getIdsPacks())) {
                $where .= empty($where) ? '' : ' AND ';
                $where .= ' (p.id_product NOT IN (' . implode(', ', $packIds) . '))';
            }
        }

        return $where;
    }

    /**
     * Parse Product Infos to List Response Array
     *
     * @param array $product
     *
     * @return array
     */
    private function getTransformedProductArray($product)
    {
        $currencySymbol = SLM::getCurrencySymbol($this->currency);
        //====================================================================//
        // Init Buffer Array
        $dataBuffer = array();
        //====================================================================//
        // Read Product Attributes Combination
        $productClass = new Product();
        $productClass->id = $product['id'];
        //====================================================================//
        // Fill Product Base Data to Buffer
        $dataBuffer['price_type'] = 'HT';
        $dataBuffer['vat'] = '';
        $dataBuffer['currency'] = $currencySymbol;
        $dataBuffer['available_for_order'] = $product['available_for_order'];
        $dataBuffer['created'] = $product['created'];
        $dataBuffer['modified'] = $product['modified'];
        $dataBuffer['fullname'] = Product::getProductName($product['id'], $product['id_attribute']);
        $dataBuffer['md5'] = self::getMd5ChecksumFromValues(
            $product['name'],
            $product['ref'],
            $this->getProductAttributesArray($productClass, $product['id_attribute'])
        );
        //====================================================================//
        // Fill Simple Product Data to Buffer
        if (!$product['id_attribute']) {
            $dataBuffer['id'] = $product['id'];
            $dataBuffer['ref'] = $product['ref'];
            $dataBuffer['supplier_reference'] = $product['supplier_reference'];
            $dataBuffer['weight'] = $product['weight'] . Configuration::get('PS_WEIGHT_UNIT');
            $dataBuffer['stock'] = $productClass->getQuantity($product['id']);
            $dataBuffer['price'] = $productClass->getPrice(false, null, 3);
            $dataBuffer['price-base'] = $productClass->getPrice(false, null, 3);
            //====================================================================//
            // Fill Product Combination Data to Buffer
        } else {
            $dataBuffer['id'] = (int) $this->getUnikId($product['id'], $product['id_attribute']);
            $dataBuffer['ref'] = (empty($product['ref_attribute']) && !empty($product['ref']))
                    ?$product['ref'] . '-' . $product['id_attribute']
                    :$product['ref_attribute'];
            $dataBuffer['supplier_reference'] = $product['supplier_reference_attribute'];
            $dataBuffer['weight'] = ($product['weight'] + $product['weight_attribute']);
            $dataBuffer['weight'] .= Configuration::get('PS_WEIGHT_UNIT');
            $dataBuffer['price'] = $productClass->getPrice(false, $product['id_attribute'], 3);
            $dataBuffer['price-base'] = $productClass->getPrice(false, null, 3);
            $dataBuffer['stock'] = $productClass->getQuantity($product['id'], $product['id_attribute']);
        }

        return $dataBuffer;
    }
}
