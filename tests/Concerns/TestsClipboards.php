<?php

namespace Silber\Bouncer\Tests\Concerns;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

use Silber\Bouncer\Clipboard;
use Workbench\App\Models\User;
use Silber\Bouncer\CachedClipboard;

use Illuminate\Cache\NullStore;

trait TestsClipboards
{
    /**
     * Provides a bouncer instance (and users) for each clipboard, respectively.
     *
     * @return array
     */
    public static function bouncerProvider()
    {
        return [
            'basic clipboard' => [
                function ($authoriesCount = 1, $authority = User::class) {
                    return static::provideBouncer(
                        new Clipboard, $authoriesCount, $authority
                    );
                }
            ],
            'null cached clipboard' => [
                function ($authoriesCount = 1, $authority = User::class) {
                    return static::provideBouncer(
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
    protected static function provideBouncer($clipboard, $authoriesCount, $authority)
    {
        $authorities = array_map(
            fn () => $authority::create(),
            range(0, $authoriesCount),
        );

        $bouncer = static::bouncer($authorities[0]);

        return array_merge([$bouncer], $authorities);
    }
}
