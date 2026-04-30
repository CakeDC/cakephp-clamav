<?php
declare(strict_types=1);

/**
 * Copyright 2013 - 2026, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2013 - 2026, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\Clamav;

use Cake\Core\BasePlugin;

/**
 * Plugin for CakeDC\Clamav
 */
class ClamavPlugin extends BasePlugin
{
    protected bool $routesEnabled = false;
    protected bool $middlewareEnabled = false;
    protected bool $consoleEnabled = false;
    protected bool $bootstrapEnabled = false;
    protected bool $servicesEnabled = false;
    protected bool $eventManagerEnabled = false;
}
