<?php

use Snowdog\DevTest\Component\CommandRepository;
use Snowdog\DevTest\Component\RouteRepository;
use Task6\Sitemap\Controller\ImportSiteMap;
use Task6\Sitemap\Command\ImportSiteMapCmd;

RouteRepository::registerRoute('POST', '/importsitemap', ImportSiteMap::class, 'execute');
CommandRepository::registerCommand('import_sitemap', ImportSiteMapCmd::class);
