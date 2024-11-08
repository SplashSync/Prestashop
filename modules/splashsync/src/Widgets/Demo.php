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
//                     SPLASH FOR DOLIBARR                            //
// *******************************************************************//
//                  TEST & DEMONSTRATION WIDGET                       //
// *******************************************************************//
//====================================================================//

namespace Splash\Local\Widgets;

use ArrayObject;
use Splash\Core\SplashCore as Splash;
use Splash\Models\AbstractWidget;

/**
 * Demo Widget for Prestashop
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Demo extends AbstractWidget
{
    //====================================================================//
    // Define Standard Options for this Widget
    // Override this array to change default options for your widget
    public static array $options = array(
        "Width" => self::SIZE_XL
    );

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Widget Disable Flag. Uncomment this line to Override this flag and disable Object.
     *
     * @var bool
     */
    protected static bool $disabled = true;

    /**
     * Widget Name (Translated by Module)
     *
     * @var string
     */
    protected static string $name = "Demo Widget";

    /**
     * Widget Description (Translated by Module)
     *
     * @var string
     */
    protected static string $description = "TEST & DEMONSTRATION WIDGET";

    /**
     * Widget Icon (FontAwesome or Glyph ico tag)
     *
     * @var string
     */
    protected static string $ico = "fa fa-magic";

    //====================================================================//
    // Class Main Functions
    //====================================================================//

    /**
     * Return Widget Customs Parameters
     *
     * @return array
     */
    public function getParameters(): array
    {
        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("text_input")
            ->name("Text Input")
            ->description("Widget Specific Custom text Input");

        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("integer_input")
            ->name("Numeric Input")
            ->description("Widget Specific Custom Numeric Input");

        //====================================================================//
        // Publish Fields
        return $this->fieldsFactory()->publish() ?? array();
    }

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
        // Build Intro Text Block
        //====================================================================//
        $this->buildIntroBlock();

        //====================================================================//
        // Build Inputs Block
        //====================================================================//
        $this->buildParametersBlock($parameters);

        //====================================================================//
        // Build Inputs Block
        //====================================================================//
        $this->buildNotificationsBlock();

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
     * Block Building - Text Intro
     *
     * @return void
     */
    private function buildIntroBlock()
    {
        //====================================================================//
        // Into Text Block
        $this->blocksFactory()->addTextBlock("This is a Demo Text Block!!"."You can repeat me as much as you want!");
    }

    /**
     * Block Building - Inputs Parameters
     *
     * @param null|array|ArrayObject $inputs
     *
     * @return void
     */
    private function buildParametersBlock($inputs = array())
    {
        //====================================================================//
        // verify Inputs
        if (!is_array($inputs) && !($inputs instanceof ArrayObject)) {
            $this->blocksFactory()
                ->addNotificationsBlock(array("warning" => "Inputs is not an Array!"));

            return;
        }
        //====================================================================//
        // Parameters Table Block
        $tableContents = array();
        $tableContents[] = array("Received ".count($inputs)." inputs parameters","Value");
        foreach ($inputs as $key => $value) {
            $tableContents[] = array($key, $value);
        }
        $this->blocksFactory()->addTableBlock($tableContents, array("Width" => self::SIZE_M));
    }

    /**
     * Block Building - Notifications Parameters
     *
     * @return void
     */
    private function buildNotificationsBlock()
    {
        //====================================================================//
        // Notifications Block
        $notifications = array(
            "error" => "This is a Sample Error Notification",
            "warning" => "This is a Sample Warning Notification",
            "success" => "This is a Sample Success Notification",
            "info" => "This is a Sample Infomation Notification",
        );

        $this->blocksFactory()->addNotificationsBlock($notifications, array("Width" => self::SIZE_M));
    }
}
