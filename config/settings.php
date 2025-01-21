<?php
return [
  "app" => [
    "encoding" => "utf-8",
    "language" => "en-US",
    "file-path" => "/downloads",
    "allowed-extensions" => [
      'json'
    ],
    "log-level" => Monolog\Logger::DEBUG
  ],
  "database" => [ 
    "dsn" => "sqlite:" . __DIR__ . "/../resources/data/dl.db"
  ]
];