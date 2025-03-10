<?php

declare(strict_types=1);

namespace ZM\Command;

use OneBot\Driver\Workerman\Worker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\AsyncUdpConnection;
use Workerman\Timer;
use ZM\Logger\ConsoleLogger;

#[AsCommand(name: 'proxy', description: '开启一个 HTTP 代理服务器')]
class ProxyServerCommand extends Command
{
    private array $config = [];

    public function onSocksMessage($connection, $buffer)
    {
        switch ($connection->stage) {
            // 初始化环节
            case STAGE_INIT:
                $request = [];
                // 当前偏移量
                $offset = 0;

                // 检测buffer长度
                if (strlen($buffer) < 2) {
                    logger()->error('init socks5 failed. buffer too short.');
                    $connection->send("\x05\xff");
                    $connection->stage = STAGE_DESTROYED;
                    $connection->close();
                    return;
                }

                // Socks5 版本
                $request['ver'] = ord($buffer[$offset]);
                ++$offset;

                // 认证方法数量
                $request['method_count'] = ord($buffer[$offset]);
                ++$offset;

                if (strlen($buffer) < 2 + $request['method_count']) {
                    logger()->warning('init authentic failed. buffer too short.');
                    $connection->send("\x05\xff");
                    $connection->stage = STAGE_DESTROYED;
                    $connection->close();
                    return;
                }
                // 客户端支持的认证方法
                $request['methods'] = [];
                for ($i = 1; $i <= $request['method_count']; ++$i) {
                    $request['methods'][] = ord($buffer[$offset]);
                    ++$offset;
                }

                foreach (($this->config['auth'] ?? []) as $k => $v) {
                    if (in_array($k, $request['methods'])) {
                        logger()->info("auth client via method {$k}");
                        logger()->debug('send:' . bin2hex("\x05" . chr($k)));

                        $connection->send("\x05" . chr($k));
                        if ($k === 0) {
                            $connection->stage = STAGE_ADDR;
                        } else {
                            $connection->stage = STAGE_AUTH;
                        }
                        $connection->auth_type = $k;
                        return;
                    }
                }
                if ($connection->stage != STAGE_AUTH) {
                    logger()->warning('client has no matched auth methods');
                    logger()->info('send:' . bin2hex("\x05\xff"));
                    $connection->send("\x05\xff");
                    $connection->stage = STAGE_DESTROYED;
                    $connection->close();
                }
                return;
            case STAGE_ADDR:
                $request = [];
                // 当前偏移量
                $offset = 0;

                if (strlen($buffer) < 4) {
                    logger()->error('connect init failed. buffer too short.');
                    $connection->stage = STAGE_DESTROYED;

                    $response = [];
                    $response['ver'] = 5;
                    $response['rep'] = ERR_GENERAL;
                    $response['rsv'] = 0;
                    $response['addr_type'] = ADDRTYPE_IPV4;
                    $response['bind_addr'] = '0.0.0.0';
                    $response['bind_port'] = 0;

                    $connection->close($this->packResponse($response));
                    return;
                }

                // Socks 版本
                $request['ver'] = ord($buffer[$offset]);
                ++$offset;

                // 命令
                $request['command'] = ord($buffer[$offset]);
                ++$offset;

                // RSV
                $request['rsv'] = ord($buffer[$offset]);
                ++$offset;

                // AddressType
                $request['addr_type'] = ord($buffer[$offset]);
                ++$offset;

                // DestAddr
                switch ($request['addr_type']) {
                    case ADDRTYPE_IPV4:
                        if (strlen($buffer) < 4 + 4) {
                            logger()->error('connect init failed.[ADDRTYPE_IPV4] buffer too short.');
                            $connection->stage = STAGE_DESTROYED;

                            $response = [];
                            $response['ver'] = 5;
                            $response['rep'] = ERR_GENERAL;
                            $response['rsv'] = 0;
                            $response['addr_type'] = ADDRTYPE_IPV4;
                            $response['bind_addr'] = '0.0.0.0';
                            $response['bind_port'] = 0;

                            $connection->close($this->packResponse($response));
                            return;
                        }

                        $tmp = substr($buffer, $offset, 4);
                        $ip = 0;
                        for ($i = 0; $i < 4; ++$i) {
                            // var_dump(ord($tmp[$i]));
                            $ip += ord($tmp[$i]) * 256 ** (3 - $i);
                        }
                        $request['dest_addr'] = long2ip($ip);
                        $offset += 4;
                        break;
                    case ADDRTYPE_HOST:
                        $request['host_len'] = ord($buffer[$offset]);
                        ++$offset;

                        if (strlen($buffer) < 4 + 1 + $request['host_len']) {
                            logger()->error('connect init failed.[ADDRTYPE_HOST] buffer too short.');
                            $connection->stage = STAGE_DESTROYED;

                            $response = [];
                            $response['ver'] = 5;
                            $response['rep'] = ERR_GENERAL;
                            $response['rsv'] = 0;
                            $response['addr_type'] = ADDRTYPE_IPV4;
                            $response['bind_addr'] = '0.0.0.0';
                            $response['bind_port'] = 0;

                            $connection->close($this->packResponse($response));
                            return;
                        }

                        $request['dest_addr'] = substr($buffer, $offset, $request['host_len']);
                        $offset += $request['host_len'];
                        break;
                    case ADDRTYPE_IPV6:
                    default:
                        logger()->error('unsupport ipv6. [ADDRTYPE_IPV6].');
                        $connection->stage = STAGE_DESTROYED;

                        $response = [];
                        $response['ver'] = 5;
                        $response['rep'] = ERR_UNKNOW_ADDR_TYPE;
                        $response['rsv'] = 0;
                        $response['addr_type'] = ADDRTYPE_IPV4;
                        $response['bind_addr'] = '0.0.0.0';
                        $response['bind_port'] = 0;

                        $connection->close($this->packResponse($response));
                        return;
                }

                // DestPort

                if (strlen($buffer) < $offset + 2) {
                    logger()->error('connect init failed.[port] buffer too short.');
                    $connection->stage = STAGE_DESTROYED;

                    $response = [];
                    $response['ver'] = 5;
                    $response['rep'] = ERR_GENERAL;
                    $response['rsv'] = 0;
                    $response['addr_type'] = ADDRTYPE_IPV4;
                    $response['bind_addr'] = '0.0.0.0';
                    $response['bind_port'] = 0;

                    $connection->close($this->packResponse($response));
                    return;
                }
                $portData = unpack('n', substr($buffer, $offset, 2));
                $request['dest_port'] = $portData[1];
                $offset += 2;

                // var_dump($request);
                switch ($request['command']) {
                    case CMD_CONNECT:
                        logger()->info('tcp://' . $request['dest_addr'] . ':' . $request['dest_port']);
                        if ($request['addr_type'] == ADDRTYPE_HOST) {
                            if (!filter_var($request['dest_addr'], FILTER_VALIDATE_IP)) {
                                logger()->debug('resolve DNS ' . $request['dest_addr']);
                                $connection->stage = STAGE_DNS;
                                $addr = dns_get_record($request['dest_addr'], DNS_A);
                                $addr = $addr ? array_pop($addr) : null;
                                logger()->debug('DNS resolved ' . $request['dest_addr'] . ' => ' . $addr['ip']);
                            } else {
                                $addr['ip'] = $request['dest_addr'];
                            }
                        } else {
                            $addr['ip'] = $request['dest_addr'];
                        }
                        if ($addr) {
                            $connection->stage = STAGE_CONNECTING;
                            $remote_connection = new AsyncTcpConnection('tcp://' . $addr['ip'] . ':' . $request['dest_port']);
                            $remote_connection->onConnect = function ($remote_connection) use ($connection, $request) {
                                $connection->state = STAGE_STREAM;
                                $response = [];
                                $response['ver'] = 5;
                                $response['rep'] = 0;
                                $response['rsv'] = 0;
                                $response['addr_type'] = $request['addr_type'];
                                $response['bind_addr'] = '0.0.0.0';
                                $response['bind_port'] = 18512;

                                $connection->send($this->packResponse($response));
                                $connection->pipe($remote_connection);
                                $remote_connection->pipe($connection);
                                logger()->debug('tcp://' . $request['dest_addr'] . ':' . $request['dest_port'] . ' [OK]');
                            };
                            $remote_connection->connect();
                        } else {
                            logger()->debug('DNS resolve failed.');
                            $connection->stage = STAGE_DESTROYED;

                            $response = [];
                            $response['ver'] = 5;
                            $response['rep'] = ERR_HOST;
                            $response['rsv'] = 0;
                            $response['addr_type'] = ADDRTYPE_IPV4;
                            $response['bind_addr'] = '0.0.0.0';
                            $response['bind_port'] = 0;

                            $connection->close($this->packResponse($response));
                        }
                        break;
                    case CMD_UDP_ASSOCIATE:
                        $connection->stage = STAGE_UDP_ASSOC;
                        if ($this->config['udp_port'] == 0) {
                            $connection->udpWorker = new Worker('udp://0.0.0.0:0');
                            /* @phpstan-ignore-next-line */
                            $connection->udpWorker->incId = 0;
                            $connection->udpWorker->onMessage = function ($udp_connection, $data) use ($connection) {
                                $this->udpWorkerOnMessage($udp_connection, $data, $connection->udpWorker);
                            };
                            $connection->udpWorker->listen();
                            $listenInfo = stream_socket_get_name($connection->udpWorker->getMainSocket(), false);
                            [$bind_addr, $bind_port] = explode(':', $listenInfo);
                        } else {
                            $bind_port = $this->config['udp_port'];
                        }
                        $bind_addr = $this->config['wanIP'] ?? '192.168.1.1';

                        $response['ver'] = 5;
                        $response['rep'] = 0;
                        $response['rsv'] = 0;
                        $response['addr_type'] = ADDRTYPE_IPV4;
                        $response['bind_addr'] = $bind_addr;
                        $response['bind_port'] = $bind_port;

                        logger()->debug('send:' . bin2hex($this->packResponse($response)));
                        $connection->send($this->packResponse($response));
                        break;
                    default:
                        logger()->error('connect init failed. unknow command.');
                        $connection->stage = STAGE_DESTROYED;

                        $response = [];
                        $response['ver'] = 5;
                        $response['rep'] = ERR_UNKNOW_COMMAND;
                        $response['rsv'] = 0;
                        $response['addr_type'] = ADDRTYPE_IPV4;
                        $response['bind_addr'] = '0.0.0.0';
                        $response['bind_port'] = 0;

                        $connection->close($this->packResponse($response));
                        return;
                }
        }
    }

    public function udpWorkerOnMessage($udp_connection, $data, &$worker)
    {
        $addr = [];
        logger()->debug('send:' . bin2hex($data));
        $request = [];
        $offset = 0;

        $request['rsv'] = substr($data, $offset, 2);
        $offset += 2;

        $request['frag'] = ord($data[$offset]);
        ++$offset;

        $request['addr_type'] = ord($data[$offset]);
        ++$offset;

        switch ($request['addr_type']) {
            case ADDRTYPE_IPV4:
                $tmp = substr($data, $offset, 4);
                $ip = 0;
                for ($i = 0; $i < 4; ++$i) {
                    $ip += ord($tmp[$i]) * 256 ** (3 - $i);
                }
                $request['dest_addr'] = long2ip($ip);
                $offset += 4;
                break;
            case ADDRTYPE_HOST:
                $request['host_len'] = ord($data[$offset]);
                ++$offset;

                $request['dest_addr'] = substr($data, $offset, $request['host_len']);
                $offset += $request['host_len'];
                break;
            case ADDRTYPE_IPV6:
                if (strlen($data) < 22) {
                    echo "buffer too short\n";
                    $error = true;
                    break;
                }
                echo "todo ipv6\n";
                $error = true;
                // no break
            default:
                echo "unsupported addrtype {$request['addr_type']}\n";
                $error = true;
        }

        $portData = unpack('n', substr($data, $offset, 2));
        $request['dest_port'] = $portData[1];
        $offset += 2;
        // var_dump($request['dest_addr']);
        if ($request['addr_type'] == ADDRTYPE_HOST) {
            logger()->debug('解析DNS');
            $addr = dns_get_record($request['dest_addr'], DNS_A);
            $addr = $addr ? array_pop($addr) : null;
            logger()->debug('DNS 解析完成' . $addr['ip']);
        } else {
            $addr['ip'] = $request['dest_addr'];
        }
        // var_dump($request);

        // var_dump($udp_connection);

        $remote_connection = new AsyncUdpConnection('udp://' . $addr['ip'] . ':' . $request['dest_port']);
        /* @phpstan-ignore-next-line */
        $remote_connection->id = $worker->incId++;
        /* @phpstan-ignore-next-line */
        $remote_connection->udp_connection = $udp_connection;
        $remote_connection->onConnect = function ($remote_connection) use ($data, $offset) {
            $remote_connection->send(substr($data, $offset));
        };
        $remote_connection->onMessage = function ($remote_connection, $recv) use ($data, $offset, $udp_connection, $worker) {
            $udp_connection->close(substr($data, 0, $offset) . $recv);
            $remote_connection->close();
            unset($worker->udpConnections[$remote_connection->id]);
        };
        /* @phpstan-ignore-next-line */
        $remote_connection->deadTime = time() + 3;
        $remote_connection->connect();
        /* @phpstan-ignore-next-line */
        $worker->udpConnections[$remote_connection->id] = $remote_connection;
    }

    /**
     * 配置
     */
    protected function configure()
    {
        $this->setDescription('开启一个 HTTP 代理服务器');
        $this->addOption('type', 't', InputOption::VALUE_REQUIRED, '类型，可选http、socks');
        $this->addOption('host', 'H', InputOption::VALUE_REQUIRED, '监听地址，默认0.0.0.0');
        $this->addOption('port', 'P', InputOption::VALUE_REQUIRED, '监听端口，默认8080');
        $this->addOption('udp-port', 'U', InputOption::VALUE_REQUIRED, '监听端口，默认8080');
        $this->addOption('username', 'u', InputOption::VALUE_REQUIRED, '认证用的用户名');
        $this->addOption('password', 'p', InputOption::VALUE_REQUIRED, '认证用的密码');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ob_logger_registered() || ob_logger_register(new ConsoleLogger());
        ini_set('memory_limit', '512M');
        // Create a TCP worker.
        $address = $input->getOption('host') ?? '0.0.0.0';
        $port = $input->getOption('port') ?? '8080';
        $this->config['auth'] = [0 => true];
        $type = $input->getOption('type') ?? 'http';
        logger()->notice('Proxy server started at ' . $type . '://' . $address . ':' . $port);
        match ($type) {
            'http' => $this->startHttpProxy($address, $port),
            'socks', 'socks5' => $this->startSocksProxy($address, $port),
            default => 0,
        };
        return 0;
    }

    private function startHttpProxy($address, $port)
    {
        // Create a TCP worker.
        /** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
        $worker = new Worker('tcp://' . $address . ':' . $port);
        // 6 processes
        $worker->count = 6;
        // Worker name.
        $worker->name = 'zhamao-http-proxy';
        Worker::$internal_running = true;

        // Emitted when data received from client.
        $worker->onMessage = function ($connection, $buffer) {
            // Parse http header.
            [$method, $addr, $http_version] = explode(' ', $buffer);
            $url_data = parse_url($addr);
            $addr = !isset($url_data['port']) ? "{$url_data['host']}:80" : "{$url_data['host']}:{$url_data['port']}";
            // Async TCP connection.
            $remote_connection = new AsyncTcpConnection("tcp://{$addr}");
            // CONNECT.
            if ($method !== 'CONNECT') {
                $remote_connection->send($buffer);
            // POST GET PUT DELETE etc.
            } else {
                $connection->send("HTTP/1.1 200 Connection Established\r\n\r\n");
                logger()->info('Receive connection: ' . $addr);
            }
            // Pipe.
            $remote_connection->pipe($connection);
            $connection->pipe($remote_connection);
            $remote_connection->connect();
        };
        Worker::runAll();
    }

    /**
     * @see https://github.com/walkor/php-socks5/blob/master/start.php
     * @param string     $address 地址
     * @param int|string $port    端口
     */
    private function startSocksProxy(string $address, int|string $port)
    {
        define('STAGE_INIT', 0);
        define('STAGE_AUTH', 1);
        define('STAGE_ADDR', 2);
        define('STAGE_UDP_ASSOC', 3);
        define('STAGE_DNS', 4);
        define('STAGE_CONNECTING', 5);
        define('STAGE_STREAM', 6);
        define('STAGE_DESTROYED', -1);

        define('CMD_CONNECT', 1);
        define('CMD_BIND', 2);
        define('CMD_UDP_ASSOCIATE', 3);

        define('ERR_GENERAL', 1);
        define('ERR_NOT_ALLOW', 2);
        define('ERR_NETWORK', 3);
        define('ERR_HOST', 4);
        define('ERR_REFUSE', 5);
        define('ERR_TTL_EXPIRED', 6);
        define('ERR_UNKNOW_COMMAND', 7);
        define('ERR_UNKNOW_ADDR_TYPE', 8);
        define('ERR_UNKNOW', 9);

        define('ADDRTYPE_IPV4', 1);
        define('ADDRTYPE_IPV6', 4);
        define('ADDRTYPE_HOST', 3);

        define('METHOD_NO_AUTH', 0);
        define('METHOD_GSSAPI', 1);
        define('METHOD_USER_PASS', 2);

        /** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
        $worker = new Worker('tcp://' . $address . ':' . $port);
        $worker->onConnect = function ($connection) {
            $connection->stage = STAGE_INIT;
            $connection->auth_type = null;
        };
        $worker->onMessage = [$this, 'onSocksMessage'];
        $worker->onWorkerStart = function () use ($address, $port) {
            $udpWorker = new Worker('udp://' . $address . ':' . $port);
            /* @phpstan-ignore-next-line */
            $udpWorker->incId = 0;
            $udpWorker->onWorkerStart = function ($worker) {
                $worker->udpConnections = [];
                Timer::add(1, function () use ($worker) {
                    /* @phpstan-ignore-next-line */
                    foreach ($worker->udpConnections as $id => $remote_connection) {
                        if ($remote_connection->deadTime < time()) {
                            $remote_connection->close();
                            $remote_connection->udp_connection->close();
                            unset($worker->udpConnections[$id]);
                        }
                    }
                });
            };
            $udpWorker->onMessage = [$this, 'udpWorkerOnMessage'];
            $udpWorker->listen();
        };
        $worker->onClose = function ($connection) {
            logger()->info('client closed.');
        };
        $worker::$internal_running = true;
        Worker::runAll();
    }

    private function packResponse($response)
    {
        $data = '';
        $data .= chr($response['ver']);
        $data .= chr($response['rep']);
        $data .= chr($response['rsv']);
        $data .= chr($response['addr_type']);

        switch ($response['addr_type']) {
            case ADDRTYPE_IPV4:
                $tmp = explode('.', $response['bind_addr']);
                foreach ($tmp as $block) {
                    $data .= chr(intval($block));
                }
                break;
            case ADDRTYPE_HOST:
                $host_len = strlen($response['bind_addr']);
                $data .= chr($host_len);
                $data .= $response['bind_addr'];
                break;
        }

        $data .= pack('n', $response['bind_port']);
        return $data;
    }
}
