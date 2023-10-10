// Profiler setup
var PQP_SHOWONLOAD = false;
var PQP_HEIGHT = 'short';
var PQP_DETAILS = true;
var PQP_BOTTOM = false;

// Raven setup
Raven.config('https://773a5c2c3fc64be3961a669bf217015c@sentry.io/103038').install();
function handleRouteError(err) { Raven.captureException(err); Raven.showReportDialog(); };

// Utility functions
