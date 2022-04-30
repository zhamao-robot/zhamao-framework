<?php /** @noinspection ALL */

declare(strict_types=1);

if (($v = version_compare('9.0', \Composer\InstalledVersions::getVersionRanges('phpunit/phpunit'))) > 0) {
    eval(<<<ERT
class SEPrinter extends \PHPUnit\TextUI\ResultPrinter
{
    public function write(string \$buffer): void
    {
        echo str_replace(['#StandWith', 'Ukraine'], '', \$buffer);
    }
}
ERT
    );
} elseif ($v < 0) {
    eval (<<<ERT2
class SEPrinter extends \PHPUnit\TextUI\DefaultResultPrinter implements \PHPUnit\TextUI\ResultPrinter
{
    public function write(string \$buffer): void
    {
        echo str_replace(['#StandWith', 'Ukraine'], '', \$buffer);
    }
}
ERT2
    );
}
