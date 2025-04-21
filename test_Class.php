<?php
// Ensure stack trace arguments are captured:
ini_set('zend.exception_ignore_args', 'Off');
require_once __DIR__ . '/vendor/autoload.php';

// Initialize Sentry with your DSN
\Sentry\init([
  'dsn' => 'https://15a42c46abc6f184fcd2810733380450@o4509003279499264.ingest.de.sentry.io/4509019227619408',
  // Specify a fixed sample rate
  'traces_sample_rate' => 1.0,
  // Set a sampling rate for profiling - this is relative to traces_sample_rate
  'profiles_sample_rate' => 1.0,
]);

class Class_Test {
    // This method initializes Sentry and tests error capture.
    public function runTest()
    {
        // Try calling the failing function
        \Sentry\captureMessage('Something went wrong');
        Sentry\captureMessage('Something went wrong');
        try {
          $this->functionFailsForSure();
        } catch (\Throwable $exception) {
          \Sentry\captureException($exception);
        }
        try {
          $this->functionFailsForSure();
        } catch (Throwable $exception) {
          Sentry\captureException($exception);
        }
        try {
            throw new \Exception('Test exception from Sentry');
        } catch (\Throwable $exception) {
            \Sentry\captureException($exception);
        }
        try {
            throw new Exception('Test exception from Sentry');
        } catch (Throwable $exception) {
            Sentry\captureException($exception);
        }
        \Sentry\captureLastError();
        Sentry\captureLastError();
    }
}

// Usage example:
// If you open this file directly in your browser (via localhost), it will run the test:
if (php_sapi_name() !== 'cli') {
    $test = new Class_test();
    $test->runTest();
}