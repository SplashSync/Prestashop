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

    $(document).ready(function () {
        {if isset($notifications.err) }
            {foreach $notifications.err as $err}
                showError(  "{$err|escape:'javascript':'UTF-8'}");
            {/foreach}
        {/if}
        {if isset($notifications.war) }
            {foreach $notifications.war as $war}
                showWarning("{$war|escape:'javascript':'UTF-8'}");
            {/foreach}
        {/if}
        {if isset($notifications.msg) }
            {foreach $notifications.msg as $msg}
                showSuccess("{$msg|escape:'javascript':'UTF-8'}");
            {/foreach}
        {/if}
        {if isset($notifications.deb) }
            {foreach $notifications.deb as $deb}
                showInfo(   "{$deb|escape:'javascript':'UTF-8'}");
            {/foreach}
        {/if}
    });    

</script>