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
 * Splash Sync Prestahop Module - Javascript Notifications
 */

/**
 * @param {string} message
 * @returns {undefined}
 */
function showError(message)             { displayNotification("error", message, false); }

/**
 * @param {string} message
 * @returns {undefined}
 */
function showWarning(message)           { displayNotification("warning",message); }

/**
 * @param {string} message
 * @returns {undefined}
 */
function showSuccess(message)           { displayNotification("success",message); }

/**
 * @param {string} message
 * @returns {undefined}
 */
function showInfo(message)              { displayNotification("info",message); }

/**
 * Display Noty Notification
 * 
 * @param {string} type
 * @param {string} message
 * @param {string} timeout
 * @returns {undefined}
 */
function displayNotification(type, message, timeout = "2500"){
    new Noty({
        layout: 'bottomRight', 
        theme: 'semanticui',
        type: type,
        text: message, 
        timeout: timeout,
        maxVisible: 5, 
        closeWith: ['click', 'backdrop']
    }).show();       
}

