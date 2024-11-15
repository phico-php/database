<?php

return new Doctum\Doctum('./src', [
  'theme' => 'default',
  'title' => 'Phico Database API',
  'build_dir' => __DIR__ . '/build/docs/api',
  'cache_dir' => __DIR__ . '/build/cache',
  // 'remote_repository'    => new GitHubRemoteRepository('username/repository', '/path/to/repository'),
  'default_opened_level' => 2,
]);
