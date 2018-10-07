<?php

namespace Silber\Bouncer\Tests\Concerns;

use Silber\Bouncer\Clipboard;
use Silber\Bouncer\Tests\User;
use Silber\Bouncer\CachedClipboard;

use Illuminate\Cache\NullStore;

trait TestsClipboards
{
    /**
     * Provides a bouncer instance (and users) for each clipboard, respectively.
     *
     * @return array
     */
    function bouncerProvider()
    {
        return [
            'basic clipboard' => [
                function ($authoriesCount = 1, $authority = User::class) {
                    return $this->provideBouncer(
                        new Clipboard, $authoriesCount, $authority
                    );
                }
            ],
            'null cached clipboard' => [
                function ($authoriesCount = 1, $authority = User::class) {
                    return $this->provideBouncer(
                        new CachedClipboard(new NullStore), $authoriesCount, $authority
                    );
                }
            ],
        ];
    }

    /**
     * Provide the bouncer instance (with its user) using the given clipboard.
     *
     * @param  \Silber\Bouncer\Clipboard  $clipboard
     * @param  int  $authoriesCount
     * @param  string  $authority
     * @return array
     */
    protected function provideBouncer($clipboard, $authoriesCount, $authority)
    {
        $authorities = array_map(function () use ($authority) {
            return $authority::create();
        }, range(0, $authoriesCount));

        $this->clipboard = $clipboard;

        $bouncer = $this->bouncer($authorities[0]);

        return array_merge([$bouncer], $authorities);
    }
}
