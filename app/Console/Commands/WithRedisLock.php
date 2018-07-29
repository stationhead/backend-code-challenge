<?php

namespace App\Console\Commands;

use Redis;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait WithRedisLock
{
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$key = 'Redis_Lock:' . get_class($this);
		$expireTime = $this->lockExpireTime ?? 120;
		$sleepTime = $this->sleepDelayTime ?? 15;

		if( !($input->hasOption("force")
			  && $input->getOption("force")
			  )
			&& Redis::setnx($key, true) === 0
		) {
			return;
		}
		Redis::expire($key, $expireTime);

		try{
			$ret = parent::execute($input, $output);
	    	Redis::expire($key, $sleepTime);
		} catch (\Exception $e){
			Redis::del($key);
			throw $e;
		}

	    return $ret;
	}
}
