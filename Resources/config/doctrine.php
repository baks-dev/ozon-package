<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BaksDev\Ozon\Package\BaksDevOzonPackageBundle;
use BaksDev\Ozon\Package\Type\Package\Event\OzonPackageEventType;
use BaksDev\Ozon\Package\Type\Package\Event\OzonPackageEventUid;
use BaksDev\Ozon\Package\Type\Package\Id\OzonPackageType;
use BaksDev\Ozon\Package\Type\Package\Id\OzonPackageUid;
use BaksDev\Ozon\Package\Type\Package\Status\OzonPackageStatus;
use BaksDev\Ozon\Package\Type\Package\Status\OzonPackageStatusType;
use BaksDev\Ozon\Package\Type\Supply\Event\OzonSupplyEventType;
use BaksDev\Ozon\Package\Type\Supply\Event\OzonSupplyEventUid;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyType;
use BaksDev\Ozon\Package\Type\Supply\Id\OzonSupplyUid;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatus;
use BaksDev\Ozon\Package\Type\Supply\Status\OzonSupplyStatusType;
use Symfony\Config\DoctrineConfig;

return static function(ContainerConfigurator $container, DoctrineConfig $doctrine) {

    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $doctrine->dbal()->type(OzonPackageUid::TYPE)->class(OzonPackageType::class);
    $doctrine->dbal()->type(OzonPackageEventUid::TYPE)->class(OzonPackageEventType::class);
    $doctrine->dbal()->type(OzonPackageStatus::TYPE)->class(OzonPackageStatusType::class);

    $doctrine->dbal()->type(OzonSupplyUid::TYPE)->class(OzonSupplyType::class);
    $services->set(OzonSupplyUid::class)->class(OzonSupplyUid::class);

    $doctrine->dbal()->type(OzonSupplyEventUid::TYPE)->class(OzonSupplyEventType::class);
    $doctrine->dbal()->type(OzonSupplyStatus::TYPE)->class(OzonSupplyStatusType::class);

    $emDefault = $doctrine->orm()->entityManager('default')->autoMapping(true);

    $emDefault->mapping('ozon-package')
        ->type('attribute')
        ->dir(BaksDevOzonPackageBundle::PATH.'Entity')
        ->isBundle(false)
        ->prefix(BaksDevOzonPackageBundle::NAMESPACE.'\\Entity')
        ->alias('ozon-package');
};