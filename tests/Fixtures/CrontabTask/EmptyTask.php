<?php

namespace Tests\Fixtures\CrontabTask;

use WebmanTech\CrontabTask\BaseTask;

class EmptyTask extends BaseTask
{
    /**
     * @return void
     */
    public function handle()
    {
        // do nothing
    }
}
