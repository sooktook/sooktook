<?php
    public function process() {
        $lockFile = '/process.lock';
        // 打开文件进行锁定
        $lockHandle = fopen($lockFile, 'w+');
        // 尝试加锁
        if (flock($lockHandle, LOCK_EX | LOCK_NB)) {
            // 获取父进程的 PID（如果需要的话）
            $pidFile = '/process.pid';
            file_put_contents($pidFile, getmypid());

            // 执行任务
            echo "Starting the parent process...\n";

            for ($i = 0; $i < 20; $i++) {
                $pid = pcntl_fork();

                if ($pid == -1) {
                    die("Could not fork process $i\n");
                } elseif ($pid) {
                    continue; // 父进程继续循环，创建下一个子进程
                } else {
                    // 子进程执行任务
                    $key = 'pcntl:' . $i;
                    var_dump($key);
                    RedisService::set($key, $i, 600);
                    sleep(20);
                    exit(0); // 子进程结束
                }
            }

            // 父进程等待所有子进程结束，并处理退出状态
            while (($pid = pcntl_waitpid(0, $status)) != -1) {
                // 获取子进程的 PID 和退出状态
                if (pcntl_wifexited($status)) {
                    $exitStatus = pcntl_wexitstatus($status);
                    echo "Child process $pid exited normally with status $exitStatus\n";
                }
            }

            echo "All child processes have finished.\n";

            // 释放文件锁
            flock($lockHandle, LOCK_UN);

            // 删除 PID 文件
            @unlink($pidFile);
        } else {
            echo "Another parent process is already running. Exiting...\n";
            fclose($lockHandle);  // 关闭文件锁
            exit(0);  // 阻止新的父进程启动
        }

        // 关闭锁文件句柄
        fclose($lockHandle);
    }
