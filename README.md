# Laravel Queue Azure Restarter

## What is this package for?

**TL;DR: My queue daemons turned to zombies after a few days, this package kills the zombies**

I use a queue daemon to process tasks such as sending emails from my Laravel applications.  The daemon has been set up as a *continuous webjob* on Azure, so if it goes down, Azure starts it back up again immediately (this is the same functionality as *Supervisord* would provide on *nix).  However, the daemon is a long-running process and I found that there were a few problems; for example, after a short while I would get SSL errors when connecting to the external mail server.  These were resolved by setting up a *scheduled webjob* to issue a `php artisan queue:restart` command periodically; it's a bit hacky, but it does the job.

So, everything was great... until it wasn't! A horrible discovery awaited me one Monday morning: a weekend's worth of mail was in the queue, waiting to be processed.  **What the hell?!** I checked the queue daemon on Azure and... *it was running?*  I delved further.  At some stage in the early hours of the previous Saturday, the daemon had stopped reacting to the restart command and it just sat there, idle.  The process had not stopped, so Azure didn't know that it needed restarting, but it was doing *nothing*.  The process had a pulse, but no brain activity.

I searched around; had anyone else seen this?  It turns out that it was uncommon, but not unprecedented (e.g. laravel/framework#4443).  Indeed, further searching suggested that is could be something to do with PHP's `sleep()` function itself.

So, I needed a way of checking that the queue daemon was not only still running, but also *functioning*.  Then, if the daemon turned into a zombie, I needed the queue to be restarted *automatically* (I like my weekends to be my own!).

Happily, the solution lay with the Kudu API on Azure.  [The API allows us to get information about the processes running on the server and, importantly, terminate them!](https://github.com/projectkudu/kudu/wiki/Process-Threads-list-and-minidump-gcdump-diagsession)

And so, this package was born!

## Laravel Installation and Configuration

Add the following to the require section of your application's *composer.json* file:

```
"marchie\laravel-queue-azure-restarter": "dev-master"
```

Run **composer update** to install the package.

Now, add the service provider to the providers array in Laravel's *config/app.php* file:

```
Marchie\LaravelQueueAzureRestarter\ServiceProvider::class
```

Now the package needs to be configured.  The following keys and values need to be added to your application's *.env* file:

- **KUDU_USER** The username to access the Kudu API
- **KUDU_PASS** The password to access the Kudu API
- **AZURE_INSTANCE** The name of your Azure instance; *i.e.* https://**[instance]**.scm.azurewebsites.net
- **QUEUE_FAIL_TIMEOUT** The time that needs to have passed before the queue has timed out, e.g. `20 minutes`, `1 hour`, etc.

([More information on Kudu credentials](https://github.com/projectkudu/kudu/wiki/Accessing-the-kudu-service))

Alternatively, you can publish the configuration using the `php artisan vendor:publish` command and edit the values in the *config/laravel-queue-azure-restarter.php* file.

You can test that your application can connect to Kudu using `php artisan kudu:test`.

That's the Laravel bit done...

## Azure Configuration

We need to configure two *scheduled webjobs*.  The first will push a job onto the queue that sets the current timestamp value in Laravel's cache.  The second job will read this timestamp value and check the time that has passed since.  If more time has passed than the **QUEUE_FAIL_TIMEOUT** value set in the configuration, then the queue daemon process will be killed using the Kudu API.  As you have set up the queue daemon as a *continuous webjob*, Azure will see that it is dead and automatically restart it!

### The First Webjob: queue:flag

The first webjob needs to execute the following command:

```
php artisan queue:flag --queue=[QUEUE] <connection>
```

The `<connection>` argument specifies the driver used by the queue; it defaults to the value you have set in Laravel's `config/queue.php` file.

The `--queue=[QUEUE]` option specifies the name of the queue; it defaults to *null* if you are not using named queues.

The webjob pushes a job onto the queue which then sets a value containing the current timestamp.

This webjob needs to be executed more frequently than the **QUEUE_FAIL_TIMEOUT** value we set in the configuration; e.g. if your QUEUE_FAIL_TIMEOUT value is '20 minutes', the webjob must be executed every 19 minutes or less.

### The Second Webjob: queue:check

The second webjob needs to execute the following command:

```
php artisan queue:check --queue=[QUEUE] <connection>
```

The `<connection>` argument specifies the driver used by the queue; it defaults to the value you have set in Laravel's `config/queue.php` file.

The `--queue=[QUEUE]` option specifies the name of the queue; it defaults to *null* if you are not using named queues.

The webjob can be executed as frequently as you wish.  The command reads the timestamp value stored in the cache by the `queue:flag` command and works out the amount of time that has passed.  If more time has passed than the **QUEUE_FAIL_TIMEOUT** value, the zombie queue daemon process is hunted down and terminated using the Kudu API.

## A Note About Failure

When the queue daemon has failed and the process is terminated, the package throws an `UnresponsiveQueueWorkerException`.  Depending on how your application handles exceptions, this can be used to record incidents where the queue daemon has failed, notify administrators, etc.