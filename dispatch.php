<?php

function copyr($source, $dest)
{
   // Simple copy for a file
   if (is_file($source)) {
      return copy($source, $dest);
   }

   // Make destination directory
   if (!is_dir($dest)) {
      mkdir($dest);
   }

   // Loop through the folder
   $dir = dir($source);
   while (false !== $entry = $dir->read()) {
      // Skip pointers
      if ($entry == '.' || $entry == '..') {
         continue;
      }

      // Deep copy directories
      if ($dest !== "$source/$entry") {
         copyr("$source/$entry", "$dest/$entry");
      }
   }

   // Clean up
   $dir->close();
   return true;
}

copyr("/home/nanard33/WebDesign/MOD-Splash/PrestaShop/modules/splashsync/","/var/www/PrestaShop/Ps-1.5.6/modules/splashsync");
copyr("/home/nanard33/WebDesign/MOD-Splash/PrestaShop/modules/splashsync/","/var/www/PrestaShop/Ps-1.6.0/modules/splashsync");
copyr("/home/nanard33/WebDesign/MOD-Splash/PrestaShop/modules/splashsync/","/var/www/PrestaShop/Ps-1.6.3/modules/splashsync");
copyr("/home/nanard33/WebDesign/MOD-Splash/PrestaShop/modules/splashsync/","/var/www/PrestaShop/Lp-Addict/modules/splashsync");
        
?>