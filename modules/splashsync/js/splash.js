/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


/**
 * @abstract    Splash Sync Prestahop Module - Javascript Notifictaions
 * @author      B. Paquier <contact@splashsync.com>
 */

/* 
 * Notifictaions
 */
    function showError(message)             { displayNotification("error",message,false); }
    function showWarning(message)           { displayNotification("warning",message,4000); }
    function showSuccess(message)           { displayNotification("success",message,4000); }
    function showInfo(message)              { displayNotification("info",message,4000); }

    
/* 
 * Plot a Notifictaion
 */
    function displayNotification(type,message,timout){

        var timeout      = "4000";
        
        if ( type === "error" ) {
            timeout      = false;
        }
        
        $.notification = {
            layout: 'bottomRight',
            theme: 'relax', // 'defaultTheme' or 'relax'
            type: type,
            text: message, // can be html or string
            animation: {
                open: {height: 'toggle'}, // or Animate.css class names like: 'animated bounceInLeft'
                close: {height: 'toggle'}, // or Animate.css class names like: 'animated bounceOutLeft'
                easing: 'swing',
                speed: 500 // opening & closing animation speed
            },
            timeout: timeout, // delay for closing event. Set false for sticky notifications
            force: false, // adds notification to the beginning of queue when set to true
            modal: false,
            maxVisible: 5, // you can set max visible notification for dismissQueue true option,
            closeWith: ['click', 'backdrop'], // ['click', 'button', 'hover', 'backdrop'] // backdrop click will close all notifications
        };        
        noty($.notification);
    }
    
