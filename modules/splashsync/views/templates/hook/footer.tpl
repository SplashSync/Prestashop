{**
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
 **}

<script type="text/javascript">

    var $Notified = false;
    
    {foreach $notifications.err as $err}
        showError(  "{$err|escape:'htmlall':'UTF-8'}");
        $Notified = true;
    {/foreach}

    {foreach $notifications.war as $war}
        showWarning("{$war|escape:'htmlall':'UTF-8'}");
        $Notified = true;
    {/foreach}


    {foreach $notifications.msg as $msg}
        showSuccess("{$msg|escape:'htmlall':'UTF-8'}");
        $Notified = true;
    {/foreach}

    {foreach $notifications.deb as $deb}
        showInfo(   "{$deb|escape:'htmlall':'UTF-8'}");
        $Notified = true;
    {/foreach}

    if ( $Notified ) {
        $.ajax({
            url: '{$url|escape:'htmlall':'UTF-8'}modules/splashsync/ajax.php',
            type: 'get',
            data: 'ClearNotifications=true&token={$smarty.get.token|escape:'htmlall':'UTF-8'}',
            success: function(data) {
                console.log('[Splash] Notifications Cleared');
            }
        }); 
    }

    
</script>