
{*{$notifications|@var_dump}*}

<script type="text/javascript">

    var $Notified = false;
    
    {foreach $notifications.err as $err}
        showError('{$err|escape}');
        $Notified = true;
    {/foreach}

    {foreach $notifications.war as $war}
        showWarning('{$war|escape}');
        $Notified = true;
    {/foreach}


    {foreach $notifications.msg as $msg}
        showSuccess('{$msg|escape}');
        $Notified = true;
    {/foreach}

    {foreach $notifications.deb as $deb}
        showInfo('{$deb|escape}');
        $Notified = true;
    {/foreach}

{*    if ( $Notified ) {*}
        $.ajax({
            url: '{$url}modules/splashsync/ajax.php',
            type: 'get',
            data: 'ClearNotifications=true&token={$smarty.get.token}',
            success: function(data) {
                    console.log('[Splash] Notifications Cleared');
            }
        }); 
{*    }*}

    
</script>