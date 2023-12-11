<?php
declare(strict_types=1);

/**
 * Copyright 2013 - 2023, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2013 - 2023, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\Clamav\Validation;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Network\Socket as BaseSocket;
use Cake\Validation\Validator;
use CakeDC\Clamav\Network\Socket;
use Exception;
use function Cake\I18n\__d;

/**
 * Class ClamdValidation
 * Validation class to handle ClamAV daemon virus check
 *
 * @package CakeDC\Clamav\Validation
 */
class ClamdValidation extends Validator
{
    public const TMP_UPLOAD_KEY = 'tmp_name';

    // SCAN mode will check files in the local filesystem
    public const MODE_SCAN = 'SCAN';

    // INSTREAM mode will send the file as a stream to the server
    public const MODE_INSTREAM = 'INSTREAM';

    /**
     * Use clamd socket to scan the uploaded tmp file
     *
     * @param mixed $check value to check
     * @return bool|string
     */
    public function fileHasNoVirusesFound(mixed $check): bool|string
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
        } catch (Exception $ex) {
            $message = __d(
                'cake_d_c/clamav',
                '{0} while checking the file {1} for viruses: {2}',
                get_class($ex),
                $tmpName,
                $ex->getMessage()
            );
            Log::warning($message);

            return $message;
        }

        return $this->checkScanResult($scanResult);
    }

    /**
     * Use clamd socket to retrieve virus scan from clamd
     *
     * @param string $tmpName path to file to scan
     * @return string|null
     */
    protected function clamdScan(string $tmpName): ?string
    {
        $socket = $this->getSocketInstance(Configure::read('CakeDC/Clamav.socketConfig'));
        $mode = Configure::read('CakeDC/Clamav.mode', static::MODE_SCAN);
        switch ($mode) {
            case static::MODE_SCAN:
                $socket->write('SCAN ' . $tmpName);
                break;
            case static::MODE_INSTREAM:
                $this->sendInstream($tmpName, $socket);
                break;
            default:
                throw new \OutOfBoundsException(sprintf('Invalid scan mode: %s', $mode));
        }

        return $socket->read();
    }

    /**
     * Send a chunked file using INSTREAM mode to clamd
     *
     * @param string $tmpName path to the file
     * @param \Cake\Network\Socket $socket socket to write
     * @return void
     */
    protected function sendInstream(string $tmpName, BaseSocket $socket): void
    {
        $fhandler = fopen($tmpName, 'r');
        $streamMaxLength = Configure::read('CakeDC/Clamav.streamMaxLength', 25 * 1024 * 1024);
        if (!$fhandler) {
            throw new \OutOfBoundsException(sprintf('Unable to open file: %s', $tmpName));
        }
        $socket->write('nINSTREAM' . PHP_EOL);
        while (!feof($fhandler)) {
            $chunk = fread($fhandler, $streamMaxLength);
            $chunckLength = pack('N', strlen($chunk));
            $socket->write($chunckLength . $chunk);
        }
        fclose($fhandler);

        // sending a 0 bytes chunk to flag the end of the stream

        $socket->write(pack('N', 0));
    }

    /**
     * Get Socket instance for DI
     *
     * @param array $config socket configuration
     * @return \CakeDC\Clamav\Network\Socket
     */
    protected function getSocketInstance(array $config): Socket
    {
        return new Socket($config);
    }

    /**
     * Check scan result and return error msg or true if OK
     *
     * @param string $result result from clamad
     * @return bool|string
     */
    protected function checkScanResult(string $result): bool|string
    {
        $virusFoundSuffix = ' FOUND' . PHP_EOL;
        $okSuffix = ' OK' . PHP_EOL;

        if (str_ends_with($result, $virusFoundSuffix)) {
            return __d('cake_d_c/clamav', 'Virus found!');
        }
        if (str_ends_with($result, $okSuffix)) {
            return true;
        }

        return __d('cake_d_c/clamav', 'Error checking the file for viruses');
    }
}
