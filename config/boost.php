<?php

return [
    // Opt in only through the isolated MCP launcher; do not inject browser logging.
    'enabled' => env('BOOST_ENABLED', false),
    'browser_logs_watcher' => false,
    'rules' => ['enabled' => false],
];
