<?php
/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Clamav\Validation;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Validation\Validator;
use CakeDC\Clamav\Network\Socket;

class ClamdValidation extends Validator
{
    const TMP_UPLOAD_KEY = 'tmp_name';

    /**
     * Use clamd socket to scan the uploaded tmp file
     *
     * @param $check
     * @return bool|string
     */
    public function fileHasNoVirusesFound($check)
    {
        if (!Configure::read('CakeDC/Clamav.enabled')) {
            // Skip virus scan by configuration
            return true;
        }

        $tmpName = $check[static::TMP_UPLOAD_KEY] ?? null;
        $tmpName = (string)$tmpName;
        if (!$tmpName || !file_exists($tmpName)) {
            return __d('cake_d_c/clamav', 'Path to uploaded file not found');
        }

        try {
            $scanResult = $this->clamdScan($tmpName);
        } catch (\Exception $ex) {
            $message = __d(
                'cake_d_c/clamav',
                'Exception while checking the file {0} for viruses: {1}', $tmpName, $ex->getMessage()
            );
            Log::warning($message);
            
            return $message;
        }

        return $this->checkScanResult($scanResult);
    }

    /**
     * Use clamd socket to retrieve virus scan from clamd
     *
     * @param string $tmpName
     * @return mixed
     */
    protected function clamdScan(string $tmpName)
    {
        $socket = $this->getSocketInstance(Configure::read('CakeDC/Clamav.socketConfig'));
        $socket->write('SCAN ' . $tmpName);

        return $socket->read();
    }

    /**
     * Get Socket instance for DI
     *
     * @param array $config
     * @return Socket
     */
    protected function getSocketInstance(array $config)
    {
        return new Socket($config);
    }

    /**
     * Check scan result and return error msg or true if OK
     *
     * @param $result
     * @return bool|string
     */
    protected function checkScanResult($result)
    {
        $virusFoundSuffix = ' FOUND' . PHP_EOL;
        $okSuffix = ' OK' . PHP_EOL;

        if (substr($result, -1 * strlen($virusFoundSuffix)) === $virusFoundSuffix) {
            return __d('cake_d_c/clamav', 'Virus found!');
        }
        if (substr($result, -1 * strlen($okSuffix)) === $okSuffix) {
            return true;
        }

        return __d('cake_d_c/clamav', 'Error checking the file for viruses');
    }
}