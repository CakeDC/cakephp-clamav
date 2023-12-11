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
namespace CakeDC\Clamav\Network;

use Cake\Network\Exception\SocketException;
use Cake\Network\Socket as BaseSocket;

/**
 * Temporary Class Socket, until PR accepted
 *
 * @package CakeDC\Clamav\Network
 */
class Socket extends BaseSocket
{
    /**
     * Override to allow using file sockets
     *
     * @return bool
     */
    public function connect(): bool
    {
        if ($this->connection) {
            $this->disconnect();
        }

        $hasProtocol = str_contains($this->_config['host'], '://');
        if ($hasProtocol) {
            [$this->_config['protocol'], $this->_config['host']] = explode('://', $this->_config['host']);
        }
        $scheme = null;
        if (!empty($this->_config['protocol'])) {
            $scheme = $this->_config['protocol'] . '://';
        }

        $this->_setSslContext($this->_config['host']);
        if (!empty($this->_config['context'])) {
            $context = stream_context_create($this->_config['context']);
        } else {
            $context = stream_context_create();
        }

        $connectAs = STREAM_CLIENT_CONNECT;
        if ($this->_config['persistent']) {
            $connectAs |= STREAM_CLIENT_PERSISTENT;
        }

        set_error_handler([$this, '_connectionErrorHandler']);
        $remoteSocketTarget = $scheme . $this->_config['host'];
        if ($this->_config['port'] !== null) {
            $remoteSocketTarget .= ':' . $this->_config['port'];
        }
        $this->connection = stream_socket_client(
            $remoteSocketTarget,
            $errNum,
            $errStr,
            $this->_config['timeout'],
            $connectAs,
            $context
        );
        restore_error_handler();

        if (!empty($errNum) || !empty($errStr)) {
            $this->setLastError($errNum, $errStr);
            throw new SocketException($errStr, $errNum);
        }

        if (!$this->connection && $this->_connectionErrors) {
            $message = implode("\n", $this->_connectionErrors);
            throw new SocketException($message, E_WARNING);
        }

        $this->connected = is_resource($this->connection);
        if ($this->connected) {
            stream_set_timeout($this->connection, $this->_config['timeout']);
        }

        return $this->connected;
    }
}
