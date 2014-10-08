<?php

namespace Utipd\SimpleDaemon;

use Exception;
use Psr\Log\LoggerInterface;

/*
* BaseDaemon
*/
class Daemon
{
    protected $loop_interval = 5;  // in seconds
    protected $shutting_down = false;

    public function __construct(Callable $loop_function, Callable $error_handler=null, LoggerInterface $logger=null) {
        $this->loop_function = $loop_function;
        $this->error_handler = $error_handler;
        $this->logger = $logger;
    }

    public function setLoopInterval($loop_interval) {
        $this->loop_interval = $loop_interval;
    }

    public function run() {
        // IMPORTANT:
        // For the sighandlers to work, you will need to have this in your calling script:
        //   declare(ticks=1);

        pcntl_signal(SIGTERM, array($this, 'handleSignal'));
        pcntl_signal(SIGINT, array($this, 'handleSignal'));

        $last_run_start = 0;
        while (!$this->shutting_down) {
            $last_run_start = time();

            try {
                // execute loop
                call_user_func($this->loop_function);
            } catch (Exception $e) {
                if ($this->error_handler) {
                    call_user_func($this->error_handler, $e);
                } else {
                    if ($this->logger) { $this->logger->error($e->getMessage()); }
                }
            }

            // sleep
            $wait_time = $this->loop_interval - (time() - $last_run_start);
            if ($wait_time > 0 AND !$this->shutting_down) { sleep($wait_time); }
        }
    }



    public function handleSignal($signo) {
        switch ($signo) {
            case SIGTERM:
            case SIGINT:
                // handle shutdown tasks
                $this->shutting_down = true;
                break;
            default:
                // handle all other signals
        }
    }


}
