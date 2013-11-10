#!/bin/sh
UNIFIED="../src/Spot/unified.php"

echo "<?php" > $UNIFIED

xargs --arg-file filelist.txt -I % sh -c "/usr/bin/php -w % | grep -v \"<?php\" >> $UNIFIED ; echo "" >> $UNIFIED"
