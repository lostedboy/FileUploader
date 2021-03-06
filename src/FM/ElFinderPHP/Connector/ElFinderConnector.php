<?php

namespace FM\ElFinderPHP\Connector;

use FM\ElFinderPHP\ElFinder;

/**
 * Default ElFinder connector
 *
 * @author Dmitry (dio) Levashov
 **/
class ElFinderConnector
{
    /**
     * ElFinder instance
     *
     * @var ElFinder
     **/
    protected $ElFinder;

    /**
     * Options
     *
     * @var array
     **/
    protected $options = array();

    /**
     * undocumented class variable
     *
     * @var string
     **/
    protected $header = 'Content-Type: application/json';


    /**
     * Constructor
     *
     * @return void
     * @author Dmitry (dio) Levashov
     **/
    public function __construct(ElFinder $ElFinder, $debug = false)
    {
        $this->ElFinder = $ElFinder;
        $this->ElFinder->setHeader($this->header);
        if ($debug) {
            $this->header = 'Content-Type: text/html; charset=utf-8';
        }
    }

    /**
     * Execute ElFinder command and output result
     *
     * @param $src
     * @param $method
     */
    public function run($method, $src, $needTramsliteration)
    {
        $isPost = strtolower($method) == 'post';

        $cmd = isset($src['cmd']) ? $src['cmd'] : '';
        $args = array();

        if (!function_exists('json_encode')) {
            $error = $this->ElFinder->error(ElFinder::ERROR_CONF, ElFinder::ERROR_CONF_NO_JSON);
            $this->output(array('error' => '{"error":["' . implode('","', $error) . '"]}', 'raw' => true));
        }

        if (!$this->ElFinder->loaded()) {
            $this->output(array('error' => $this->ElFinder->
                    error(ElFinder::ERROR_CONF, ElFinder::ERROR_CONF_NO_VOL),
                'debug' => $this->ElFinder->mountErrors));
        }

        // telepat_mode: on
        if (!$cmd && $isPost) {
            $this->output(array('error' => $this->ElFinder->error(ElFinder::ERROR_UPLOAD, ElFinder::ERROR_UPLOAD_TOTAL_SIZE), 'header' => 'Content-Type: text/html'));
        }
        // telepat_mode: off

        if (!$this->ElFinder->commandExists($cmd)) {
            $this->output(array('error' => $this->ElFinder->error(ElFinder::ERROR_UNKNOWN_CMD)));
        }

        // collect required arguments to exec command
        foreach ($this->ElFinder->commandArgsList($cmd) as $name => $req) {
            $arg = $name == 'FILES'
                ? $_FILES
                : (isset($src[$name]) ? $src[$name] : '');

            if (!is_array($arg)) {
                $arg = trim($arg);
            }
            if ($req && (!isset($arg) || $arg === '')) {
                $this->output(array('error' => $this->ElFinder->error(ElFinder::ERROR_INV_PARAMS, $cmd)));
            }
            $args[$name] = $arg;
        }

        $args['debug'] = isset($src['debug']) ? !!$src['debug'] : false;

        $this->output($this->ElFinder->exec($cmd, $args, $needTramsliteration));
    }

    /**
     * Output json
     *
     * @param  array  data to output
     * @return void
     * @author Dmitry (dio) Levashov
     **/
    protected function output(array $data)
    {
        $header = isset($data['header']) ? $data['header'] : $this->header;
        unset($data['header']);
        if ($header) {
            if (is_array($header)) {
                foreach ($header as $h) {
                    header($h);
                }
            } else {
                header($header);
            }
        }

        if (isset($data['pointer'])) {
            rewind($data['pointer']);
            fpassthru($data['pointer']);
            if (!empty($data['volume'])) {
                $data['volume']->close($data['pointer'], $data['info']['hash']);
            }
            exit();
        } else {
            if (!empty($data['raw']) && !empty($data['error'])) {
                exit($data['error']);
            } else {
                exit(json_encode($data));
            }
        }

    }

}
