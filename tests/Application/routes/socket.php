<?php

/*
 *  Routes for WebSocket
 *
 * Add route (Symfony Routing Component)
 * $socket->route('/myclass', new MyClass, ['*']);
 */

/** @var $this \Orchid\Socket\Console\Server */
/** @var $socket \Ratchet\App */
$articleIterationSocket = $this->getLaravel()->make('\App\Http\Sockets\ArticleIterations');
$socket->route('/articles/{article}/iterations', $articleIterationSocket, ['*']);