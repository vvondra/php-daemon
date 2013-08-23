A simple unix daemon in PHP
==========

If you're comfortable with PHP and want to have a PHP script running as a daemon (meaning you can leave it running in the background, stop, restart it and check it's status) this class provides a basic implementation of the necessary features.

Running a script as a daemon can be more convenient than a CRON triggered task and you can be sure it will be running in only one process at a time..

Simply subclass it and implement the run() method. You might find the [React Event Loop](https://github.com/reactphp/event-loop) useful if you want an alternative to sleeping.
