<?php

namespace Encore\Admin\TaskScheduling\Providers;

use Illuminate\Support\ServiceProvider;
use Encore\Admin\TaskScheduling\TaskScheduling; 
use Illuminate\Console\Scheduling\Schedule;
use Encore\Admin\TaskScheduling\Http\Models\Task;
use Encore\Admin\TaskScheduling\Services\TaskService;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Support\Facades\Schema;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
		$this->app->resolving(Schedule::class, function($schedule, $app) {
			$this->schedule($schedule);
		});
    }

	public function schedule(Schedule $schedule)
	{
        if (Schema::hasTable(config('task-scheduling.table_prefix').'tasks')) {
            // the table exists, you can continue with your logic here
            $service = new TaskService;
            $tasks = Task::where('enabled', 1)->get(); 
            
            foreach($tasks as $task) {
                $event = $schedule->command($task->command);
                $event->cron($service->getCronExpression($task))
                    ->name($task->description)
                    ->before(function () use ($event){
                        $event->start = microtime(true);
                        // The task is about to execute...
                    })
                    ->after(function () use( $task, $event ){
                        $task->commandLogs()->create([
                            'duration' => microtime(true)-$event->start,
                        ]);
                    });
            }

        } else {
            // the table does not exist, handle the error or exception here
        }


	}
}
