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
        $this->logger = $logger;
    }

    public function run() {
        // IMPORTANT:
        // For the sighandlers to work, you will need to have this in your calling script:
        //   declare(ticks=1);

        pcntl_signal(SIGTERM, array($this, 'handleSignal'));
        pcntl_signal(SIGINT, array($this, 'handleSignal'));

        $last_run_time = 0;
        while (!$this->shutting_down) {
            try {
                // execute loop
                call_user_func($this->loop_function);
            } catch (Exception $e) {
                if ($this->error_handler) {
                    call_user_func($this->loop_function, $e);
                } else {
                    if ($this->logger) { $this->logger->error($e->getMessage()); }
                }
            }

            $wait_time = time() - $last_run_time;
            if ($wait_time > 0) { sleep($wait_time); }
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
