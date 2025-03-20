<?php
// Ensure stack trace arguments are captured:
ini_set('zend.exception_ignore_args', 'Off');
require 'vendor/autoload.php';
// Initialize Sentry with your DSN
\Sentry\init([
  'dsn' => 'https://e21244fd9d0d7b39d054e4dda21a860c@o4509003279499264.ingest.de.sentry.io/4509005618741328',
]);
class Class_Test {
    // This method initializes Sentry and tests error capture.
    public function runTest()
    {
        // Try calling the failing function
        \Sentry\captureMessage('Something went wrong');
        try {
          $this->functionFailsForSure();
        } catch (\Throwable $exception) {
          \Sentry\captureException($exception);
        }
        try {
            throw new \Exception('Test exception from Sentry');
        } catch (\Throwable $exception) {
            \Sentry\captureException($exception);
        }
    }
}

// Usage example:
// If you open this file directly in your browser (via localhost), it will run the test:
if (php_sapi_name() !== 'cli') {
    $test = new Class_test();
    $test->runTest();
}