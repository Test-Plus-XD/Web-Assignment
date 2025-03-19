<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'head.php';

class Class_Test {
    // This method intentionally throws an exception.
    public function functionFailsForSure()
    {
        // Throw an exception to simulate a failure
        throw new \Exception('This function fails for sure!');
    }

    // This method initializes Sentry and tests error capture.
    public function runTest()
    {
        // Initialize Sentry with your DSN
        \Sentry\init([
            'dsn' => 'https://4afa0389cc52f86c98bd64c8f6913b2e@o4509003279499264.ingest.de.sentry.io/4509003478859856',
        ]);

        // Try calling the failing function
        try {
            $this->functionFailsForSure();
        } catch (\Throwable $exception) {
            \Sentry\captureException($exception);
            echo "Sentry captured an exception: " . $exception->getMessage();
        }
    }
}

// Usage example:
// If you open this file directly in your browser (via localhost), it will run the test:
if (php_sapi_name() !== 'cli') {
    $test = new Class_Test();
    $test->runTest();
}