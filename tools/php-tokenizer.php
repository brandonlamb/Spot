#!/usr/bin/php -q
<?php
$filename = '/web/library/Eve-Framework/src/Eve/Mvc/View.php';

/** method 1 */
$fileStr = file_get_contents($filename);
foreach (token_get_all($fileStr) as $token ) {
    if ($token[0] != T_COMMENT && $token[0] != T_DOC_COMMENT) {
        continue;
    }
    $fileStr = str_replace($token[1], '', $fileStr);
}

exit($fileStr);

/** method 2 */

$fileStr = file_get_contents($filename);
$newStr  = '';

$commentTokens = array(T_COMMENT);

if (defined('T_DOC_COMMENT')) {
    $commentTokens[] = T_DOC_COMMENT;
}

$tokens = token_get_all($fileStr);

foreach ($tokens as $token) {    
    if (is_array($token)) {
        if (in_array($token[0], $commentTokens)) {
            continue;
		}

        $token = $token[1];
    }

    $newStr .= $token;
}

echo $newStr;
