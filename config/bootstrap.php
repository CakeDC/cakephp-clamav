<?php
\Cake\Core\Configure::write('CakeDC/Clamav', [
    // WARNING, disabling will SKIP virus check in validation rules
    'enabled' => true,
    'mode' => \CakeDC\Clamav\Validation\ClamdValidation::MODE_SCAN,
    // Only used if mode is INSTREAM, value of StreamMaxLength (check clamd configuration file) in bytes
    'streamMaxLength' => 25 * 1024 * 1024,
    // clamd listening in this socket, defaults to unix file socket
    'socketConfig' => [
        'host' => 'unix:///var/run/clamav/clamd.ctl',
        'port' => null,
        'persistent' => true
    ],
]);

/*
 * Examples
 * 1. Defaults provided are for a typical clamav daemon setup using localhost. In this case, there is a file based
 * socket and the filesystem can be accessed by the clamd service to check the file
 * 2. In case clamd is hosted in another container/node, access can be made via TCP, usually through port 3310.
 * \Cake\Core\Configure::write('CakeDC/Clamav.mode', \CakeDC\Clamav\Validation\ClamdValidation::MODE_INSTREAM);
 * \Cake\Core\Configure::write('CakeDC/Clamav.socketConfig.host', '1.2.3.4');
 * \Cake\Core\Configure::write('CakeDC/Clamav.socketConfig.port', 3310);
 */
