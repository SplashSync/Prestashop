
{*{$notifications|@var_dump}*}

<script type="text/javascript">

{foreach $notifications.err as $err}
    showError('{$err|escape}');
{/foreach}

{foreach $notifications.war as $war}
    showWarning('{$war|escape}');
{/foreach}


{foreach $notifications.msg as $msg}
    showSuccess('{$msg|escape}');
{/foreach}

{foreach $notifications.deb as $deb}
    showInfo('{$deb|escape}');
{/foreach}

    
$.ajax({
        url: '{$url}/modules/splashsync/ajax.php',
        type: 'get',
        data: 'ClearNotifications=true',
        success: function(data) {
                console.log('success');
        }
});    
</script>