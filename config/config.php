<?php

return [
    /*
     * Which signed-in user Pulse attributes requests and jobs to, checked in
     * this order. "frontend" needs the Winter.User plugin and is skipped
     * without it. An empty array turns user attribution off.
     */
    'users' => ['frontend', 'backend'],

    /*
     * Service providers of Pulse card packages. Winter doesn't run Laravel's
     * package discovery, so list each card's provider (see its composer.json
     * "extra.laravel.providers") here.
     */
    'providers' => [],
];
