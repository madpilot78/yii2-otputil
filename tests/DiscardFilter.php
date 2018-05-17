<?php

namespace madpilot78\otputil\tests;

use php_user_filter;

/**
 * Filter to silence yii2 migration
 */
class DiscardFilter extends php_user_filter
{
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;
        }
        return PSFS_PASS_ON;
    }
}
