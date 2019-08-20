<?php
\Cake\Core\Configure::write('CakeDC/Clamav', [
    // WARNING, disabling will SKIP virus check in validation rules
    'enabled' => true,
    // clamd listening in this socket, defaults to unix file socket
    'socketConfig' => [
        'host' => 'unix:///var/run/clamav/clamd.ctl',
        'port' => null,
        'persistent' => true
    ],
]);