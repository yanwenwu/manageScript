<?php

namespace Cron;

use Cron\Task;
use Core\Cen\Log;
use Core\Cen\Process;
use Core\Cen\Config;
use Core\Cen\ErrorException;
use Cron\TaskManage;

class Crontab
{
    private $process;
    //保存worker pid;
    private $tasks = [];
    //启动任务 数量
    private $task_num;
    //启动的任务
    private $task;
    //任务最大数量
    private $task_max_num;
    //脚本解释器
    private $php;
    //进程配置
    private $task_config;
    //task对象
    private $task_queue;
    
    public function __construct()
    {
        $this->task_config = Config::get('task');
        $this->php          = $_ENV['_'];
        $this->task_index   = $this->task_config['directory'];
        $this->task_num     = $this->task_config['task_num'] ?? 1;
        $this->task_max_num = $this->task_config['task_max_num'] ?? 5;
        $this->process      = new Process();
        $this->task_queue   = new Task;
    }
    
    /**
     * 
     * @description:信号处理
     * @author wuyanwen(2017年8月17日)
     */
    public function registerSignal()
    {
        $this->process->registerSignal(SIGALRM,function(){  
            $this->pushTasksToQueue();
            while ($this->task_queue->getTaskQueueLen()) {
                list($namepace, $task) = json_decode($this->task_queue->getTask(), true);
                $this->create($task, $namepace);
            }
        });
        
        $this->process->registerSignal(SIGCHLD, function(){
            $this->wait();
        });
    }
    
    /**
     * 
     * @description:启动Crontab
     * @author wuyanwen(2017年8月17日)
     */
    public function start()
    {
        $this->process->deamon();
        $this->process->setTaskName('php Crond Master');
        $pid = getmypid();
        file_put_contents($this->task_config['pid_file'], $pid);
        Log::write(Log::INFO, 'Crontab Service Start Success');
        $this->registerSignal();
        $this->alarm();
    }
    
    /**
     * 
     * @description:信号注册
     * @author wuyanwen(2017年8月17日)
     */
    private function alarm()
    {
        $this->process->alarm(300 * 1000);
    }
    
    
    /**
     * 
     * @description:启动任务
     * @author wuyanwen(2017年8月17日)
     */
    private function create($task, $namespace)
    {
        if (array_count_values($this->tasks)[$task] > $this->task_max_num) {
            Log::write(Log::INFO, sprintf('Task %s Has exceeded the maximum task limit', $task));
            return false;
        }
        
        try {
            $pid = $this->process->createProccess(function(\swoole_process $worker) use ($task, $namespace){
                try {
                    $task = $namespace ? [$this->task_index, $namespace, $task] : [$this->task_index, $task];
                    $worker->exec($this->php,$task);
                } catch (ErrorException $e) {
                    Log::write(Log::INFO, $e->getMessage());
                }
            });
        } catch (ErrorException $e) {
            Log::write(Log::INFO, $e->getMessage());
        }
        
        if (!$pid) {
            Log::write(Log::INFO, $task . ' start failure');
        } else {
            $this->tasks[$pid] = $task;
            Log::write(Log::INFO, $task . ' start success');
        }
    }
    
    /**
     * 
     * @description:推送任务
     * @author wuyanwen(2017年8月17日)
     */
    private function pushTasksToQueue()
    {
        $taskManage = new TaskManage;
        $taskManage->dealTasks();
    }
    
    /**
     * 
     * @description:等待任务结束并且记录
     * @author wuyanwen(2017年8月17日)
     */
    private function wait()
    {
        while(true) {
            $result =  $this->process->wait();
            if ($result) {
                $pid = $result['pid'];
                if ($pid) {
                    Log::write(Log::INFO, $this->tasks[$pid] . ' task is over');
                    unset($this->tasks[$pid]);
                }
            } else { break;}
        }
    }
}


